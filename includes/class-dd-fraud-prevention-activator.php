<?php

/**
 * Fired during plugin activation
 *
 * @link       https://jennychan.dev
 * @since      1.0.0
 *
 * @package    Dd_Fraud_Prevention
 * @subpackage Dd_Fraud_Prevention/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Dd_Fraud_Prevention
 * @subpackage Dd_Fraud_Prevention/includes
 * @author     Jenny Chan <jenny@jennychan.dev>
 */
class Dd_Fraud_Prevention_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$bigo_table = $wpdb->prefix . 'dd_fraud_bigo_id';

		$bigo_sql = "CREATE TABLE $bigo_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			bigo_id varchar(60) NOT NULL,
			flag varchar(10) NOT NULL,
			notes text,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY id (id),
			UNIQUE (bigo_id)
		) $charset_collate;";

		dbDelta( $bigo_sql );

		$email_table = $wpdb->prefix . 'dd_fraud_email';

		$email_sql = "CREATE TABLE $email_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			email varchar(100) NOT NULL,
			flag varchar(10) NOT NULL,
			notes text,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY id (id),
			UNIQUE (email)
		) $charset_collate;";

		dbDelta( $email_sql );

		$customer_name_table = $wpdb->prefix . 'dd_fraud_customer_name';

		$customer_name_sql = "CREATE TABLE $customer_name_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			customer_name varchar(250) NOT NULL,
			flag varchar(10) NOT NULL,
			notes text,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY id (id),
			UNIQUE (customer_name)
		) $charset_collate;";

		dbDelta( $customer_name_sql );

		$ip_table = $wpdb->prefix . 'dd_fraud_ip';

		$ip_sql = "CREATE TABLE $ip_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			ip_address varchar(45) NOT NULL,
			flag varchar(10) NOT NULL,
			notes text,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY id (id),
			UNIQUE (ip_address)
		) $charset_collate;";

		dbDelta( $ip_sql );
	}

}
