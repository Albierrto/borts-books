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
                        <h3>Navigation</h3>
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
                            <span>Cart (${cartCount})</span>
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
        if (!this.isMobile) return;

        const overlay = document.querySelector('.mobile-nav-overlay');
        const menu = document.querySelector('.mobile-nav-menu');
        const toggle = document.querySelector('.mobile-menu-toggle');

        if (overlay && menu && toggle) {
            // Store current scroll position before preventing scroll
            this.scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            
            // Prevent body scroll with improved method
            document.body.style.position = 'fixed';
            document.body.style.top = `-${this.scrollPosition}px`;
            document.body.style.width = '100%';
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
            // Restore body scroll with improved method
            document.body.classList.remove('mobile-nav-open');
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            
            // Restore scroll position
            window.scrollTo(0, this.scrollPosition);
            
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
        const mobileNavContainer = document.querySelector('.mobile-nav-container');
        if (mobileNavContainer) {
            mobileNavContainer.remove();
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
        const mobileCartLink = document.querySelector('.mobile-cart-link span');
        if (mobileCartLink) {
            mobileCartLink.textContent = `Cart (${cartCount})`;
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

// Simple Mobile Navigation System
document.addEventListener('DOMContentLoaded', function() {
    initMobileNavigation();
});

function initMobileNavigation() {
    // Always create mobile navigation, but only show on mobile
    if (!document.querySelector('.mobile-nav')) {
        createMobileNavigation();
    }
    
    // Bind events
    bindMobileNavEvents();
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // Close mobile nav if screen gets bigger
            closeMobileNav();
        }
    });
}

function createMobileNavigation() {
    // Get navigation links from desktop nav
    const desktopNav = document.querySelector('nav ul');
    if (!desktopNav) {
        console.log('No desktop navigation found');
        return;
    }
    
    const links = Array.from(desktopNav.querySelectorAll('a')).map(link => ({
        href: link.href,
        text: link.textContent.trim(),
        icon: getIconForPage(link.textContent.trim())
    }));
    
    console.log('Found navigation links:', links);
    
    // Create mobile nav HTML
    const mobileNavHTML = `
        <div class="mobile-nav" id="mobileNav">
            <div class="mobile-nav-content">
                <div class="mobile-nav-header">
                    <a href="/index.php" class="logo">Bort's <span>Books</span></a>
                    <button class="mobile-nav-close" id="mobileNavClose">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mobile-nav-links">
                    ${links.map(link => `
                        <a href="${link.href}">
                            <i class="${link.icon}"></i>
                            ${link.text}
                        </a>
                    `).join('')}
                    <a href="/cart.php" class="mobile-cart-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Cart (${getCartCount()})</span>
                    </a>
                </div>
            </div>
        </div>
    `;
    
    // Add mobile nav to body
    document.body.insertAdjacentHTML('beforeend', mobileNavHTML);
    
    // Add mobile nav toggle button to header
    const headerContainer = document.querySelector('.header-container');
    if (headerContainer && !document.querySelector('.mobile-nav-toggle')) {
        const toggleButton = document.createElement('button');
        toggleButton.className = 'mobile-nav-toggle';
        toggleButton.id = 'mobileNavToggle';
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        toggleButton.setAttribute('aria-label', 'Open mobile menu');
        
        // Insert the toggle button before the search-cart div
        const searchCart = document.querySelector('.search-cart');
        if (searchCart) {
            headerContainer.insertBefore(toggleButton, searchCart);
        } else {
            headerContainer.appendChild(toggleButton);
        }
        
        console.log('Mobile nav toggle button added');
    }
}

function getCartCount() {
    const cartCountElement = document.querySelector('.cart-count');
    return cartCountElement ? cartCountElement.textContent : '0';
}

function bindMobileNavEvents() {
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    const mobileNavClose = document.getElementById('mobileNavClose');
    const mobileNav = document.getElementById('mobileNav');
    
    if (!mobileNavToggle || !mobileNav) {
        console.log('Mobile nav elements not found');
        return;
    }
    
    // Remove existing event listeners to prevent duplicates
    mobileNavToggle.removeEventListener('click', openMobileNav);
    
    // Toggle mobile nav
    mobileNavToggle.addEventListener('click', openMobileNav);
    
    // Close mobile nav
    if (mobileNavClose) {
        mobileNavClose.removeEventListener('click', closeMobileNav);
        mobileNavClose.addEventListener('click', closeMobileNav);
    }
    
    // Close on overlay click
    mobileNav.removeEventListener('click', handleOverlayClick);
    mobileNav.addEventListener('click', handleOverlayClick);
    
    // Close on escape key
    document.removeEventListener('keydown', handleEscapeKey);
    document.addEventListener('keydown', handleEscapeKey);
    
    // Close mobile nav when clicking on a link
    const mobileNavLinks = mobileNav.querySelectorAll('.mobile-nav-links a');
    mobileNavLinks.forEach(link => {
        link.removeEventListener('click', closeMobileNav);
        link.addEventListener('click', closeMobileNav);
    });
    
    console.log('Mobile nav events bound');
}

function openMobileNav() {
    const mobileNav = document.getElementById('mobileNav');
    if (mobileNav) {
        mobileNav.classList.add('active');
        document.body.style.overflow = 'hidden';
        console.log('Mobile nav opened');
    }
}

function closeMobileNav() {
    const mobileNav = document.getElementById('mobileNav');
    if (mobileNav) {
        mobileNav.classList.remove('active');
        document.body.style.overflow = '';
        console.log('Mobile nav closed');
    }
}

function handleOverlayClick(e) {
    if (e.target === e.currentTarget) {
        closeMobileNav();
    }
}

function handleEscapeKey(e) {
    if (e.key === 'Escape') {
        const mobileNav = document.getElementById('mobileNav');
        if (mobileNav && mobileNav.classList.contains('active')) {
            closeMobileNav();
        }
    }
}

function getIconForPage(pageText) {
    const iconMap = {
        'Home': 'fas fa-home',
        'Shop': 'fas fa-store',
        'Track Order': 'fas fa-shipping-fast',
        'Sell Manga': 'fas fa-dollar-sign',
        'Sell': 'fas fa-dollar-sign',
        'About': 'fas fa-info-circle',
        'Contact': 'fas fa-envelope',
        'FAQ': 'fas fa-question-circle',
        'Returns': 'fas fa-undo',
        'Collections': 'fas fa-layer-group'
    };
    
    return iconMap[pageText] || 'fas fa-link';
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

// Re-initialize on window resize
window.addEventListener('resize', function() {
    // Debounce resize events
    clearTimeout(window.mobileNavResizeTimeout);
    window.mobileNavResizeTimeout = setTimeout(function() {
        if (window.innerWidth > 768) {
            closeMobileNav();
        }
    }, 250);
}); 