<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
session_start();

// Create A/B test tracking table if it doesn't exist
try {
    $db->exec("CREATE TABLE IF NOT EXISTS ab_test_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        test_name VARCHAR(100),
        variant VARCHAR(10),
        visitor_id VARCHAR(100),
        event_type VARCHAR(50),
        event_data TEXT,
        ip_address VARCHAR(50),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
    error_log("AB test table creation failed: " . $e->getMessage());
}

// Track page view or conversion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_name = $_POST['test_name'] ?? 'sell-page-test';
    $variant = $_POST['variant'] ?? 'A';
    $event_type = $_POST['event_type'] ?? 'page_view';
    $event_data = $_POST['event_data'] ?? '';
    
    $visitor_id = $_SESSION['visitor_id'] ?? uniqid('visitor_', true);
    $_SESSION['visitor_id'] = $visitor_id;
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    try {
        $stmt = $db->prepare("INSERT INTO ab_test_tracking (test_name, variant, visitor_id, event_type, event_data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$test_name, $variant, $visitor_id, $event_type, $event_data, $ip_address, $user_agent]);
        
        echo json_encode(['success' => true, 'message' => 'Event tracked']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Get A/B test assignment for visitor
$visitor_id = $_SESSION['visitor_id'] ?? uniqid('visitor_', true);
$_SESSION['visitor_id'] = $visitor_id;

// Check if visitor already has a variant assigned
$stmt = $db->prepare("SELECT variant FROM ab_test_tracking WHERE visitor_id = ? AND test_name = 'sell-page-test' LIMIT 1");
$stmt->execute([$visitor_id]);
$existing = $stmt->fetch();

if ($existing) {
    $variant = $existing['variant'];
} else {
    // Randomly assign variant (50/50 split)
    $variant = (rand(1, 100) <= 50) ? 'A' : 'B';
    
    // Track the assignment
    try {
        $stmt = $db->prepare("INSERT INTO ab_test_tracking (test_name, variant, visitor_id, event_type, ip_address, user_agent) VALUES (?, ?, ?, 'page_view', ?, ?)");
        $stmt->execute(['sell-page-test', $variant, $visitor_id, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown']);
    } catch (Exception $e) {
        error_log("AB test tracking failed: " . $e->getMessage());
    }
}

// Redirect to appropriate version
if ($variant === 'B') {
    header('Location: sell-variation-b.php');
} else {
    header('Location: sell-optimized.php');
}
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A/B Testing Tracker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
            margin: 20px 0;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 A/B Testing System</h1>
        
        <div class="info">
            <strong>🎯 Test Configuration:</strong><br>
            • Test Name: sell-page-test<br>
            • Variant A: Benefits-focused (sell-optimized.php)<br>
            • Variant B: Urgency-focused (sell-variation-b.php)<br>
            • Split: 50/50 random assignment<br>
            • Visitor ID: <?php echo htmlspecialchars($visitor_id); ?><br>
            • Assigned Variant: <?php echo $variant; ?>
        </div>
        
        <p><strong>Test Versions:</strong></p>
        <a href="sell-optimized.php" class="btn">📈 Version A (Benefits)</a>
        <a href="sell-variation-b.php" class="btn">⚡ Version B (Urgency)</a>
        <a href="ab-test-results.php" class="btn">📊 View Results</a>
    </div>
</body>
</html> 