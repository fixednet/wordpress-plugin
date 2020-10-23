<?php

/**
 * Provide a admin area view for the plugin
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
                <?php printf(
                    esc_html__('%1$s %2$s', MY_MANAGED_TEXT_DOMAIN),
                    esc_html__('You need to enter your unique access token first. You can enter this on the', MY_MANAGED_TEXT_DOMAIN),
                    sprintf(
                        '<a href="%s">%s</a>',
                        esc_url('admin.php?page=' . mm_get_plugin_slug() . '-settings'),
                        esc_html__('Settings', MY_MANAGED_TEXT_DOMAIN)
                    )
                ); ?>
            </p>
        </div>
    </div>
    <?php
}