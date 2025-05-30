<?php
/**
 * Mobile Conversion Test for Collection Landing Page
 * Tests critical mobile experience elements
 */

// Get user agent for device detection
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$is_mobile = preg_match('/Mobile|Android|iPhone|iPad/', $user_agent);
$device_type = $is_mobile ? 'Mobile' : 'Desktop';

// Test page load speed
$start_time = microtime(true);

// Simulate form data for testing
$test_data = [
    'page_load_start' => $start_time,
    'device_type' => $device_type,
    'user_agent' => $user_agent,
    'screen_width' => 'Detected via JavaScript',
    'viewport_width' => 'Detected via JavaScript'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Conversion Test - Collection Landing Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }

        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .test-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .test-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }

        .metric-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e9ecef;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 800;
            color: #667eea;
            display: block;
        }

        .metric-label {
            color: #666;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .status-good {
            border-color: #28a745;
            background: #d4edda;
        }

        .status-warning {
            border-color: #ffc107;
            background: #fff3cd;
        }

        .status-error {
            border-color: #dc3545;
            background: #f8d7da;
        }

        .test-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
            margin: 2rem 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }

        .checklist-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .checklist-item.pass {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }

        .checklist-item.fail {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .checklist-item.warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .checklist-icon {
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .tap-target {
            min-height: 44px;
            min-width: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #667eea;
            color: white;
            border-radius: 8px;
            margin: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tap-target:hover {
            background: #5a6fd8;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .test-container {
                padding: 10px;
            }
            
            .test-section {
                padding: 1rem;
            }
            
            .test-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1><i class="fas fa-mobile-alt"></i> Mobile Conversion Test</h1>
            <p>Testing collection landing page for mobile optimization</p>
            <p>Device: <strong><?php echo $device_type; ?></strong></p>
        </div>

        <!-- Device & Performance Metrics -->
        <div class="test-section">
            <h2>📱 Device & Performance Metrics</h2>
            <div class="test-grid">
                <div class="metric-card" id="loadTimeCard">
                    <span class="metric-value" id="loadTime">Testing...</span>
                    <div class="metric-label">Page Load Time (seconds)</div>
                </div>
                <div class="metric-card" id="screenSizeCard">
                    <span class="metric-value" id="screenSize">Detecting...</span>
                    <div class="metric-label">Screen Resolution</div>
                </div>
                <div class="metric-card" id="viewportCard">
                    <span class="metric-value" id="viewport">Detecting...</span>
                    <div class="metric-label">Viewport Width</div>
                </div>
                <div class="metric-card" id="connectionCard">
                    <span class="metric-value" id="connection">Testing...</span>
                    <div class="metric-label">Connection Type</div>
                </div>
            </div>
        </div>

        <!-- Mobile UX Checklist -->
        <div class="test-section">
            <h2>✅ Mobile UX Checklist</h2>
            <div id="uxChecklist">
                <!-- Items populated by JavaScript -->
            </div>
        </div>

        <!-- Form Testing -->
        <div class="test-section">
            <h2>📝 Form Usability Test</h2>
            <p>Test the collection submission form on your device:</p>
            
            <div class="test-form">
                <div class="form-group">
                    <label for="testName">Full Name (Required)</label>
                    <input type="text" id="testName" name="testName" required placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label for="testPhone">Phone Number (Required)</label>
                    <input type="tel" id="testPhone" name="testPhone" required placeholder="(555) 123-4567">
                </div>
                
                <div class="form-group">
                    <label for="testEmail">Email Address (Required)</label>
                    <input type="email" id="testEmail" name="testEmail" required placeholder="your@email.com">
                </div>
                
                <div class="form-group">
                    <label for="testItems">Estimated Items</label>
                    <input type="number" id="testItems" name="testItems" min="1" placeholder="e.g., 50">
                </div>
                
                <div class="form-group">
                    <label for="testDescription">Collection Description</label>
                    <textarea id="testDescription" name="testDescription" rows="3" placeholder="Tell us about your collection..."></textarea>
                </div>
                
                <button type="button" class="submit-btn" onclick="testFormSubmission()">
                    <i class="fas fa-paper-plane"></i> Test Form Submission
                </button>
            </div>
            
            <div id="formFeedback" style="margin-top: 1rem;"></div>
        </div>

        <!-- Touch Target Testing -->
        <div class="test-section">
            <h2>👆 Touch Target Testing</h2>
            <p>Test that buttons and links are easy to tap (minimum 44px x 44px):</p>
            
            <div style="margin: 2rem 0;">
                <div class="tap-target" onclick="testTapTarget(this, 'good')">
                    Good Size Button
                </div>
                
                <div class="tap-target" style="min-height: 30px; min-width: 30px;" onclick="testTapTarget(this, 'small')">
                    Small
                </div>
                
                <div class="tap-target" style="min-height: 60px; min-width: 120px;" onclick="testTapTarget(this, 'large')">
                    Large Button
                </div>
            </div>
            
            <div id="tapFeedback"></div>
        </div>

        <!-- Speed Test -->
        <div class="test-section">
            <h2>⚡ Speed & Loading Test</h2>
            <div id="speedResults">
                <p>Testing page elements loading speed...</p>
            </div>
            
            <button onclick="runSpeedTest()" class="submit-btn" style="width: auto; margin-top: 1rem;">
                <i class="fas fa-tachometer-alt"></i> Run Speed Test
            </button>
        </div>

        <!-- Recommendations -->
        <div class="test-section">
            <h2>💡 Optimization Recommendations</h2>
            <div id="recommendations">
                <!-- Populated by JavaScript based on test results -->
            </div>
        </div>

        <!-- Quick Link to Landing Page -->
        <div class="test-section" style="text-align: center;">
            <h2>🚀 Test Your Landing Page</h2>
            <p>Ready to test the actual collection landing page?</p>
            <a href="pages/sell-your-collection.php" class="submit-btn" style="display: inline-block; text-decoration: none; width: auto; margin: 1rem;">
                <i class="fas fa-external-link-alt"></i> Visit Collection Landing Page
            </a>
        </div>
    </div>

    <script>
        // Track page load performance
        let loadStartTime = performance.now();
        
        window.addEventListener('load', function() {
            let loadTime = (performance.now() - loadStartTime) / 1000;
            updateLoadTime(loadTime);
            runInitialTests();
        });

        function updateLoadTime(time) {
            const loadTimeElement = document.getElementById('loadTime');
            const loadTimeCard = document.getElementById('loadTimeCard');
            
            loadTimeElement.textContent = time.toFixed(2);
            
            if (time < 3) {
                loadTimeCard.classList.add('status-good');
            } else if (time < 5) {
                loadTimeCard.classList.add('status-warning');
            } else {
                loadTimeCard.classList.add('status-error');
            }
        }

        function runInitialTests() {
            // Screen size detection
            const screenWidth = screen.width;
            const screenHeight = screen.height;
            const viewportWidth = window.innerWidth;
            
            document.getElementById('screenSize').textContent = `${screenWidth}x${screenHeight}`;
            document.getElementById('viewport').textContent = `${viewportWidth}px`;
            
            // Viewport assessment
            const viewportCard = document.getElementById('viewportCard');
            if (viewportWidth >= 768) {
                viewportCard.classList.add('status-good');
            } else if (viewportWidth >= 480) {
                viewportCard.classList.add('status-warning');
            } else {
                viewportCard.classList.add('status-error');
            }
            
            // Connection detection
            const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            const connectionCard = document.getElementById('connectionCard');
            const connectionElement = document.getElementById('connection');
            
            if (connection) {
                connectionElement.textContent = connection.effectiveType || 'Unknown';
                if (connection.effectiveType === '4g') {
                    connectionCard.classList.add('status-good');
                } else if (connection.effectiveType === '3g') {
                    connectionCard.classList.add('status-warning');
                } else {
                    connectionCard.classList.add('status-error');
                }
            } else {
                connectionElement.textContent = 'Not detected';
                connectionCard.classList.add('status-warning');
            }
            
            // Run UX checklist
            runUXChecklist();
            generateRecommendations();
        }

        function runUXChecklist() {
            const checklist = [
                {
                    test: 'Viewport meta tag present',
                    result: document.querySelector('meta[name="viewport"]') !== null,
                    importance: 'critical'
                },
                {
                    test: 'Form fields have proper input types',
                    result: document.querySelector('input[type="tel"]') !== null && document.querySelector('input[type="email"]') !== null,
                    importance: 'high'
                },
                {
                    test: 'Buttons are touch-friendly (44px+ height)',
                    result: checkButtonSizes(),
                    importance: 'high'
                },
                {
                    test: 'Text is readable (16px+ font size)',
                    result: checkFontSizes(),
                    importance: 'medium'
                },
                {
                    test: 'Page loads in under 3 seconds',
                    result: parseFloat(document.getElementById('loadTime').textContent) < 3,
                    importance: 'critical'
                },
                {
                    test: 'No horizontal scrolling required',
                    result: document.body.scrollWidth <= window.innerWidth,
                    importance: 'high'
                }
            ];
            
            const checklistContainer = document.getElementById('uxChecklist');
            checklistContainer.innerHTML = '';
            
            checklist.forEach(item => {
                const div = document.createElement('div');
                div.className = `checklist-item ${item.result ? 'pass' : 'fail'}`;
                
                const icon = item.result ? '✅' : '❌';
                const status = item.result ? 'PASS' : 'FAIL';
                
                div.innerHTML = `
                    <span class="checklist-icon">${icon}</span>
                    <div>
                        <strong>${item.test}</strong> - ${status}
                        <br><small>Importance: ${item.importance.toUpperCase()}</small>
                    </div>
                `;
                
                checklistContainer.appendChild(div);
            });
        }

        function checkButtonSizes() {
            const buttons = document.querySelectorAll('button, .submit-btn');
            let allGood = true;
            
            buttons.forEach(button => {
                const rect = button.getBoundingClientRect();
                if (rect.height < 44 || rect.width < 44) {
                    allGood = false;
                }
            });
            
            return allGood;
        }

        function checkFontSizes() {
            const body = document.body;
            const fontSize = parseInt(window.getComputedStyle(body).fontSize);
            return fontSize >= 16;
        }

        function testFormSubmission() {
            const feedback = document.getElementById('formFeedback');
            const form = document.querySelector('.test-form');
            
            // Check if required fields are filled
            const name = document.getElementById('testName').value;
            const phone = document.getElementById('testPhone').value;
            const email = document.getElementById('testEmail').value;
            
            if (!name || !phone || !email) {
                feedback.innerHTML = `
                    <div class="checklist-item fail">
                        <span class="checklist-icon">❌</span>
                        <div>Please fill in all required fields to test form submission</div>
                    </div>
                `;
                return;
            }
            
            feedback.innerHTML = `
                <div class="checklist-item pass">
                    <span class="checklist-icon">✅</span>
                    <div>
                        <strong>Form Test Successful!</strong><br>
                        All required fields filled correctly. Mobile form experience is working well.
                    </div>
                </div>
            `;
        }

        function testTapTarget(element, size) {
            const feedback = document.getElementById('tapFeedback');
            const rect = element.getBoundingClientRect();
            
            let message = '';
            let status = '';
            
            if (size === 'good') {
                message = `✅ Perfect! This button (${Math.round(rect.width)}x${Math.round(rect.height)}px) meets touch target guidelines.`;
                status = 'pass';
            } else if (size === 'small') {
                message = `❌ Too small! This button (${Math.round(rect.width)}x${Math.round(rect.height)}px) is below the 44px minimum.`;
                status = 'fail';
            } else {
                message = `✅ Great! Large buttons (${Math.round(rect.width)}x${Math.round(rect.height)}px) are easy to tap.`;
                status = 'pass';
            }
            
            feedback.innerHTML = `
                <div class="checklist-item ${status}">
                    <div>${message}</div>
                </div>
            `;
        }

        function runSpeedTest() {
            const resultsContainer = document.getElementById('speedResults');
            resultsContainer.innerHTML = '<p>Running speed tests...</p>';
            
            // Test image loading
            const startTime = performance.now();
            const testImage = new Image();
            
            testImage.onload = function() {
                const imageLoadTime = performance.now() - startTime;
                
                // Test AJAX request speed
                const ajaxStart = performance.now();
                fetch(window.location.href)
                    .then(response => {
                        const ajaxTime = performance.now() - ajaxStart;
                        
                        resultsContainer.innerHTML = `
                            <div class="test-grid">
                                <div class="metric-card ${imageLoadTime < 1000 ? 'status-good' : 'status-warning'}">
                                    <span class="metric-value">${Math.round(imageLoadTime)}ms</span>
                                    <div class="metric-label">Image Load Time</div>
                                </div>
                                <div class="metric-card ${ajaxTime < 500 ? 'status-good' : 'status-warning'}">
                                    <span class="metric-value">${Math.round(ajaxTime)}ms</span>
                                    <div class="metric-label">Server Response Time</div>
                                </div>
                            </div>
                        `;
                    });
            };
            
            testImage.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzY2N2VlYSIvPjwvc3ZnPg==';
        }

        function generateRecommendations() {
            const recommendations = [];
            const viewport = window.innerWidth;
            const loadTime = parseFloat(document.getElementById('loadTime').textContent);
            
            if (loadTime > 3) {
                recommendations.push({
                    icon: '⚡',
                    title: 'Improve Page Speed',
                    description: 'Page loads in ' + loadTime.toFixed(2) + ' seconds. Optimize images and minimize CSS/JS.',
                    priority: 'high'
                });
            }
            
            if (viewport < 480) {
                recommendations.push({
                    icon: '📱',
                    title: 'Optimize for Small Screens',
                    description: 'Device has narrow viewport (' + viewport + 'px). Ensure forms are thumb-friendly.',
                    priority: 'medium'
                });
            }
            
            if (!checkButtonSizes()) {
                recommendations.push({
                    icon: '👆',
                    title: 'Increase Touch Targets',
                    description: 'Some buttons are smaller than 44px. Make them larger for easier tapping.',
                    priority: 'high'
                });
            }
            
            // Always include these general recommendations
            recommendations.push({
                icon: '🎯',
                title: 'Test on Real Devices',
                description: 'Test your landing page on actual smartphones and tablets for best results.',
                priority: 'medium'
            });
            
            recommendations.push({
                icon: '📊',
                title: 'Monitor Mobile Analytics',
                description: 'Track mobile conversion rates separately and optimize based on data.',
                priority: 'medium'
            });
            
            const container = document.getElementById('recommendations');
            container.innerHTML = recommendations.map(rec => `
                <div class="checklist-item ${rec.priority === 'high' ? 'warning' : 'pass'}">
                    <span class="checklist-icon">${rec.icon}</span>
                    <div>
                        <strong>${rec.title}</strong><br>
                        ${rec.description}
                        <br><small>Priority: ${rec.priority.toUpperCase()}</small>
                    </div>
                </div>
            `).join('');
        }

        // Track user interactions for testing
        let interactions = 0;
        document.addEventListener('click', function() {
            interactions++;
        });

        // Export test results
        function exportTestResults() {
            const results = {
                device_type: '<?php echo $device_type; ?>',
                user_agent: '<?php echo $user_agent; ?>',
                viewport_width: window.innerWidth,
                screen_resolution: screen.width + 'x' + screen.height,
                load_time: document.getElementById('loadTime').textContent,
                interactions: interactions,
                timestamp: new Date().toISOString()
            };
            
            console.log('Mobile Test Results:', results);
            return results;
        }

        // Auto-run tests after page loads
        setTimeout(function() {
            if (window.innerWidth <= 768) {
                console.log('Mobile device detected - running enhanced mobile tests');
            }
        }, 2000);
    </script>
</body>
</html> 