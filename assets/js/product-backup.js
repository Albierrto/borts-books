// Product page functionality

// Image caching and preloading for better performance
const imageCache = new Map();
let preloadedImages = new Set();

function preloadImage(src) {
    if (preloadedImages.has(src)) return Promise.resolve();
    
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => {
            imageCache.set(src, img);
            preloadedImages.add(src);
            resolve(img);
        };
        img.onerror = reject;
        img.src = src;
    });
}

function preloadAllImages() {
    if (typeof productImages !== 'undefined' && productImages.length > 0) {
        // Preload first 3 images immediately for instant viewing
        const priorityImages = productImages.slice(0, 3);
        priorityImages.forEach(img => preloadImage(img.image_url));
        
        // Preload remaining images with staggered delay to avoid blocking
        setTimeout(() => {
            const remainingImages = productImages.slice(3);
            remainingImages.forEach((img, index) => {
                setTimeout(() => preloadImage(img.image_url), index * 100);
            });
        }, 1000);
    }
}

// Image gallery functionality
function changeMainImage(src, thumbnail) {
    document.getElementById('mainImage').src = src;
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail-image').forEach(img => img.classList.remove('active'));
    thumbnail.classList.add('active');
    
    // Update current modal index for main image clicks
    if (typeof productImages !== 'undefined') {
        currentModalIndex = productImages.findIndex(img => img.image_url === src);
        if (currentModalIndex === -1) currentModalIndex = 0;
    }
}

function openImageModal(index = 0) {
    if (typeof productImages === 'undefined' || productImages.length === 0) return;
    
    currentModalIndex = index;
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalCounter = document.getElementById('modalCounter');
    
    modalImage.src = productImages[currentModalIndex].image_url;
    modalImage.alt = productImages[currentModalIndex].alt || 'Product image';
    modalCounter.textContent = `${currentModalIndex + 1} / ${productImages.length}`;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.remove('active');
    document.body.style.overflow = ''; // Restore scrolling
}

function nextImage() {
    if (typeof productImages === 'undefined' || productImages.length === 0) return;
    
    currentModalIndex = (currentModalIndex + 1) % productImages.length;
    const modalImage = document.getElementById('modalImage');
    const modalCounter = document.getElementById('modalCounter');
    
    modalImage.src = productImages[currentModalIndex].image_url;
    modalCounter.textContent = `${currentModalIndex + 1} / ${productImages.length}`;
}

function prevImage() {
    if (typeof productImages === 'undefined' || productImages.length === 0) return;
    
    currentModalIndex = (currentModalIndex - 1 + productImages.length) % productImages.length;
    const modalImage = document.getElementById('modalImage');
    const modalCounter = document.getElementById('modalCounter');
    
    modalImage.src = productImages[currentModalIndex].image_url;
    modalCounter.textContent = `${currentModalIndex + 1} / ${productImages.length}`;
}

function showAllImages() {
    // Open modal with first image
    openImageModal(0);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Start preloading images for better performance with many photos
    preloadAllImages();
    
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
    
    // Image modal keyboard navigation
    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('imageModal');
        if (modal && modal.classList.contains('active')) {
            switch(e.key) {
                case 'Escape':
                    closeImageModal();
                    break;
                case 'ArrowLeft':
                    prevImage();
                    break;
                case 'ArrowRight':
                    nextImage();
                    break;
            }
        }
    });
    
    // Click outside modal to close
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeImageModal();
            }
        });
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
                    // Show notification with appropriate message
                    notification.textContent = data.message;
                    
                    // Change styling based on whether item was already in cart
                    if (data.already_in_cart) {
                        notification.style.background = '#fff3cd';
                        notification.style.color = '#856404';
                        notification.style.borderColor = '#ffeaa7';
                    } else {
                        notification.style.background = '#d4edda';
                        notification.style.color = '#155724';
                        notification.style.borderColor = '#c3e6cb';
                    }
                    
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