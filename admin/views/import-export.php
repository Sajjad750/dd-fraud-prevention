<?php
$admin_url = admin_url('admin-post.php');
?>
<div class="wrap">

  <h1>Fraud Prevention System</h1>

  <h2>Settings</h2>
  <form method="post" action="options.php">
  <?php settings_fields( 'dd_fraud_options_group' ); ?>
    <div class="input_group">
        <label for="dd_fraud_order_limit">Number of Previous Orders with Matching Bigo ID to Check<br>Default: 100</label>
        <input type="text" id="dd_fraud_order_limit" name="dd_fraud_order_limit" value="<?php echo get_option('dd_fraud_order_limit'); ?>" />
    </div>
    <div class="input_group">
        <label for="dd_fraud_match_threshold">% Match Threshold to Compare Entries<br>Default: 70 (100 is exact match)<br>e.g. Entries that match at least 70% percent will pass fraud check. </label>
        <input type="text" id="dd_fraud_match_threshold" name="dd_fraud_match_threshold" value="<?php echo get_option('dd_fraud_match_threshold'); ?>" />
    </div>
    <div class="input_group">
        <label for="dd_auto_refund_enabled">Auto-Refund Settings</label>
        <div style="margin-left: 20px;">
            <input type="checkbox" id="dd_auto_refund_enabled" name="dd_auto_refund_enabled" value="1" <?php checked(1, get_option('dd_auto_refund_enabled', '0')); ?> />
            <label for="dd_auto_refund_enabled">Enable automatic refunds for blocked orders</label>
            <br><br>
            <label for="dd_auto_refund_reason">Refund Reason:</label><br>
            <input type="text" id="dd_auto_refund_reason" name="dd_auto_refund_reason" value="<?php echo esc_attr(get_option('dd_auto_refund_reason', 'Order blocked by fraud prevention system')); ?>" class="regular-text" />
            <p class="description">This message will be displayed to customers when their order is automatically refunded.</p>
        </div>
    </div>
    <div class="input_group">
        <label for="dd_fraud_vpn_block">VPN Blocking</label>
        <div style="margin-left: 20px;">
            <input type="checkbox" id="dd_fraud_vpn_block" name="dd_fraud_vpn_block" value="1" <?php checked(1, get_option('dd_fraud_vpn_block', '1')); ?> />
            <label for="dd_fraud_vpn_block">Enable VPN blocking</label>
            <p class="description">When enabled, orders from known VPN IP addresses will be automatically blocked.</p>
        </div>
    </div>
    <?php submit_button(); ?>
  </form>

  <div class="flex_row">
    <div class="content_left">
        <h2>Import / Export</h2>
        <h3>Upload CSV to bulk add or update</h3>
        <p>CSV Example</p>
        <pre>
            bigo_id,flag,notes
            testId123,blocked,lorem ipsum
            testIDfjfjf,review,
            verifiedId,verified,
            removethis,delete,
            newIDToInsert,verified,
        </pre>
        <p>Change the "bigo_id" to "email" or "customer_name" in the header row to manage the different lists.</p>
        <form id="upload_form" action="<?php echo($admin_url);?>" method="post" enctype="multipart/form-data" target="message">
                <input type="hidden" name="action" value="dd_import" />
            <p><input name="upload" id="upload" type="file" accept="text/csv" /></p>
            <p><input class="button button-primary" type="submit" value="Upload CSV file"/></p>
            <iframe name="message" style="width:400px;height:100px"></iframe>
        </form>
    </div>
    <div class="content_right">

        <h2>Export CSV</h2>
        <form id="export_form" action="<?php echo($admin_url);?>" method="post" enctype="multipart/form-data" target="export_message">
            <input type="hidden" name="action" value="dd_export">
            <input type="hidden" name="type" value="bigo_id">
            <?php wp_nonce_field('dd_export_csv'); ?>
            <input class="button button-primary" type="submit" value="Export Bigo IDs"/></p>
        </form>

        <form id="export_form" action="<?php echo($admin_url);?>" method="post" enctype="multipart/form-data" target="export_message">
            <input type="hidden" name="action" value="dd_export">
            <input type="hidden" name="type" value="email">
            <?php wp_nonce_field('dd_export_csv'); ?>
            <input class="button button-primary" type="submit" value="Export Emails"/></p>
        </form>

        <form id="export_form" action="<?php echo($admin_url);?>" method="post" enctype="multipart/form-data" target="export_message">
            <input type="hidden" name="action" value="dd_export">
            <input type="hidden" name="type" value="customer_name">
            <?php wp_nonce_field('dd_export_csv'); ?>
            <input class="button button-primary" type="submit" value="Export Names"/></p>
        </form>

        <iframe name="message" style="height:50px"></iframe>
    </div>
</div><!-- .row -->