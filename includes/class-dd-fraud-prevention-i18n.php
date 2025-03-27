<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://jennychan.dev
 * @since      1.0.0
 *
 * @package    Dd_Fraud_Prevention
 * @subpackage Dd_Fraud_Prevention/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Dd_Fraud_Prevention
 * @subpackage Dd_Fraud_Prevention/includes
 * @author     Jenny Chan <jenny@jennychan.dev>
 */
class Dd_Fraud_Prevention_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'dd-fraud-prevention',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
