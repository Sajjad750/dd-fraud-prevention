<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class IP_Addresses_List_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct(array(
            'singular' => 'ip_address',
            'plural'   => 'ip_addresses',
            'ajax'     => false
        ));
    }

    public function get_columns() {
        return array(
            'cb'         => '<input type="checkbox" />',
            'ip_address' => __('IP Address', 'dd-fraud'),
            'flag'       => __('Flag', 'dd-fraud'),
            'notes'      => __('Notes', 'dd-fraud'),
            'created_at' => __('Created At', 'dd-fraud')
        );
    }

    public function get_sortable_columns() {
        return array(
            'ip_address' => array('ip_address', true),
            'flag'       => array('flag', false),
            'created_at' => array('created_at', true)
        );
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dd_fraud_ip';
        
        // Handle search
        $search = isset($_REQUEST['s']) ? wp_unslash($_REQUEST['s']) : '';
        
        // Items per page
        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        // Get total items
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
        
        // Get items with explicit column names
        $query = "SELECT id, ip_address, flag, notes, created_at FROM $table_name";
        if (!empty($search)) {
            $query .= $wpdb->prepare(" WHERE ip_address LIKE %s", '%' . $wpdb->esc_like($search) . '%');
        }
        
        // Handle sorting
        $orderby = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'created_at';
        $order = isset($_REQUEST['order']) ? $_REQUEST['order'] : 'DESC';
        if ($orderby === 'ip_address') {
            $orderby = 'INET_ATON(ip_address)'; // Sort IP addresses numerically
        }
        $query .= " ORDER BY $orderby $order";
        
        // Add pagination
        $query .= " LIMIT $per_page";
        $query .= ' OFFSET ' . ($current_page - 1) * $per_page;
        
        $this->items = $wpdb->get_results($query, ARRAY_A);
        
        // Debug output
        error_log('IP Query: ' . $query);
        error_log('IP Results: ' . print_r($this->items, true));
        
        // Set pagination args
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    public function column_default($item, $column_name) {
        if (!is_array($item)) {
            return '';
        }
        
        switch ($column_name) {
            case 'ip_address':
                return isset($item['ip_address']) ? esc_html($item['ip_address']) : '';
            case 'flag':
                return isset($item['flag']) ? esc_html($item['flag']) : '';
            case 'notes':
                return isset($item['notes']) ? esc_html($item['notes']) : '';
            case 'created_at':
                return isset($item['created_at']) ? esc_html($item['created_at']) : '';
            default:
                return '';
        }
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="ip_address[]" value="%s" />', $item['id']);
    }

    public function column_ip_address($item) {
        if (!is_array($item) || !isset($item['ip_address'])) {
            return '';
        }

        // Actions for each row
        $actions = array(
            'edit'   => sprintf('<a href="?page=%s&action=%s&ip=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['id']),
            'delete' => sprintf('<a href="?page=%s&action=%s&ip=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['id'])
        );

        // Return the IP address with row actions
        return sprintf('%1$s %2$s', 
            esc_html($item['ip_address']),
            $this->row_actions($actions)
        );
    }

    // Display the table
    public function display_table() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post">
                <?php
                $this->prepare_items();
                $this->search_box('Search IP Addresses', 'search_id');
                $this->display();
                ?>
            </form>
        </div>
        <?php
    }

    protected function get_bulk_actions() {
        return array(
            'delete' => __('Delete', 'dd-fraud')
        );
    }

    public function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            // Handle bulk delete action
            if (isset($_POST['ip'])) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'dd_fraud_ip';
                $ids = array_map('intval', $_POST['ip']);
                
                foreach ($ids as $id) {
                    $wpdb->delete(
                        $table_name,
                        array('id' => $id),
                        array('%d')
                    );
                }
                
                wp_redirect(add_query_arg());
                exit;
            }
        }
    }
}

// Initialize the list table
$ip_addresses_table = new IP_Addresses_List_Table();
?>

<div class="wrap">    
    <h1><?php echo esc_html__('IP Address List', 'dd-fraud'); ?></h1>
    
    <div id="ip-addresses">			
        <div id="nds-post-body">		
            <form id="ip-addresses-filter" method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                <?php
                    $ip_addresses_table->prepare_items();
                    $ip_addresses_table->search_box('Search IP Addresses', 'ip-search');
                    $ip_addresses_table->display();
                ?>
            </form>
        </div>			
    </div>
</div>