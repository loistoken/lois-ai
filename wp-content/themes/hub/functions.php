<?php
update_option( 'hub_purchase_code', '**********' );
update_option( 'hub_purchase_code_status', 'valid' );
update_option( 'hub_register_email', 'noreply@gmail.com' );
/**
 * The Liquid Themes Hub Theme
 *
 * Note: Do not add any custom code here. Please use a child theme so that your customizations aren't lost during updates.
 * http://codex.wordpress.org/Child_Themes
 *
 * @link https://codex.wordpress.org/Theme_Development
 * @link https://codex.wordpress.org/Child_Themes
 *
 * Text Domain: 'hub'
 * Domain Path: /languages/
 */

// Starting The Engine / Load the Liquid Framework ----------------
include_once( get_template_directory() . '/liquid/liquid-init.php' );
