<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/email-system.php';

$pageTitle = "Track Your Order";
$currentPage = "track";

$order = null;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $orderNumber = trim($_POST['order_number'] ?? '');
    
    if (!$email) {
        $error = 'Please enter a valid email address';
    } elseif (empty($orderNumber)) {
        $error = 'Please enter your order number';
    } else {
        $emailSystem = new EmailSystem($db);
        $order = $emailSystem->getOrderByEmailAndNumber($email, $orderNumber);
        
        if (!$order) {
            $error = 'Order not found. Please check your email address and order number.';
        }
    }
}

// Initialize cart for header
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart_count = count($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Bort's Books</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .track-container {
            max-width: 800px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(35,41,70,0.08);
            padding: 2.5rem;
        }
        .track-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-align: center;
            color: #232946;
        }
        .track-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
        }
        .track-form {
            max-width: 500px;
            margin: 0 auto 2rem auto;
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
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #eebbc3;
            box-shadow: 0 0 0 3px rgba(238, 187, 195, 0.1);
        }
        .track-btn {
            background: linear-gradient(45deg, #eebbc3, #f7c7d0);
            color: #232946;
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .track-btn:hover {
            background: linear-gradient(45deg, #232946, #395aa0);
            color: #fff;
            transform: translateY(-2px);
        }
        .error-message {
            background: #ffe0e0;
            color: #d63384;
            border: 1px solid #f8d7da;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .order-details {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eebbc3;
        }
        .order-number {
            font-size: 1.5rem;
            font-weight: 800;
            color: #232946;
        }
        .order-status {
            background: #28a745;
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .info-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }
        .info-value {
            font-size: 1.1rem;
            color: #232946;
        }
        .order-items {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .items-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #232946;
        }
        .item-list {
            color: #666;
            line-height: 1.6;
        }
        .help-section {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1.5rem;
            margin-top: 2rem;
            border-radius: 0 12px 12px 0;
        }
        .help-title {
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 0.5rem;
        }
        @media (max-width: 768px) {
            .track-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            .order-header {
                flex-direction: column;
                text-align: center;
            }
            .order-info {
                grid-template-columns: 1fr;
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
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/pages/shop.php">Shop</a></li>
                    <li><a href="/pages/sell.php">Sell Manga</a></li>
                    <li><a href="/pages/about.php">About</a></li>
                </ul>
            </nav>
            <div class="search-cart">
                <a href="../search.php" title="Search"><i class="fas fa-search"></i></a>
                <a href="../cart.php" title="Shopping Cart" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </header>

    <main>
        <div class="track-container">
            <h1 class="track-title">Track Your Order</h1>
            <p class="track-subtitle">Enter your email address and order number to view your order details</p>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="track-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="Enter your email address">
                </div>
                
                <div class="form-group">
                    <label for="order_number">Order Number</label>
                    <input type="text" id="order_number" name="order_number" required 
                           value="<?php echo htmlspecialchars($_POST['order_number'] ?? ''); ?>"
                           placeholder="e.g. BB2400123">
                </div>
                
                <button type="submit" class="track-btn">
                    <i class="fas fa-search"></i>
                    Track Order
                </button>
            </form>
            
            <?php if ($order): ?>
                <div class="order-details">
                    <div class="order-header">
                        <div class="order-number">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
                        <div class="order-status"><?php echo htmlspecialchars($order['payment_status'] ?? 'Processing'); ?></div>
                    </div>
                    
                    <div class="order-info">
                        <div class="info-card">
                            <div class="info-label">Order Date</div>
                            <div class="info-value"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-label">Total Amount</div>
                            <div class="info-value">$<?php echo number_format($order['total_amount'], 2); ?></div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-label">Customer Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['items'])): ?>
                        <div class="order-items">
                            <div class="items-title">Items Ordered</div>
                            <div class="item-list">
                                <?php echo htmlspecialchars($order['items']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="help-section">
                <div class="help-title">Need Help?</div>
                <p>If you can't find your order or need assistance, please contact us at 
                   <a href="mailto:info@bortsbooks.com" style="color: #1976d2; font-weight: 600;">info@bortsbooks.com</a> 
                   or call <strong>(123) 456-7890</strong>. Have your order number ready!</p>
            </div>
        </div>
    </main>
</body>
</html> 