<?php
/**
 * Creates the import/export page for the plugin.
 *
 * Provides the functionality necessary for rendering the page corresponding
 * to the submenu with which this page is associated.
 * @package Dd_Fraud_Prevention
 */
 
class Import_Export_Page {
 
    /**
     * This function renders the contents of the page associated with the Import/Export
     * that invokes the render method. In the context of this plugin, this is the
     * Import_Export class.
     */
    public function render() {
        include_once( 'views/import-export.php' );
    }
}