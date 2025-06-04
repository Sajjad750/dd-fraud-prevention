<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://myportfoliosajjad.netlify.app/
 * @since             1.0.0
 * @package           Dd_Fraud_Prevention
 *
 * @wordpress-plugin
 * Plugin Name:       DD Fraud Prevention
 * Plugin URI:        https://discountdiamondstore.com/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Sajjad Ahmad
 * Author URI:        https://myportfoliosajjad.netlify.app/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dd-fraud-prevention
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

foreach ( glob( plugin_dir_path( __FILE__ ) . 'admin/*.php' ) as $file ) {
	include_once $file;
}	

// Include logger class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-dd-fraud-logger.php';

// Include Bigo API classes
require_once plugin_dir_path(__FILE__) . 'includes/class-dd-bigo-api.php';

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'DD_FRAUD_PREVENTION_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-dd-fraud-prevention-activator.php
 */
function activate_dd_fraud_prevention() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dd-fraud-prevention-activator.php';
	Dd_Fraud_Prevention_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-dd-fraud-prevention-deactivator.php
 */
function deactivate_dd_fraud_prevention() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dd-fraud-prevention-deactivator.php';
	Dd_Fraud_Prevention_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_dd_fraud_prevention' );
register_deactivation_hook( __FILE__, 'deactivate_dd_fraud_prevention' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-dd-fraud-prevention.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_dd_fraud_prevention() {

	$plugin = new Dd_Fraud_Prevention();
	$plugin->run();

	Dd_Order_Statuses::init();

	$settings = new Settings( new Settings_Page(), new Import_Export_Page(), new Listings_Page(), new Add_Entry_Page() );
	$settings->init();
	
	// Initialize logger
	$logger = new DD_Fraud_Logger();
	$logger->init();

}

run_dd_fraud_prevention();

// validate the 2 Bigo ID inputs
add_action( 'woocommerce_after_checkout_validation', 'dd_validate_bigo_ids', 10, 2 );
 
function dd_validate_bigo_ids( $fields, $errors ){
 
    if ($fields['billing_bigo_id'] !== $fields['billing_confirm_bigo_id']) {
        $errors->add( 'validation', 'Oops, the Bigo IDs you entered don\'t match. Please correct this error to proceed.' );
    }

		if (str_contains($fields['billing_bigo_id'], " ")) {
			$errors->add( 'validation', 'Oops, your Bigo ID cannot contain spaces. Please correct this error to proceed.' );
	}
}

add_action( 'wp_footer', 'dd_add_bigo_id_checkout_validation_js');

//old

function dd_add_bigo_id_checkout_validation_js() {
 
	// we need it only on our checkout page
	if( ! is_checkout() ) {
		return;
	}
 
	?>
	<script>
	jQuery(function($){
		// Create user info display container
		$('<div id="bigo-user-info" style="display:none; margin-top:10px; padding:10px; border:1px solid #ddd; border-radius:4px;"></div>').insertAfter('#billing_bigo_id_field');
		
		// Handle Bigo ID blur event
		$('#billing_bigo_id').on('blur', function() {
			var bigoId = $(this).val();
			if (!bigoId) {
				$('#bigo-user-info').hide();
				return;
			}
			
			// Show loading indicator
			$('#bigo-user-info').html('<p>Loading user information...</p>').show();
			
			// Make AJAX request to get user info
			$.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				type: 'POST',
				data: {
					action: 'dd_get_bigo_user_info',
					bigo_id: bigoId,
					nonce: '<?php echo wp_create_nonce('dd_bigo_user_info_nonce'); ?>'
				},
				success: function(response) {
					console.log('Bigo API Response:', response);
					if (response.success) {
						var userInfo = response.data;
						console.log('Setting profile picture URL:', userInfo.profile_pic);
						
						var html = '<div style="display:flex; align-items:center;">';
						
						if (userInfo.profile_pic) {
							html += '<img src="' + userInfo.profile_pic + '" alt="Profile Picture" style="width:50px; height:50px; border-radius:50%; margin-right:10px;">';
						}
						
						html += '<div>';
						html += '<p style="margin:0; font-weight:bold;">' + userInfo.nickname + '</p>';
						html += '<p style="margin:0; color:#666;">ID: ' + bigoId + '</p>';
						html += '</div>';
						html += '</div>';
						
						$('#bigo-user-info').html(html);
						
						// Set hidden fields for order meta
						$('#billing_bigo_nickname').val(userInfo.nickname);
						$('#billing_bigo_profile_pic').val(userInfo.profile_pic);
						
						// Verify hidden fields are set
						console.log('Nickname set:', $('#billing_bigo_nickname').val());
						console.log('Profile picture set:', $('#billing_bigo_profile_pic').val());
					} else {
						console.error('Error in Bigo API response:', response.data);
						$('#bigo-user-info').html('<p style="color:red;">' + response.data + '</p>');
						$('#billing_bigo_nickname').val('');
						$('#billing_bigo_profile_pic').val('');
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX Error:', error);
					$('#bigo-user-info').html('<p style="color:red;">Error connecting to server</p>');
					$('#billing_bigo_nickname').val('');
					$('#billing_bigo_profile_pic').val('');
				}
			});
		});

		// Existing validation code
		$( 'body' ).on( 'blur change', '#billing_confirm_bigo_id', function(){
			const wrapper = $(this).closest( '.form-row' );
			let bigoId = $('#billing_bigo_id').val();
			let val = $(this).val();

			if( bigoId !== val ) {
				wrapper.addClass( 'woocommerce-invalid' );
				wrapper.removeClass( 'woocommerce-validated' );
			}
			else if ( val.indexOf(' ') > -1 )
			{
				wrapper.addClass( 'woocommerce-invalid' );
				wrapper.removeClass( 'woocommerce-validated' );
			} else {
				wrapper.addClass( 'woocommerce-validated' );
				wrapper.removeClass( 'woocommerce-invalid' ); 
			}
		});

		$( 'body' ).on( 'blur change', '#billing_bigo_id', function(){
			const wrapper = $(this).closest( '.form-row' );
			const val = $(this).val();

			if( val.indexOf(' ') > -1 ) {
				wrapper.addClass( 'woocommerce-invalid' );
				wrapper.removeClass( 'woocommerce-validated' );

				if (!wrapper.find('.error-message').length)
				{
					wrapper.append('<p class="error-message" style="color:#a00">Bigo ID cannot contain spaces</p>')
				}
			} else {
				wrapper.addClass( 'woocommerce-validated' );
				wrapper.removeClass( 'woocommerce-invalid' ); 
				wrapper.find('.error-message').remove();
			}
		});
	});
	</script>
	<?php
}

add_action( 'woocommerce_checkout_order_processed', 'dd_scan_orders_for_fraud', 1000, 3);

function dd_scan_orders_for_fraud($order_id, $posted_data, $order)
{
	if ( ! $order_id ) {
		return;
	}

	// Get customer IP address
	$ip_address = dd_get_customer_ip();
	
	// Debug logging
	error_log('DD Fraud Prevention - IP Address: ' . $ip_address);
	error_log('DD Fraud Prevention - Order ID: ' . $order_id);
	
	$order->update_meta_data('_customer_ip', $ip_address);
	$order->save();

	// Check VPN if enabled
	if (get_option('dd_fraud_vpn_block', '1') === '1' && dd_check_vpn_ip($ip_address)) {
		$order->update_status('blocked');
		dd_record_block_details($order, 'auto', 'IP address detected as VPN');
		throw new Exception(__('Orders from VPN IP addresses are not allowed.', 'dd_fraud' ) );
	}

	// Check past orders for inconsistencies
	$inconsistencies = dd_check_past_orders($order);
	if (!empty($inconsistencies)) {
		$order->add_order_note('Inconsistencies found in past orders: ' . implode(', ', $inconsistencies));
	}	

	$status = dd_run_manual_scan($order);

	if ($status === "blocked") {
		throw new Exception( __( 'Our fraud system has detected a problem with this order and has blocked it. If there is a mistake, please email Support at <a href="mailto:bigodiscountdiamonds@gmail.com">bigodiscountdiamonds@gmail.com.</a> We are open 24 hours a day, 7 days a week.</a>', 'dd_fraud' ) );
	}
	elseif ($status !== "verified-email")
	{
		dd_run_automatic_scan($order, $status);
	}
}

// Function to get customer IP address with improved validation
function dd_get_customer_ip() {
    $ipaddress = '';
    
    // Check for Cloudflare
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ipaddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    // Check for other proxy headers
    else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    }
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    }
    else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    }
    else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    }
    else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    }
    else {
        $ipaddress = 'UNKNOWN';
    }

    // Clean the IP address
    $ipaddress = filter_var($ipaddress, FILTER_VALIDATE_IP);
    
    // If validation failed, return UNKNOWN
    if ($ipaddress === false) {
        $ipaddress = 'UNKNOWN';
    }
    
    return $ipaddress;
}

// Function to check if IP is from a VPN
function dd_check_vpn_ip($ip_address) {
    // Check using IPQualityScore API
    $api_result = dd_check_ipqualityscore($ip_address);
    
    if ($api_result !== null) {
        // Log VPN detection
        $logger = new DD_Fraud_Logger();
        $logger->log('VPN Detection', "IP address {$ip_address} detected as VPN using IPQualityScore API");
        return $api_result;
    }
    
    // If API check fails, log the failure and return false
    $logger = new DD_Fraud_Logger();
    $logger->log('VPN Detection', "IPQualityScore API check failed for IP address {$ip_address}");
    return false;
}

/**
 * Check IP address using IPQualityScore API
 * 
 * @param string $ip_address The IP address to check
 * @return bool|null Returns true if VPN detected, false if not, null if API check failed
 */
function dd_check_ipqualityscore($ip_address) {
    // Get API key from WordPress options
    $api_key = get_option('dd_ipqualityscore_api_key');
    if (empty($api_key)) {
        error_log('IPQualityScore API Error: No API key configured');
        return null;
    }

    // Build API URL
    $url = add_query_arg(
        array(
            'key' => $api_key,
            'ip' => $ip_address,
            'strictness' => 1, // 0-3, higher means stricter checking
            'allow_public_access_points' => 'true',
            'fast' => 'false',
            'lighter_penalties' => 'false'
        ),
        'https://www.ipqualityscore.com/api/json/ip'
    );

    // Make API request
    $response = wp_remote_get($url);
    
    if (is_wp_error($response)) {
        error_log('IPQualityScore API Error: ' . $response->get_error_message());
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data) || !isset($data['vpn'])) {
        error_log('IPQualityScore API Error: Invalid response format');
        return null;
    }

    // Log the full response for debugging
    error_log('IPQualityScore API Response: ' . print_r($data, true));

    // Check if IP is using VPN/proxy
    return ($data['vpn'] === true || $data['proxy'] === true || $data['tor'] === true);
}

// Helper function to check if IP is in range
function dd_ip_in_range($ip, $range) {
    list($range, $netmask) = explode('/', $range, 2);
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}

// Function to check past orders for inconsistencies
function dd_check_past_orders($order) {
    $past_orders_limit = get_option('dd_fraud_past_orders_check', 10);
    $current_email = $order->get_billing_email();
    $current_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $current_bigo_id = $order->get_meta('_billing_bigo_id');
    
    $args = array(
        'limit' => $past_orders_limit,
        'exclude' => array($order->get_id()),
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    $past_orders = wc_get_orders($args);
    $inconsistencies = array();
    
    foreach ($past_orders as $past_order) {
        $past_email = $past_order->get_billing_email();
        $past_name = $past_order->get_billing_first_name() . ' ' . $past_order->get_billing_last_name();
        $past_bigo_id = $past_order->get_meta('_billing_bigo_id');
        
        if ($past_email !== $current_email) {
            $inconsistencies[] = "Email mismatch with order #" . $past_order->get_id();
        }
        if ($past_name !== $current_name) {
            $inconsistencies[] = "Name mismatch with order #" . $past_order->get_id();
        }
        if ($past_bigo_id !== $current_bigo_id) {
            $inconsistencies[] = "Bigo ID mismatch with order #" . $past_order->get_id();
        }
    }
    
    return $inconsistencies;
}

// Check order data (Bigo ID, email, customer name, IP) to see if they are stored in the database as blocked, review or verified
function dd_run_manual_scan($order) {
	global $wpdb;

	// fetch data
	$bigo_table = $wpdb->prefix . 'dd_fraud_bigo_id';
	$email_table = $wpdb->prefix . 'dd_fraud_email';
	$customer_name_table = $wpdb->prefix . 'dd_fraud_customer_name';
	$ip_table = $wpdb->prefix . 'dd_fraud_ip';

	$order_id = $order->get_id();
	$bigo_id = $order->get_meta('_billing_bigo_id');
	$email = $order->get_billing_email();
	$name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
	$ip_address = $order->get_meta('_customer_ip');

	// Debug logging for IP check
	error_log('DD Fraud Prevention - Checking IP: ' . $ip_address);

	// Check IP address
	if (!empty($ip_address) && $ip_address !== 'UNKNOWN') {
		$ip_query = $wpdb->prepare("SELECT * FROM $ip_table WHERE ip_address = %s", $ip_address);
		$ip_row = $wpdb->get_row($ip_query, ARRAY_A);
		
		if (!empty($ip_row)) {
			error_log('DD Fraud Prevention - IP Match Found: ' . print_r($ip_row, true));
			
			$order_check_arr['ip_address'] = [
				'flag' => $ip_row['flag'],
				'notes' => $ip_row['notes']
			];

			if ($ip_row['flag'] === "blocked") {
				// Log IP blocked
				$logger = new DD_Fraud_Logger();
				$logger->log('Order Blocked', "Order #{$order_id} blocked due to blocked IP address: {$ip_address}");
				return "blocked";
			}
			elseif ($ip_row['flag'] === "review") {
				// Log IP review
				$logger = new DD_Fraud_Logger();
				$logger->log('Order Review', "Order #{$order_id} flagged for review due to IP address: {$ip_address}");
				return "review";
			}
			elseif ($ip_row['flag'] === "verified") {
				// Log IP verified
				$logger = new DD_Fraud_Logger();
				$logger->log('Order Verified', "Order #{$order_id} verified due to verified IP address: {$ip_address}");
				return "verified";
			}
		} else {
			// Add IP to order check array even if not found in database
			$order_check_arr['ip_address'] = [
				'flag' => 'check',
				'notes' => 'IP address checked'
			];
		}
	}

	$bigo_id_query = $wpdb->prepare( "SELECT * FROM $bigo_table WHERE %s LIKE bigo_id" , $bigo_id );
	$bigo_id_row = $wpdb->get_row( $bigo_id_query, ARRAY_A );
	$bigo_id_row = isset($bigo_id_row) ? $bigo_id_row : [];

	$email_query = $wpdb->prepare( "SELECT * FROM $email_table WHERE %s LIKE email" , $email );
	$email_row = $wpdb->get_row( $email_query, ARRAY_A );
	$email_row = isset($email_row) ? $email_row : [];

	$name_query = $wpdb->prepare( "SELECT * FROM $customer_name_table WHERE %s LIKE customer_name" , $name );
	$name_row = $wpdb->get_row( $name_query, ARRAY_A );

	$is_email_verifed = false;
	$is_email_verified = isset($is_email_verified) ? $is_email_verified : false;
	$is_verified = false;
	$is_blocked = false;
	$review_required = false;

	$order_check_arr = [];
	
	if (!empty($bigo_id_row))
	{
		if ($bigo_id_row['flag'] === "verified")
		{
			$is_verified = true;
		}
		elseif ($bigo_id_row['flag'] === "review")
		{
			$review_required = true;
		}
		elseif ($bigo_id_row['flag'] === "blocked")
		{
			$is_blocked = true;
		}

		if (str_contains($bigo_id_row['bigo_id'], "%"))
		{
			$notes = $bigo_id_row['notes'] . "<br>" . "Matched wildcard: " . $bigo_id_row['bigo_id']; 
		}
		else
		{
			$notes = $bigo_id_row['notes'];
		}

		$order_check_arr['bigo_id'] = [
			'flag' => $bigo_id_row['flag'],
			'notes' => $notes
		];
	}

	if (!empty($email_row))
	{
		if ($email_row['flag'] === "verified")
		{
			$is_email_verified = true;
		}
		elseif ($email_row['flag'] === "review")
		{
			$review_required = true;
		}
		elseif ($email_row['flag'] === "blocked")
		{
			$is_blocked = true;
		}

		if (str_contains($email_row['email'], "%"))
		{
			$notes = $email_row['notes'] . "<br>" . "Matched wildcard: " . $email_row['email']; 
		}
		else
		{
			$notes = $email_row['notes'];
		}

		$order_check_arr['email'] = [
			'flag' => $email_row['flag'],
			'notes' => $notes
		];
	}

	if (!empty($name_row))
	{
		if ($name_row['flag'] === "verified")
		{
			$is_verified = true;
		}
		elseif ($name_row['flag'] === "review")
		{
			$review_required = true;
		}
		elseif ($name_row['flag'] === "blocked")
		{
			$is_blocked = true;
		}
	
		if (str_contains($name_row['customer_name'], "%"))
		{
			$notes = $name_row['notes'] . "<br>" . "Matched wildcard: " . $name_row['customer_name']; 
		}
		else
		{
			$notes = $name_row['notes'];
		}

		$order_check_arr['customer_name'] = [
			'flag' => $name_row['flag'],
			'notes' => $notes
		];
	}

	// if email is verified, that takes precedence over found blocked Bigo ID or name
	if ($is_email_verifed)
	{
		$status = "verified-email";
		// Log email verified
		$logger = new DD_Fraud_Logger();
		$logger->log('Order Verified', "Order #{$order_id} verified due to verified email: {$email}");
	}
 	else if ($is_blocked)
	{
		$order->update_status('blocked');
		dd_record_block_details($order, 'auto', 'Blocked by fraud prevention system based on customer data');
		$status = "blocked";
		// Log order blocked
		$logger = new DD_Fraud_Logger();
		$logger->log('Order Blocked', "Order #{$order_id} blocked by fraud prevention system based on customer data");
	}
	else if ($review_required && !$is_verified)
	{
		update_post_meta($order_id, '_review_required', 1 );
		$status = "review_required";
		// Log review required
		$logger = new DD_Fraud_Logger();
		$logger->log('Order Review', "Order #{$order_id} flagged for review");
	}
	else if ($is_verified) {
		$status = "verified";
		// Log order verified
		$logger = new DD_Fraud_Logger();
		$logger->log('Order Verified', "Order #{$order_id} verified");
	}
	else {
		$status = "processing";
		// Log order processing
		$logger = new DD_Fraud_Logger();
		$logger->log('Order Processing', "Order #{$order_id} proceeding to automatic scan");
	}

	if (!empty($order_check_arr))
	{
		update_post_meta($order_id, '_fraud_check', json_encode($order_check_arr) );
	}

	$order->save();

	// if email or order is not verified and found a blocked email or bigo id,
	// then flag other emails/bigo ids used in other orders
	if ($is_email_verified || $is_verified)
	{
		return $status;
	}
	else {
    if (isset($bigo_id_row['flag']) && $bigo_id_row['flag'] === "blocked") 
    {
        dd_flag_emails($order);
    }
    
    if (isset($email_row['flag']) && $email_row['flag'] === "blocked") {
        dd_flag_bigo_ids($order);
    }
	}
	
	return $status;
}

// scan previous orders based on Bigo ID for unique addresses and emails
function dd_run_automatic_scan($current_order, $status) 
{
	$review_required = false;
	
	$bigo_id = $current_order->get_meta('_billing_bigo_id');
	$fraud_threshold = get_option('dd_fraud_match_threshold') ?: 70;
	$limit = get_option('dd_fraud_order_limit') ?: 100;

	$args = [
		'bigo_id' => $bigo_id,
		'limit' => $limit,
	];

	$orders = wc_get_orders($args);

	$name_arr = [];
	$address_arr = [];
	$email_arr = [];

	foreach($orders as $order) {
		$id = $order->get_id();
		$status_arr[$id] = $order->get_status();
		$email_arr[$id] = strtolower($order->get_billing_email());
		$name_arr[$id] = strtolower($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
		$address_arr[$id] = strtolower($order->get_billing_address_1() . ' ' . $order->get_billing_address_2() . ' ' . $order->get_billing_city() . ' ' . $order->get_billing_state() . ' ' . $order->get_billing_country());
	}

	$unique_emails = array_unique($email_arr);
	$unique_names = array_unique($name_arr);
	$unique_addresses = array_unique($address_arr);

	$arr_to_check = ['emails' => $unique_emails, 'names' => $unique_names, 'addresses' => $unique_addresses];
	$notes = [
		'emails' => [], 
		'names' => [], 
		'addresses' => []
	];

	foreach($arr_to_check as $key => $arr) {
		$values = array_values($arr);
		$order_ids = array_keys($arr); 
		$count = count($arr);
		for ($i = 0; $i < $count - 1; $i++) {
			$percents = [];
			for ($j = $i + 1; $j < $count; $j++) {
				$sim = similar_text($values[$i],$values[$j],$percent);
				$percents[] = $percent;
				
				if ($percent <= $fraud_threshold) {
					$notes[$key][$order_ids[$i]] = $values[$i];
					$notes[$key][$order_ids[$j]] = $values[$j];
					$review_required = true;
					break;
				}
			}
		}
	}

	if (!empty($review_required) && $status != "verified") {
		update_post_meta($current_order->get_id(), '_review_required', 1 );
	}

	$notes['status_count'] = array_count_values($status_arr);
	$notes['status_count']['total'] = count($orders);
	update_post_meta($current_order->get_id(), '_auto_fraud_check', json_encode($notes) );
}

function dd_flag_bigo_ids($current_order) 
{
	$current_email = $current_order->get_billing_email();
	$current_bigo_id = $current_order->get_meta('_billing_bigo_id');
	$limit = get_option('dd_fraud_order_limit') ?: 100;

	$args = [
		'billing_email' => $current_email,
		'limit' => $limit
	];

	$orders = wc_get_orders($args);
	$bigo_id_arr = [];

	if (count($orders)) {
		foreach($orders as $order) {
			$bigo_id_arr[$order->get_id()] = $order->get_meta('_billing_bigo_id');
		}
	
		$unique_bigo_ids = array_unique($bigo_id_arr);
	
		if (count($unique_bigo_ids)) {
			update_post_meta($current_order->get_id(), '_flagged_bigo_ids', implode(', ', $unique_bigo_ids) );
		}

		foreach($unique_bigo_ids as $order_id => $bigo_id)
		{
			global $wpdb;

			$table = $wpdb->prefix . "dd_fraud_bigo_id";

			// if bigo id is already verified or blocked in the db, then don't need to add it to the database
			$fetch_sql = $wpdb->prepare( "SELECT * FROM $table WHERE bigo_id = %d", $bigo_id );
			$existing_bigo_id = $wpdb->get_row($fetch_sql, ARRAY_A);
			
			if ($existing_bigo_id)
			{
				if ($existing_bigo_id['flag'] === "blocked" || $existing_bigo_id['flag'] === "verified") {
					continue;
				}
			}

			$date = date('Y-m-d h:i:s');
			$flag = "blocked";
			$notes = "Automatically blocked - used with blocked email: " . $current_email . " for Order #" . $order_id . ". Triggered by Order #" . $current_order->get_id();
			
			$sql = $wpdb->prepare( "INSERT INTO $table (bigo_id, flag, notes, created_at) VALUES (%s, %s, %s, %s) ON DUPLICATE KEY UPDATE flag = %s, notes = %s", [$bigo_id, $flag, $notes, $date, $flag, $notes]);
		
			$wpdb->get_results($sql);
		}
	}
}

function dd_flag_emails($current_order) 
{
	$current_bigo_id = $current_order->get_meta('_billing_bigo_id');
	$limit = get_option('dd_fraud_order_limit') ?: 100;

	$args = [
		'bigo_id' => $current_bigo_id,
		'limit' => $limit,
	];

	$orders = wc_get_orders($args);
	$email_arr = [];

	if (count($orders)) {
		foreach($orders as $order) {
			$email_arr[$order->get_id()] = $order->get_billing_email();
		}
	
		$unique_emails = array_unique($email_arr);
	
		if (count($unique_emails)) {
			update_post_meta($current_order->get_id(), '_flagged_emails', implode(', ', $unique_emails) );
		}

		foreach($unique_emails as $order_id => $email)
		{
			global $wpdb;

			$table = $wpdb->prefix . "dd_fraud_email";

			// if email is already blocked or verified in the db, then don't need to add it to the database
			$fetch_sql = $wpdb->prepare( "SELECT * FROM $table WHERE email = %s", $email );
			$existing_email = $wpdb->get_row($fetch_sql, ARRAY_A);
			
			if ($existing_email)
			{
				if ($existing_email['flag'] === "blocked" || $existing_email['flag'] === "verified") {
					continue;
				}
			}

			$date = date('Y-m-d h:i:s');
			$flag = "blocked";
			$notes = "Automatically blocked - used with blocked Bigo ID: " . $current_bigo_id . " for Order #" . $order_id . ". Triggered by Order #" . $current_order->get_id();
			
			$sql = $wpdb->prepare( "INSERT INTO $table (email, flag, notes, created_at) VALUES (%s, %s, %s, %s) ON DUPLICATE KEY UPDATE flag = %s, notes = %s", [$email, $flag, $notes, $date, $flag, $notes]);
		
			$wpdb->get_results($sql);
		}
	}
}

// See if order has the order meta "review_required", if it is then update the status
add_action( 'woocommerce_order_status_processing', 'dd_update_status', 1000, 1);

function dd_update_status($order_id)
{
	if ( ! $order_id ) {
		return;
	}

	$order = wc_get_order( $order_id );
	$review_required = $order->get_meta('_review_required', true);

	if ($review_required)
	{
		$order->update_status('review-required');
	}
}

function dd_add_custom_box() 	
{
	add_meta_box(
			'dd_fraud_details',      // Unique ID
			'Fraud Check Details',   // Box title
			'dd_fraud_details_html', // Content callback, must be of type callable
			'shop_order'             // Post type
	);
}

add_action( 'add_meta_boxes', 'dd_add_custom_box' );

function dd_fraud_details_html($post) {
    error_log('DD Fraud Prevention - Starting to display order details for Order #' . $post->ID);
    
    $order = wc_get_order($post->ID);
    
    // Get order details with detailed logging
    $bigo_id = $order->get_meta('_billing_bigo_id');
    error_log('DD Fraud Prevention - Retrieved Bigo ID: ' . $bigo_id);
    
    $bigo_nickname = $order->get_meta('_billing_bigo_nickname');
    error_log('DD Fraud Prevention - Retrieved Bigo Nickname: ' . $bigo_nickname);
    
    $bigo_profile_pic = $order->get_meta('_billing_bigo_profile_pic');
    error_log('DD Fraud Prevention - Retrieved Profile Picture URL: ' . $bigo_profile_pic);
    
    // Get all meta data for debugging
    $all_meta = get_post_meta($post->ID);
    error_log('DD Fraud Prevention - All order meta data: ' . print_r($all_meta, true));

    // Rest of the order details...
    $email = $order->get_billing_email();
    $first_name = $order->get_billing_first_name();
    $last_name = $order->get_billing_last_name();
    $ip_address = $order->get_meta('_customer_ip');
    $customer_name = $first_name . " " . $last_name;

    // Get fraud check data
    $fraud_check_string = $order->get_meta('_fraud_check');
    $fraud_check_arr = is_serialized($fraud_check_string) ? 
        unserialize($fraud_check_string) : 
        json_decode(stripslashes($fraud_check_string), true);

    $auto_fraud_check_string = $order->get_meta('_auto_fraud_check');
    $auto_fraud_check_arr = is_serialized($auto_fraud_check_string) ? 
        unserialize($auto_fraud_check_string) : 
        json_decode(stripslashes($auto_fraud_check_string), true);

    // Determine status and icon
    $status = 'Processing';
    $status_class = '';
    $status_icon = '';
    $status_description = '';
    
    if ($order->get_status() === "blocked") {
        $status = "Blocked";
        $status_class = "blocked";
        $status_icon = "🚫";
        $status_description = "This order has been blocked due to suspected fraudulent activity.";
    } else if ($order->get_status() === "review-required") {
        $status = "Held for Review";
        $status_class = "review";
        $status_icon = "⚠️";
        $status_description = "This order requires manual review due to suspicious activity.";
    } else if ($order->get_status() === "verified") {
        $status = "Verified";
        $status_class = "verified";
        $status_icon = "✅";
        $status_description = "This order has passed all fraud checks.";
    }

    // Log the profile picture section HTML before output
    $profile_pic_html = '';
    if ($bigo_profile_pic) {
        error_log('DD Fraud Prevention - Generating profile picture HTML with URL: ' . $bigo_profile_pic);
        $profile_pic_html = '<img src="' . esc_url($bigo_profile_pic) . '" alt="Bigo Profile Picture" style="width:100px; height:100px; border-radius:50%; object-fit:cover;">';
    } else {
        error_log('DD Fraud Prevention - No profile picture URL found, displaying placeholder');
        $profile_pic_html = '<div class="no_profile_pic" style="width:100px; height:100px; border-radius:50%; background:#f0f0f0; display:flex; align-items:center; justify-content:center;"><span style="color:#666;">No Image</span></div>';
    }
    error_log('DD Fraud Prevention - Profile picture HTML to be displayed: ' . $profile_pic_html);

    // Start output buffer to catch any potential errors
    ob_start();
    ?>
    <div class="fraud-details-container">


        <!-- Status Banner -->
        <div class="fraud-status-banner <?php echo esc_attr($status_class); ?>">
            <div class="fraud-status-icon"><?php echo $status_icon; ?></div>
            <div class="fraud-status-content">
                <h2><?php echo esc_html($status); ?></h2>
                <p><?php echo esc_html($status_description); ?></p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="fraud-quick-actions">
            <?php if ($order->get_status() !== 'blocked'): ?>
                <button type="button" class="fraud-quick-action-button block" data-order-id="<?php echo $order->get_id(); ?>">
                    <span class="dashicons dashicons-shield"></span>
                    Block Customer
                    <span class="fraud-tooltip">
                        Block this customer from placing future orders. 
                        This will affect all orders using the same email, Bigo ID, or IP address.
                        <br><kbd>Alt</kbd> + <kbd>B</kbd>
                    </span>
                </button>
            <?php else: ?>
                <button type="button" class="fraud-quick-action-button verify" data-order-id="<?php echo $order->get_id(); ?>">
                    <span class="dashicons dashicons-yes"></span>
                    Unblock Customer
                    <span class="fraud-tooltip">
                        Remove blocking restrictions from this customer. 
                        This will allow future orders from this customer.
                        <br><kbd>Alt</kbd> + <kbd>U</kbd>
                    </span>
                </button>
            <?php endif; ?>
            
            <?php if ($order->get_status() === 'review-required'): ?>
                <button type="button" class="fraud-quick-action-button verify" data-order-id="<?php echo $order->get_id(); ?>">
                    <span class="dashicons dashicons-yes-alt"></span>
                    Verify Customer
                    <span class="fraud-tooltip">
                        Mark this customer as verified. 
                        Future orders will be processed automatically.
                        <br><kbd>Alt</kbd> + <kbd>V</kbd>
                    </span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Fraud Check Details -->
        <div class="fraud-details-grid">
            <!-- Bigo ID -->
            <div class="fraud-detail-card">
                <h4>Bigo ID</h4>
                <div class="fraud-detail-value"><?php echo esc_html($bigo_id); ?></div>
                <?php if (!empty($fraud_check_arr['bigo_id'])): ?>
                    <div class="fraud-status-badge <?php echo esc_attr($fraud_check_arr['bigo_id']['flag']); ?>">
                        <?php echo esc_html(ucfirst($fraud_check_arr['bigo_id']['flag'])); ?>
                    </div>
                    <?php if (!empty($fraud_check_arr['bigo_id']['notes'])): ?>
                        <div class="fraud-notes"><?php echo wp_kses_post($fraud_check_arr['bigo_id']['notes']); ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="fraud-detail-card">
                <h4>Email</h4>
                <div class="fraud-detail-value"><?php echo esc_html($email); ?></div>
                <?php if (!empty($fraud_check_arr['email'])): ?>
                    <div class="fraud-status-badge <?php echo esc_attr($fraud_check_arr['email']['flag']); ?>">
                        <?php echo esc_html(ucfirst($fraud_check_arr['email']['flag'])); ?>
                    </div>
                    <?php if (!empty($fraud_check_arr['email']['notes'])): ?>
                        <div class="fraud-notes"><?php echo wp_kses_post($fraud_check_arr['email']['notes']); ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Customer Name -->
            <div class="fraud-detail-card">
                <h4>Customer Name</h4>
                <div class="fraud-detail-value"><?php echo esc_html($customer_name); ?></div>
                <?php if (!empty($fraud_check_arr['customer_name'])): ?>
                    <div class="fraud-status-badge <?php echo esc_attr($fraud_check_arr['customer_name']['flag']); ?>">
                        <?php echo esc_html(ucfirst($fraud_check_arr['customer_name']['flag'])); ?>
                    </div>
                    <?php if (!empty($fraud_check_arr['customer_name']['notes'])): ?>
                        <div class="fraud-notes"><?php echo wp_kses_post($fraud_check_arr['customer_name']['notes']); ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- IP Address -->
            <div class="fraud-detail-card">
                <h4>IP Address</h4>
                <div class="fraud-detail-value"><?php echo esc_html($ip_address); ?></div>
                <?php if (!empty($fraud_check_arr['ip_address'])): ?>
                    <div class="fraud-status-badge <?php echo esc_attr($fraud_check_arr['ip_address']['flag']); ?>">
                        <?php echo esc_html(ucfirst($fraud_check_arr['ip_address']['flag'])); ?>
                    </div>
                    <?php if (!empty($fraud_check_arr['ip_address']['notes'])): ?>
                        <div class="fraud-notes"><?php echo wp_kses_post($fraud_check_arr['ip_address']['notes']); ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Trigger Type - Consolidated -->
            <div class="fraud-detail-card">
                <h4>Trigger Type</h4>
                <div class="fraud-detail-value">
                    <?php
                    $trigger_types = array();
                    
                    // Check for trigger types in fraud check data
                    if (!empty($fraud_check_arr)) {
                        foreach ($fraud_check_arr as $key => $data) {
                            if (isset($data['trigger_type'])) {
                                $trigger_types[$key] = $data['trigger_type'];
                            } else {
                                // Default to manual if not specified
                                $trigger_types[$key] = 'manual';
                            }
                        }
                    }
                    
                    // If no trigger types found in fraud check, check individual flags
                    if (empty($trigger_types)) {
                        if (!empty($fraud_check_arr['bigo_id'])) {
                            $trigger_types['Bigo ID'] = isset($fraud_check_arr['bigo_id']['trigger_type']) ? 
                                $fraud_check_arr['bigo_id']['trigger_type'] : 'manual';
                        }
                        if (!empty($fraud_check_arr['email'])) {
                            $trigger_types['Email'] = isset($fraud_check_arr['email']['trigger_type']) ? 
                                $fraud_check_arr['email']['trigger_type'] : 'manual';
                        }
                        if (!empty($fraud_check_arr['customer_name'])) {
                            $trigger_types['Customer Name'] = isset($fraud_check_arr['customer_name']['trigger_type']) ? 
                                $fraud_check_arr['customer_name']['trigger_type'] : 'manual';
                        }
                        if (!empty($fraud_check_arr['ip_address'])) {
                            $trigger_types['IP Address'] = isset($fraud_check_arr['ip_address']['trigger_type']) ? 
                                $fraud_check_arr['ip_address']['trigger_type'] : 'manual';
                        }
                    }

                    if (!empty($trigger_types)) {
                        echo '<div class="trigger-type-details">';
                        foreach ($trigger_types as $type => $trigger) {
                            $class = $trigger === 'automatic' ? 'auto-trigger' : 'manual-trigger';
                            echo '<div class="trigger-type-item">';
                            echo '<span class="trigger-type-label">' . esc_html($type) . ':</span> ';
                            echo '<span class="' . esc_attr($class) . '">' . esc_html(ucfirst($trigger)) . '</span>';
                            echo '</div>';
                        }
                        echo '</div>';
                    } else {
                        // If still no trigger types found, show default values
                        echo '<div class="trigger-type-details">';
                        echo '<div class="trigger-type-item">';
                        echo '<span class="trigger-type-label">Bigo ID:</span> ';
                        echo '<span class="manual-trigger">Manual</span>';
                        echo '</div>';
                        echo '<div class="trigger-type-item">';
                        echo '<span class="trigger-type-label">Email:</span> ';
                        echo '<span class="manual-trigger">Manual</span>';
                        echo '</div>';
                        echo '<div class="trigger-type-item">';
                        echo '<span class="trigger-type-label">Customer Name:</span> ';
                        echo '<span class="manual-trigger">Manual</span>';
                        echo '</div>';
                        echo '<div class="trigger-type-item">';
                        echo '<span class="trigger-type-label">IP Address:</span> ';
                        echo '<span class="manual-trigger">Manual</span>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

    </div>
            <div class="">
                    <!-- Discrepancies Section -->
                    <?php if (!empty($discrepancies)): ?>
                <div class="fraud_section">
                    <h3>Discrepancies Found in Last <?php echo esc_html(get_option('dd_fraud_order_limit', '100')); ?> Orders</h3>
                    <div class="discrepancies_grid">
                        <?php if (!empty($discrepancies['emails'])): ?>
                        <div class="discrepancy_card">
                            <h4>Different Emails Used</h4>
                            <ul>
                                <?php foreach($discrepancies['emails'] as $email): ?>
                                    <li><?php echo esc_html($email); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($discrepancies['names'])): ?>
                        <div class="discrepancy_card">
                            <h4>Different Names Used</h4>
                            <ul>
                                <?php foreach($discrepancies['names'] as $name): ?>
                                    <li><?php echo esc_html($name); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($discrepancies['addresses'])): ?>
                        <div class="discrepancy_card">
                            <h4>Different Addresses Used</h4>
                            <ul>
                                <?php foreach($discrepancies['addresses'] as $address): ?>
                                    <li><?php echo esc_html($address); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Flagged Items -->
                <?php if (!empty($flagged_bigo_ids) || !empty($flagged_emails) || !empty($flagged_ips)): ?>
                <div class="fraud_section">
                    <h3>Flagged Items</h3>
                    <div class="flagged_items_table_container">
                        <table class="flagged_items_table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Current Value</th>
                                    <th>Previous Values</th>
                                    <th>Issue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($flagged_bigo_ids)): 
                                    $bigo_ids = explode(', ', $flagged_bigo_ids);
                                    $current_bigo_id = $bigo_ids[0];
                                    $previous_bigo_ids = array_slice($bigo_ids, 1);
                                ?>
                                <tr>
                                    <td>Bigo ID</td>
                                    <td><?php echo esc_html($current_bigo_id); ?></td>
                                    <td>
                                        <?php if (!empty($previous_bigo_ids)): ?>
                                            <ul class="previous-values-list">
                                                <?php foreach ($previous_bigo_ids as $previous_id): ?>
                                                    <li><?php echo esc_html($previous_id); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span class="no-previous-values">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Multiple Bigo IDs used across orders</td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if (!empty($flagged_emails)): 
                                    $emails = explode(', ', $flagged_emails);
                                    $current_email = $emails[0];
                                    $previous_emails = array_slice($emails, 1);
                                ?>
                                <tr>
                                    <td>Email</td>
                                    <td><?php echo esc_html($current_email); ?></td>
                                    <td>
                                        <?php if (!empty($previous_emails)): ?>
                                            <ul class="previous-values-list">
                                                <?php foreach ($previous_emails as $previous_email): ?>
                                                    <li><?php echo esc_html($previous_email); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span class="no-previous-values">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Multiple emails used across orders</td>
                                </tr>
                                <?php endif; ?>

                                <?php if (!empty($flagged_ips)): 
                                    $ips = explode(', ', $flagged_ips);
                                    $current_ip = $ips[0];
                                    $previous_ips = array_slice($ips, 1);
                                ?>
                                <tr>
                                    <td>IP Address</td>
                                    <td><?php echo esc_html($current_ip); ?></td>
                                    <td>
                                        <?php if (!empty($previous_ips)): ?>
                                            <ul class="previous-values-list">
                                                <?php foreach ($previous_ips as $previous_ip): ?>
                                                    <li><?php echo esc_html($previous_ip); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span class="no-previous-values">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Multiple IP addresses used across orders</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                        <!-- Bigo User Profile Section -->
                <?php if ($bigo_id): ?>
                <div class="fraud_section">
                    <h3>Bigo User Profile</h3>
                    <div class="bigo_profile_card">
                        <div class="bigo_profile_pic">
                            <?php 
                            echo $profile_pic_html;
                            error_log('DD Fraud Prevention - Profile picture HTML output completed');
                            ?>
                        </div>
                        <div class="bigo_profile_info">
                            <h4>Bigo ID: <?php echo esc_html($bigo_id); ?></h4>
                            <?php if ($bigo_nickname): ?>
                            <p><strong>Nickname:</strong> <?php echo esc_html($bigo_nickname); ?></p>
                            <?php endif; ?>
                            <p><a href="https://www.bigo.tv/user/<?php echo esc_attr($bigo_id); ?>" target="_blank" class="button button-secondary">View Profile on Bigo</a></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

    <style>
    .fraud-details-container {
        padding: 20px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .fraud-quick-actions {
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 4px;
        display: flex;
        gap: 10px;
    }

    .fraud-quick-actions .fraud-quick-action-button {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .fraud-quick-actions .fraud-quick-action-button.block {
        background: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }

    .fraud-quick-actions .fraud-quick-action-button.block:hover {
        background: #c82333;
        border-color: #bd2130;
    }

    .fraud-quick-actions .fraud-quick-action-button.verify {
        background: #28a745;
        border-color: #28a745;
        color: #fff;
    }

    .fraud-quick-actions .fraud-quick-action-button.verify:hover {
        background: #218838;
        border-color: #1e7e34;
    }

    .fraud-status-banner {
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .fraud-status-banner.blocked {
        background: #ffebee;
        border: 1px solid #ffcdd2;
    }

    .fraud-status-banner.review {
        background: #fff3e0;
        border: 1px solid #ffe0b2;
    }

    .fraud-status-banner.verified {
        background: #e8f5e9;
        border: 1px solid #c8e6c9;
    }

    .fraud-status-icon {
        font-size: 32px;
    }

    .fraud-status-content h2 {
        margin: 0;
        font-size: 24px;
    }

    .fraud-status-content p {
        margin: 5px 0 0;
        color: #666;
    }

    .fraud-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }

    .fraud-detail-card {
        background: #fff;
        padding: 15px;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .fraud-detail-card h4 {
        margin: 0 0 10px 0;
        color: #666;
    }

    .fraud-detail-value {
        font-weight: bold;
        margin-bottom: 10px;
    }

    .fraud-status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .fraud-status-badge.blocked {
        background: #ffebee;
        color: #c62828;
    }

    .fraud-status-badge.review {
        background: #fff3e0;
        color: #ef6c00;
    }

    .fraud-status-badge.verified {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .fraud-notes {
        margin-top: 10px;
        font-size: 12px;
        color: #666;
        font-style: italic;
    }

    .discrepancies_grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }

    .discrepancy_card {
        background: #fff;
        padding: 15px;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .discrepancy_card h4 {
        margin: 0 0 10px 0;
        color: #666;
    }

    .discrepancy_card ul {
        margin: 0;
        padding-left: 20px;
    }

    .discrepancy_card li {
        margin: 5px 0;
        color: #666;
    }

    .flagged_items_table_container {
        overflow-x: auto;
        margin-top: 15px;
    }

    .flagged_items_table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .flagged_items_table th,
    .flagged_items_table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }

    .flagged_items_table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .flagged_items_table tr:last-child td {
        border-bottom: none;
    }

    .flagged_items_table tr:hover {
        background-color: #f5f5f5;
    }

    .flagged_items_table td:nth-child(1) {
        font-weight: 600;
        color: #333;
    }

    .flagged_items_table td:nth-child(2) {
        font-weight: 500;
    }

    .flagged_items_table td:nth-child(4) {
        color: #d32f2f;
        font-style: italic;
    }

    .previous-values-list {
        margin: 0;
        padding-left: 20px;
        list-style-type: disc;
    }

    .previous-values-list li {
        margin: 3px 0;
        color: #666;
    }

    .no-previous-values {
        color: #999;
        font-style: italic;
    }

    .fraud_section {
        margin-top: 20px;
        display: block;
        width: 100%;
        clear: both;
    }

    .fraud_section h3 {
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e0e0e0;
    }

    .bigo_profile_card {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-top: 15px;
    }

    .bigo_profile_pic {
        flex-shrink: 0;
    }

    .bigo_profile_info {
        flex: 1;
    }

    .bigo_profile_info h4 {
        margin: 0 0 10px 0;
        color: #333;
    }

    .bigo_profile_info p {
        margin: 5px 0;
        color: #666;
    }

    .bigo_profile_info .button {
        margin-top: 10px;
    }

    .no_profile_pic {
        border: 1px solid #ddd;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Only process if no input/textarea is focused
            if ($('input:focus, textarea:focus').length) {
                return;
            }
            
            // Alt + B: Block Customer
            if (e.altKey && e.key.toLowerCase() === 'b') {
                $('.fraud-quick-action-button.block').click();
            }
            // Alt + U: Unblock Customer
            if (e.altKey && e.key.toLowerCase() === 'u') {
                $('.fraud-quick-action-button.verify:contains("Unblock")').click();
            }
            // Alt + V: Verify Customer
            if (e.altKey && e.key.toLowerCase() === 'v') {
                $('.fraud-quick-action-button.verify:contains("Verify")').click();
            }
        });

        // Block Customer
        $('.fraud-quick-action-button.block').on('click', function() {
            if (!confirm('Are you sure you want to block this customer?\n\nThis will:\n- Prevent future orders\n- Flag associated email and Bigo ID\n- Block the IP address')) {
                return;
            }
            
            var orderId = $(this).data('order-id');
            $.post(ajaxurl, {
                action: 'dd_block_customer',
                order_id: orderId,
                nonce: '<?php echo wp_create_nonce('dd_fraud_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error blocking customer: ' + response.data);
                }
            });
        });

        // Verify/Unblock Customer
        $('.fraud-quick-action-button.verify').on('click', function() {
            var isUnblock = $(this).text().trim().startsWith('Unblock');
            var confirmMessage = isUnblock ? 
                'Are you sure you want to unblock this customer?\n\nThis will:\n- Allow future orders\n- Remove blocking flags\n- Enable normal order processing' :
                'Are you sure you want to verify this customer?\n\nThis will:\n- Mark the customer as verified\n- Allow future orders\n- Enable automatic processing';

            if (!confirm(confirmMessage)) {
                return;
            }
            
            var orderId = $(this).data('order-id');
            $.post(ajaxurl, {
                action: isUnblock ? 'dd_unblock_customer' : 'dd_verify_customer',
                order_id: orderId,
                nonce: '<?php echo wp_create_nonce('dd_fraud_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error ' + (isUnblock ? 'unblocking' : 'verifying') + ' customer: ' + response.data);
                }
            });
        });
    });
    </script>
    <?php
    $output = ob_get_clean();
    error_log('DD Fraud Prevention - Order details display completed');
    echo $output;
}

add_action( 'admin_post_dd_import', 'dd_import' );
 
function dd_import() 
{
	global $wpdb;

	$file_info = pathinfo($_FILES['upload']['name']);
	
	if ($file_info['extension'] !== 'csv')
	{
		echo "<p>The uploaded file is not a CSV file.</p>";
		exit();
	}
		
	if($_FILES['upload']['name']) 
	{
		if(!$_FILES['upload']['error']) 
		{
			$rows = array_map('str_getcsv', file($_FILES['upload']['tmp_name']));
			$header = array_shift($rows);
			$header = array_map('trim', $header);
			
			$csv = array();
			$error = array();
			
			foreach ($rows as $row) {
				if (count($header) === count($row))
				{
					$csv[] = array_combine($header, $row);
				}
				else
				{
					$error[] = $row[0];
				}
			}
	
			$type = $header[0];
			$accepted_types = ['bigo_id', 'email', 'customer_name', 'ip_address'];
	
			if (!in_array($type, $accepted_types))
			{
				echo "<p>CSV must contain bigo_id, email, customer_name or ip as one of its headers.</p>";
				exit();
			}
	
			$table = $wpdb->prefix . 'dd_fraud_' . $type;
	
			foreach ($csv as $data)
			{
				$data = array_map('trim', $data);
				$data['flag'] = strtolower($data['flag']);
	
				if ($data['flag'] === "delete")
				{
					$sql = $wpdb->prepare( "DELETE FROM $table WHERE {$type} = %s", $data[$type]);
				}
				else if ($data['flag'] === "review" || $data['flag'] === "verified" || $data['flag'] === "blocked")
				{
					$date = date('Y-m-d h:i:s');
					$sql = $wpdb->prepare( "INSERT INTO $table ({$type}, flag, notes, created_at) VALUES (%s, %s, %s, %s) ON DUPLICATE KEY UPDATE flag = %s, notes = %s", [$data[$type], $data['flag'], $data['notes'], $date, $data['flag'], $data['notes']]);
				}
	
				$results = $wpdb->get_results($sql);
			}
		}
	}

	echo "<p>Import $type processed</p>";
	echo "<p>Errors for rows: " . implode( ", ", $error) . "</p>";
}

add_action( 'admin_post_dd_export', 'dd_export' );

function dd_export()
{
	global $wpdb;
	if (!current_user_can( "administrator" ))
	{
		header("Location:" . wp_login_url());
		exit();
	}

	if (!isset($_POST['type']))
	{
		exit();
	}

	$type = $_POST['type'];

	header("Content-Type: text/csv; charset=utf-8");
	header("Content-Disposition: attachment; filename=$type.csv");  
	$output = fopen("php://output", "w");  
	fputcsv($output, array($type, "flag", "notes"));
	$table = $wpdb->prefix . "dd_fraud_" . $type;
	$sql = "SELECT $type, flag, notes from $table";
	$rows = $wpdb->get_results($sql, ARRAY_A);

	foreach($rows as $row)
	{
		fputcsv($output, $row);
	}

	fclose($output);

	return $ouput;
}


add_action( 'admin_post_dd_add_entry', 'dd_add_entry' );

function dd_add_entry()
{
	global $wpdb;
	$type = $_POST['type'];
	$table = $wpdb->prefix . 'dd_fraud_' . $type;
	$entry = sanitize_text_field($_POST['entry']);
	$notes = sanitize_textarea_field($_POST['notes']);

	$date = date('Y-m-d h:i:s');
	$current_user = wp_get_current_user();
	$admin_user = $current_user->user_login;

	$trigger_type = 'manual'; // Default to manual

	// Example logic to determine if the block is automatic
	if ($is_automatic) {
		$trigger_type = 'automatic';
	}

	$sql = $wpdb->prepare(
		"INSERT INTO $table ({$type}, flag, notes, created_at, admin_user, trigger_type) VALUES (%s, %s, %s, %s, %s, %s) ON DUPLICATE KEY UPDATE flag = %s, notes = %s, admin_user = %s, trigger_type = %s",
		[$entry, $_POST['flag'], $notes, $date, $admin_user, $trigger_type, $_POST['flag'], $notes, $admin_user, $trigger_type]
	);

	$added = $wpdb->get_results($sql);

	// Log the manual entry
	$logger = new DD_Fraud_Logger();
	$logger->log(
		'Manual Entry Added',
		sprintf(
			'Added %s entry: %s with flag: %s. Notes: %s',
			$type,
			$entry,
			$_POST['flag'],
			$notes
		)
	);

	wp_safe_redirect( esc_url_raw( add_query_arg( array( 'added' => $added ), admin_url( 'admin.php?page=dd_fraud_' . $type ) ) ) );
	exit();
}

/**
 * Handle a custom 'bigo_id' query var to get orders with the 'bigo_id' meta.
 * @param array $query - Args for WP_Query.
 * @param array $query_vars - Query vars from WC_Order_Query.
 * @return array modified $query
 */
function dd_handle_bigo_id_query_var( $query, $query_vars ) {
	if ( ! empty( $query_vars['bigo_id'] ) ) {
		$query['meta_query'][] = array(
			'key' => '_billing_bigo_id',
			'value' => esc_attr( $query_vars['bigo_id'] ),
		);
	}

	return $query;
}
add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'dd_handle_bigo_id_query_var', 10, 2 );

function woocommerce_shop_order_search_bigo_id( $search_fields ) {

  $search_fields[] = '_billing_bigo_id';

  return $search_fields;
}
add_filter( 'woocommerce_shop_order_search_fields', 'woocommerce_shop_order_search_bigo_id' );

// Register settings
function dd_register_settings() {
    // Debug output
    error_log('Registering DD Fraud Prevention settings');
    
    register_setting('dd_fraud_options_group', 'dd_fraud_order_limit');
    register_setting('dd_fraud_options_group', 'dd_fraud_match_threshold');
    register_setting('dd_fraud_options_group', 'dd_fraud_auto_block');
    register_setting('dd_fraud_options_group', 'dd_fraud_vpn_block');
    register_setting('dd_fraud_options_group', 'dd_fraud_past_orders_check');
    
    // Debug output
    error_log('DD Fraud Prevention settings registered');
}
add_action('admin_init', 'dd_register_settings');

// Add AJAX handlers for quick actions
add_action('wp_ajax_dd_block_customer', 'dd_block_customer_ajax');
add_action('wp_ajax_dd_unblock_customer', 'dd_unblock_customer_ajax');
add_action('wp_ajax_dd_verify_customer', 'dd_verify_customer_ajax');

function dd_block_customer_ajax() {
    check_ajax_referer('dd_fraud_nonce', 'nonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Insufficient permissions');
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('Order not found');
    }

    // Block the customer
    $order->update_status('blocked');
    dd_record_block_details($order, 'manual', 'Blocked by administrator');
    
    // Add to blocked list
    global $wpdb;
    $bigo_id = $order->get_meta('_billing_bigo_id');
    $email = $order->get_billing_email();
    $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $ip_address = $order->get_meta('_customer_ip');

    // Block Bigo ID
    if ($bigo_id) {
        $bigo_table = $wpdb->prefix . 'dd_fraud_bigo_id';
        $wpdb->insert($bigo_table, array(
            'bigo_id' => $bigo_id,
            'flag' => 'blocked',
            'notes' => 'Manually blocked from order #' . $order_id,
            'created_at' => current_time('mysql'),
            'admin_user' => $admin_user,
            'trigger_type' => 'manual'
        ));
    }

    // Block Email
    if ($email) {
        $email_table = $wpdb->prefix . 'dd_fraud_email';
        $wpdb->insert($email_table, array(
            'email' => $email,
            'flag' => 'blocked',
            'notes' => 'Manually blocked from order #' . $order_id,
            'created_at' => current_time('mysql'),
            'admin_user' => $admin_user,
            'trigger_type' => 'manual'
        ));
    }

    // Block Name
    if ($name) {
        $name_table = $wpdb->prefix . 'dd_fraud_customer_name';
        $wpdb->insert($name_table, array(
            'customer_name' => $name,
            'flag' => 'blocked',
            'notes' => 'Manually blocked from order #' . $order_id,
            'created_at' => current_time('mysql'),
            'admin_user' => $admin_user,
            'trigger_type' => 'manual'
        ));
    }

    // Block IP
    if ($ip_address && $ip_address !== 'UNKNOWN') {
        $ip_table = $wpdb->prefix . 'dd_fraud_ip';
        $wpdb->insert($ip_table, array(
            'ip_address' => $ip_address,
            'flag' => 'blocked',
            'notes' => 'Manually blocked from order #' . $order_id,
            'created_at' => current_time('mysql'),
            'admin_user' => $admin_user,
            'trigger_type' => 'manual'
        ));
    }

    wp_send_json_success('Customer blocked successfully');
}

function dd_unblock_customer_ajax() {
    check_ajax_referer('dd_fraud_nonce', 'nonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Insufficient permissions');
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('Order not found');
    }

    // Unblock the customer
    $order->update_status('processing');
    
    // Remove from blocked list
    global $wpdb;
    $bigo_id = $order->get_meta('_billing_bigo_id');
    $email = $order->get_billing_email();
    $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $ip_address = $order->get_meta('_customer_ip');

    // Unblock Bigo ID
    if ($bigo_id) {
        $bigo_table = $wpdb->prefix . 'dd_fraud_bigo_id';
        $wpdb->delete($bigo_table, array('bigo_id' => $bigo_id));
    }

    // Unblock Email
    if ($email) {
        $email_table = $wpdb->prefix . 'dd_fraud_email';
        $wpdb->delete($email_table, array('email' => $email));
    }

    // Unblock Name
    if ($name) {
        $name_table = $wpdb->prefix . 'dd_fraud_customer_name';
        $wpdb->delete($name_table, array('customer_name' => $name));
    }

    // Unblock IP
    if ($ip_address && $ip_address !== 'UNKNOWN') {
        $ip_table = $wpdb->prefix . 'dd_fraud_ip';
        $wpdb->delete($ip_table, array('ip_address' => $ip_address));
    }

    wp_send_json_success('Customer unblocked successfully');
}

function dd_verify_customer_ajax() {
    check_ajax_referer('dd_fraud_nonce', 'nonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Insufficient permissions');
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('Order not found');
    }

    // Verify the customer
    $order->update_status('processing');
    
    // Add to verified list
    global $wpdb;
    $bigo_id = $order->get_meta('_billing_bigo_id');
    $email = $order->get_billing_email();
    $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $ip_address = $order->get_meta('_customer_ip');

    // Verify Bigo ID
    if ($bigo_id) {
        $bigo_table = $wpdb->prefix . 'dd_fraud_bigo_id';
        $wpdb->insert($bigo_table, array(
            'bigo_id' => $bigo_id,
            'flag' => 'verified',
            'notes' => 'Manually verified from order #' . $order_id,
            'created_at' => current_time('mysql'),
            'admin_user' => $admin_user,
            'trigger_type' => 'manual'
        ));
    }

    // Verify Email
    if ($email) {
        $email_table = $wpdb->prefix . 'dd_fraud_email';
        $wpdb->insert($email_table, array(
            'email' => $email,
            'flag' => 'verified',
            'notes' => 'Manually verified from order #' . $order_id,
            'created_at' => current_time('mysql'),
            'admin_user' => $admin_user,
            'trigger_type' => 'manual'
        ));
    }

    // Verify Name
    if ($name) {
        $name_table = $wpdb->prefix . 'dd_fraud_customer_name';
        $wpdb->insert($name_table, array(
            'customer_name' => $name,
            'flag' => 'verified',
            'notes' => 'Manually verified from order #' . $order_id,
            'created_at' => current_time('mysql'),
            'admin_user' => $admin_user,
            'trigger_type' => 'manual'
        ));
    }

    // Verify IP
    if ($ip_address && $ip_address !== 'UNKNOWN') {
        $ip_table = $wpdb->prefix . 'dd_fraud_ip';
        $wpdb->insert($ip_table, array(
            'ip_address' => $ip_address,
            'flag' => 'verified',
            'notes' => 'Manually verified from order #' . $order_id,
            'created_at' => current_time('mysql'),
            'admin_user' => $admin_user,
            'trigger_type' => 'manual'
        ));
    }

    wp_send_json_success('Customer verified successfully');
}

/**
 * Add auto-refund settings to the plugin
 */
function dd_add_auto_refund_settings() {
    register_setting('dd_fraud_settings', 'dd_auto_refund_enabled');
    register_setting('dd_fraud_settings', 'dd_auto_refund_reason');
    
    add_settings_field(
        'dd_auto_refund_enabled',
        'Enable Auto-Refund',
        'dd_auto_refund_enabled_callback',
        'dd_fraud_settings',
        'dd_fraud_general_section'
    );
    
    add_settings_field(
        'dd_auto_refund_reason',
        'Refund Reason',
        'dd_auto_refund_reason_callback',
        'dd_fraud_settings',
        'dd_fraud_general_section'
    );
}
add_action('admin_init', 'dd_add_auto_refund_settings');

function dd_auto_refund_enabled_callback() {
    $enabled = get_option('dd_auto_refund_enabled', '0');
    echo '<input type="checkbox" name="dd_auto_refund_enabled" value="1" ' . checked(1, $enabled, false) . ' />';
    echo '<p class="description">Automatically refund orders that are blocked by the fraud prevention system.</p>';
}

function dd_auto_refund_reason_callback() {
    $reason = get_option('dd_auto_refund_reason', 'Order blocked by fraud prevention system');
    echo '<input type="text" name="dd_auto_refund_reason" value="' . esc_attr($reason) . '" class="regular-text" />';
    echo '<p class="description">The reason that will be shown to customers for the refund.</p>';
}

/**
 * Handle auto-refund for blocked orders
 */
function dd_handle_auto_refund($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    // Only process if order is blocked
    if ($order->get_status() !== 'blocked') {
        return;
    }

    // Check if auto-refund is enabled
    if (get_option('dd_auto_refund_enabled', '0') !== '1') {
        return;
    }

    // Check if order was already refunded
    if ($order->get_meta('_auto_refunded', true)) {
        return;
    }

    try {
        // Get the refund reason from settings
        $refund_reason = get_option('dd_auto_refund_reason', 'Order blocked by fraud prevention system');
        
        // Process the refund
        $refund = wc_create_refund(array(
            'order_id' => $order_id,
            'amount' => $order->get_total(),
            'reason' => $refund_reason,
            'refunded_by' => get_current_user_id(),
            'refund_payment' => true,
            'restock_items' => true,
        ));

        if (is_wp_error($refund)) {
            throw new Exception($refund->get_error_message());
        }

        // Mark order as auto-refunded
        $order->update_meta_data('_auto_refunded', true);
        $order->save();

        // Add note to order
        $order->add_order_note(sprintf(
            'Order automatically refunded due to fraud prevention. Refund ID: %s',
            $refund->get_id()
        ));

    } catch (Exception $e) {
        // Log the error
        error_log('DD Fraud Prevention - Auto-refund failed for order ' . $order_id . ': ' . $e->getMessage());
        
        // Add note to order about failed refund
        $order->add_order_note('Auto-refund failed: ' . $e->getMessage());
    }
}

// Hook into order status changes to trigger auto-refund
add_action('woocommerce_order_status_changed', 'dd_handle_auto_refund', 10, 3);

// Add auto-refund status to order actions
add_filter('woocommerce_order_actions', 'dd_add_auto_refund_order_action');
function dd_add_auto_refund_order_action($actions) {
    $actions['dd_manual_auto_refund'] = array(
        'url'    => wp_nonce_url(admin_url('admin-post.php?action=dd_manual_auto_refund&order_id=' . get_the_ID()), 'dd-manual-auto-refund'),
        'name'   => __('Process Auto-Refund', 'dd-fraud-prevention'),
        'action' => 'dd-manual-auto-refund'
    );
    return $actions;
}

// Handle manual auto-refund action
add_action('admin_post_dd_manual_auto_refund', 'dd_process_manual_auto_refund');
function dd_process_manual_auto_refund() {
    if (!current_user_can('edit_shop_orders')) {
        wp_die(__('You do not have permission to do this.', 'dd-fraud-prevention'));
    }

    $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
    if (!$order_id) {
        wp_die(__('No order ID provided.', 'dd-fraud-prevention'));
    }

    check_admin_referer('dd-manual-auto-refund');

    // Check if auto-refund is enabled
    if (get_option('dd_auto_refund_enabled', '0') !== '1') {
        wp_die(__('Auto-refund is currently disabled. Please enable it in the settings first.', 'dd-fraud-prevention'));
    }

    dd_handle_auto_refund($order_id);

    wp_redirect(wp_get_referer() ?: admin_url());
    exit;
}

// Function to record block details
function dd_record_block_details($order, $block_type, $reason) {
    // Record block details in order meta
    $order->update_meta_data('_dd_block_type', $block_type); // 'auto' or 'manual'
    $order->update_meta_data('_dd_block_reason', $reason);
    $order->update_meta_data('_dd_block_date', current_time('mysql'));
    $order->update_meta_data('_dd_blocked_by', $block_type === 'manual' ? get_current_user_id() : 'system');
    
    // Add a note to the order
    $note = sprintf(
        'Order blocked: %s. Reason: %s',
        $block_type === 'auto' ? 'Automatically by the fraud prevention system' : 'Manually by an administrator',
        $reason
    );
    $order->add_order_note($note);
    
    $order->save();
}

// Add Bigo user info fetching function
function dd_get_bigo_user_info($bigo_id) {
    // Bigo User URL
    $url = "https://www.bigo.tv/user/" . $bigo_id;

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36");

    // Execute cURL request
    $html_content = curl_exec($ch);

    if(curl_errno($ch)) {
        curl_close($ch);
        return array('error' => 'Failed to fetch user information');
    }

    curl_close($ch);

    if (empty($html_content)) {
        return array('error' => 'No content returned');
    }

    // Load HTML content
    $dom = new DOMDocument();
    @$dom->loadHTML($html_content);
    $xpath = new DOMXPath($dom);

    // Find nickname and profile picture
    $nickname = "";
    $profile_pic = "";

    // XPath for nickname
    $nickname_elements = $xpath->query("//h1[@class='nickName']");
    // XPath for profile picture
    $profile_pic_elements = $xpath->query("//div[@class='img-preview']/img[@class='img']");

    if ($nickname_elements->length > 0) {
        $nickname = trim($nickname_elements->item(0)->textContent);
    }

    if ($profile_pic_elements->length > 0) {
        $profile_pic = $profile_pic_elements->item(0)->getAttribute('src');
    }

    if ($nickname && $profile_pic) {
        return array(
            'success' => true,
            'nickname' => $nickname,
            'profile_pic' => $profile_pic
        );
    } else {
        return array('error' => 'User information not found');
    }
}

// Add AJAX handler for fetching Bigo user info
add_action('wp_ajax_dd_get_bigo_user_info', 'dd_get_bigo_user_info_ajax');
add_action('wp_ajax_nopriv_dd_get_bigo_user_info', 'dd_get_bigo_user_info_ajax');
function dd_get_bigo_user_info_ajax() {
    check_ajax_referer('dd_bigo_user_info_nonce', 'nonce');
    
    $bigo_id = isset($_POST['bigo_id']) ? sanitize_text_field($_POST['bigo_id']) : '';
    
    if (empty($bigo_id)) {
        wp_send_json_error('Bigo ID is required');
    }
    
    error_log('DD Fraud Prevention - Fetching Bigo user info for ID: ' . $bigo_id);
    
    $user_info = dd_get_bigo_user_info($bigo_id);
    
    error_log('DD Fraud Prevention - Bigo API Response: ' . print_r($user_info, true));
    
    if (isset($user_info['error'])) {
        error_log('DD Fraud Prevention - Error fetching user info: ' . $user_info['error']);
        wp_send_json_error($user_info['error']);
    } else {
        error_log('DD Fraud Prevention - Successfully fetched user info with profile pic: ' . $user_info['profile_pic']);
        wp_send_json_success($user_info);
    }
}

// Add hidden fields for storing Bigo user info
function dd_add_bigo_hidden_fields($checkout = null) {
    // Add nonce for security
    wp_nonce_field('dd_bigo_user_info', 'dd_bigo_user_info_nonce');
    
    echo '<div id="bigo-user-info" style="display:none; margin-top:10px;"></div>';
    
    if ($checkout instanceof WC_Checkout) {
        woocommerce_form_field('billing_bigo_nickname', array(
            'type' => 'hidden',
            'class' => array('bigo-user-field'),
        ), $checkout->get_value('billing_bigo_nickname'));
        
        woocommerce_form_field('billing_bigo_profile_pic', array(
            'type' => 'hidden',
            'class' => array('bigo-user-field'),
        ), $checkout->get_value('billing_bigo_profile_pic'));
    } else {
        echo '<input type="hidden" id="billing_bigo_nickname" name="billing_bigo_nickname" value="" />';
        echo '<input type="hidden" id="billing_bigo_profile_pic" name="billing_bigo_profile_pic" value="" />';
    }
}

// Update the action hook
remove_action('woocommerce_after_checkout_form', 'dd_add_bigo_hidden_fields');
add_action('woocommerce_after_checkout_billing_form', 'dd_add_bigo_hidden_fields');

// Add JavaScript to handle Bigo user info
add_action('wp_footer', 'dd_add_bigo_checkout_js');
function dd_add_bigo_checkout_js() {
    if (!is_checkout()) return;
    ?>
    <script type="text/javascript">
    jQuery(function($) {
        var bigoUserInfo = null;
        
        $('#billing_bigo_id').on('blur', function() {
            var bigoId = $(this).val();
            if (!bigoId) {
                $('#bigo-user-info').hide();
                clearBigoUserInfo();
                return;
            }
            
            $('#bigo-user-info').html('<p>Loading user information...</p>').show();
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'dd_get_bigo_user_info',
                    bigo_id: bigoId,
                    nonce: '<?php echo wp_create_nonce('dd_bigo_user_info_nonce'); ?>'
                },
                success: function(response) {
                    console.log('Bigo API Response:', response);
                    
                    if (response.success) {
                        bigoUserInfo = response.data;
                        updateBigoUserInfo(bigoUserInfo);
                    } else {
                        console.error('Error:', response.data);
                        $('#bigo-user-info').html('<p style="color:red;">' + response.data + '</p>');
                        clearBigoUserInfo();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    $('#bigo-user-info').html('<p style="color:red;">Error connecting to server</p>');
                    clearBigoUserInfo();
                }
            });
        });

        function updateBigoUserInfo(userInfo) {
            var html = '<div style="display:flex; align-items:center;">';
            if (userInfo.profile_pic) {
                html += '<img src="' + userInfo.profile_pic + '" alt="Profile Picture" style="width:50px; height:50px; border-radius:50%; margin-right:10px;">';
            }
            html += '<div>';
            html += '<p style="margin:0; font-weight:bold;">' + userInfo.nickname + '</p>';
            html += '<p style="margin:0; color:#666;">ID: ' + $('#billing_bigo_id').val() + '</p>';
            html += '</div></div>';
            
            $('#bigo-user-info').html(html).show();
            
            // Update hidden fields
            $('input[name="billing_bigo_nickname"]').val(userInfo.nickname);
            $('input[name="billing_bigo_profile_pic"]').val(userInfo.profile_pic);
            
            console.log('Updated hidden fields:', {
                nickname: $('input[name="billing_bigo_nickname"]').val(),
                profile_pic: $('input[name="billing_bigo_profile_pic"]').val()
            });
        }

        function clearBigoUserInfo() {
            bigoUserInfo = null;
            $('input[name="billing_bigo_nickname"]').val('');
            $('input[name="billing_bigo_profile_pic"]').val('');
        }

        // Ensure Bigo user info is set before form submission
        $('form.checkout').on('checkout_place_order', function() {
            if (bigoUserInfo) {
                $('input[name="billing_bigo_nickname"]').val(bigoUserInfo.nickname);
                $('input[name="billing_bigo_profile_pic"]').val(bigoUserInfo.profile_pic);
                
                console.log('Form submission - Bigo user info:', {
                    nickname: $('input[name="billing_bigo_nickname"]').val(),
                    profile_pic: $('input[name="billing_bigo_profile_pic"]').val()
                });
            }
            return true;
        });
    });
    </script>
    <?php
}

// Save Bigo user info to order
add_action('woocommerce_checkout_update_order_meta', 'dd_save_bigo_user_info');
function dd_save_bigo_user_info($order_id) {
    error_log('DD Fraud Prevention - Starting to save Bigo user info for order: ' . $order_id);
    error_log('DD Fraud Prevention - POST data: ' . print_r($_POST, true));
    
    $bigo_id = isset($_POST['billing_bigo_id']) ? sanitize_text_field($_POST['billing_bigo_id']) : '';
    
    if ($bigo_id) {
        // Try to get cached user info first
        $user_info = get_transient('dd_bigo_user_' . $bigo_id);
        
        if ($user_info) {
            error_log('DD Fraud Prevention - Found cached Bigo user info: ' . print_r($user_info, true));
            update_post_meta($order_id, '_billing_bigo_nickname', sanitize_text_field($user_info['nickname']));
            update_post_meta($order_id, '_billing_bigo_profile_pic', esc_url_raw($user_info['profile_pic']));
        } else {
            // Fallback to POST data
            if (!empty($_POST['billing_bigo_nickname'])) {
                update_post_meta($order_id, '_billing_bigo_nickname', sanitize_text_field($_POST['billing_bigo_nickname']));
            }
            if (!empty($_POST['billing_bigo_profile_pic'])) {
                update_post_meta($order_id, '_billing_bigo_profile_pic', esc_url_raw($_POST['billing_bigo_profile_pic']));
            }
        }
        
        // Verify saved data
        $saved_nickname = get_post_meta($order_id, '_billing_bigo_nickname', true);
        $saved_profile_pic = get_post_meta($order_id, '_billing_bigo_profile_pic', true);
        
        error_log('DD Fraud Prevention - Saved Bigo user info:');
        error_log('DD Fraud Prevention - Nickname: ' . $saved_nickname);
        error_log('DD Fraud Prevention - Profile Picture: ' . $saved_profile_pic);
    }
}
