<?php
/**
 * Bigo API Integration Class
 *
 * @package DD_Fraud_Prevention
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class DD_Bigo_API {
    /**
     * API Base URL
     *
     * @var string
     */
    private $api_base_url = 'https://oauth.bigolive.tv'; // Production environment

    /**
     * Client ID assigned by Bigo
     *
     * @var string
     */
    private $client_id = 'k3ohm2sDuExb9QO'; // Production client ID

    /**
     * Private Key for RSA signing
     *
     * @var string
     */
    private $private_key = <<<EOD
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCewloc+C9OtyOB
Lwt+TvTPB9HjwQyfBLFhGFZHWfJi8rBUmDONdoArwD61FiJl4vKn38DhWygqpNM3
Kiih4VQGxl7GvFIULGiscqwvYLHyQu9bZ8OdXrV3xF+KwuVd8iI1v0rBrzwNvUiP
C4ucRBQFSOSwPvB3ySkN/59AbApHCplO2hk9iQd6GCNp7bfpHyGNFXoAb8vY60fu
FtLGPrNrrqxxDrRVjF7XOSdqiQoe4z4B1FlDwX0+GVpbhYqn7CZoRe8Uhkz5/MhO
h7kGtqnLZovoZUnGUN+Bc7dCeKoR2ZS43/IYdKmBl+9M1TbAQsOwBcmS54OxCkCn
/BVw65tJAgMBAAECggEADnrokDLc0b+nd91sHXGjJ4ztimnttkVNzm7TU7+y+W5s
QdL+BL2VtCfdMFQcABICkug4JfXUBIuzDhmEyjsMmG+YbmT30Yo5Y90zskCOCmwr
e7lLoLtmLs3U7wmWtQpkL2XKsj7C6fflOdLSQYb+EntTDHY5JZvN6E5z3oLcLx12
C4eMOU8TumQCaZxiv8pItP7MMur6iwVsbqXfl5nVwuMRPNBK0OkStbjobEif29XN
Gh7KeD7QZwJ0LSwa2kqWceXNOzULctLK8GJEGGnhDj20z1BAfxlGP9+N0V0cPpSf
PfsiP6/cvP69QvMYrnq8mGo/GpK6kfPbwiUoTxIOmQKBgQDTp4FCtZ5wOyJ3/lZW
5ts5hr9V7gAoguht5w9+L7RIBokbvM4Z1F/pXcvLjvDmTQmD5Z7EQMc0lmDfO2/T
RC1tINT0rKduU6fWI9gnoR6RuSyyWMIiTKR9QYNHSw1mUB6oKbo4CU4d4EkClsfd
X8F+s0lDQayYkzGqFPSloSsmbQKBgQDABbaDBh2NvVqqLFKatggDNUZkwN0roj6/
YsXhTFL8SqpYio8JP0nlwTjYq27ZLGxeyM7K83ZIqadtgDhR2cITWG7lP6pV3XYr
LwYk6F5HTfVz2HDnj94iWFvycXrT57lC6ntZnq5whsyXHN12bpk/4mINH7UkLRh6
OPeHRWduzQKBgCFmX4mNa5E+Y7QX2Lwh9hpf3zXKNxAtiEw/mDxLfuGW1nAgHU4K
K5CCErTuu6k8IvJDfAhwSH9N87+Ge6EVMy3zbmemD03juaqbQXMPg+lvFVSXmRsc
iSCTBApuF5E7t3rGCvLo1QD18c+Mx8FxaPF7jWYlqPzyzXWPlQPGKS8JAoGABzFv
l9Ln0oJwXgWRBpihDjW1sFqFLnhCb3rsvLbWOPs3DGAMYaVMSF4Hmh455crDOH2/
OV0LZkdsrS5rba2BlqXuaYoMAHFuVsnJKiLGPVePRUqrWBFMme7Dav6TQlLg3r/X
5RCLqk1yZoq/RQt5lCoP0DwK1hMWYqW1qAyShlUCgYEAlEeMSn2N3z1DjxPRXKoR
kqfrayvhhovFIEN9kAJktaNZDTtv6HQgJy1rOLTJZj3txkOhdE/nvTj06fRisFkQ
SoApaTYlPyJDt5U5YZPkyrkDWjc0NrxxwflyTLdb80jNVSjTQn4RbVlf2ofcdqRq
uDkIz0GEs6LJb9kPqhJO79A=
-----END PRIVATE KEY-----
EOD;

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into WooCommerce order processing
        add_action('woocommerce_order_status_changed', array($this, 'process_bigo_order'), 10, 3);
    }

    /**
     * Process Bigo order when WooCommerce order status changes
     *
     * @param int    $order_id   Order ID
     * @param string $old_status Old status
     * @param string $new_status New status
     */
    public function process_bigo_order($order_id, $old_status, $new_status) {
        // Only process when order is processing
        if ($new_status !== 'processing') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Add debug note about order status change
        $order->add_order_note(sprintf(
            'Processing Bigo recharge - Status changed from %s to %s',
            $old_status,
            $new_status
        ));

        // Get Bigo ID from checkout field
        $bigo_id = $order->get_meta('_billing_bigo_id');
        
        // Validate Bigo ID format
        if (empty($bigo_id)) {
            $this->log_error($order_id, 'Bigo ID not found in order');
            $order->add_order_note('❌ Bigo recharge failed: Bigo ID not found in order');
            $order->update_status('failed', 'Bigo ID not found');
            return;
        }

        // Remove any spaces from Bigo ID
        $bigo_id = str_replace(' ', '', $bigo_id);

        // Add debug note about Bigo ID
        $order->add_order_note(sprintf(
            "Found Bigo ID: %s\nNickname: %s",
            $bigo_id,
            $order->get_meta('_billing_bigo_nickname') ?: 'Not available'
        ));

        // Get diamond amount from order items
        $diamond_amount = 0;
        $diamond_details = array();

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->get_sku()) {
                $item_sku = $product->get_sku();
                $item_quantity = $item->get_quantity();
                $item_diamonds = intval($item_sku) * $item_quantity;
                
                $diamond_amount += $item_diamonds;
                
                // Store details for debugging
                $diamond_details[] = sprintf(
                    'Product: %s, SKU: %s, Quantity: %d, Diamonds: %d',
                    $product->get_name(),
                    $item_sku,
                    $item_quantity,
                    $item_diamonds
                );
            }
        }

        // Add debug note about diamond calculation
        $order->add_order_note(sprintf(
            "Diamond calculation details:\n%s\nTotal diamonds: %d",
            implode("\n", $diamond_details),
            $diamond_amount
        ));

        if ($diamond_amount <= 0) {
            $this->log_error($order_id, 'Invalid diamond amount');
            $order->add_order_note('❌ Bigo recharge failed: Invalid diamond amount (0 or negative)');
            $order->update_status('failed', 'Invalid diamond amount');
            return;
        }

        // Use WooCommerce order ID as sequence ID
        $seqid = 'order_' . $order_id;

        // Prepare API request
        $api_data = array(
            'recharge_bigoid' => $bigo_id,
            'value' => $diamond_amount,
            'seqid' => $seqid,
            'timestamp' => time(),
        );

        // Add debug note about API request
        $order->add_order_note(sprintf(
            "Preparing Bigo API request:\nOrder ID: %s\nData: %s",
            $order_id,
            json_encode($api_data, JSON_PRETTY_PRINT)
        ));

        // First test the signature generation with test endpoint
        $test_response = $this->test_signature();
        if (is_wp_error($test_response)) {
            $error_message = $test_response->get_error_message();
            $this->log_error($order_id, 'Signature test failed: ' . $error_message);
            $order->add_order_note('❌ Bigo API signature test failed: ' . $error_message);
            $order->update_status('failed', 'API signature test failed');
            return;
        }

        // Generate signature for actual request
        try {
            $signature = $this->generate_signature($api_data);
        } catch (Exception $e) {
            $this->log_error($order_id, 'Failed to generate signature: ' . $e->getMessage());
            $order->add_order_note('❌ Bigo API signature generation failed: ' . $e->getMessage());
            $order->update_status('failed', 'API signature generation failed');
            return;
        }

        // Make API request with retry logic
        $max_retries = 3;
        $retry_count = 0;
        $response = null;

        while ($retry_count < $max_retries) {
            $response = $this->make_api_request($api_data, $signature);
            
            if (!is_wp_error($response)) {
                break;
            }
            
            $retry_count++;
            if ($retry_count < $max_retries) {
                sleep(2); // Wait 2 seconds before retrying
                $order->add_order_note(sprintf('Retrying Bigo API request (attempt %d of %d)', $retry_count + 1, $max_retries));
            }
        }

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->log_error($order_id, $error_message);
            $order->add_order_note(sprintf('❌ Bigo API request failed after %d attempts: %s', $max_retries, $error_message));
            $order->update_status('failed', 'API request failed: ' . $error_message);
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Add debug note about API response
        $order->add_order_note(sprintf(
            "Bigo API Response:\n%s",
            json_encode($body, JSON_PRETTY_PRINT)
        ));

        if (isset($body['rescode']) && $body['rescode'] === 0) {
            // Success
            $order->add_order_note(sprintf(
                "✅ Bigo diamonds recharged successfully\nOrder ID: %s\nAmount: %d diamonds\nBigo ID: %s",
                $order_id,
                $diamond_amount,
                $bigo_id
            ));
            $order->update_status('completed', 'Bigo diamonds recharged successfully');
            
            // Store successful recharge details
            update_post_meta($order_id, '_bigo_recharge_success', true);
            update_post_meta($order_id, '_bigo_recharge_amount', $diamond_amount);
            update_post_meta($order_id, '_bigo_recharge_time', current_time('mysql'));
        } else {
            // Failure
            $error_message = isset($body['message']) ? $body['message'] : 'Unknown error';
            $this->log_error($order_id, $error_message);
            $order->add_order_note(sprintf(
                "❌ Bigo recharge failed: %s\nBigo ID: %s",
                $error_message,
                $bigo_id
            ));
            $order->update_status('failed', 'Bigo recharge failed: ' . $error_message);
            
            // Store failure details
            update_post_meta($order_id, '_bigo_recharge_success', false);
            update_post_meta($order_id, '_bigo_recharge_error', $error_message);
            update_post_meta($order_id, '_bigo_recharge_time', current_time('mysql'));
        }
    }

    /**
     * Test signature generation with test endpoint
     *
     * @return WP_Error|array
     */
    private function test_signature() {
        $test_data = array('msg' => 'hello');
        $signature = $this->generate_signature($test_data, '/oauth2/test_sign');
        
        $args = array(
            'method' => 'POST',
            'timeout' => 45,
            'headers' => array(
                'Content-Type' => 'application/json',
                'bigo-client-id' => $this->client_id,
                'bigo-timestamp' => time(),
                'bigo-oauth-signature' => $signature,
            ),
            'body' => json_encode($test_data),
        );

        $response = wp_remote_post($this->api_base_url . '/oauth2/test_sign', $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error('api_error', 'Test API request failed with status code: ' . $response_code);
        }

        return $response;
    }

    /**
     * Generate API signature using RSA
     *
     * @param array  $data     API request data
     * @param string $endpoint API endpoint
     * @return string
     */
    private function generate_signature($data, $endpoint = '/oauth2/recharge') {
        // Sort data by key
        ksort($data);

        // Create message string
        $message = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        // Add endpoint and timestamp to message
        $timestamp = time();
        $message = $message . $endpoint . $timestamp;

        // Create SHA256 hash of the message
        $hash = hash('sha256', $message, true);

        // Sign the hash with RSA private key
        $signature = '';
        if (!openssl_sign($hash, $signature, $this->private_key, OPENSSL_ALGO_SHA256)) {
            throw new Exception('Failed to generate signature');
        }

        // Return base64 encoded signature
        return base64_encode($signature);
    }

    /**
     * Make API request to Bigo
     *
     * @param array  $data      API request data
     * @param string $signature API signature
     * @return WP_Error|array
     */
    private function make_api_request($data, $signature) {
        $timestamp = time();
        
        $args = array(
            'method' => 'POST',
            'timeout' => 45,
            'headers' => array(
                'Content-Type' => 'application/json',
                'bigo-client-id' => $this->client_id,
                'bigo-timestamp' => $timestamp,
                'bigo-oauth-signature' => $signature,
            ),
            'body' => json_encode($data),
        );

        $response = wp_remote_post($this->api_base_url . '/oauth2/recharge', $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error('api_error', 'API request failed with status code: ' . $response_code);
        }

        return $response;
    }

    /**
     * Log error message
     *
     * @param int    $order_id Order ID
     * @param string $message  Error message
     */
    private function log_error($order_id, $message) {
        error_log(sprintf(
            'Bigo API Error - Order #%s: %s',
            $order_id,
            $message
        ));
    }
}

// Initialize the class
new DD_Bigo_API(); 