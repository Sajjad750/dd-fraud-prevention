# DD Fraud Prevention Plugin Documentation

## Overview

The DD Fraud Prevention plugin is a comprehensive solution for detecting and preventing fraudulent orders in WooCommerce. It uses multiple detection methods including VPN detection, IP tracking, customer data verification, and pattern matching to identify potentially fraudulent transactions.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Modules](#modules)
   - [VPN Detection](#vpn-detection)
   - [Customer Data Verification](#customer-data-verification)
   - [Order History Analysis](#order-history-analysis)
   - [Manual and Automatic Scanning](#manual-and-automatic-scanning)
   - [Logging System](#logging-system)
4. [Database Structure](#database-structure)
5. [API Integration](#api-integration)
6. [Troubleshooting](#troubleshooting)

## Installation

1. Upload the `dd-fraud-prevention` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Fraud Prevention > Settings / Import to configure the plugin

## Configuration

### General Settings

- **Number of Previous Orders to Check**: Determines how many past orders to analyze for fraud patterns (default: 100)
- **Match Threshold (%)**: Percentage threshold for matching customer data (default: 70%)
- **Auto-Blocking**: Enable/disable automatic blocking of suspicious orders
- **VPN Blocking**: Enable/disable blocking of orders from VPN IP addresses
- **Past Orders Check**: Number of past orders to check for inconsistencies (default: 10)

### IPQualityScore API Integration

- **API Key**: Enter your IPQualityScore API key for enhanced VPN detection
- Get an API key from [IPQualityScore](https://www.ipqualityscore.com/documentation/ip-address-validation-api/overview)

## Modules

### VPN Detection

The VPN detection module identifies customers using VPNs, proxies, or Tor networks to place orders.

#### How It Works

1. **IPQualityScore API Check**:
   - Checks the customer's IP address against IPQualityScore's database of VPN/proxy IPs
   - Provides detailed information about the type of VPN/proxy being used
   - Falls back to static IP range check if API check fails

2. **Static IP Range Check**:
   - Checks the customer's IP address against a predefined list of known VPN IP ranges
   - Primarily includes Cloudflare IP ranges
   - Used as a fallback when the API check fails

3. **Blocking Process**:
   - If a VPN is detected, the order is immediately blocked
   - The order status is set to "blocked"
   - A record is created with the reason "IP address detected as VPN"
   - The customer sees an error message and cannot complete the order

### Customer Data Verification

This module verifies customer data against a database of known fraudulent or verified customers.

#### How It Works

1. **Data Collection**:
   - Collects Bigo ID, email, customer name, and IP address from the order
   - Stores this data in the order metadata for future reference

2. **Database Lookup**:
   - Checks each piece of customer data against the corresponding database tables:
     - `dd_fraud_bigo_id`: Stores blocked, reviewed, or verified Bigo IDs
     - `dd_fraud_email`: Stores blocked, reviewed, or verified email addresses
     - `dd_fraud_customer_name`: Stores blocked, reviewed, or verified customer names
     - `dd_fraud_ip`: Stores blocked, reviewed, or verified IP addresses

3. **Flag Processing**:
   - If any data matches a "blocked" entry, the order is blocked
   - If any data matches a "review" entry, the order is flagged for review
   - If any data matches a "verified" entry, the customer is considered trusted

4. **Priority Rules**:
   - Verified email takes precedence over blocked Bigo ID or name
   - If email is not verified, any blocked data will result in order blocking

### Order History Analysis

This module analyzes the customer's order history to identify patterns of fraudulent behavior.

#### How It Works

1. **Past Order Retrieval**:
   - Retrieves the customer's past orders based on the configured limit
   - Analyzes orders with matching Bigo ID, email, or name

2. **Inconsistency Detection**:
   - Checks for inconsistencies in shipping addresses, billing addresses, and payment methods
   - Identifies rapid order placement patterns
   - Detects unusual order amounts or quantities

3. **Pattern Matching**:
   - Compares current order data with historical patterns
   - Flags orders that deviate significantly from established patterns

### Manual and Automatic Scanning

The plugin uses a two-stage scanning process to evaluate orders.

#### Manual Scan

1. **Initial Check**:
   - Runs immediately when an order is placed
   - Checks customer data against the database tables
   - Returns a status: "blocked", "review_required", "verified", or "processing"

2. **Blocking Decision**:
   - If status is "blocked", the order is blocked and an error message is displayed
   - If status is "review_required", the order is flagged for manual review
   - If status is "verified", the order proceeds to automatic scan
   - If status is "processing", the order proceeds to automatic scan

#### Automatic Scan

1. **Pattern Analysis**:
   - Analyzes the order for patterns of fraudulent behavior
   - Checks for inconsistencies with past orders
   - Evaluates the risk level based on multiple factors

2. **Decision Making**:
   - Makes a final decision on whether to block, review, or approve the order
   - Updates the order status accordingly
   - Records the decision and reasoning in the order metadata

### Logging System

The plugin includes a comprehensive logging system to track all important events and actions.

#### How It Works

1. **Log Storage**:
   - Logs are stored in the `dd_fraud_logs` database table
   - Each log entry includes timestamp, user ID, action, details, and IP address

2. **Logged Events**:
   - VPN detection events
   - Order blocking events
   - Order verification events
   - Settings changes
   - Admin actions (adding, updating, deleting entries)

3. **Logs Page**:
   - Access logs through Fraud Prevention > Logs in the admin menu
   - View logs with pagination
   - See detailed information about each logged event

4. **Log Details**:
   - **Timestamp**: When the event occurred
   - **User**: Which admin user performed the action
   - **Action**: Type of action (e.g., "VPN Detection", "Order Blocked")
   - **Details**: Specific information about the event
   - **IP Address**: IP address of the user who performed the action

## Database Structure

The plugin uses several custom database tables to store fraud prevention data:

1. **dd_fraud_bigo_id**:
   - Stores Bigo IDs with flags (blocked, review, verified)
   - Includes notes and creation timestamp

2. **dd_fraud_email**:
   - Stores email addresses with flags (blocked, review, verified)
   - Includes notes and creation timestamp

3. **dd_fraud_customer_name**:
   - Stores customer names with flags (blocked, review, verified)
   - Includes notes and creation timestamp

4. **dd_fraud_ip**:
   - Stores IP addresses with flags (blocked, review, verified)
   - Includes notes and creation timestamp

5. **dd_fraud_logs**:
   - Stores logs of all important events and actions
   - Includes timestamp, user ID, action, details, and IP address

## API Integration

### IPQualityScore API

The plugin integrates with the IPQualityScore API for enhanced VPN detection.

#### Configuration

1. Obtain an API key from IPQualityScore
2. Enter the API key in the plugin settings
3. Enable VPN blocking in the settings

#### API Parameters

- **strictness**: Level of strictness for VPN detection (0-3)
- **allow_public_access_points**: Whether to allow public access points
- **fast**: Whether to use fast mode (less accurate but faster)
- **lighter_penalties**: Whether to use lighter penalties for certain types of VPNs

## Troubleshooting

### Common Issues

1. **VPN Detection Not Working**:
   - Verify that VPN blocking is enabled in the settings
   - Check that the IPQualityScore API key is correctly entered
   - Ensure the customer's IP address is being correctly captured

2. **False Positives**:
   - Adjust the match threshold in the settings
   - Review and update the database entries for legitimate customers
   - Consider using the "verified" flag for trusted customers

3. **API Errors**:
   - Check the WordPress error log for API-related errors
   - Verify your internet connection
   - Ensure the API key is valid and has sufficient credits

### Debugging

The plugin includes debug logging for troubleshooting:

- IP address logging
- API response logging
- Order processing logging
- Comprehensive event logging through the Logs page

To enable more detailed logging, you can add the following to your wp-config.php file:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For support or questions about the DD Fraud Prevention plugin, please contact:

- Email: bigodiscountdiamonds@gmail.com
- Support Hours: 24 hours a day, 7 days a week 