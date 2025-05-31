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
    private $lastError = null; // Store last error for user display
    
    public function __construct($originZip = null, $consumerKey = null, $consumerSecret = null) {
        // Load environment variables if .env file exists
        $this->loadEnvironmentVariables();
        
        // Use provided values or fall back to environment variables
        $this->originZip = $originZip ?? $_ENV['USPS_ORIGIN_ZIP'] ?? '90210';
        $this->consumerKey = $consumerKey ?? $_ENV['USPS_CONSUMER_KEY'] ?? null;
        $this->consumerSecret = $consumerSecret ?? $_ENV['USPS_CONSUMER_SECRET'] ?? null;
        
        // Debug logging
        error_log("USPS Constructor - Origin ZIP: " . $this->originZip);
        error_log("USPS Constructor - Has Consumer Key: " . ($this->consumerKey ? 'Yes (' . substr($this->consumerKey, 0, 10) . '...)' : 'No'));
        error_log("USPS Constructor - Has Consumer Secret: " . ($this->consumerSecret ? 'Yes' : 'No'));
        
        // Get access token if credentials are available
        if ($this->consumerKey && $this->consumerSecret) {
            $this->getAccessToken();
        } else {
            $this->lastError = "USPS API credentials not configured. Using estimated rates.";
        }
    }
    
    /**
     * Load environment variables from .env file
     */
    private function loadEnvironmentVariables() {
        $envPath = __DIR__ . '/../.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue; // Skip comments
                if (strpos($line, '=') === false) continue; // Skip invalid lines
                list($name, $value) = array_map('trim', explode('=', $line, 2));
                $_ENV[$name] = $value;
            }
            error_log("USPS - Environment variables loaded from .env file");
        } else {
            error_log("USPS - No .env file found at: $envPath");
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
                'content' => http_build_query($data),
                'timeout' => 30
            ]
        ];
        
        error_log("USPS OAuth - Attempting to get access token from: $url");
        error_log("USPS OAuth - Using client_id: " . substr($this->consumerKey, 0, 10) . '...');
        
        try {
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            
            if ($response) {
                $result = json_decode($response, true);
                error_log("USPS OAuth - Response: " . $response);
                
                if (isset($result['access_token'])) {
                    $this->accessToken = $result['access_token'];
                    error_log("USPS OAuth - Access token acquired successfully");
                    return true;
                } else {
                    // Handle specific USPS API errors
                    if (isset($result['error'])) {
                        switch ($result['error']) {
                            case 'invalid_client':
                                $this->lastError = "Invalid USPS API credentials. Please check your API keys.";
                                break;
                            case 'unauthorized':
                                $this->lastError = "USPS API access denied. Please verify your account status.";
                                break;
                            default:
                                $this->lastError = "USPS API error: " . ($result['error_description'] ?? $result['error']);
                        }
                    } else {
                        $this->lastError = "USPS API returned unexpected response. Using estimated rates.";
                    }
                }
            } else {
                $this->lastError = "Unable to connect to USPS API. Please check your internet connection.";
            }
        } catch (Exception $e) {
            $this->lastError = "USPS API connection failed: " . $e->getMessage();
        }
        
        return false;
    }
    
    /**
     * Get last error message for user display
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Calculate shipping rates for a product
     */
    public function calculateShipping($product, $destinationZip, $service = 'Ground') {
        // Clear previous errors
        $this->lastError = null;
        
        // Add debugging
        error_log("USPS Shipping Debug - Product: " . json_encode($product));
        error_log("USPS Shipping Debug - Service: $service, Zip: $destinationZip");
        
        // If it's free shipping, return 0
        if (($product['shipping_option'] ?? 'calculated') === 'free') {
            return ['rate' => 0, 'service' => 'Free Shipping', 'days' => '3-5'];
        }
        
        // If it's flat rate, return the flat rate
        if (($product['shipping_option'] ?? 'calculated') === 'flat') {
            return [
                'rate' => $product['flat_rate'] ?? 5.00,
                'service' => 'Flat Rate',
                'days' => '3-5'
            ];
        }
        
        // For calculated shipping, use weight/dimensions
        $weight = $product['weight'] ?? 6.0; // Default manga weight
        $dimensions = $this->parseDimensions($product['dimensions'] ?? '7.5x5.0x0.8');
        
        error_log("USPS Shipping Debug - Weight: $weight, Dimensions: " . json_encode($dimensions));
        
        // If we have USPS API credentials, use the real API
        if ($this->accessToken) {
            $result = $this->calculateUSPSAPI($weight, $dimensions, $destinationZip, $service);
        } else {
            // Otherwise, use our estimation algorithm
            $result = $this->estimateShipping($weight, $dimensions, $destinationZip, $service);
            $result['warning'] = $this->lastError ?? "Using estimated rates. For exact rates, USPS API integration is required.";
        }
        
        error_log("USPS Shipping Debug - Result: " . json_encode($result));
        return $result;
    }
    
    /**
     * Parse dimensions string (e.g., "7.5x5.0x0.8" or "7.5 x 5.0 x 0.8") into array
     */
    private function parseDimensions($dimensionString) {
        if (empty($dimensionString)) {
            return ['length' => 7.5, 'width' => 5.0, 'height' => 0.8]; // Default manga
        }
        
        // Handle different separators: x, ×, *, spaces
        $parts = preg_split('/[x×*\s]+/i', trim($dimensionString));
        $parts = array_filter($parts); // Remove empty elements
        
        if (count($parts) >= 3) {
            return [
                'length' => (float)$parts[0],
                'width' => (float)$parts[1],
                'height' => (float)$parts[2]
            ];
        }
        
        error_log("Warning: Could not parse dimensions '$dimensionString', using defaults");
        return ['length' => 7.5, 'width' => 5.0, 'height' => 0.8];
    }
    
    /**
     * Calculate shipping using real USPS API
     */
    private function calculateUSPSAPI($weight, $dimensions, $destinationZip, $service = 'Ground') {
        if (!$this->accessToken) {
            return $this->estimateShipping($weight, $dimensions, $destinationZip, $service);
        }
        
        $url = $this->apiBaseUrl . '/prices/v3/base-rates/search';
        
        // Convert service names to USPS API format
        $serviceMapping = [
            'Media' => 'MEDIA_MAIL',
            'Ground' => 'USPS_GROUND_ADVANTAGE'
        ];
        
        $uspsService = $serviceMapping[$service] ?? 'USPS_GROUND_ADVANTAGE';
        
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
    
    /**
     * Get estimated delivery days for service
     */
    private function getDeliveryDays($service) {
        $deliveryTimes = [
            'Media' => '2-8 business days',
            'Ground' => '2-5 business days'
        ];
        
        return $deliveryTimes[$service] ?? '2-5 business days';
    }
    
    /**
     * Estimate shipping using realistic USPS rates (fallback)
     */
    private function estimateShipping($weight, $dimensions, $destinationZip, $service = 'Ground') {
        $distance = $this->estimateDistance($this->originZip, $destinationZip);
        $zone = $this->calculateUSPSZone($this->originZip, $destinationZip);
        
        // Convert weight to pounds if needed (assume ounces if under 50)
        $weightInPounds = $weight > 50 ? $weight / 16 : $weight;
        
        // 2024 USPS Rate Tables - Updated to match current pricing
        $rates = $this->getUSPSRates2024($service, $zone, $weightInPounds, $dimensions);
        
        $result = [
            'rate' => round($rates['cost'], 2),
            'service' => $rates['service_name'],
            'days' => $rates['delivery_days'],
            'zone' => $zone,
            'estimated' => true,
            'debug' => [
                'weight_lbs' => $weightInPounds,
                'zone' => $zone,
                'distance_miles' => round($distance),
                'base_rate' => $rates['base_rate'],
                'weight_surcharge' => $rates['weight_surcharge'],
                'zone_surcharge' => $rates['zone_surcharge'],
                'size_surcharge' => $rates['size_surcharge']
            ]
        ];
        
        return $result;
    }
    
    /**
     * Calculate USPS Zone based on ZIP codes (more accurate than distance)
     */
    private function calculateUSPSZone($originZip, $destZip) {
        // Get first 3 digits of ZIP codes for zone calculation
        $originPrefix = substr($originZip, 0, 3);
        $destPrefix = substr($destZip, 0, 3);
        
        // Same ZIP prefix = Local (Zone 1-2)
        if ($originPrefix === $destPrefix) {
            return 1;
        }
        
        // Calculate distance-based zones using more accurate ZIP prefix mapping
        $distance = $this->estimateDistance($originZip, $destZip);
        
        if ($distance <= 150) return 1;
        if ($distance <= 300) return 2;
        if ($distance <= 600) return 3;
        if ($distance <= 1000) return 4;
        if ($distance <= 1400) return 5;
        if ($distance <= 1800) return 6;
        if ($distance <= 2200) return 7;
        return 8; // 2200+ miles
    }
    
    /**
     * Get 2024 USPS Rate Tables - Accurate current pricing
     */
    private function getUSPSRates2024($service, $zone, $weightInPounds, $dimensions) {
        $volume = $dimensions['length'] * $dimensions['width'] * $dimensions['height'];
        
        switch ($service) {
            case 'Media':
                return $this->getMediaMailRates2024($weightInPounds, $dimensions);
            
            case 'Ground':
                return $this->getGroundAdvantageRates2024($zone, $weightInPounds, $dimensions);
            
            case 'Priority':
                return $this->getPriorityMailRates2024($zone, $weightInPounds, $dimensions);
            
            default:
                return $this->getGroundAdvantageRates2024($zone, $weightInPounds, $dimensions);
        }
    }
    
    /**
     * 2024 Media Mail Rates (Books/Educational Materials)
     */
    private function getMediaMailRates2024($weightInPounds, $dimensions) {
        // 2024 Media Mail Rate Table
        $rateTable = [
            1 => 4.63,   // Up to 1 lb
            2 => 5.36,   // Up to 2 lbs
            3 => 6.09,   // Up to 3 lbs
            4 => 6.82,   // Up to 4 lbs
            5 => 7.55,   // Up to 5 lbs
            6 => 8.28,   // Up to 6 lbs
            7 => 9.01,   // Up to 7 lbs
            8 => 9.74,   // Up to 8 lbs
            9 => 10.47,  // Up to 9 lbs
            10 => 11.20  // Up to 10 lbs
        ];
        
        $weight = max(1, ceil($weightInPounds));
        $baseRate = $rateTable[$weight] ?? (11.20 + (($weight - 10) * 0.73));
        
        // Size surcharges for Media Mail
        $sizeSurcharge = 0;
        $volume = $dimensions['length'] * $dimensions['width'] * $dimensions['height'];
        
        // Large package surcharge
        if ($volume > 1728) { // Over 1 cubic foot
            $sizeSurcharge = 15.00;
        }
        
        return [
            'cost' => $baseRate + $sizeSurcharge,
            'service_name' => 'USPS Media Mail',
            'delivery_days' => '2-8 business days',
            'base_rate' => $baseRate,
            'weight_surcharge' => 0,
            'zone_surcharge' => 0,
            'size_surcharge' => $sizeSurcharge
        ];
    }
    
    /**
     * 2024 Ground Advantage Rates (Replaces Retail Ground)
     */
    private function getGroundAdvantageRates2024($zone, $weightInPounds, $dimensions) {
        $weight = max(1, ceil($weightInPounds));
        
        // 2024 Ground Advantage Rate Table by Zone and Weight
        $rateTable = [
            // Weight => [Zone 1-2, Zone 3, Zone 4, Zone 5, Zone 6, Zone 7, Zone 8]
            1 => [5.50, 5.50, 5.50, 5.50, 5.50, 5.50, 5.50],
            2 => [5.50, 5.50, 5.50, 5.50, 5.50, 5.50, 5.50],
            3 => [5.50, 5.50, 5.50, 5.50, 5.50, 5.50, 5.50],
            4 => [5.50, 5.50, 5.50, 5.50, 5.50, 5.50, 5.50],
            5 => [6.25, 6.75, 7.25, 7.75, 8.25, 8.75, 9.25],
            6 => [6.85, 7.45, 8.05, 8.65, 9.25, 9.85, 10.45],
            7 => [7.45, 8.15, 8.85, 9.55, 10.25, 10.95, 11.65],
            8 => [8.05, 8.85, 9.65, 10.45, 11.25, 12.05, 12.85],
            9 => [8.65, 9.55, 10.45, 11.35, 12.25, 13.15, 14.05],
            10 => [9.25, 10.25, 11.25, 12.25, 13.25, 14.25, 15.25]
        ];
        
        $zoneIndex = min(7, max(0, $zone - 1)); // Convert zone 1-8 to index 0-7
        $baseRate = $rateTable[$weight][$zoneIndex] ?? $this->calculateOverweightRate($weight, $zone);
        
        // Size surcharges
        $sizeSurcharge = 0;
        $volume = $dimensions['length'] * $dimensions['width'] * $dimensions['height'];
        $maxDimension = max($dimensions['length'], $dimensions['width'], $dimensions['height']);
        
        // Dimensional weight calculation
        $dimWeight = $volume / 166; // USPS dimensional weight divisor
        $billableWeight = max($weight, $dimWeight);
        
        // Large package surcharge
        if ($volume > 1728 || $maxDimension > 22) { // Over 1 cubic foot or over 22 inches
            $sizeSurcharge = 15.00;
        } elseif ($volume > 864) { // Over 0.5 cubic foot
            $sizeSurcharge = 4.00;
        }
        
        // Oversize surcharge
        if ($maxDimension > 30) {
            $sizeSurcharge += 30.00;
        }
        
        $totalCost = $baseRate + $sizeSurcharge;
        
        return [
            'cost' => $totalCost,
            'service_name' => 'USPS Ground Advantage',
            'delivery_days' => '2-5 business days',
            'base_rate' => $baseRate,
            'weight_surcharge' => 0,
            'zone_surcharge' => 0,
            'size_surcharge' => $sizeSurcharge
        ];
    }
    
    /**
     * 2024 Priority Mail Rates
     */
    private function getPriorityMailRates2024($zone, $weightInPounds, $dimensions) {
        $weight = max(1, ceil($weightInPounds));
        
        // 2024 Priority Mail Rate Table by Zone and Weight
        $rateTable = [
            // Weight => [Zone 1-2, Zone 3, Zone 4, Zone 5, Zone 6, Zone 7, Zone 8]
            1 => [9.35, 9.35, 9.35, 9.35, 9.35, 9.35, 9.35],
            2 => [9.35, 9.35, 9.35, 9.35, 9.35, 9.35, 9.35],
            3 => [9.35, 9.35, 9.35, 9.35, 9.35, 9.35, 9.35],
            4 => [10.40, 11.15, 11.90, 12.65, 13.40, 14.15, 14.90],
            5 => [11.45, 12.35, 13.25, 14.15, 15.05, 15.95, 16.85],
            6 => [12.50, 13.55, 14.60, 15.65, 16.70, 17.75, 18.80],
            7 => [13.55, 14.75, 15.95, 17.15, 18.35, 19.55, 20.75],
            8 => [14.60, 15.95, 17.30, 18.65, 20.00, 21.35, 22.70],
            9 => [15.65, 17.15, 18.65, 20.15, 21.65, 23.15, 24.65],
            10 => [16.70, 18.35, 20.00, 21.65, 23.30, 24.95, 26.60]
        ];
        
        $zoneIndex = min(7, max(0, $zone - 1));
        $baseRate = $rateTable[$weight][$zoneIndex] ?? $this->calculatePriorityOverweightRate($weight, $zone);
        
        // Size surcharges for Priority Mail
        $sizeSurcharge = 0;
        $volume = $dimensions['length'] * $dimensions['width'] * $dimensions['height'];
        $maxDimension = max($dimensions['length'], $dimensions['width'], $dimensions['height']);
        
        // Large package surcharge
        if ($volume > 1728 || $maxDimension > 22) {
            $sizeSurcharge = 15.00;
        }
        
        return [
            'cost' => $baseRate + $sizeSurcharge,
            'service_name' => 'USPS Priority Mail',
            'delivery_days' => '1-3 business days',
            'base_rate' => $baseRate,
            'weight_surcharge' => 0,
            'zone_surcharge' => 0,
            'size_surcharge' => $sizeSurcharge
        ];
    }
    
    /**
     * Calculate rates for packages over 10 lbs (Ground Advantage)
     */
    private function calculateOverweightRate($weight, $zone) {
        // Base 10 lb rate
        $baseRates = [9.25, 10.25, 11.25, 12.25, 13.25, 14.25, 15.25];
        $zoneIndex = min(6, max(0, $zone - 1));
        $base10lbRate = $baseRates[$zoneIndex];
        
        // Additional per-pound rate
        $additionalPounds = $weight - 10;
        $perPoundRates = [0.85, 0.95, 1.05, 1.15, 1.25, 1.35, 1.45];
        $perPoundRate = $perPoundRates[$zoneIndex];
        
        return $base10lbRate + ($additionalPounds * $perPoundRate);
    }
    
    /**
     * Calculate rates for Priority Mail over 10 lbs
     */
    private function calculatePriorityOverweightRate($weight, $zone) {
        // Base 10 lb rate
        $baseRates = [16.70, 18.35, 20.00, 21.65, 23.30, 24.95, 26.60];
        $zoneIndex = min(6, max(0, $zone - 1));
        $base10lbRate = $baseRates[$zoneIndex];
        
        // Additional per-pound rate
        $additionalPounds = $weight - 10;
        $perPoundRates = [1.35, 1.55, 1.75, 1.95, 2.15, 2.35, 2.55];
        $perPoundRate = $perPoundRates[$zoneIndex];
        
        return $base10lbRate + ($additionalPounds * $perPoundRate);
    }
    
    /**
     * Estimate distance between zip codes (improved calculation)
     */
    private function estimateDistance($zip1, $zip2) {
        // More accurate ZIP code to coordinate mapping
        $zipCoordinates = $this->getZipCoordinates();
        
        // Get first 3 digits for regional lookup
        $region1 = substr($zip1, 0, 3);
        $region2 = substr($zip2, 0, 3);
        
        // Use more precise coordinates if available
        $coord1 = $zipCoordinates[$region1] ?? $this->getRegionalCoordinates(substr($zip1, 0, 1));
        $coord2 = $zipCoordinates[$region2] ?? $this->getRegionalCoordinates(substr($zip2, 0, 1));
        
        // Haversine formula for distance calculation
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
     * Get more accurate ZIP code coordinates (first 3 digits)
     */
    private function getZipCoordinates() {
        return [
            // Major metropolitan areas for more accurate distance calculation
            '900' => ['lat' => 34.0522, 'lng' => -118.2437], // Los Angeles
            '902' => ['lat' => 34.0522, 'lng' => -118.2437], // Los Angeles
            '906' => ['lat' => 37.7749, 'lng' => -122.4194], // San Francisco
            '941' => ['lat' => 37.7749, 'lng' => -122.4194], // San Francisco
            '100' => ['lat' => 40.7128, 'lng' => -74.0060],  // New York
            '101' => ['lat' => 40.7128, 'lng' => -74.0060],  // New York
            '112' => ['lat' => 40.7128, 'lng' => -74.0060],  // New York
            '600' => ['lat' => 41.8781, 'lng' => -87.6298],  // Chicago
            '606' => ['lat' => 41.8781, 'lng' => -87.6298],  // Chicago
            '770' => ['lat' => 29.7604, 'lng' => -95.3698],  // Houston
            '772' => ['lat' => 29.7604, 'lng' => -95.3698],  // Houston
            '330' => ['lat' => 25.7617, 'lng' => -80.1918],  // Miami
            '331' => ['lat' => 25.7617, 'lng' => -80.1918],  // Miami
            '800' => ['lat' => 39.7392, 'lng' => -104.9903], // Denver
            '801' => ['lat' => 39.7392, 'lng' => -104.9903], // Denver
            '980' => ['lat' => 47.6062, 'lng' => -122.3321], // Seattle
            '981' => ['lat' => 47.6062, 'lng' => -122.3321], // Seattle
            '300' => ['lat' => 33.4484, 'lng' => -84.3880],  // Atlanta
            '303' => ['lat' => 33.4484, 'lng' => -84.3880],  // Atlanta
            '750' => ['lat' => 32.7767, 'lng' => -96.7970],  // Dallas
            '752' => ['lat' => 32.7767, 'lng' => -96.7970],  // Dallas
            '190' => ['lat' => 39.9526, 'lng' => -75.1652],  // Philadelphia
            '191' => ['lat' => 39.9526, 'lng' => -75.1652],  // Philadelphia
            '850' => ['lat' => 33.4484, 'lng' => -112.0740], // Phoenix
            '852' => ['lat' => 33.4484, 'lng' => -112.0740], // Phoenix
            '200' => ['lat' => 38.9072, 'lng' => -77.0369],  // Washington DC
            '202' => ['lat' => 38.9072, 'lng' => -77.0369],  // Washington DC
            '021' => ['lat' => 42.3601, 'lng' => -71.0589],  // Boston
            '022' => ['lat' => 42.3601, 'lng' => -71.0589],  // Boston
            '890' => ['lat' => 36.1627, 'lng' => -115.1099], // Las Vegas
            '891' => ['lat' => 36.1627, 'lng' => -115.1099], // Las Vegas
            '840' => ['lat' => 40.7608, 'lng' => -111.8910], // Salt Lake City
            '841' => ['lat' => 40.7608, 'lng' => -111.8910], // Salt Lake City
        ];
    }
    
    /**
     * Get regional coordinates (fallback for first digit)
     */
    private function getRegionalCoordinates($firstDigit) {
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
        
        return $regionDistances[$firstDigit] ?? $regionDistances['9'];
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
        
        // For calculated shipping, offer only Media and Ground
        $services = ['Media', 'Ground'];
        
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
        $serviceType = $selectedServices[$item['id']] ?? 'Ground';
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