<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Custom Data Organizer (SCF/ACF compatible)
 *
 * Moves all non-core CPTs under:
 *
 * Custom Data
 *   Accommodations
 *     – View All Accommodations
 *     – Add Accommodation
 *     – Categories
 *   Destinations
 *     – View All Destinations
 *     – Add Destination
 *     – Categories
 *   ...
 */
class CDO_Admin_Menu {

    /**
     * Core post types we NEVER touch.
     */
    protected $core_post_types = [
        'post',
        'page',
        'attachment',
        'revision',
        'nav_menu_item',
        'custom_css',
        'customize_changeset',
        'oembed_cache',
        'user_request',
        'wp_block',
        'wp_template',
        'wp_template_part',
        'wp_global_styles',
        'wp_navigation',
    ];

    public function __construct() {
        // Register menu UI.
        add_action( 'admin_menu', [ $this, 'register_menu' ], 99 );

        // Force all CPTs (non-core) to show under our Custom Data menu,
        // and NOT as their own top-level menu.
        add_filter( 'register_post_type_args', [ $this, 'attach_cpts_to_custom_data_menu' ], 20, 2 );
    }

    /**
     * Parent "Custom Data" menu + all CPT submenus.
     */
    public function register_menu() {

        add_menu_page(
            __( 'Custom Data', 'custom-data-organizer' ),
            __( 'Custom Data', 'custom-data-organizer' ),
            'edit_posts',
            'cdo-main',
            [ $this, 'render_overview' ],
            'dashicons-index-card',
            55
        );

        add_submenu_page(
            'cdo-main',
            __( 'Overview', 'custom-data-organizer' ),
            __( 'Overview', 'custom-data-organizer' ),
            'edit_posts',
            'cdo-main',
            [ $this, 'render_overview' ]
        );

        // Build the per-CPT groups.
        $post_types = get_post_types(
            [
                '_builtin' => false,
                'show_ui'  => true,
            ],
            'objects'
        );

        if ( empty( $post_types ) ) {
            return;
        }

        foreach ( $post_types as $post_type ) {

            // Skip the internal ACF/SCF admin types.
            if ( strpos( $post_type->name, 'acf-' ) === 0 ) {
                continue;
            }

            $this->add_cpt_menu_group( $post_type );
        }
    }

    /**
     * For each CPT:
     *  - {Menu Name}           -> View All
     *  - — Add {Singular}
     *  - — Categories          -> first taxonomy (if any)
     *
     * @param WP_Post_Type $post_type
     */
    protected function add_cpt_menu_group( $post_type ) {

        $pt        = $post_type->name;
        $plural    = $post_type->labels->name;
        $menu_name = $post_type->labels->menu_name ?: $plural;
        $singular  = $post_type->labels->singular_name ?: rtrim( $menu_name, 's' );
        $cap       = $post_type->cap->edit_posts ?? 'edit_posts';

        // 1) View All {Plural}  (main entry)
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

        // 2) Add {Singular}
        add_submenu_page(
            'cdo-main',
            sprintf( __( 'Add %s', 'custom-data-organizer' ), $singular ),
            '— ' . sprintf( __( 'Add %s', 'custom-data-organizer' ), $singular ),
            $cap,
            'cdo-add-' . $pt,
            function() use ( $pt ) {
                wp_safe_redirect( admin_url( 'post-new.php?post_type=' . $pt ) );
                exit;
            }
        );

        // 3) Categories (Taxonomies) – if any exist for this CPT.
        $taxonomies = get_object_taxonomies( $pt, 'objects' );

        if ( ! empty( $taxonomies ) ) {

            $taxonomy = reset( $taxonomies );

            add_submenu_page(
                'cdo-main',
                sprintf( __( 'Categories (%s)', 'custom-data-organizer' ), $menu_name ),
                '— ' . __( 'Categories', 'custom-data-organizer' ),
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
     * Filter CPT registration so that any non-core CPT's menu appears
     * ONLY under our "Custom Data" menu and not as a separate parent item.
     *
     * @param array  $args
     * @param string $post_type
     *
     * @return array
     */
    public function attach_cpts_to_custom_data_menu( $args, $post_type ) {

        // Skip core post types.
        if ( in_array( $post_type, $this->core_post_types, true ) ) {
            return $args;
        }

        // Skip internal ACF/SCF admin types.
        if ( strpos( $post_type, 'acf-' ) === 0 ) {
            return $args;
        }

        // If another plugin intentionally hides it, respect that.
        if ( isset( $args['show_in_menu'] ) && $args['show_in_menu'] === false ) {
            return $args;
        }

        // For any CPT that WOULD have its own menu, move it under our menu.
        // (true means default "own menu", so we override that too.)
        if ( ! isset( $args['show_in_menu'] ) || $args['show_in_menu'] === true || $args['show_in_menu'] === 'edit.php?post_type=' . $post_type ) {
            $args['show_in_menu'] = 'cdo-main';
        }

        return $args;
    }

    /**
     * Simple overview page so you know what's going on.
     */
    public function render_overview() {

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Custom Data Organizer', 'custom-data-organizer' ) . '</h1>';
        echo '<p>' . esc_html__( 'All custom post types are grouped under the “Custom Data” menu. Each type gets:', 'custom-data-organizer' ) . '</p>';
        echo '<ul style="list-style: disc; margin-left: 20px;">';
        echo '<li>' . esc_html__( 'Main entry → View All {Plural}', 'custom-data-organizer' ) . '</li>';
        echo '<li>' . esc_html__( 'Indented entry → Add {Singular}', 'custom-data-organizer' ) . '</li>';
        echo '<li>' . esc_html__( 'Indented entry → Categories (first taxonomy attached to that type)', 'custom-data-organizer' ) . '</li>';
        echo '</ul>';

        echo '</div>';
    }
}