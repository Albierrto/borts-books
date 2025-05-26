<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/email-system.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

$pageTitle = "Email Management";
$currentPage = "admin";

$emailSystem = new EmailSystem($db);

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'export_subscribers':
                // Export active subscribers to CSV
                $subscribers = $emailSystem->getActiveSubscribers();
                
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Email', 'Name', 'Subscribed Date', 'Source']);
                
                foreach ($subscribers as $sub) {
                    fputcsv($output, [
                        $sub['email'],
                        $sub['name'] ?: 'Not provided',
                        $sub['subscribed_at'],
                        $sub['source']
                    ]);
                }
                
                fclose($output);
                exit;
                
            case 'add_subscriber':
                $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
                $name = trim($_POST['name']);
                
                if ($email) {
                    $result = $emailSystem->addSubscriber($email, $name, 'admin');
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                }
                break;
        }
    }
}

// Get subscriber statistics
$stats = $emailSystem->getSubscriberStats();

// Get recent subscribers
$recentSubscribers = $emailSystem->getActiveSubscribers(10);

// Get customer stats
try {
    $customerStatsQuery = $db->query("
        SELECT 
            COUNT(*) as total_customers,
            COUNT(CASE WHEN total_orders > 1 THEN 1 END) as repeat_customers,
            AVG(total_spent) as avg_spent,
            SUM(total_spent) as total_revenue
        FROM customer_emails
    ");
    $customerStats = $customerStatsQuery->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $customerStats = ['total_customers' => 0, 'repeat_customers' => 0, 'avg_spent' => 0, 'total_revenue' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Bort's Books Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .admin-header {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(35,41,70,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .admin-title {
            font-size: 2rem;
            font-weight: 800;
            color: #232946;
            margin-bottom: 0.5rem;
        }
        .admin-subtitle {
            color: #666;
            font-size: 1.1rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(35,41,70,0.08);
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #232946;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .panel {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(35,41,70,0.08);
            overflow: hidden;
        }
        .panel-header {
            background: linear-gradient(45deg, #232946, #395aa0);
            color: #fff;
            padding: 1.5rem;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .panel-content {
            padding: 1.5rem;
        }
        .subscribers-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .subscriber-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .subscriber-item:last-child {
            border-bottom: none;
        }
        .subscriber-info {
            flex: 1;
        }
        .subscriber-email {
            font-weight: 600;
            color: #232946;
        }
        .subscriber-meta {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }
        .subscriber-date {
            font-size: 0.8rem;
            color: #999;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #232946;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #eebbc3;
        }
        .btn {
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .btn:hover {
            background: linear-gradient(45deg, #232946, #395aa0);
            color: #fff;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #495057;
        }
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .empty-state {
            text-align: center;
            color: #666;
            padding: 2rem;
            font-style: italic;
        }
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="../index.php" class="logo">Bort's <span>Books</span></a>
            <nav>
                <ul>
                    <li><a href="/pages/admin-dashboard.php">Dashboard</a></li>
                    <li><a href="/pages/mass-edit.php">Products</a></li>
                    <li><a href="/pages/admin-email.php" class="active">Email</a></li>
                    <li><a href="/pages/admin.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="admin-container">
            <div class="admin-header">
                <h1 class="admin-title">Email Marketing Dashboard</h1>
                <p class="admin-subtitle">Manage subscribers and track email campaign performance</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['active_subscribers'] ?? 0); ?></div>
                    <div class="stat-label">Active Subscribers</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['today_signups'] ?? 0); ?></div>
                    <div class="stat-label">Today's Signups</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['week_signups'] ?? 0); ?></div>
                    <div class="stat-label">This Week</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($customerStats['total_customers'] ?? 0); ?></div>
                    <div class="stat-label">Total Customers</div>
                </div>
            </div>

            <div class="content-grid">
                <div class="panel">
                    <div class="panel-header">
                        <i class="fas fa-users"></i>
                        Recent Subscribers
                    </div>
                    <div class="panel-content">
                        <?php if (empty($recentSubscribers)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                <p>No subscribers yet. Start growing your email list!</p>
                            </div>
                        <?php else: ?>
                            <div class="subscribers-list">
                                <?php foreach ($recentSubscribers as $subscriber): ?>
                                    <div class="subscriber-item">
                                        <div class="subscriber-info">
                                            <div class="subscriber-email"><?php echo htmlspecialchars($subscriber['email']); ?></div>
                                            <div class="subscriber-meta">
                                                <?php if ($subscriber['name']): ?>
                                                    <?php echo htmlspecialchars($subscriber['name']); ?> • 
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($subscriber['source']); ?>
                                            </div>
                                        </div>
                                        <div class="subscriber-date">
                                            <?php echo date('M j', strtotime($subscriber['subscribed_at'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 1.5rem; text-align: center;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="export_subscribers">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-download"></i>
                                    Export CSV
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <i class="fas fa-user-plus"></i>
                        Add Subscriber
                    </div>
                    <div class="panel-content">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_subscriber">
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" required 
                                       placeholder="subscriber@example.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="name">Name (Optional)</label>
                                <input type="text" id="name" name="name" 
                                       placeholder="Subscriber name">
                            </div>
                            
                            <button type="submit" class="btn">
                                <i class="fas fa-plus"></i>
                                Add Subscriber
                            </button>
                        </form>
                        
                        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e9ecef;">
                            <h4 style="margin-bottom: 1rem; color: #232946;">Quick Stats</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem;">
                                <div>
                                    <strong>Repeat Customers:</strong><br>
                                    <?php echo number_format($customerStats['repeat_customers'] ?? 0); ?>
                                </div>
                                <div>
                                    <strong>Avg Order Value:</strong><br>
                                    $<?php echo number_format($customerStats['avg_spent'] ?? 0, 2); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 