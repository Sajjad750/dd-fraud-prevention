<?php
/**
 * Creates the settings page for the plugin.
 *
 * Provides the functionality necessary for rendering the page corresponding
 * to the submenu with which this page is associated.
 * @package Dd_Fraud_Prevention
 */
 
class Settings_Page {
 
        /**
     * This function renders the contents of the page associated with the Settings
     * that invokes the render method. In the context of this plugin, this is the
     * Settings class.
     */
    public function render() {
        include_once( 'views/settings.php' );
    }
}