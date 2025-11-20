<?php
/**
 * Plugin Name:       SCF Admin Organizer
 * Description:       Organize Secure Custom Fields (SCF) / ACF field groups under a single, tidy admin menu.
 * Version:           0.1.0
 * Author:            SpecNet
 * Text Domain:       scf-admin-organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SCAO_VERSION', '0.1.0' );
define( 'SCAO_PLUGIN_FILE', __FILE__ );
define( 'SCAO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCAO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load classes
require_once SCAO_PLUGIN_DIR . 'includes/class-scao-admin-menu.php';

/**
 * Bootstrap plugin.
 */
add_action( 'plugins_loaded', function() {

    // SCF is a fork of ACF and uses the same function names.
    if ( ! function_exists( 'acf_get_field_groups' ) ) {

        // Optional: admin notice if SCF/ACF not active.
        add_action( 'admin_notices', function() {
            if ( current_user_can( 'manage_options' ) ) {
                echo '<div class="notice notice-warning"><p>';
                echo esc_html__( 'SCF Admin Organizer requires Secure Custom Fields (or ACF-compatible fields) to be active.', 'scf-admin-organizer' );
                echo '</p></div>';
            }
        } );

        return;
    }

    new SCAO_Admin_Menu();
} );