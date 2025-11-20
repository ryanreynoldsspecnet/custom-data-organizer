<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom Data Organizer
 *
 * - Adds a "Custom Data" top-level menu.
 * - Settings page lets you choose which CPTs to group under it.
 * - Selected CPTs:
 *      • have their original top-level menu entries hidden
 *      • appear under "Custom Data" as:
 *
 *        Custom Data
 *            Accommodations
 *                – Add Accommodations
 *                – Categories
 *            Destinations
 *                – Add Destinations
 *                – Categories
 */
class CDO_Admin_Menu {

    const OPTION_KEY  = 'cdo_managed_post_types';
    const PARENT_SLUG = 'cdo-main';

    /**
     * Core post types we never touch (safety only).
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
        // Build our menu + submenus.
        add_action( 'admin_menu', [ $this, 'register_menus' ], 20 );

        // After all menus registered, hide original CPT menus.
        add_action( 'admin_menu', [ $this, 'hide_original_cpt_menus' ], 999 );
    }

    /**
     * Get CPT slugs that should be grouped under "Custom Data".
     */
    public static function get_managed_post_types(): array {
        $managed = get_option( self::OPTION_KEY, [] );

        if ( ! is_array( $managed ) ) {
            $managed = [];
        }

        $managed = array_map( 'sanitize_key', $managed );
        $managed = array_unique( $managed );

        return $managed;
    }

    /**
     * Register:
     * - "Custom Data" top-level menu
     * - Overview
     * - Settings
     * - Submenus for each selected CPT
     */
    public function register_menus() {

        // Parent menu.
        add_menu_page(
            __( 'Custom Data', 'custom-data-organizer' ),
            __( 'Custom Data', 'custom-data-organizer' ),
            'manage_options',
            self::PARENT_SLUG,
            [ $this, 'render_overview_page' ],
            'dashicons-index-card',
            30
        );

        // Overview submenu (same slug as parent).
        add_submenu_page(
            self::PARENT_SLUG,
            __( 'Overview', 'custom-data-organizer' ),
            __( 'Overview', 'custom-data-organizer' ),
            'manage_options',
            self::PARENT_SLUG,
            [ $this, 'render_overview_page' ]
        );

        // Settings page.
        add_submenu_page(
            self::PARENT_SLUG,
            __( 'Custom Data Organizer Settings', 'custom-data-organizer' ),
            __( 'Settings', 'custom-data-organizer' ),
            'manage_options',
            'cdo-settings',
            [ $this, 'render_settings_page' ]
        );

        // Add submenus for each CPT that has been selected.
        $managed = self::get_managed_post_types();

        if ( empty( $managed ) ) {
            return;
        }

        foreach ( $managed as $post_type ) {
            $pt_obj = get_post_type_object( $post_type );
            if ( ! $pt_obj ) {
                continue;
            }

            // Skip core types just in case.
            if ( in_array( $post_type, $this->core_post_types, true ) ) {
                continue;
            }

            $this->add_cpt_group( $pt_obj );
        }
    }

    /**
     * For one CPT, add:
     *  - {Plural Label}     → View all
     *  - — Add {Plural}     → Add new
     *  - — Categories       → first taxonomy, if any
     */
    protected function add_cpt_group( $pt_obj ) {

        $slug       = $pt_obj->name;
        $plural     = $pt_obj->labels->menu_name ?: $pt_obj->labels->name ?: ucfirst( $slug );
        $cap_edit   = $pt_obj->cap->edit_posts ?? 'edit_posts';
        $cap_create = $pt_obj->cap->create_posts ?? $cap_edit;

        // 1) View all.
        add_submenu_page(
            self::PARENT_SLUG,
            sprintf( __( 'View All %s', 'custom-data-organizer' ), $plural ),
            $plural,
            $cap_edit,
            'edit.php?post_type=' . $slug
        );

        // 2) Add new (visually indented with "— ").
        add_submenu_page(
            self::PARENT_SLUG,
            sprintf( __( 'Add %s', 'custom-data-organizer' ), $plural ),
            '— ' . sprintf( __( 'Add %s', 'custom-data-organizer' ), $plural ),
            $cap_create,
            'post-new.php?post_type=' . $slug
        );

        // 3) Categories/Taxonomies (first taxonomy, if present).
        $taxonomies = get_object_taxonomies( $slug, 'objects' );

        if ( ! empty( $taxonomies ) ) {
            $taxonomy = reset( $taxonomies );

            add_submenu_page(
                self::PARENT_SLUG,
                sprintf( __( '%s Taxonomies', 'custom-data-organizer' ), $plural ),
                '— ' . __( 'Categories', 'custom-data-organizer' ),
                'manage_categories',
                'edit-tags.php?taxonomy=' . $taxonomy->name . '&post_type=' . $slug
            );
        }
    }

    /**
     * Hide original top-level menus for selected CPTs,
     * AFTER all menus have been registered.
     */
    public function hide_original_cpt_menus() {

        $managed = self::get_managed_post_types();

        if ( empty( $managed ) ) {
            return;
        }

        foreach ( $managed as $post_type ) {
            $pt_obj = get_post_type_object( $post_type );
            if ( ! $pt_obj ) {
                continue;
            }

            // Core safety.
            if ( in_array( $post_type, $this->core_post_types, true ) ) {
                continue;
            }

            // Default CPT menu slug is usually "edit.php?post_type={slug}".
            $menu_slug = 'edit.php?post_type=' . $post_type;

            remove_menu_page( $menu_slug );
        }
    }

    /**
     * Overview page content.
     */
    public function render_overview_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Custom Data Organizer', 'custom-data-organizer' ); ?></h1>
            <p><?php esc_html_e( 'Selected custom post types are grouped here under a single "Custom Data" menu.', 'custom-data-organizer' ); ?></p>
            <p>
                <?php
                printf(
                    esc_html__( 'To choose which post types appear here, go to %sSettings → Custom Data Organizer%s.', 'custom-data-organizer' ),
                    '<a href="' . esc_url( admin_url( 'admin.php?page=cdo-settings' ) ) . '">',
                    '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Settings page: tick which CPTs to manage.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle save.
        if ( isset( $_POST['cdo_nonce'] ) && wp_verify_nonce( $_POST['cdo_nonce'], 'cdo_save_settings' ) ) {
            $selected = isset( $_POST['cdo_post_types'] ) ? (array) $_POST['cdo_post_types'] : [];
            $selected = array_map( 'sanitize_key', $selected );
            update_option( self::OPTION_KEY, $selected );

            echo '<div class="updated notice"><p>' . esc_html__( 'Settings saved.', 'custom-data-organizer' ) . '</p></div>';
        }

        // Candidate CPTs: public, non-builtin.
        $post_types = get_post_types(
            [
                'public'   => true,
                '_builtin' => false,
            ],
            'objects'
        );

        $managed = self::get_managed_post_types();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Custom Data Organizer Settings', 'custom-data-organizer' ); ?></h1>
            <p><?php esc_html_e( 'Select the custom post types you want grouped under the "Custom Data" menu.', 'custom-data-organizer' ); ?></p>

            <form method="post">
                <?php wp_nonce_field( 'cdo_save_settings', 'cdo_nonce' ); ?>

                <?php if ( empty( $post_types ) ) : ?>
                    <p><?php esc_html_e( 'No custom post types found.', 'custom-data-organizer' ); ?></p>
                <?php else : ?>
                    <table class="form-table">
                        <tbody>
                        <?php foreach ( $post_types as $slug => $pt_obj ) : ?>
                            <tr>
                                <th scope="row">
                                    <?php echo esc_html( $pt_obj->labels->name ); ?>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="cdo_post_types[]"
                                               value="<?php echo esc_attr( $slug ); ?>"
                                            <?php checked( in_array( $slug, $managed, true ) ); ?>
                                        />
                                        <code><?php echo esc_html( $slug ); ?></code>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}