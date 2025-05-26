// Shipping calculation function
function calculateShipping() {
    const zipInput = document.getElementById('zip') || document.querySelector('input[name="zip"]');
    const serviceSelect = document.getElementById('shipping_service') || document.querySelector('select[name="shipping_service"]');
    const resultsDiv = document.getElementById('shipping-results') || createShippingResultsDiv();
    
    if (!zipInput || !zipInput.value) {
        showShippingError('Please enter a ZIP code');
        return;
    }
    
    const zip = zipInput.value.trim();
    const service = serviceSelect ? serviceSelect.value : 'Ground';
    
    // Validate ZIP code
    if (!/^\d{5}(-\d{4})?$/.test(zip)) {
        showShippingError('Please enter a valid ZIP code (e.g., 90210 or 90210-1234)');
        return;
    }
    
    // Show loading state
    resultsDiv.innerHTML = '<div class="shipping-loading"><i class="fas fa-spinner fa-spin"></i> Calculating shipping rates...</div>';
    resultsDiv.style.display = 'block';
    
    // Make AJAX request
    const formData = new FormData();
    formData.append('calculate_shipping_only', '1');
    formData.append('zip', zip);
    formData.append('shipping_service', service);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            showShippingError(data.error, data.fallback_message);
        } else if (data.success) {
            showShippingSuccess(data);
        } else {
            showShippingError('Unexpected response from server');
        }
    })
    .catch(error => {
        console.error('Shipping calculation error:', error);
        showShippingError('Network error. Please check your connection and try again.');
    });
}

function showShippingError(message, fallbackMessage = null) {
    const resultsDiv = document.getElementById('shipping-results') || createShippingResultsDiv();
    let html = `<div class="shipping-error">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Shipping Error:</strong> ${message}
    </div>`;
    
    if (fallbackMessage) {
        html += `<div class="shipping-fallback">
            <i class="fas fa-info-circle"></i>
            ${fallbackMessage}
        </div>`;
    }
    
    resultsDiv.innerHTML = html;
    resultsDiv.style.display = 'block';
}

function showShippingSuccess(data) {
    const resultsDiv = document.getElementById('shipping-results') || createShippingResultsDiv();
    
    let html = `<div class="shipping-success">
        <h4><i class="fas fa-check-circle"></i> Shipping Calculated</h4>
        <p><strong>Shipping Cost:</strong> ${data.formatted_shipping}</p>
        <p><strong>Total:</strong> ${data.formatted_total}</p>
    </div>`;
    
    // Show API status
    if (data.api_status && data.api_status.length > 0) {
        const status = data.api_status.includes('USPS API') ? 'Real-time USPS rates' : 'Estimated rates';
        html += `<div class="shipping-status">
            <small><i class="fas fa-info-circle"></i> ${status}</small>
        </div>`;
    }
    
    // Show warnings if any
    if (data.warnings && data.warnings.length > 0) {
        html += '<div class="shipping-warnings">';
        data.warnings.forEach(warning => {
            html += `<div class="shipping-warning">
                <i class="fas fa-exclamation-circle"></i> ${warning}
            </div>`;
        });
        html += '</div>';
    }
    
    resultsDiv.innerHTML = html;
    resultsDiv.style.display = 'block';
    
    // Update the order summary
    updateOrderSummary(data);
}

function createShippingResultsDiv() {
    const div = document.createElement('div');
    div.id = 'shipping-results';
    div.className = 'shipping-results';
    
    // Find a good place to insert it (after ZIP input or in order summary)
    const zipInput = document.getElementById('zip') || document.querySelector('input[name="zip"]');
    if (zipInput && zipInput.parentNode) {
        zipInput.parentNode.insertBefore(div, zipInput.nextSibling);
    }
    
    return div;
}

function updateOrderSummary(data) {
    // Update shipping cost display
    const shippingElement = document.getElementById('checkout-shipping');
    if (shippingElement) {
        shippingElement.textContent = data.formatted_shipping;
    }
    
    // Update total display
    const totalElement = document.getElementById('checkout-total');
    if (totalElement) {
        totalElement.textContent = data.formatted_total;
    }
}

// Add CSS styles for the shipping results
const shippingStyles = `
.shipping-results {
    margin: 1rem 0;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.shipping-error {
    color: #721c24;
    background: #f8d7da;
    padding: 0.75rem;
    border-radius: 6px;
    margin-bottom: 0.5rem;
}

.shipping-success {
    color: #155724;
    background: #d4edda;
    padding: 0.75rem;
    border-radius: 6px;
    margin-bottom: 0.5rem;
}

.shipping-warning {
    color: #856404;
    background: #fff3cd;
    padding: 0.5rem;
    border-radius: 4px;
    margin: 0.25rem 0;
    font-size: 0.9rem;
}

.shipping-loading {
    color: #0c5460;
    background: #d1ecf1;
    padding: 0.75rem;
    border-radius: 6px;
    text-align: center;
}

.shipping-status {
    color: #0c5460;
    font-size: 0.85rem;
    margin-top: 0.5rem;
}

.shipping-fallback {
    color: #856404;
    background: #fff3cd;
    padding: 0.5rem;
    border-radius: 4px;
    margin-top: 0.5rem;
    font-size: 0.9rem;
}
`;

// Add styles to page
const styleSheet = document.createElement('style');
styleSheet.textContent = shippingStyles;
document.head.appendChild(styleSheet);

// Auto-bind to shipping calculation triggers
document.addEventListener('DOMContentLoaded', function() {
    // Bind to calculate shipping button
    const calculateBtn = document.getElementById('calculate-shipping') || document.querySelector('button[onclick*="calculateShipping"]');
    if (calculateBtn) {
        calculateBtn.addEventListener('click', calculateShipping);
    }
    
    // Bind to ZIP code change
    const zipInput = document.getElementById('zip') || document.querySelector('input[name="zip"]');
    if (zipInput) {
        zipInput.addEventListener('blur', function() {
            if (this.value.length >= 5) {
                calculateShipping();
            }
        });
    }
});
