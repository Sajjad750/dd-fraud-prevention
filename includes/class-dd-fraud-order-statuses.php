<?php
/**
 * Custom Order Statuses
 *
 * Registers new post statuses for Woocommerce Orders
 *
 * @package Dd_Fraud_Prevention
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types Class.
 */
class Dd_Order_Statuses {
  public static function init() 
  {
		add_action( 'init', array( __CLASS__, 'register_custom_order_statuses' ), 9 );
        add_filter( 'wc_order_statuses', array(__CLASS__, 'add_custom_statuses_to_order' ) );
        add_filter( 'woocommerce_valid_order_statuses_for_payment', array(__CLASS__, 'add_review_to_valid_order_statuses' ) , 10, 2 );

	}

  public static function add_custom_statuses_to_order( $order_statuses ) {
    $new_order_statuses = array();
    foreach ( $order_statuses as $key => $status ) {
        $new_order_statuses[ $key ] = $status;
        if ( 'wc-processing' === $key ) {
            // $new_order_statuses['wc-verified'] = 'Verified';
            $new_order_statuses['wc-review-required'] = 'Review Required';
            $new_order_statuses['wc-blocked'] = 'Blocked';
        }
    }
    return $new_order_statuses;
  }
  

  public static function register_custom_order_statuses()
  {
    // register_post_status( 'wc-verified', array(
		// 	'label'                     => 'Verified',
		// 	'public'                    => true,
		// 	'show_in_admin_status_list' => true,
		// 	'show_in_admin_all_list'    => true,
		// 	'exclude_from_search'       => false,
		// 	'label_count'               => _n_noop( 'Verified <span class="count">(%s)</span>', 'Verified <span class="count">(%s)</span>' )
    // ) );

    register_post_status( 'wc-review-required', array(
      'label'                     => 'Review Required',
      'public'                    => true,
      'show_in_admin_status_list' => true,
      'show_in_admin_all_list'    => true,
      'exclude_from_search'       => false,
      'label_count'               => _n_noop( 'Review Required <span class="count">(%s)</span>', 'Review Required <span class="count">(%s)</span>' )
    ) );

    register_post_status( 'wc-blocked', array(
      'label'                     => 'Blocked',
      'public'                    => true,
      'show_in_admin_status_list' => true,
      'show_in_admin_all_list'    => true,
      'exclude_from_search'       => false,
      'label_count'               => _n_noop( 'Blocked <span class="count">(%s)</span>', 'Blocked <span class="count">(%s)</span>' )
    ) );
  }

  public static function add_review_to_valid_order_statuses( $statuses, $order ) 
  {
    // Registering the custom status as valid for payment
    $statuses[] = 'review-required';

    return $statuses;
  }
}

