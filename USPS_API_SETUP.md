# USPS API Integration Setup Guide

## Overview
Your Bort's Books website now supports real-time USPS shipping calculations using the modern USPS API with OAuth2 authentication.

## Steps to Configure

### 1. Add Your USPS API Credentials

Open your `.env` file and add these lines:

```env
# USPS API Configuration
USPS_CONSUMER_KEY=your_actual_consumer_key_here
USPS_CONSUMER_SECRET=your_actual_consumer_secret_here
USPS_ORIGIN_ZIP=your_business_zip_code
```

### 2. Replace the Placeholder Values

- **USPS_CONSUMER_KEY**: Your Consumer Key from USPS Developer Portal
- **USPS_CONSUMER_SECRET**: Your Consumer Secret from USPS Developer Portal  
- **USPS_ORIGIN_ZIP**: Your business ZIP code (where you ship from)

### 3. Test the Integration

1. Visit: `http://your-domain.com/test-usps-api.php`
2. This will test your API connection and show sample shipping rates
3. **Delete the test file** when you're done testing

### 4. How It Works

- **With API Keys**: Uses real USPS rates via their API
- **Without API Keys**: Falls back to estimated rates based on distance/weight
- **Product Pages**: Shows shipping calculator widget with real-time quotes
- **Admin Panel**: Configure shipping per product (weight, dimensions, shipping type)

### 5. Shipping Options Per Product

In the admin panel when editing products, you can set:

- **Calculated Shipping**: Uses USPS API or estimates
- **Free Shipping**: No shipping charge
- **Flat Rate**: Fixed shipping amount
- **Weight**: Product weight in ounces
- **Dimensions**: Length x Width x Height in inches

### 6. Getting USPS API Keys

1. Go to: https://developer.usps.com/
2. Create a developer account
3. Create a new application
4. Copy your Consumer Key and Consumer Secret
5. Add them to your `.env` file

### 7. API Features

- **Real-time rates** for Ground, Priority, and Express mail
- **OAuth2 authentication** (secure and modern)
- **Fallback system** if API is unavailable
- **Zone-based calculations** for accurate pricing
- **Weight and dimension support** for precise quotes

### 8. Files Updated

- `includes/usps-shipping.php` - Main shipping calculator
- `includes/shipping-calculator-widget.php` - Product page widget
- `env-example.txt` - Environment template
- `env-live-server-example.txt` - Live server template

### 9. Admin Database Update

Make sure to run the shipping fields database update:
`http://your-domain.com/database/add_shipping_fields.php`

This adds weight, dimensions, and shipping option fields to your products table.

### 10. Testing Checklist

- [ ] USPS API credentials added to `.env`
- [ ] API connection test passes
- [ ] Shipping calculator works on product pages
- [ ] Admin panel shows new shipping fields
- [ ] Database update completed
- [ ] Test file deleted

## Support

The system now supports both estimated and real USPS rates, providing a professional eBay-style shipping experience for your customers. 