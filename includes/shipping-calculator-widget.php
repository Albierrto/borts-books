<?php
/**
 * Shipping Calculator Widget
 * Displays a shipping calculator form for product pages
 */

function renderShippingCalculator($productId) {
    ob_start();
    ?>
    <div class="shipping-calculator" id="shipping-calculator">
        <h4 style="margin-bottom: 1rem; color: #232946;">
            <i class="fas fa-shipping-fast"></i> Shipping Calculator
        </h4>
        
        <div class="shipping-form" style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
            <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 120px;">
                    <label for="shipping-zip" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #232946;">ZIP Code:</label>
                    <input type="text" id="shipping-zip" placeholder="e.g. 90210" maxlength="10" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                </div>
                <button id="calculate-shipping" onclick="calculateShipping(<?php echo $productId; ?>)"
                        style="background: #eebbc3; color: #232946; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                    Calculate
                </button>
            </div>
        </div>
        
        <div id="shipping-results" style="display: none;">
            <!-- Shipping options will be displayed here -->
        </div>
    </div>

    <style>
    .shipping-option {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: border-color 0.2s;
    }
    .shipping-option:hover {
        border-color: #eebbc3;
    }
    .shipping-service {
        font-weight: 600;
        color: #232946;
        margin-bottom: 0.25rem;
    }
    .shipping-time {
        font-size: 0.9rem;
        color: #666;
    }
    .shipping-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: #e63946;
    }
    .shipping-loading {
        text-align: center;
        padding: 2rem;
        color: #666;
    }
    .shipping-error {
        background: #fee2e2;
        color: #dc2626;
        padding: 1rem;
        border-radius: 6px;
        text-align: center;
    }
    .shipping-success {
        background: #d1fae5;
        color: #065f46;
        padding: 0.75rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        text-align: center;
        font-weight: 600;
    }
    </style>

    <script>
    function calculateShipping(productId) {
        const zipInput = document.getElementById('shipping-zip');
        const resultsDiv = document.getElementById('shipping-results');
        const calculateBtn = document.getElementById('calculate-shipping');
        
        const zip = zipInput.value.trim();
        
        if (!zip) {
            alert('Please enter a ZIP code');
            return;
        }
        
        if (!/^\d{5}(-\d{4})?$/.test(zip)) {
            alert('Please enter a valid ZIP code (e.g. 90210 or 90210-1234)');
            return;
        }
        
        // Show loading state
        calculateBtn.disabled = true;
        calculateBtn.textContent = 'Calculating...';
        resultsDiv.style.display = 'block';
        resultsDiv.innerHTML = '<div class="shipping-loading"><i class="fas fa-spinner fa-spin"></i> Calculating shipping rates...</div>';
        
        // Make AJAX request
        fetch('/includes/usps-shipping.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_shipping_quote&product_id=${productId}&zip=${encodeURIComponent(zip)}`
        })
        .then(response => response.json())
        .then(data => {
            calculateBtn.disabled = false;
            calculateBtn.textContent = 'Calculate';
            
            if (data.error) {
                resultsDiv.innerHTML = `<div class="shipping-error">${data.error}</div>`;
                return;
            }
            
            if (data.success && data.options) {
                displayShippingOptions(data.options, zip);
            } else {
                resultsDiv.innerHTML = '<div class="shipping-error">No shipping options available</div>';
            }
        })
        .catch(error => {
            calculateBtn.disabled = false;
            calculateBtn.textContent = 'Calculate';
            resultsDiv.innerHTML = '<div class="shipping-error">Error calculating shipping. Please try again.</div>';
            console.error('Shipping calculation error:', error);
        });
    }
    
    function displayShippingOptions(options, zip) {
        const resultsDiv = document.getElementById('shipping-results');
        
        let html = `<div class="shipping-success">Shipping rates to ${zip}:</div>`;
        
                options.forEach(option => {            const price = option.rate === 0 ? 'FREE' : `$${option.rate.toFixed(2)}`;            const apiSource = option.api_source ? `<small style="color: #666; font-size: 0.8rem; margin-left: 0.5rem;">(${option.api_source})</small>` : '';            html += `                <div class="shipping-option">                    <div>                        <div class="shipping-service">${option.service}${apiSource}</div>                        <div class="shipping-time">${option.days}</div>                    </div>                    <div class="shipping-price">${price}</div>                </div>            `;        });
        
        resultsDiv.innerHTML = html;
    }
    
    // Allow Enter key in ZIP input
    document.getElementById('shipping-zip').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            calculateShipping(<?php echo $productId; ?>);
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
?> 