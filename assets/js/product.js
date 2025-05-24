// Product page functionality

// Image gallery functionality
function changeMainImage(src, thumbnail) {
    document.getElementById('mainImage').src = src;
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail-image').forEach(img => img.classList.remove('active'));
    thumbnail.classList.add('active');
}

function showAllImages() {
    // In a real implementation, this could open a modal or lightbox with all images
    alert('Feature coming soon: View all images in gallery mode');
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Carousel functionality
    const carousel = document.getElementById('carousel');
    if (carousel) {
        const visibleCards = 4;
        const cardWidth = 215; // 200px + 15px margin
        let currentIndex = 0;
        const items = carousel.children;
        const totalItems = items.length;
        const maxIndex = Math.max(0, totalItems - visibleCards);
        
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        
        function updateCarousel() {
            carousel.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
            if (prevBtn) prevBtn.disabled = currentIndex === 0;
            if (nextBtn) nextBtn.disabled = currentIndex >= maxIndex;
        }
        
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateCarousel();
                }
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                if (currentIndex < maxIndex) {
                    currentIndex++;
                    updateCarousel();
                }
            });
        }
        
        // Initialize carousel
        updateCarousel();
    }
    
    // Add to cart with AJAX functionality
    const addToCartForm = document.getElementById('addToCartForm');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = document.getElementById('addToCartBtn');
            const notification = document.getElementById('addToCartNotification');
            
            // Disable button temporarily
            button.disabled = true;
            button.textContent = 'Adding...';
            
            fetch('/cart.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success notification
                    notification.style.display = 'block';
                    
                    // Update cart count in header
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Reset button
                    button.disabled = false;
                    button.textContent = 'Add to Cart';
                    
                    // Hide notification after 3 seconds
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 3000);
                } else {
                    throw new Error('Failed to add item to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.disabled = false;
                button.textContent = 'Add to Cart';
                alert('Error adding item to cart. Please try again.');
            });
        });
    }
}); 