<?php
/**
 * Tracking Pixels Setup for Bort's Books
 * Includes Google Analytics, Facebook Pixel, and Google Ads tracking
 */

// Configuration - Replace with your actual tracking IDs
$tracking_config = [
    'google_analytics_id' => 'G-XXXXXXXXXX', // Replace with your GA4 ID
    'facebook_pixel_id' => '1234567890123456', // Replace with your Facebook Pixel ID
    'google_ads_id' => 'AW-XXXXXXXXX', // Replace with your Google Ads ID
    'google_ads_conversion_label' => 'XXXXXXXXX' // Replace with conversion label
];

/**
 * Get the current page type for tracking
 */
function getCurrentPageType() {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    
    $page_types = [
        'index' => 'homepage',
        'shop' => 'shop',
        'product' => 'product',
        'checkout' => 'checkout',
        'sell-your-collection' => 'collection_landing',
        'track-order' => 'order_tracking',
        'about' => 'about',
        'contact' => 'contact'
    ];
    
    return $page_types[$current_page] ?? 'other';
}

/**
 * Generate Google Analytics 4 tracking code
 */
function generateGA4Code($ga_id) {
    $page_type = getCurrentPageType();
    
    return "
    <!-- Google Analytics 4 -->
    <script async src='https://www.googletagmanager.com/gtag/js?id={$ga_id}'></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{$ga_id}', {
            page_title: document.title,
            page_location: window.location.href,
            custom_map: {
                'custom_parameter_1': 'page_type'
            }
        });
        
        // Track page type
        gtag('event', 'page_view', {
            'page_type': '{$page_type}',
            'site_section': 'main'
        });
        
        // Enhanced ecommerce tracking
        gtag('config', '{$ga_id}', {
            'custom_map': {'custom_parameter_1': 'page_type'}
        });
    </script>
    ";
}

/**
 * Generate Facebook Pixel tracking code
 */
function generateFacebookPixelCode($pixel_id) {
    $page_type = getCurrentPageType();
    
    return "
    <!-- Facebook Pixel -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        
        fbq('init', '{$pixel_id}');
        fbq('track', 'PageView');
        
        // Track page type
        fbq('trackCustom', 'PageView', {
            page_type: '{$page_type}',
            content_category: 'manga_books'
        });
    </script>
    <noscript>
        <img height='1' width='1' style='display:none' 
             src='https://www.facebook.com/tr?id={$pixel_id}&ev=PageView&noscript=1'/>
    </noscript>
    ";
}

/**
 * Generate Google Ads conversion tracking
 */
function generateGoogleAdsCode($ads_id) {
    return "
    <!-- Google Ads -->
    <script async src='https://www.googletagmanager.com/gtag/js?id={$ads_id}'></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{$ads_id}');
    </script>
    ";
}

/**
 * Track collection form submission
 */
function trackCollectionSubmission($pixel_id, $ga_id, $ads_id, $conversion_label) {
    return "
    <script>
        // Facebook Pixel - Lead event
        fbq('track', 'Lead', {
            content_category: 'collection_submission',
            content_name: 'sell_collection_form',
            value: 0,
            currency: 'USD'
        });
        
        // Google Analytics - Lead event
        gtag('event', 'generate_lead', {
            'event_category': 'collection',
            'event_label': 'form_submission',
            'value': 1
        });
        
        // Google Ads - Conversion
        gtag('event', 'conversion', {
            'send_to': '{$ads_id}/{$conversion_label}',
            'value': 1.0,
            'currency': 'USD'
        });
        
        console.log('Collection submission tracked across all platforms');
    </script>
    ";
}

/**
 * Track purchase/order completion
 */
function trackPurchase($pixel_id, $ga_id, $order_value, $order_id, $items = []) {
    $items_json = json_encode($items);
    
    return "
    <script>
        // Facebook Pixel - Purchase event
        fbq('track', 'Purchase', {
            value: {$order_value},
            currency: 'USD',
            content_type: 'product',
            content_ids: " . json_encode(array_column($items, 'id')) . ",
            contents: {$items_json}
        });
        
        // Google Analytics - Purchase event
        gtag('event', 'purchase', {
            'transaction_id': '{$order_id}',
            'value': {$order_value},
            'currency': 'USD',
            'items': {$items_json}
        });
        
        console.log('Purchase tracked: Order {$order_id}, Value: \${$order_value}');
    </script>
    ";
}

/**
 * Track add to cart events
 */
function trackAddToCart($pixel_id, $ga_id, $product_id, $product_name, $value) {
    return "
    <script>
        // Facebook Pixel - Add to Cart
        fbq('track', 'AddToCart', {
            content_ids: ['{$product_id}'],
            content_name: '{$product_name}',
            content_type: 'product',
            value: {$value},
            currency: 'USD'
        });
        
        // Google Analytics - Add to Cart
        gtag('event', 'add_to_cart', {
            'currency': 'USD',
            'value': {$value},
            'items': [{
                'item_id': '{$product_id}',
                'item_name': '{$product_name}',
                'category': 'manga',
                'quantity': 1,
                'price': {$value}
            }]
        });
    </script>
    ";
}

/**
 * Track phone number clicks
 */
function trackPhoneClick($pixel_id, $ga_id) {
    return "
    <script>
        function trackPhoneClick() {
            // Facebook Pixel
            fbq('trackCustom', 'PhoneClick', {
                content_category: 'contact',
                content_name: 'phone_number'
            });
            
            // Google Analytics
            gtag('event', 'phone_click', {
                'event_category': 'contact',
                'event_label': 'phone_number',
                'value': 1
            });
        }
        
        // Add click tracking to phone links
        document.addEventListener('DOMContentLoaded', function() {
            const phoneLinks = document.querySelectorAll('a[href^=\"tel:\"]');
            phoneLinks.forEach(function(link) {
                link.addEventListener('click', trackPhoneClick);
            });
        });
    </script>
    ";
}

/**
 * Track email clicks
 */
function trackEmailClick($pixel_id, $ga_id) {
    return "
    <script>
        function trackEmailClick() {
            // Facebook Pixel
            fbq('trackCustom', 'EmailClick', {
                content_category: 'contact',
                content_name: 'email_address'
            });
            
            // Google Analytics
            gtag('event', 'email_click', {
                'event_category': 'contact',
                'event_label': 'email_address',
                'value': 1
            });
        }
        
        // Add click tracking to email links
        document.addEventListener('DOMContentLoaded', function() {
            const emailLinks = document.querySelectorAll('a[href^=\"mailto:\"]');
            emailLinks.forEach(function(link) {
                link.addEventListener('click', trackEmailClick);
            });
        });
    </script>
    ";
}

/**
 * Track scroll depth
 */
function trackScrollDepth($ga_id) {
    return "
    <script>
        // Track scroll depth
        let scrollDepthTracked = [];
        
        function trackScrollDepth() {
            const scrollPercent = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
            
            [25, 50, 75, 90].forEach(function(threshold) {
                if (scrollPercent >= threshold && !scrollDepthTracked.includes(threshold)) {
                    scrollDepthTracked.push(threshold);
                    gtag('event', 'scroll', {
                        'event_category': 'engagement',
                        'event_label': threshold + '%',
                        'value': threshold
                    });
                }
            });
        }
        
        window.addEventListener('scroll', trackScrollDepth);
    </script>
    ";
}

/**
 * Main function to output all tracking codes
 */
function outputTrackingCodes($config = null) {
    global $tracking_config;
    
    if ($config) {
        $tracking_config = array_merge($tracking_config, $config);
    }
    
    echo generateGA4Code($tracking_config['google_analytics_id']);
    echo generateFacebookPixelCode($tracking_config['facebook_pixel_id']);
    echo generateGoogleAdsCode($tracking_config['google_ads_id']);
    echo trackPhoneClick($tracking_config['facebook_pixel_id'], $tracking_config['google_analytics_id']);
    echo trackEmailClick($tracking_config['facebook_pixel_id'], $tracking_config['google_analytics_id']);
    echo trackScrollDepth($tracking_config['google_analytics_id']);
}

/**
 * Output tracking for specific events
 */
function outputEventTracking($event_type, $data = []) {
    global $tracking_config;
    
    switch ($event_type) {
        case 'collection_submission':
            echo trackCollectionSubmission(
                $tracking_config['facebook_pixel_id'],
                $tracking_config['google_analytics_id'],
                $tracking_config['google_ads_id'],
                $tracking_config['google_ads_conversion_label']
            );
            break;
            
        case 'purchase':
            echo trackPurchase(
                $tracking_config['facebook_pixel_id'],
                $tracking_config['google_analytics_id'],
                $data['value'] ?? 0,
                $data['order_id'] ?? '',
                $data['items'] ?? []
            );
            break;
            
        case 'add_to_cart':
            echo trackAddToCart(
                $tracking_config['facebook_pixel_id'],
                $tracking_config['google_analytics_id'],
                $data['product_id'] ?? '',
                $data['product_name'] ?? '',
                $data['value'] ?? 0
            );
            break;
    }
}

/**
 * Generate retargeting audiences setup
 */
function generateRetargetingSetup($pixel_id, $ga_id) {
    return "
    <script>
        // Facebook Custom Audiences
        fbq('trackCustom', 'ViewContent', {
            content_category: 'manga_books',
            content_type: 'website_visit'
        });
        
        // Google Analytics Audiences
        gtag('event', 'page_view', {
            'custom_parameter_1': 'retargeting_audience',
            'user_engagement': 'high'
        });
        
        // Track time on page for audience building
        let timeOnPage = 0;
        setInterval(function() {
            timeOnPage += 10;
            if (timeOnPage === 30) {
                fbq('trackCustom', 'EngagedUser', {time_on_page: 30});
                gtag('event', 'engaged_user', {'time_on_page': 30});
            }
            if (timeOnPage === 60) {
                fbq('trackCustom', 'HighlyEngagedUser', {time_on_page: 60});
                gtag('event', 'highly_engaged_user', {'time_on_page': 60});
            }
        }, 10000);
    </script>
    ";
}
?>