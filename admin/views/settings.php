<?php
global $wpdb;
$search_term = "";

if (isset($_REQUEST['s']))
{
    $search_term = $_REQUEST['s'];
}

?>

<div class="wrap">

    <h1>Fraud Prevention</h1>
    
    <h2>Search</h2>
    <form id="dd-fraud-search" class="dd-fraud-search" method="get">
        <input type="hidden" name="page" value="dd_fraud_ip">
        <input type="text" name="s" placeholder="<?php _e( 'Search Keywords', 'admin-search' ); ?>" value="<?php echo $search_term ?>" autocomplete="off" id="dd-fraud-search-input">
        <input class="button button-primary" type="submit" value="Search"/></p>
    </form>

    <h2>Add a New Entry</h2>
    <form id="dd-fraud-add" method="post" action="/wp-content/plugins/dd-fraud-prevention/add.php" target="message">
        <p><label for="type">Type</label><br>
        <select name="type">
            <option selected>Bigo ID</option>
            <option>Email</option>
            <option>Name</option>
            <option>IP Address</option>
        </select></p>
        <p><label for="name">ID / Email / Name / IP</label><br>
        <input type="text" name="name" value=""></p>
        <p><label for="flag">Flag</label><br>
        <select name="flag">
            <option selected>Blocked</option>
            <option>Review</option>
            <option>Verified</option>
        </select></p>
        <p><input class="button button-primary" type="submit" value="Add New Entry"/></p>
        <iframe name="message">
    </form>

    <h2>Settings</h2>
    <?php
    // Debug information
    if (current_user_can('manage_options')) {
        echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0;">';
        echo '<h3>Debug Information:</h3>';
        echo '<p>Settings Group: dd_fraud_options_group</p>';
        echo '<p>VPN Block Setting: ' . (get_option('dd_fraud_vpn_block') ? 'Set' : 'Not Set') . '</p>';
        echo '<p>Auto Block Setting: ' . (get_option('dd_fraud_auto_block') ? 'Set' : 'Not Set') . '</p>';
        echo '</div>';
    }
    ?>
    <form method="post" action="options.php">
        <?php 
        settings_fields('dd_fraud_options_group');
        do_settings_sections('dd_fraud_options_group');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="dd_fraud_order_limit">Number of Previous Orders to Check</label>
                </th>
                <td>
                    <input type="number" id="dd_fraud_order_limit" name="dd_fraud_order_limit" 
                           value="<?php echo esc_attr(get_option('dd_fraud_order_limit', '100')); ?>" class="regular-text">
                    <p class="description">Default: 100</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="dd_fraud_match_threshold">Match Threshold (%)</label>
                </th>
                <td>
                    <input type="number" id="dd_fraud_match_threshold" name="dd_fraud_match_threshold" 
                           value="<?php echo esc_attr(get_option('dd_fraud_match_threshold', '70')); ?>" class="regular-text">
                    <p class="description">Default: 70 (100 is exact match)</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="dd_fraud_auto_block">Auto-Blocking</label>
                </th>
                <td>
                    <select id="dd_fraud_auto_block" name="dd_fraud_auto_block">
                        <option value="1" <?php selected(get_option('dd_fraud_auto_block', '1'), '1'); ?>>Enabled</option>
                        <option value="0" <?php selected(get_option('dd_fraud_auto_block', '1'), '0'); ?>>Disabled</option>
                    </select>
                    <p class="description">Automatically block orders when fraud is detected</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="dd_fraud_vpn_block">VPN Blocking</label>
                </th>
                <td>
                    <select id="dd_fraud_vpn_block" name="dd_fraud_vpn_block">
                        <option value="1" <?php selected(get_option('dd_fraud_vpn_block', '1'), '1'); ?>>Enabled</option>
                        <option value="0" <?php selected(get_option('dd_fraud_vpn_block', '1'), '0'); ?>>Disabled</option>
                    </select>
                    <p class="description">Block orders from known VPN IP addresses</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="dd_fraud_past_orders_check">Past Orders Check</label>
                </th>
                <td>
                    <input type="number" id="dd_fraud_past_orders_check" name="dd_fraud_past_orders_check" 
                           value="<?php echo esc_attr(get_option('dd_fraud_past_orders_check', '10')); ?>" class="regular-text">
                    <p class="description">Number of past orders to check for inconsistencies (Default: 10)</p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <?php
    if (!empty($search_term))
    {
        $bigo_table = $wpdb->prefix . 'dd_fraud_bigo_id';
        $email_table = $wpdb->prefix . 'dd_fraud_email';
        $customer_name_table = $wpdb->prefix . 'dd_fraud_customer_name';
        $ip_table = $wpdb->prefix . 'dd_fraud_ip';

        $search_term = $_REQUEST['s'];

        $bigo_sql = $wpdb->prepare("SELECT * FROM $bigo_table WHERE bigo_id LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
        $bigo_results = $wpdb->get_results($bigo_sql, ARRAY_A);
        $bigo_count = $wpdb->num_rows;

        $email_sql = $wpdb->prepare("SELECT * FROM $email_table WHERE email LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
        $email_results = $wpdb->get_results($email_sql, ARRAY_A);
        $email_count = $wpdb->num_rows;

        $name_sql = $wpdb->prepare("SELECT * FROM $customer_name_table WHERE customer_name LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
        $name_results = $wpdb->get_results($name_sql, ARRAY_A);
        $name_count = $wpdb->num_rows;

        $ip_sql = $wpdb->prepare("SELECT * FROM $ip_table WHERE ip_address LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
        $ip_results = $wpdb->get_results($ip_sql, ARRAY_A);
        $ip_count = $wpdb->num_rows;

        if ($bigo_count > 0)
        {
            echo(generate_results_table($bigo_count, "bigo_id", "Bigo ID", $bigo_results));
        }

        if ($email_count > 0)
        {
            echo(generate_results_table($email_count, "email", "Email", $email_results));
        }

        if ($name_count > 0)
        {
            echo(generate_results_table($name_count, "customer_name", "Customer", $name_results));
        }

        if ($ip_count > 0)
        {
            echo(generate_results_table($ip_count, "ip_address", "IP Address", $ip_results));
        }
    } else {
        // Show all records when no search term
        $ip_table = $wpdb->prefix . 'dd_fraud_ip';
        $ip_sql = "SELECT * FROM $ip_table";
        $ip_results = $wpdb->get_results($ip_sql, ARRAY_A);
        $ip_count = $wpdb->num_rows;

        if ($ip_count > 0)
        {
            echo(generate_results_table($ip_count, "ip_address", "IP Address", $ip_results));
        }
    }

    if (isset($_REQUEST['action']) && $_REQUEST['action'] === "delete")
    {
        if (!isset($_REQUEST['type']) && !isset($_REQUEST['id']))
        {
            exit();
        }

        $type = $_REQUEST['type'];
        $accepted_types = ['bigo_id', 'email', 'customer_name', 'ip_address'];

        if (!in_array($type, $accepted_types))
        {
            exit();
        }

        $table = $wpdb->prefix . 'dd_fraud_' . $type;

        $count = $wpdb->delete( $table, array( 'ID' => $_REQUEST['id'] ) );

        if ($count > 0)
        {
            ?>
                <p>Entry deleted.</p>
            <?php 
        }
    }
    ?>

</div><!-- .wrap -->

<?php
function generate_results_table($count, $type, $title, $results)
{
    ob_start();

    $heading = $count . " " . $title;
    $heading .= $count > 1 ? "s" : "";
    ?>

    <h3><?php echo $heading ?> Found</h3>
    <table class="widefat">
        <thead>
            <tr>
                <th>ID</th>
                <th><?php echo $title; ?></th>
                <th>Flag</th>
                <th>Notes</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach($results as $result) {
                    $delete_url = "?page=dd_fraud_ip&action=delete&type=" . $type ."&id=" . $result['id'];
            ?>
                    <tr>
                        <td><?php echo esc_html($result['id']); ?></td>
                        <td><?php 
                            // Handle IP address specifically
                            if ($type === 'ip_address') {
                                echo esc_html($result['ip_address']);
                            } else {
                                echo esc_html($result[$type]);
                            }
                        ?></td>
                        <td><?php echo esc_html($result['flag']); ?></td>
                        <td><?php echo esc_html($result['notes']); ?></td>
                        <td><?php echo esc_html($result['created_at']); ?></td>
                        <td><a href='<?php echo esc_url($delete_url); ?>'>Delete</a></td>
                    </tr>
            <?php } ?>
        </tbody>
    </table>

    <?php
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
}