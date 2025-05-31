/**
 * Mobile Navigation JavaScript
 * Provides consistent mobile navigation across all pages
 * Only initializes on mobile devices (768px and below)
 */

class MobileNavigation {
    constructor() {
        this.isOpen = false;
        this.isInitialized = false;
        this.isMobile = false;
        this.scrollPosition = 0;
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    setup() {
        // Prevent multiple initializations
        if (this.isInitialized) {
            return;
        }
        
        // Check if we're on mobile
        this.checkMobile();
        
        // Only initialize on mobile
        if (this.isMobile) {
            this.createMobileNavigation();
            this.bindEvents();
        }
        
        // Always bind resize event to handle screen size changes
        this.handleResize();
        this.isInitialized = true;
    }

    checkMobile() {
        this.isMobile = window.innerWidth <= 768;
    }

    createMobileNavigation() {
        // Check if mobile nav already exists
        if (document.querySelector('.mobile-nav')) {
            return;
        }

        // Get current page for active state
        const currentPage = this.getCurrentPage();
        
        // Get cart count
        const cartCount = this.getCartCount();

        // Create mobile navigation toggle button
        const toggleButton = `
            <button class="mobile-nav-toggle" aria-label="Open navigation menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        `;

        // Create mobile navigation HTML using existing CSS classes
        const mobileNavHTML = `
            <div class="mobile-nav">
                <div class="mobile-nav-content">
                    <div class="mobile-nav-header">
                        <h3>Navigation</h3>
                        <button class="mobile-nav-close" aria-label="Close navigation menu">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="mobile-nav-links">
                        <a href="/index.php" class="${currentPage === 'home' ? 'active' : ''}">
                            <i class="fas fa-home"></i> Home
                        </a>
                        <a href="/pages/shop.php" class="${currentPage === 'shop' ? 'active' : ''}">
                            <i class="fas fa-store"></i> Shop
                        </a>
                        <a href="/pages/track-order.php" class="${currentPage === 'track' ? 'active' : ''}">
                            <i class="fas fa-shipping-fast"></i> Track Order
                        </a>
                        <a href="/pages/sell.php" class="${currentPage === 'sell' ? 'active' : ''}">
                            <i class="fas fa-dollar-sign"></i> Sell Manga
                        </a>
                        <a href="/pages/about.php" class="${currentPage === 'about' ? 'active' : ''}">
                            <i class="fas fa-info-circle"></i> About
                        </a>
                        <a href="/pages/contact.php" class="${currentPage === 'contact' ? 'active' : ''}">
                            <i class="fas fa-envelope"></i> Contact
                        </a>
                        <a href="/pages/faq.php" class="${currentPage === 'faq' ? 'active' : ''}">
                            <i class="fas fa-question-circle"></i> FAQ
                        </a>
                        <a href="/pages/returns.php" class="${currentPage === 'returns' ? 'active' : ''}">
                            <i class="fas fa-undo"></i> Returns
                        </a>
                        
                        <a href="/pages/cart.php" class="mobile-cart-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Cart (${cartCount})</span>
                        </a>
                    </div>
                </div>
            </div>
        `;

        // Insert mobile navigation toggle into header
        const header = document.querySelector('header .header-container');
        if (header) {
            // Add toggle button to header
            header.insertAdjacentHTML('beforeend', toggleButton);
            
            // Add mobile navigation to body
            document.body.insertAdjacentHTML('beforeend', mobileNavHTML);
        }
    }

    bindEvents() {
        // Use event delegation to avoid multiple listeners
        document.removeEventListener('click', this.handleClick);
        document.removeEventListener('keydown', this.handleKeydown);
        
        // Bind events with proper context
        this.handleClick = this.handleClick.bind(this);
        this.handleKeydown = this.handleKeydown.bind(this);
        
        document.addEventListener('click', this.handleClick);
        document.addEventListener('keydown', this.handleKeydown);

        // Handle window resize with debouncing
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => this.handleResize(), 100);
        });

        // Update cart count when it changes
        this.observeCartChanges();
    }

    handleClick(e) {
        // Only handle clicks if we're on mobile
        if (!this.isMobile) return;

        // Mobile menu toggle button
        if (e.target.closest('.mobile-nav-toggle')) {
            e.preventDefault();
            e.stopPropagation();
            this.toggleMenu();
            return;
        }

        // Mobile menu close button
        if (e.target.closest('.mobile-nav-close')) {
            e.preventDefault();
            e.stopPropagation();
            this.closeMenu();
            return;
        }

        // Close menu when clicking outside (on the mobile nav overlay)
        if (e.target.classList.contains('mobile-nav') && this.isOpen) {
            e.preventDefault();
            e.stopPropagation();
            this.closeMenu();
            return;
        }

        // Close menu when clicking on navigation links
        if (e.target.closest('.mobile-nav-links a')) {
            this.closeMenu();
            return;
        }
    }

    handleKeydown(e) {
        if (e.key === 'Escape' && this.isOpen && this.isMobile) {
            e.preventDefault();
            this.closeMenu();
        }
    }

    toggleMenu() {
        if (!this.isMobile) return;
        
        if (this.isOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }

    openMenu() {
        if (!this.isMobile || this.isOpen) return;

        const mobileNav = document.querySelector('.mobile-nav');
        const toggleButton = document.querySelector('.mobile-nav-toggle');
        
        if (mobileNav && toggleButton) {
            // Store current scroll position
            this.scrollPosition = window.pageYOffset;
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.top = `-${this.scrollPosition}px`;
            document.body.style.width = '100%';
            
            // Show mobile navigation
            mobileNav.classList.add('active');
            toggleButton.setAttribute('aria-expanded', 'true');
            
            // Focus management
            const firstLink = mobileNav.querySelector('.mobile-nav-links a');
            if (firstLink) {
                setTimeout(() => firstLink.focus(), 100);
            }
            
            this.isOpen = true;
        }
    }

    closeMenu() {
        if (!this.isMobile || !this.isOpen) return;

        const mobileNav = document.querySelector('.mobile-nav');
        const toggleButton = document.querySelector('.mobile-nav-toggle');
        
        if (mobileNav && toggleButton) {
            // Hide mobile navigation
            mobileNav.classList.remove('active');
            toggleButton.setAttribute('aria-expanded', 'false');
            
            // Restore body scroll
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            
            // Restore scroll position
            window.scrollTo(0, this.scrollPosition);
            
            // Return focus to toggle button
            toggleButton.focus();
            
            this.isOpen = false;
        }
    }

    handleResize() {
        const wasMobile = this.isMobile;
        this.checkMobile();
        
        // If we switched from mobile to desktop, close menu and remove mobile nav
        if (wasMobile && !this.isMobile) {
            this.closeMenu();
            this.removeMobileNavigation();
        }
        // If we switched from desktop to mobile, create mobile nav
        else if (!wasMobile && this.isMobile && this.isInitialized) {
            this.createMobileNavigation();
            this.bindEvents();
        }
        // If we're on mobile and menu is open but window got bigger, close it
        else if (this.isMobile && this.isOpen && window.innerWidth > 768) {
            this.closeMenu();
        }
    }

    removeMobileNavigation() {
        const mobileNav = document.querySelector('.mobile-nav');
        const toggleButton = document.querySelector('.mobile-nav-toggle');
        
        if (mobileNav) mobileNav.remove();
        if (toggleButton) toggleButton.remove();
        
        // Reset state
        this.isOpen = false;
    }

    getCurrentPage() {
        const path = window.location.pathname;
        if (path === '/' || path === '/index.php') return 'home';
        if (path.includes('/shop.php')) return 'shop';
        if (path.includes('/track-order.php')) return 'track';
        if (path.includes('/sell.php')) return 'sell';
        if (path.includes('/about.php')) return 'about';
        if (path.includes('/contact.php')) return 'contact';
        if (path.includes('/faq.php')) return 'faq';
        if (path.includes('/returns.php')) return 'returns';
        return '';
    }

    getCartCount() {
        // Try to get cart count from existing element
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            return cartCountElement.textContent || '0';
        }
        
        // Fallback to 0 if no cart count found
        return '0';
    }

    observeCartChanges() {
        // Watch for changes to cart count
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        this.updateMobileCartCount();
                    }
                });
            });

            observer.observe(cartCountElement, {
                childList: true,
                characterData: true,
                subtree: true
            });
        }
    }

    updateMobileCartCount() {
        const cartLink = document.querySelector('.mobile-cart-link span');
        if (cartLink) {
            const count = this.getCartCount();
            cartLink.textContent = `Cart (${count})`;
        }
    }

    // Public method to update cart count externally
    static updateCartCount(count) {
        const mobileCartLink = document.querySelector('.mobile-cart-link span');
        if (mobileCartLink) {
            mobileCartLink.textContent = `Cart (${count})`;
        }
    }

    // Public method to close menu externally
    static closeMenu() {
        if (window.mobileNav) {
            window.mobileNav.closeMenu();
        }
    }

    getIconForPage(page) {
        const iconMap = {
            'home': 'fas fa-home',
            'shop': 'fas fa-store',
            'track': 'fas fa-shipping-fast',
            'sell': 'fas fa-dollar-sign',
            'about': 'fas fa-info-circle',
            'contact': 'fas fa-envelope',
            'faq': 'fas fa-question-circle',
            'returns': 'fas fa-undo'
        };
        return iconMap[page] || 'fas fa-link';
    }
}

// Initialize mobile navigation when script loads
// Prevent multiple initializations
if (!window.mobileNav) {
    window.mobileNav = new MobileNavigation();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MobileNavigation;
}

// Handle payment method switching on checkout page
document.addEventListener('DOMContentLoaded', function() {
    const creditCardRadio = document.getElementById('credit-card');
    const applePayRadio = document.getElementById('apple-pay');
    const creditCardForm = document.getElementById('credit-card-form');
    const applePayForm = document.getElementById('apple-pay-form');
    
    if (creditCardRadio && applePayRadio && creditCardForm && applePayForm) {
        creditCardRadio.addEventListener('change', function() {
            if (this.checked) {
                creditCardForm.style.display = 'block';
                applePayForm.style.display = 'none';
            }
        });
        
        applePayRadio.addEventListener('change', function() {
            if (this.checked) {
                creditCardForm.style.display = 'none';
                applePayForm.style.display = 'block';
            }
        });
    }
});

// Initialize mobile navigation
let mobileNav;
document.addEventListener('DOMContentLoaded', function() {
    mobileNav = new MobileNavigation();
});

// Re-initialize on window resize
window.addEventListener('resize', function() {
    // Debounce resize events
    clearTimeout(window.mobileNavResizeTimeout);
    window.mobileNavResizeTimeout = setTimeout(function() {
        if (mobileNav && window.innerWidth > 768) {
            mobileNav.closeMenu();
        }
    }, 250);
}); 