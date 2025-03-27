<?php
/**
 * Creates the add entry page for the plugin.
 *
 * Provides the functionality necessary for rendering the page corresponding
 * to the submenu with which this page is associated.
 * @package Dd_Fraud_Prevention
 */

defined( 'ABSPATH' ) || exit;
 
class Add_Entry_Page {
 
  public function render() {
    include_once( 'views/add-entry.php' );
  }
}