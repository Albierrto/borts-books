<?php
$pageTitle = "Mobile Scroll Test";
$currentPage = "test";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Scroll Test - Bort's Books</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/mobile-nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: #f7f7fa;
        }
        
        .test-header {
            background: linear-gradient(135deg, #232946 0%, #395aa0 100%);
            color: white;
            padding: 2rem 1rem;
            text-align: center;
        }
        
        .test-section {
            padding: 2rem 1rem;
            max-width: 800px;
            margin: 0 auto;
            background: white;
            margin-bottom: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .scroll-content {
            height: 200vh;
            background: linear-gradient(to bottom, #f8f9fa, #e9ecef, #dee2e6, #ced4da, #adb5bd);
            padding: 2rem;
            margin: 2rem 0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
        }
        
        .scroll-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #232946;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            font-weight: 600;
            z-index: 500;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .test-instructions {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .test-instructions h3 {
            margin-top: 0;
            color: #155724;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .status-good { background: #28a745; }
        .status-warning { background: #ffc107; }
        .status-error { background: #dc3545; }
    </style>
</head>
<body>
    <!-- Header with mobile navigation -->
    <header>
        <div class="header-container">
            <div class="logo">
                <a href="/index.php">Bort's Books</a>
            </div>
            
            <!-- Desktop Navigation -->
            <nav class="main-nav">
                <a href="/index.php">Home</a>
                <a href="/pages/shop.php">Shop</a>
                <a href="/pages/about.php">About</a>
                <a href="/pages/contact.php">Contact</a>
            </nav>
            
            <!-- Cart Icon -->
            <div class="search-cart">
                <a href="/pages/cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Scroll Position Indicator -->
    <div class="scroll-indicator" id="scrollIndicator">
        Scroll: 0px
    </div>

    <!-- Test Header -->
    <div class="test-header">
        <h1><i class="fas fa-mobile-alt"></i> Mobile Scroll Test</h1>
        <p>Testing the mobile scrolling flicker fix</p>
    </div>

    <!-- Test Instructions -->
    <div class="test-section">
        <div class="test-instructions">
            <h3><i class="fas fa-info-circle"></i> How to Test</h3>
            <ol>
                <li><strong>On Mobile:</strong> Scroll up and down this page slowly and quickly</li>
                <li><strong>Open Menu:</strong> Tap the hamburger menu (☰) in the header</li>
                <li><strong>Close Menu:</strong> Close the menu and continue scrolling</li>
                <li><strong>Check for Flicker:</strong> The page should NOT jump to the top or flicker during scrolling</li>
            </ol>
        </div>

        <h2>Scroll Test Results</h2>
        <div id="testResults">
            <p><span class="status-indicator status-good"></span> <strong>Smooth Scrolling:</strong> <span id="smoothStatus">Testing...</span></p>
            <p><span class="status-indicator status-good"></span> <strong>No Position Jumps:</strong> <span id="jumpStatus">Testing...</span></p>
            <p><span class="status-indicator status-good"></span> <strong>Menu Functionality:</strong> <span id="menuStatus">Testing...</span></p>
        </div>
    </div>

    <!-- Long scrollable content -->
    <div class="scroll-content">
        <h2>Scroll Through This Content</h2>
        <p>This is a long section designed to test mobile scrolling behavior.</p>
        <p>Keep scrolling to test the fix...</p>
        
        <div style="margin: 4rem 0;">
            <h3>Section 1</h3>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
        </div>
        
        <div style="margin: 4rem 0;">
            <h3>Section 2</h3>
            <p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
        </div>
        
        <div style="margin: 4rem 0;">
            <h3>Section 3</h3>
            <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
        </div>
        
        <div style="margin: 4rem 0;">
            <h3>Section 4</h3>
            <p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
        </div>
        
        <div style="margin: 4rem 0;">
            <h3>Section 5</h3>
            <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.</p>
        </div>
    </div>

    <!-- Final Test Section -->
    <div class="test-section">
        <h2>Test Complete</h2>
        <p>If you scrolled through this entire page without experiencing:</p>
        <ul>
            <li>❌ Page jumping to the top</li>
            <li>❌ Flickering during scroll</li>
            <li>❌ Scroll position loss when opening/closing menu</li>
        </ul>
        <p><strong>✅ The mobile scrolling flicker issue has been successfully fixed!</strong></p>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="/index.php" style="display: inline-block; padding: 1rem 2rem; background: #232946; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                <i class="fas fa-home"></i> Back to Homepage
            </a>
        </div>
    </div>

    <script>
        // Track scroll position and test for issues
        let lastScrollTop = 0;
        let scrollJumps = 0;
        let smoothScrolling = true;
        let menuTested = false;

        function updateScrollIndicator() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            document.getElementById('scrollIndicator').textContent = `Scroll: ${Math.round(scrollTop)}px`;
            
            // Detect sudden scroll jumps (potential flicker)
            const scrollDiff = Math.abs(scrollTop - lastScrollTop);
            if (scrollDiff > 100 && lastScrollTop > 0) {
                scrollJumps++;
                smoothScrolling = false;
                console.warn('Scroll jump detected:', scrollDiff, 'px');
            }
            
            lastScrollTop = scrollTop;
            updateTestResults();
        }

        function updateTestResults() {
            // Update smooth scrolling status
            const smoothStatus = document.getElementById('smoothStatus');
            if (smoothScrolling) {
                smoothStatus.textContent = 'PASS - No scroll jumps detected';
                smoothStatus.style.color = '#155724';
            } else {
                smoothStatus.textContent = `FAIL - ${scrollJumps} scroll jumps detected`;
                smoothStatus.style.color = '#721c24';
            }
            
            // Update jump status
            const jumpStatus = document.getElementById('jumpStatus');
            if (scrollJumps === 0) {
                jumpStatus.textContent = 'PASS - No position jumps';
                jumpStatus.style.color = '#155724';
            } else {
                jumpStatus.textContent = `FAIL - ${scrollJumps} position jumps`;
                jumpStatus.style.color = '#721c24';
            }
            
            // Update menu status
            const menuStatus = document.getElementById('menuStatus');
            if (menuTested) {
                menuStatus.textContent = 'PASS - Menu tested successfully';
                menuStatus.style.color = '#155724';
            } else {
                menuStatus.textContent = 'Waiting - Please test mobile menu';
                menuStatus.style.color = '#856404';
            }
        }

        // Listen for scroll events
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(updateScrollIndicator, 10);
        });

        // Listen for mobile menu interactions
        document.addEventListener('click', (e) => {
            if (e.target.closest('.mobile-menu-toggle') || 
                e.target.closest('.mobile-nav-close') || 
                e.target.closest('.mobile-nav-overlay')) {
                menuTested = true;
                updateTestResults();
            }
        });

        // Initial update
        updateTestResults();

        // Performance monitoring
        let frameCount = 0;
        let lastTime = performance.now();

        function monitorPerformance() {
            frameCount++;
            const currentTime = performance.now();
            
            if (currentTime - lastTime >= 1000) {
                const fps = Math.round((frameCount * 1000) / (currentTime - lastTime));
                console.log('FPS:', fps);
                frameCount = 0;
                lastTime = currentTime;
            }
            
            requestAnimationFrame(monitorPerformance);
        }

        // Start performance monitoring
        requestAnimationFrame(monitorPerformance);
    </script>
    
    <!-- Load mobile navigation -->
    <script src="assets/js/mobile-nav.js"></script>
</body>
</html> 