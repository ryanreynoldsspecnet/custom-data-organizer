<?php
/**
 * Plugin Name:       Custom Data Organizer
 * Description:       Clean and simple admin organizer for SCF/ACF custom post types.
 * Version:           1.1.0
 * Author:            SpecNet
 * Text Domain:       custom-data-organizer
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CDO_VERSION', '1.1.0' );
define( 'CDO_PLUGIN_FILE', __FILE__ );
define( 'CDO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CDO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once CDO_PLUGIN_DIR . 'includes/class-cdo-admin-menu.php';

add_action( 'plugins_loaded', function() {

    // SCF is ACF-compatible, so just check for acf core.
    if ( ! function_exists( 'acf_get_field_groups' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__( 'Custom Data Organizer requires Secure Custom Fields (SCF) or ACF to be active.', 'custom-data-organizer' );
            echo '</p></div>';
        });
        return;
    }

    new CDO_Admin_Menu();
});