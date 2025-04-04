<?php
/**
 * Creates the submenu item for the plugin.
 *
 * @package Dd_Fraud_Prevention
 */

class Settings {
 
    /**
     * A reference the class responsible for rendering the submenu page.
     *
     * @var    Settings_Page
     * @access private
     */
    private $settings_page;

    /**
     * A reference the class responsible for rendering the submenu page.
     *
     * @var    Import_Export_Page
     * @access private
     */
    private $import_export_page;

    /**
     * A reference the class responsible for rendering the submenu page.
     *
     * @var    Listings_Page
     * @access private
     */
    private $listings_page;

    /**
     * A reference the class responsible for rendering the submenu page.
     *
     * @var    Add_Entry_page
     * @access private
     */
    private $add_entry_page;
 
    /**
     * Initializes all of the partial classes.
     *
     * @param Settings_Page $settings_page
     * @param Import_Export_Page $import_export_page 
     * @param Listings_Page $listings_page
     * @param Add_Entry_Page $add_entry_page
     */
    public function __construct( $settings_page, $import_export_page, $listings_page, $add_entry_page ) {
        $this->settings_page = $settings_page;
        $this->import_export_page = $import_export_page;
        $this->listings_page = $listings_page;
        $this->add_entry_page = $add_entry_page;
    }
 
    /**
     * Adds a submenu for this plugin to the 'Tools' menu.
     */
    public function init() {
         add_action( 'admin_menu', array( $this, 'add_pages' ) );
         add_action( 'admin_init', array( $this, 'dd_fraud_register_settings') );
    }
 
    /**
     * Creates the settings menu on the Settings Page object to render
     * the actual contents of the page.
     */
    public function add_pages() {

        add_menu_page(
            __( 'DD Fraud Prevention', 'textdomain' ),
            'Fraud Prevention',
            'manage_options',
            'dd_fraud',
            array( $this->add_entry_page, 'render' ),
            'dashicons-shield',
            67
        );

        add_submenu_page('dd_fraud', 'Add Entry', 'Add Entry', 'manage_options', 'dd_fraud',  array( $this->add_entry_page, 'render' ));

        $types = $this->listings_page->get_types();
        foreach($types as $index => $value)
        {
            $page_hook = add_submenu_page('dd_fraud', $value, $value, 'manage_options', 'dd_fraud_' . $index,  array( $this->listings_page, 'render' ));

            add_action( 'load-'.$page_hook, array( $this->listings_page, 'load_screen_options' ) );
        }

        add_submenu_page('dd_fraud', 'Settings / Import', 'Settings / Import', 'manage_options', 'dd_fraud_import_export',  array( $this->import_export_page, 'render' ));

    }

    public function dd_fraud_register_settings() {
        add_option( 'dd_fraud_order_limit', 100 );
        add_option( 'dd_fraud_match_threshold', 70 );
        add_option( 'dd_auto_refund_enabled', '0' );
        add_option( 'dd_auto_refund_reason', 'Order blocked by fraud prevention system' );
        add_option( 'dd_fraud_vpn_block', '1' );

        register_setting( 'dd_fraud_options_group', 'dd_fraud_order_limit', 'intval' );
        register_setting( 'dd_fraud_options_group', 'dd_fraud_match_threshold', 'intval' );
        register_setting( 'dd_fraud_options_group', 'dd_auto_refund_enabled' );
        register_setting( 'dd_fraud_options_group', 'dd_auto_refund_reason', 'sanitize_text_field' );
        register_setting( 'dd_fraud_options_group', 'dd_fraud_vpn_block' );
    }

    public function redirect()
    {
        wp_safe_redirect( admin_url( 'admin.php?page=dd_fraud_bigo_id' ) );
    }
    
}