<?php
/**
 * USPS Shipping Calculator
 * Dynamic shipping calculation using modern USPS API
 */

class USPSShipping {
    
    private $originZip;
    private $consumerKey;
    private $consumerSecret;
    private $accessToken;
    private $apiBaseUrl = 'https://api.usps.com'; // Production URL
    
    public function __construct($originZip = null, $consumerKey = null, $consumerSecret = null) {
        // Load from environment variables if not provided
        $this->originZip = $originZip ?? $_ENV['USPS_ORIGIN_ZIP'] ?? '90210';
        $this->consumerKey = $consumerKey ?? $_ENV['USPS_CONSUMER_KEY'] ?? null;
        $this->consumerSecret = $consumerSecret ?? $_ENV['USPS_CONSUMER_SECRET'] ?? null;
        
        // Get access token if credentials are available
        if ($this->consumerKey && $this->consumerSecret) {
            $this->getAccessToken();
        }
    }
    
    /**
     * Get OAuth2 access token from USPS
     */
    private function getAccessToken() {
        $url = $this->apiBaseUrl . '/oauth2/v3/token';
        
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->consumerKey,
            'client_secret' => $this->consumerSecret
        ];
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json'
                ],
                'content' => http_build_query($data)
            ]
        ];
        
        try {
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            
            if ($response) {
                $result = json_decode($response, true);
                if (isset($result['access_token'])) {
                    $this->accessToken = $result['access_token'];
                    return true;
                }
            }
        } catch (Exception $e) {
            error_log('USPS OAuth Error: ' . $e->getMessage());
        }
        
        return false;
    }
    
        /**     * Calculate shipping rates for a product     */    public function calculateShipping($product, $destinationZip, $service = 'Priority') {        // Add debugging        error_log("USPS Shipping Debug - Product: " . json_encode($product));        error_log("USPS Shipping Debug - Service: $service, Zip: $destinationZip");                // If it's free shipping, return 0        if (($product['shipping_option'] ?? 'calculated') === 'free') {            return ['rate' => 0, 'service' => 'Free Shipping', 'days' => '3-5'];        }                // If it's flat rate, return the flat rate        if (($product['shipping_option'] ?? 'calculated') === 'flat') {            return [                'rate' => $product['flat_rate'] ?? 5.00,                'service' => 'Flat Rate',                'days' => '3-5'            ];        }                // For calculated shipping, use weight/dimensions        $weight = $product['weight'] ?? 6.0; // Default manga weight        $dimensions = $this->parseDimensions($product['dimensions'] ?? '7.5x5.0x0.8');                error_log("USPS Shipping Debug - Weight: $weight, Dimensions: " . json_encode($dimensions));                // If we have USPS API credentials, use the real API        if ($this->accessToken) {            $result = $this->calculateUSPSAPI($weight, $dimensions, $destinationZip, $service);        } else {            // Otherwise, use our estimation algorithm            $result = $this->estimateShipping($weight, $dimensions, $destinationZip, $service);        }                error_log("USPS Shipping Debug - Result: " . json_encode($result));        return $result;    }
    
        /**     * Parse dimensions string (e.g., "7.5x5.0x0.8" or "7.5 x 5.0 x 0.8") into array     */    private function parseDimensions($dimensionString) {        if (empty($dimensionString)) {            return ['length' => 7.5, 'width' => 5.0, 'height' => 0.8]; // Default manga        }                // Handle different separators: x, ×, *, spaces        $parts = preg_split('/[x×*\s]+/i', trim($dimensionString));        $parts = array_filter($parts); // Remove empty elements                if (count($parts) >= 3) {            return [                'length' => (float)$parts[0],                'width' => (float)$parts[1],                'height' => (float)$parts[2]            ];        }                error_log("Warning: Could not parse dimensions '$dimensionString', using defaults");        return ['length' => 7.5, 'width' => 5.0, 'height' => 0.8];    }
    
    /**
     * Calculate shipping using real USPS API
     */
    private function calculateUSPSAPI($weight, $dimensions, $destinationZip, $service = 'Priority') {
        if (!$this->accessToken) {
            return $this->estimateShipping($weight, $dimensions, $destinationZip, $service);
        }
        
        $url = $this->apiBaseUrl . '/prices/v3/base-rates/search';
        
                // Convert service names to USPS API format        $serviceMapping = [            'Media' => 'MEDIA_MAIL',            'Ground' => 'USPS_GROUND_ADVANTAGE',            'Priority' => 'PRIORITY_MAIL',            'Express' => 'PRIORITY_MAIL_EXPRESS'        ];
        
        $uspsService = $serviceMapping[$service] ?? 'PRIORITY_MAIL';
        
        // Convert weight to pounds and ounces
        $pounds = floor($weight / 16);
        $ounces = $weight % 16;
        
        $requestData = [
            'originZIPCode' => $this->originZip,
            'destinationZIPCode' => $destinationZip,
            'weight' => [
                'pounds' => $pounds,
                'ounces' => $ounces
            ],
            'length' => $dimensions['length'],
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'mailClass' => $uspsService,
            'processingCategory' => 'MACHINABLE',
            'destinationType' => 'RESIDENTIAL',
            'rateIndicator' => 'SP', // Single Piece
            'mailingDate' => date('Y-m-d')
        ];
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Authorization: Bearer ' . $this->accessToken,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                'content' => json_encode($requestData)
            ]
        ];
        
        try {
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            
            if ($response) {
                $result = json_decode($response, true);
                
                if (isset($result['baseRate'])) {
                    $deliveryDays = $this->getDeliveryDays($service);
                    
                    return [
                        'rate' => (float)$result['baseRate'],
                        'service' => $result['mailClass'] ?? "USPS $service Mail",
                        'days' => $deliveryDays,
                        'usps_api' => true
                    ];
                }
            }
        } catch (Exception $e) {
            error_log('USPS API Rate Request Error: ' . $e->getMessage());
        }
        
        // Fallback to estimation if API fails
        return $this->estimateShipping($weight, $dimensions, $destinationZip, $service);
    }
    
    /**     * Get estimated delivery days for service     */    private function getDeliveryDays($service) {        $deliveryTimes = [            'Media' => '2-8 business days',            'Ground' => '2-5 business days',            'Priority' => '1-3 business days',            'Express' => '1-2 business days'        ];                return $deliveryTimes[$service] ?? '1-3 business days';    }
    
    /**
     * Estimate shipping using realistic USPS rates (fallback)
     */
    private function estimateShipping($weight, $dimensions, $destinationZip, $service = 'Priority') {
        $distance = $this->estimateDistance($this->originZip, $destinationZip);
        
                // Base rates for different services        $baseRates = [            'Media' => 3.00,            'Ground' => 4.50,            'Priority' => 7.50,            'Express' => 25.00        ];
        
        $baseRate = $baseRates[$service] ?? $baseRates['Priority'];
        
        // Weight multiplier (USPS charges by weight zones)
        $weightCost = max(0, ($weight - 4) * 0.75); // First 4oz usually included
        
        // Distance multiplier (zones 1-8)
        $zone = min(8, max(1, ceil($distance / 600))); // Rough zone calculation
        $zoneCost = ($zone - 1) * 1.25;
        
        // Size adjustment for large items
        $volume = $dimensions['length'] * $dimensions['width'] * $dimensions['height'];
        $sizeCost = $volume > 50 ? ($volume - 50) * 0.05 : 0;
        
                $totalCost = $baseRate + $weightCost + $zoneCost + $sizeCost;                // Ensure minimum shipping cost        $totalCost = max($totalCost, $baseRate);                // Service delivery times        $deliveryTimes = [            'Media' => '2-8 business days',            'Ground' => '3-5 business days',            'Priority' => '1-3 business days',            'Express' => 'Next business day'        ];                $result = [            'rate' => round($totalCost, 2),            'service' => "USPS $service Mail",            'days' => $deliveryTimes[$service] ?? '1-3 business days',            'zone' => $zone,            'estimated' => true        ];                error_log("Estimate shipping calculation: base=$baseRate, weight=$weightCost, zone=$zoneCost, size=$sizeCost, total=$totalCost");        return $result;
    }
    
    /**
     * Estimate distance between zip codes (rough calculation)
     */
    private function estimateDistance($zip1, $zip2) {
        // Very rough estimation based on first digits of ZIP codes
        $region1 = substr($zip1, 0, 1);
        $region2 = substr($zip2, 0, 1);
        
        // Approximate distances between ZIP regions
        $regionDistances = [
            '0' => ['lat' => 42.0, 'lng' => -71.0], // Northeast
            '1' => ['lat' => 42.0, 'lng' => -71.0], // Northeast
            '2' => ['lat' => 39.0, 'lng' => -77.0], // Mid-Atlantic
            '3' => ['lat' => 33.0, 'lng' => -84.0], // Southeast
            '4' => ['lat' => 36.0, 'lng' => -86.0], // South Central
            '5' => ['lat' => 41.0, 'lng' => -93.0], // Midwest
            '6' => ['lat' => 32.0, 'lng' => -97.0], // South
            '7' => ['lat' => 39.0, 'lng' => -105.0], // Mountain
            '8' => ['lat' => 47.0, 'lng' => -120.0], // Northwest
            '9' => ['lat' => 37.0, 'lng' => -119.0], // West
        ];
        
        $coord1 = $regionDistances[$region1] ?? $regionDistances['9'];
        $coord2 = $regionDistances[$region2] ?? $regionDistances['9'];
        
        // Haversine formula (simplified)
        $latDiff = deg2rad($coord2['lat'] - $coord1['lat']);
        $lngDiff = deg2rad($coord2['lng'] - $coord1['lng']);
        
        $a = sin($latDiff/2) * sin($latDiff/2) + 
             cos(deg2rad($coord1['lat'])) * cos(deg2rad($coord2['lat'])) * 
             sin($lngDiff/2) * sin($lngDiff/2);
        
        $c = 2 * asin(sqrt($a));
        $distance = 3959 * $c; // Earth radius in miles
        
        return max(50, $distance); // Minimum 50 miles
    }
    
    /**
     * Get available shipping services for checkout
     */
    public function getShippingOptions($product, $destinationZip) {
        $options = [];
        
        // Check product's shipping option
        $shippingOption = $product['shipping_option'] ?? 'calculated';
        
        if ($shippingOption === 'free') {
            return [[
                'rate' => 0,
                'service' => 'Free Standard Shipping',
                'days' => '3-5 business days',
                'id' => 'free'
            ]];
        }
        
        if ($shippingOption === 'flat') {
            return [[
                'rate' => $product['flat_rate'] ?? 5.00,
                'service' => 'Flat Rate Shipping',
                'days' => '3-5 business days',
                'id' => 'flat'
            ]];
        }
        
                // For calculated shipping, offer multiple services        $services = ['Media', 'Ground', 'Priority'];
        
        foreach ($services as $service) {
            $result = $this->calculateShipping($product, $destinationZip, $service);
            $options[] = [
                'rate' => $result['rate'],
                'service' => $result['service'],
                'days' => $result['days'],
                'id' => strtolower($service),
                'api_source' => isset($result['usps_api']) ? 'USPS API' : 'Estimated'
            ];
        }
        
        return $options;
    }
    
    /**
     * Test API connection
     */
    public function testConnection() {
        if (!$this->consumerKey || !$this->consumerSecret) {
            return [
                'success' => false,
                'message' => 'USPS API credentials not configured'
            ];
        }
        
        if ($this->getAccessToken()) {
            return [
                'success' => true,
                'message' => 'USPS API connection successful',
                'access_token' => substr($this->accessToken, 0, 10) . '...'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to authenticate with USPS API'
        ];
    }
}

/**
 * Calculate shipping for cart
 */
function calculateCartShipping($cartItems, $destinationZip, $selectedServices = []) {
    $usps = new USPSShipping();
    $totalShipping = 0;
    
    foreach ($cartItems as $item) {
        $serviceType = $selectedServices[$item['id']] ?? 'Priority';
        $shipping = $usps->calculateShipping($item, $destinationZip, $serviceType);
        $totalShipping += $shipping['rate'];
    }
    
    return $totalShipping;
}

/**
 * Get shipping calculator for AJAX
 */
function getShippingQuote() {
    if (!isset($_POST['zip']) || !isset($_POST['product_id'])) {
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }
    
    global $db;
    
    $zip = $_POST['zip'];
    $productId = (int)$_POST['product_id'];
    
    try {
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo json_encode(['error' => 'Product not found']);
            return;
        }
        
        $usps = new USPSShipping();
        $options = $usps->getShippingOptions($product, $zip);
        
        echo json_encode(['success' => true, 'options' => $options]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error calculating shipping: ' . $e->getMessage()]);
    }
}

// Handle AJAX requests
if (isset($_POST['action']) && $_POST['action'] === 'get_shipping_quote') {
    getShippingQuote();
    exit;
}
?> 