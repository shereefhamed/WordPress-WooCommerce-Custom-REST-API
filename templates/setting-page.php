<?php
if (isset($_POST['wordpress_api_settings'])) {
    if (!wp_verify_nonce($_POST['wordpress_api_settings'], 'wordpress_api_settings')) {
        return;
    }
}
if (isset($_POST['save_worpress_api_settings'])) {
    $enabled = isset($_POST['wordpress_api_enabled']) ? true : false;
    update_option('wordpress_api_enabled', $enabled);

}


$wordpress_api_enabled = get_option('wordpress_api_enabled', false);
?>
<div class="wrap">
    <h1><?php esc_html_e(get_admin_page_title()) ?></h1>
    <form action="" method="POST">
        <?php
        wp_nonce_field(
            action: 'wordpress_api_settings',
            name: 'wordpress_api_settings'
        );
        ?>
        <div>
            <input type="checkbox" name="wordpress_api_enabled" id="wordpress_api_enabled" class="regular-text"
                value="1" <?php echo $wordpress_api_enabled ? 'checked' : '' ?> />
            <label for="wordpress_api_enabled">Enabled</label>
        </div>
        <button type="submit" name="save_worpress_api_settings" class="button-primary">Save Changes</button>
    </form>
</div>