<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Custom Data Organizer (SCF/ACF Compatible)
 *
 * Creates:
 *
 * Custom Data
 *    Accommodations
 *        – View All Accommodations
 *        – Add Accommodation
 *        – Categories
 *    Destinations
 *        – View All Destinations
 *        – Add Destination
 *        – Categories
 *    ...etc for every CPT
 */
class CDO_Admin_Menu {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ], 99 );
    }

    /**
     * Create the main "Custom Data" parent menu + all CPT submenus.
     */
    public function register_menu() {

        // Parent menu
        add_menu_page(
            __( 'Custom Data', 'custom-data-organizer' ),
            __( 'Custom Data', 'custom-data-organizer' ),
            'edit_posts',
            'cdo-main',
            [ $this, 'render_overview' ],
            'dashicons-index-card',
            55
        );

        // Overview
        add_submenu_page(
            'cdo-main',
            __( 'Overview', 'custom-data-organizer' ),
            __( 'Overview', 'custom-data-organizer' ),
            'edit_posts',
            'cdo-main',
            [ $this, 'render_overview' ]
        );

        // Detect all CPTs
        $post_types = get_post_types(
            [
                '_builtin' => false,
                'show_ui'  => true,
            ],
            'objects'
        );

        if ( empty( $post_types ) ) return;

        foreach ( $post_types as $post_type ) {

            // Skip internal SCF/ACF post types
            if ( strpos( $post_type->name, 'acf-' ) === 0 ) continue;

            $this->add_cpt_menu_group( $post_type );
        }
    }

    /**
     * Add:
     *  - View All {Plural}
     *  - Add {Singular}
     *  - Categories (if taxonomy exists)
     */
    protected function add_cpt_menu_group( $post_type ) {

        $pt        = $post_type->name;
        $plural    = $post_type->labels->name;
        $menu_name = $post_type->labels->menu_name ?: $plural;
        $singular  = $post_type->labels->singular_name ?: rtrim( $menu_name, 's' );
        $cap       = $post_type->cap->edit_posts ?? 'edit_posts';

        // — View All
        add_submenu_page(
            'cdo-main',
            sprintf( __( 'View All %s', 'custom-data-organizer' ), $plural ),
            $menu_name,
            $cap,
            'cdo-view-' . $pt,
            function() use ( $pt ) {
                wp_safe_redirect( admin_url( 'edit.php?post_type=' . $pt ) );
                exit;
            }
        );

        // — Add New
        add_submenu_page(
            'cdo-main',
            sprintf( __( 'Add %s', 'custom-data-organizer' ), $singular ),
            '— Add ' . $singular,
            $cap,
            'cdo-add-' . $pt,
            function() use ( $pt ) {
                wp_safe_redirect( admin_url( 'post-new.php?post_type=' . $pt ) );
                exit;
            }
        );

        // Taxonomies?
        $taxonomies = get_object_taxonomies( $pt, 'objects' );

        if ( ! empty( $taxonomies ) ) {

            $taxonomy = reset( $taxonomies );

            add_submenu_page(
                'cdo-main',
                sprintf( __( 'Categories (%s)', 'custom-data-organizer' ), $menu_name ),
                '— Categories',
                'manage_categories',
                'cdo-tax-' . $pt,
                function() use ( $taxonomy, $pt ) {
                    $url = admin_url(
                        'edit-tags.php?taxonomy=' . $taxonomy->name . '&post_type=' . $pt
                    );
                    wp_safe_redirect( $url );
                    exit;
                }
            );
        }
    }

    /**
     * Simple overview page.
     */
    public function render_overview() {

        echo '<div class="wrap">';
        echo '<h1>Custom Data Organizer</h1>';
        echo '<p>This menu organizes all SCF/ACF custom post types into one place.</p>';
        echo '<p>Structure follows exactly:</p>';

        echo '<pre style="background:#fff;padding:15px;border:1px solid #ccc;">';
        echo "Custom Data\n";
        echo "    Accommodations\n";
        echo "        – View All Accommodations\n";
        echo "        – Add Accommodation\n";
        echo "        – Categories\n";
        echo "</pre>";

        echo '</div>';
    }
}