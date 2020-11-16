<?php

/**
 * Provide a admin area view for the plugin settings
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 *
 * @package    MYMANAGED
 * @subpackage MYMANAGED/admin/view
 */

if (!defined('WPINC')) die('Silly human what are you doing here');

if (!get_mm_access_token()) { ?>
    <div class='notice notice-error mymanaged-id-notice'>
        <div style='margin: 8px 0;'>
            <p><strong><?= __('Error:', MY_MANAGED_TEXT_DOMAIN) ?></strong>
                <?= esc_html__('Please enter your access token which you can find on your site settings page.'); ?>
            </p>
        </div>
    </div>
    <?php
} else {
    $audit_class = new MyManaged_Audit();
    $response = $audit_class->audit_changes(array(
        'initiator' => array(
            'key' => 'call_from_settings',
            'user' => $audit_class->get_user_data(),
        )));

    if (!is_wp_error($response) && !empty($response['response']) && !empty($response['response']['code'])) {
        if ($response['response']['code'] == 200) {
            $payloads = get_mm_jwt_payloads();
            ?>
            <div class="notice notice-success">
                <p>
                    <?php printf(
                        esc_html__('%1$s %2$s', MY_MANAGED_TEXT_DOMAIN),
                        esc_html__('Your website and ' . $payloads->brand . ' account have been successfully linked.
                                    Site changes are now being monitored and can be seen ', MY_MANAGED_TEXT_DOMAIN),
                        sprintf(
                            '<a href="%s">%s</a>',
                            esc_url($payloads->siteUrl . 'changes'),
                            esc_html__('within your site dashboard.', MY_MANAGED_TEXT_DOMAIN)
                        )
                    );
                    printf(
                        esc_html__('%1$s %2$s', MY_MANAGED_TEXT_DOMAIN),
                        esc_html__(' You can also run plugin and theme updates directly from ', MY_MANAGED_TEXT_DOMAIN),
                        sprintf(
                            '<a href="%s">%s</a>',
                            esc_url($payloads->siteUrl . 'plugins-and-themes'),
                            esc_html__('here', MY_MANAGED_TEXT_DOMAIN)
                        )
                    );
                    printf(esc_html__(', without needing to login to Wordpress.', MY_MANAGED_TEXT_DOMAIN));
                    ?>
                </p>
            </div>
        <?php } else { ?>
            <div class="notice notice-error">
                <p>
                    <?= esc_html__('Your access token has expired or is invalid. 
                    Please generate a new token from your site settings page.', MY_MANAGED_TEXT_DOMAIN) ?>
                </p>
            </div>
        <?php }
    }
}
?>

<div class="wrap">
    <div id="icon-themes" class="icon32"></div>
    <h2><?= __('Website Configuration', MY_MANAGED_TEXT_DOMAIN) ?></h2>
    <p><?= __('Connect your WordPress site to your account by entering a valid access token. 
    You can generate new access tokens directly from your site settings page. 
    <br/>Once submitted, we\'ll verify site ownership and begin logging changes.', MY_MANAGED_TEXT_DOMAIN) ?></p>

    <?php settings_errors(); ?>
    <form method="POST" action="options.php">
        <?php
        settings_fields('mymanaged_plugin_options');
        do_settings_sections('mymanaged_plugin');
        submit_button();
        ?>
    </form>
</div>