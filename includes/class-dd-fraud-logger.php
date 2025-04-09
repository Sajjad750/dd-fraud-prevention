<?php
/**
 * Logger class for DD Fraud Prevention
 *
 * Handles logging of timestamps, admin actions, and notes
 *
 * @package Dd_Fraud_Prevention
 */

class DD_Fraud_Logger {
    /**
     * Log table name
     *
     * @var string
     */
    private $log_table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->log_table = $wpdb->prefix . 'dd_fraud_logs';
    }

    /**
     * Initialize the logger
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_logs_page'));
        add_action('admin_init', array($this, 'create_log_table'));
    }

    /**
     * Create the logs table if it doesn't exist
     */
    public function create_log_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->log_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            user_id bigint(20) NOT NULL,
            action varchar(255) NOT NULL,
            details text NOT NULL,
            ip_address varchar(45) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add logs page to admin menu
     */
    public function add_logs_page() {
        add_submenu_page(
            'dd_fraud',
            'Fraud Prevention Logs',
            'Logs',
            'manage_options',
            'dd_fraud_logs',
            array($this, 'render_logs_page')
        );
    }

    /**
     * Render the logs page
     */
    public function render_logs_page() {
        include_once(plugin_dir_path(dirname(__FILE__)) . 'admin/views/logs.php');
    }

    /**
     * Log an action
     *
     * @param string $action The action performed
     * @param string $details Details about the action
     * @return bool Whether the log was successfully created
     */
    public function log($action, $details) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $ip_address = $this->get_user_ip();
        
        $result = $wpdb->insert(
            $this->log_table,
            array(
                'timestamp' => current_time('mysql'),
                'user_id' => $user_id,
                'action' => $action,
                'details' => $details,
                'ip_address' => $ip_address
            ),
            array('%s', '%d', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }

    /**
     * Get logs with pagination
     *
     * @param int $page Current page
     * @param int $per_page Items per page
     * @return array Logs and total count
     */
    public function get_logs($page = 1, $per_page = 20) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->log_table} ORDER BY timestamp DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->log_table}");
        
        return array(
            'logs' => $logs,
            'total' => $total
        );
    }

    /**
     * Get user IP address
     *
     * @return string IP address
     */
    private function get_user_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }

    /**
     * Get username by user ID
     *
     * @param int $user_id User ID
     * @return string Username
     */
    public function get_username($user_id) {
        $user = get_user_by('id', $user_id);
        return $user ? $user->user_login : 'Unknown';
    }
} 