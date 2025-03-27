<?php
$heading = isset($entry) ? "Update Entry" : "Add Entry";
$add_url = admin_url('admin-post.php');

$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : "";
?>

<div class="wrap">

  <h1><?php echo ($heading); ?></h1>

  <form id="dd-fraud-add" method="post" action="<?php echo $add_url ?>">
    <input type="hidden" name="action" value="dd_add_entry" />
    <p><label for="type">Type</label><br>
    <select name="type">
        <option value="bigo_id" <?php echo (isset($_REQUEST['type']) && $_REQUEST['type'] == 'bigo_id') ? "selected" : ""; ?>>Bigo ID</option>
        <option value="email" <?php echo (isset($_REQUEST['type']) && $_REQUEST['type'] == 'email') ? "selected" : ""; ?>>Email</option>
        <option value="customer_name" <?php echo (isset($_REQUEST['type']) && $_REQUEST['type'] == 'customer_name') ? "selected" : ""; ?>>Name</option>
        <option value="ip" <?php echo (isset($_REQUEST['type']) && $_REQUEST['type'] == 'ip') ? "selected" : ""; ?>>IP Address</option>
    </select></p>
    <p><label for="entry">Entry Value</label><br>
    <input type="text" name="entry" value="<?php echo(isset($entry[$type]) ? sanitize_text_field($entry[$type]) : ""); ?>"></p>
    <p><label for="flag">Flag</label><br>
    <select name="flag">
        <option value="blocked" <?php echo (isset($entry['flag']) && $entry['flag'] == 'blocked') ? "selected" : "" ?>>Blocked</option>
        <option value="review" <?php echo (isset($entry['flag']) && $entry['flag'] == 'review') ? "selected" : "" ?>>Review</option>
        <option value="verified" <?php echo (isset($entry['flag']) && $entry['flag'] == 'verified') ? "selected" : "" ?>>Verified</option>
    </select></p>
    <p><label for="notes">Notes</label></p>
    <textarea name="notes" cols="50" rows="5"><?php echo( isset($entry['notes']) ? stripslashes($entry['notes']) : ""); ?></textarea>
    <p><input class="button button-primary" type="submit" value="<?php echo ($heading); ?>"/></p>
  </form>

</div>