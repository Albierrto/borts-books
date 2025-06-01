<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';

// Get test results
try {
    // Page views by variant
    $stmt = $db->prepare("
        SELECT 
            variant,
            COUNT(*) as page_views,
            COUNT(DISTINCT visitor_id) as unique_visitors
        FROM ab_test_tracking 
        WHERE test_name = 'sell-page-test' AND event_type = 'page_view'
        GROUP BY variant
    ");
    $stmt->execute();
    $page_views = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Form submissions (conversions) by variant
    $stmt = $db->prepare("
        SELECT 
            variant,
            COUNT(*) as conversions,
            COUNT(DISTINCT visitor_id) as converted_visitors
        FROM ab_test_tracking 
        WHERE test_name = 'sell-page-test' AND event_type = 'conversion'
        GROUP BY variant
    ");
    $stmt->execute();
    $conversions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activity
    $stmt = $db->prepare("
        SELECT 
            variant,
            event_type,
            event_data,
            created_at,
            ip_address
        FROM ab_test_tracking 
        WHERE test_name = 'sell-page-test'
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $page_views = [];
    $conversions = [];
    $recent_activity = [];
    $error = $e->getMessage();
}

// Process data
$results = ['A' => ['views' => 0, 'conversions' => 0, 'rate' => 0], 'B' => ['views' => 0, 'conversions' => 0, 'rate' => 0]];

foreach ($page_views as $pv) {
    $results[$pv['variant']]['views'] = $pv['unique_visitors'];
}

foreach ($conversions as $cv) {
    $results[$cv['variant']]['conversions'] = $cv['converted_visitors'];
}

// Calculate conversion rates
foreach ($results as $variant => &$data) {
    if ($data['views'] > 0) {
        $data['rate'] = ($data['conversions'] / $data['views']) * 100;
    }
}

// Calculate statistical significance (basic z-test)
function calculateZScore($n1, $c1, $n2, $c2) {
    if ($n1 == 0 || $n2 == 0) return null;
    
    $p1 = $c1 / $n1;
    $p2 = $c2 / $n2;
    $p_pool = ($c1 + $c2) / ($n1 + $n2);
    
    $se = sqrt($p_pool * (1 - $p_pool) * (1/$n1 + 1/$n2));
    
    if ($se == 0) return null;
    
    return ($p1 - $p2) / $se;
}

$z_score = calculateZScore(
    $results['A']['views'], $results['A']['conversions'],
    $results['B']['views'], $results['B']['conversions']
);

$confidence_level = null;
$is_significant = false;

if ($z_score !== null) {
    $confidence_level = 2 * (1 - abs($z_score) / 1.96); // Rough approximation
    $is_significant = abs($z_score) > 1.96; // 95% confidence
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A/B Test Results - Sell Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .header p {
            margin: 10px 0 0 0;
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .variant-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-top: 5px solid #007bff;
            position: relative;
            overflow: hidden;
        }
        
        .variant-card.variant-a {
            border-top-color: #28a745;
        }
        
        .variant-card.variant-b {
            border-top-color: #dc3545;
        }
        
        .variant-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }
        
        .variant-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .variant-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            font-weight: bold;
        }
        
        .variant-a .variant-icon {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .variant-b .variant-icon {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
        }
        
        .variant-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .variant-subtitle {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 1rem;
        }
        
        .metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .metric {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 900;
            color: #333;
            margin-bottom: 5px;
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .conversion-rate {
            text-align: center;
            padding: 25px;
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border-radius: 15px;
            border: 2px solid #2196f3;
        }
        
        .conversion-rate-value {
            font-size: 3rem;
            font-weight: 900;
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .conversion-rate-label {
            font-size: 1.2rem;
            color: #666;
            font-weight: 600;
        }
        
        .significance-panel {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .significance-status {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        
        .significant {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .not-significant {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: #333;
        }
        
        .winner-banner {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 20px;
            animation: winner-glow 2s infinite;
        }
        
        @keyframes winner-glow {
            0%, 100% { box-shadow: 0 5px 20px rgba(40, 167, 69, 0.3); }
            50% { box-shadow: 0 10px 40px rgba(40, 167, 69, 0.6); }
        }
        
        .activity-panel {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .activity-header {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .activity-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .activity-icon.view {
            background: #2196f3;
        }
        
        .activity-icon.conversion {
            background: #4caf50;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-type {
            font-weight: 600;
            color: #333;
        }
        
        .activity-meta {
            font-size: 0.9rem;
            color: #666;
            margin-top: 2px;
        }
        
        .refresh-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }
        
        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .metrics {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> A/B Test Results</h1>
            <p>Sell Page Conversion Optimization Test</p>
        </div>
        
        <button class="refresh-btn" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Refresh Data
        </button>
        
        <?php if (isset($error)): ?>
        <div class="empty-state">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Error Loading Data</h3>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php elseif (empty($page_views)): ?>
        <div class="empty-state">
            <i class="fas fa-chart-line"></i>
            <h3>No Test Data Yet</h3>
            <p>Start the A/B test to see results here!</p>
            <p><a href="ab-test-tracker.php" style="color: #667eea;">Start Testing →</a></p>
        </div>
        <?php else: ?>
        
        <!-- Winner Banner -->
        <?php if ($is_significant && $results['A']['rate'] !== $results['B']['rate']): ?>
        <div class="winner-banner">
            <i class="fas fa-trophy"></i>
            <?php 
            $winner = $results['A']['rate'] > $results['B']['rate'] ? 'A (Benefits-Focused)' : 'B (Urgency-Focused)';
            echo "🏆 WINNER: Version $winner";
            ?>
        </div>
        <?php endif; ?>
        
        <!-- Statistical Significance -->
        <div class="significance-panel">
            <h2 style="margin-bottom: 20px;">📊 Statistical Analysis</h2>
            
            <?php if ($z_score !== null): ?>
            <div class="significance-status <?php echo $is_significant ? 'significant' : 'not-significant'; ?>">
                <i class="fas <?php echo $is_significant ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                <?php echo $is_significant ? 'STATISTICALLY SIGNIFICANT' : 'NOT YET SIGNIFICANT'; ?>
            </div>
            
            <p><strong>Z-Score:</strong> <?php echo number_format($z_score, 3); ?></p>
            <p><strong>Confidence Level:</strong> <?php echo $is_significant ? '95%+' : 'Below 95%'; ?></p>
            <p><strong>Sample Size:</strong> <?php echo array_sum(array_column($results, 'views')); ?> total visitors</p>
            <?php else: ?>
            <div class="significance-status not-significant">
                <i class="fas fa-hourglass-half"></i>
                INSUFFICIENT DATA
            </div>
            <p>Need more data points to calculate statistical significance.</p>
            <?php endif; ?>
        </div>
        
        <!-- Variant Comparison -->
        <div class="stats-grid">
            <!-- Version A -->
            <div class="variant-card variant-a">
                <div class="variant-header">
                    <div class="variant-icon">A</div>
                    <div>
                        <h3 class="variant-title">Benefits-Focused</h3>
                        <p class="variant-subtitle">Value proposition approach</p>
                    </div>
                </div>
                
                <div class="metrics">
                    <div class="metric">
                        <div class="metric-value"><?php echo number_format($results['A']['views']); ?></div>
                        <div class="metric-label">Visitors</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo number_format($results['A']['conversions']); ?></div>
                        <div class="metric-label">Conversions</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo $results['A']['views'] > 0 ? number_format($results['A']['rate'], 1) : '0'; ?>%</div>
                        <div class="metric-label">Rate</div>
                    </div>
                </div>
                
                <div class="conversion-rate">
                    <div class="conversion-rate-value"><?php echo number_format($results['A']['rate'], 2); ?>%</div>
                    <div class="conversion-rate-label">Conversion Rate</div>
                </div>
            </div>
            
            <!-- Version B -->
            <div class="variant-card variant-b">
                <div class="variant-header">
                    <div class="variant-icon">B</div>
                    <div>
                        <h3 class="variant-title">Urgency-Focused</h3>
                        <p class="variant-subtitle">Scarcity & urgency approach</p>
                    </div>
                </div>
                
                <div class="metrics">
                    <div class="metric">
                        <div class="metric-value"><?php echo number_format($results['B']['views']); ?></div>
                        <div class="metric-label">Visitors</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo number_format($results['B']['conversions']); ?></div>
                        <div class="metric-label">Conversions</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo $results['B']['views'] > 0 ? number_format($results['B']['rate'], 1) : '0'; ?>%</div>
                        <div class="metric-label">Rate</div>
                    </div>
                </div>
                
                <div class="conversion-rate">
                    <div class="conversion-rate-value"><?php echo number_format($results['B']['rate'], 2); ?>%</div>
                    <div class="conversion-rate-label">Conversion Rate</div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="activity-panel">
            <h2 class="activity-header">
                <i class="fas fa-activity"></i>
                Recent Activity
            </h2>
            
            <?php if (empty($recent_activity)): ?>
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <p>No activity recorded yet.</p>
            </div>
            <?php else: ?>
            <div class="activity-list">
                <?php foreach ($recent_activity as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo $activity['event_type']; ?>">
                        <?php echo $activity['variant']; ?>
                    </div>
                    <div class="activity-details">
                        <div class="activity-type">
                            <?php 
                            echo $activity['event_type'] === 'conversion' ? 
                                '🎉 Form Submission' : 
                                '👁️ Page View';
                            ?>
                            (Version <?php echo $activity['variant']; ?>)
                        </div>
                        <div class="activity-meta">
                            <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?> 
                            • <?php echo htmlspecialchars($activity['ip_address']); ?>
                            <?php if ($activity['event_data']): ?>
                            • <?php echo htmlspecialchars($activity['event_data']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
        
        <!-- Navigation -->
        <div style="text-align: center; margin-top: 40px;">
            <a href="ab-test-tracker.php" class="refresh-btn">
                <i class="fas fa-arrow-left"></i> Back to Test Setup
            </a>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
        
        // Add conversion tracking to forms
        document.addEventListener('DOMContentLoaded', function() {
            // This would be added to the actual sell pages
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    // Track conversion
                    fetch('ab-test-tracker.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'event_type=conversion&variant=' + (window.location.href.includes('variation-b') ? 'B' : 'A')
                    });
                });
            });
        });
    </script>
</body>
</html> 