/**
 * Mobile Navigation JavaScript
 * Provides consistent mobile navigation across all pages
 */

class MobileNavigation {
    constructor() {
        this.isOpen = false;
        this.isInitialized = false;
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
        
        this.createMobileNavigation();
        this.bindEvents();
        this.handleResize();
        this.isInitialized = true;
    }

    createMobileNavigation() {
        // Check if mobile nav already exists
        if (document.querySelector('.mobile-nav-container')) {
            return;
        }

        // Get current page for active state
        const currentPage = this.getCurrentPage();
        
        // Get cart count
        const cartCount = this.getCartCount();

        // Create mobile navigation HTML
        const mobileNavHTML = `
            <div class="mobile-nav-container">
                <!-- Mobile Menu Toggle Button -->
                <button class="mobile-menu-toggle" aria-label="Open navigation menu" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Mobile Navigation Overlay -->
                <div class="mobile-nav-overlay"></div>

                <!-- Mobile Navigation Menu -->
                <nav class="mobile-nav-menu" role="navigation" aria-label="Mobile navigation">
                    <div class="mobile-nav-header">
                        <h3>Menu</h3>
                        <button class="mobile-nav-close" aria-label="Close navigation menu">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <ul class="mobile-nav-items">
                        <li><a href="/index.php" class="${currentPage === 'home' ? 'active' : ''}">
                            <i class="fas fa-home"></i> Home
                        </a></li>
                        <li><a href="/pages/shop.php" class="${currentPage === 'shop' ? 'active' : ''}">
                            <i class="fas fa-store"></i> Shop
                        </a></li>
                        <li><a href="/pages/track-order.php" class="${currentPage === 'track' ? 'active' : ''}">
                            <i class="fas fa-shipping-fast"></i> Track Order
                        </a></li>
                        <li><a href="/pages/sell.php" class="${currentPage === 'sell' ? 'active' : ''}">
                            <i class="fas fa-dollar-sign"></i> Sell Manga
                        </a></li>
                        <li><a href="/pages/about.php" class="${currentPage === 'about' ? 'active' : ''}">
                            <i class="fas fa-info-circle"></i> About
                        </a></li>
                        <li><a href="/pages/contact.php" class="${currentPage === 'contact' ? 'active' : ''}">
                            <i class="fas fa-envelope"></i> Contact
                        </a></li>
                        <li><a href="/pages/faq.php" class="${currentPage === 'faq' ? 'active' : ''}">
                            <i class="fas fa-question-circle"></i> FAQ
                        </a></li>
                        <li><a href="/pages/returns.php" class="${currentPage === 'returns' ? 'active' : ''}">
                            <i class="fas fa-undo"></i> Returns
                        </a></li>
                    </ul>

                    <div class="mobile-nav-footer">
                        <a href="/pages/cart.php" class="mobile-cart-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Shopping Cart</span>
                            <span class="mobile-cart-count">${cartCount}</span>
                        </a>
                    </div>
                </nav>
            </div>
        `;

        // Insert mobile navigation into header
        const header = document.querySelector('header .header-container');
        if (header) {
            header.insertAdjacentHTML('beforeend', mobileNavHTML);
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

        // Handle window resize
        window.addEventListener('resize', () => this.handleResize());

        // Update cart count when it changes
        this.observeCartChanges();
    }

    handleClick(e) {
        // Mobile menu toggle button
        if (e.target.closest('.mobile-menu-toggle')) {
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

        // Overlay click to close
        if (e.target.classList.contains('mobile-nav-overlay')) {
            e.preventDefault();
            e.stopPropagation();
            this.closeMenu();
            return;
        }

        // Close menu when clicking on navigation links
        if (e.target.closest('.mobile-nav-items a')) {
            this.closeMenu();
            return;
        }
    }

    handleKeydown(e) {
        if (e.key === 'Escape' && this.isOpen) {
            e.preventDefault();
            this.closeMenu();
        }
    }

    toggleMenu() {
        if (this.isOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }

    openMenu() {
        const overlay = document.querySelector('.mobile-nav-overlay');
        const menu = document.querySelector('.mobile-nav-menu');
        const toggle = document.querySelector('.mobile-menu-toggle');

        if (overlay && menu && toggle) {
            // Prevent body scroll
            document.body.classList.add('mobile-nav-open');
            
            // Show overlay and menu
            overlay.classList.add('active');
            menu.classList.add('active');
            
            // Update toggle button
            const icon = toggle.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            }
            toggle.setAttribute('aria-expanded', 'true');
            
            // Focus management
            const firstLink = menu.querySelector('.mobile-nav-items a');
            if (firstLink) {
                setTimeout(() => firstLink.focus(), 100);
            }

            this.isOpen = true;
        }
    }

    closeMenu() {
        const overlay = document.querySelector('.mobile-nav-overlay');
        const menu = document.querySelector('.mobile-nav-menu');
        const toggle = document.querySelector('.mobile-menu-toggle');

        if (overlay && menu && toggle) {
            // Allow body scroll
            document.body.classList.remove('mobile-nav-open');
            
            // Hide overlay and menu
            overlay.classList.remove('active');
            menu.classList.remove('active');
            
            // Update toggle button
            const icon = toggle.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
            toggle.setAttribute('aria-expanded', 'false');

            this.isOpen = false;
        }
    }

    handleResize() {
        // Close mobile menu if window is resized to desktop size
        if (window.innerWidth > 768 && this.isOpen) {
            this.closeMenu();
        }
    }

    getCurrentPage() {
        const path = window.location.pathname;
        
        if (path === '/' || path === '/index.php' || path.endsWith('/')) return 'home';
        if (path.includes('/shop')) return 'shop';
        if (path.includes('/track-order')) return 'track';
        if (path.includes('/sell')) return 'sell';
        if (path.includes('/about')) return 'about';
        if (path.includes('/contact')) return 'contact';
        if (path.includes('/faq')) return 'faq';
        if (path.includes('/returns')) return 'returns';
        if (path.includes('/cart')) return 'cart';
        
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
        const cartCount = this.getCartCount();
        const mobileCartCount = document.querySelector('.mobile-cart-count');
        if (mobileCartCount) {
            mobileCartCount.textContent = cartCount;
        }
    }

    // Public method to update cart count externally
    static updateCartCount(count) {
        const mobileCartCount = document.querySelector('.mobile-cart-count');
        if (mobileCartCount) {
            mobileCartCount.textContent = count;
        }
    }

    // Public method to close menu externally
    static closeMenu() {
        if (window.mobileNav) {
            window.mobileNav.closeMenu();
        }
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