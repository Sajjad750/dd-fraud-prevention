<?php
/**
 * Logs page view
 *
 * @package Dd_Fraud_Prevention
 */

// Get current page
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;

// Get logs
$logger = new DD_Fraud_Logger();
$logs_data = $logger->get_logs($current_page, $per_page);
$logs = $logs_data['logs'];
$total_logs = $logs_data['total'];

// Calculate pagination
$total_pages = ceil($total_logs / $per_page);
?>

<div class="wrap">
    <h1>Fraud Prevention Logs</h1>
    
    <div class="tablenav top">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo $total_logs; ?> items</span>
            <?php if ($total_pages > 1) : ?>
                <span class="pagination-links">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Details</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)) : ?>
                <tr>
                    <td colspan="5">No logs found.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['timestamp']))); ?></td>
                        <td><?php echo esc_html($logger->get_username($log['user_id'])); ?></td>
                        <td><?php echo esc_html($log['action']); ?></td>
                        <td><?php echo nl2br(esc_html($log['details'])); ?></td>
                        <td><?php echo esc_html($log['ip_address']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo $total_logs; ?> items</span>
            <?php if ($total_pages > 1) : ?>
                <span class="pagination-links">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div> 