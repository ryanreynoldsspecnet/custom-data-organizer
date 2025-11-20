<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the SCF Admin Organizer menu and routing.
 */
class SCAO_Admin_Menu {

    public function __construct() {
        // High priority so we run after most menus are registered.
        add_action( 'admin_menu', [ $this, 'register_menu' ], 99 );
    }

    /**
     * Register top-level and dynamic submenu items.
     */
    public function register_menu() {

        // Top-level menu.
        add_menu_page(
            __( 'SCF Admin Organizer', 'scf-admin-organizer' ),
            __( 'SCF Fields', 'scf-admin-organizer' ),
            'manage_options',
            'scao-dashboard',
            [ $this, 'render_dashboard' ],
            'dashicons-index-card',
            58
        );

        // Get all SCF/ACF field groups.
        $groups = acf_get_field_groups();

        if ( empty( $groups ) ) {
            // No submenu needed if no groups.
            return;
        }

        // Add one submenu per field group.
        foreach ( $groups as $group ) {

            // Some safety: ensure it has ID and title.
            if ( empty( $group['ID'] ) || empty( $group['title'] ) ) {
                continue;
            }

            $submenu_slug = 'scao-field-group-' . intval( $group['ID'] );

            add_submenu_page(
                'scao-dashboard',
                esc_html( $group['title'] ),
                esc_html( $group['title'] ),
                'manage_options',
                $submenu_slug,
                function() use ( $group ) {
                    $this->redirect_to_field_group( $group );
                }
            );
        }
    }

    /**
     * Overview page listing all field groups.
     */
    public function render_dashboard() {

        if ( ! function_exists( 'acf_get_field_groups' ) ) {
            echo '<div class="wrap"><h1>SCF Admin Organizer</h1>';
            echo '<p>' . esc_html__( 'Secure Custom Fields (or ACF) does not appear to be active.', 'scf-admin-organizer' ) . '</p>';
            echo '</div>';
            return;
        }

        $groups = acf_get_field_groups();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'SCF Admin Organizer', 'scf-admin-organizer' ) . '</h1>';
        echo '<p>' . esc_html__( 'Quick access to all Secure Custom Fields / ACF field groups from one place.', 'scf-admin-organizer' ) . '</p>';

        if ( empty( $groups ) ) {
            echo '<p>' . esc_html__( 'No field groups found.', 'scf-admin-organizer' ) . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped" style="max-width: 900px; margin-top: 20px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Field Group', 'scf-admin-organizer' ) . '</th>';
        echo '<th>' . esc_html__( 'Location', 'scf-admin-organizer' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'scf-admin-organizer' ) . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ( $groups as $group ) {

            $id    = isset( $group['ID'] ) ? intval( $group['ID'] ) : 0;
            $title = isset( $group['title'] ) ? $group['title'] : '';
            $key   = isset( $group['key'] ) ? $group['key'] : '';

            if ( ! $id || ! $title ) {
                continue;
            }

            $edit_url = admin_url( 'post.php?post=' . $id . '&action=edit' );

            // Location summary (very basic, just for info).
            $location_summary = '';
            if ( ! empty( $group['location'] ) && is_array( $group['location'] ) ) {
                $location_summary = $this->format_location_rules( $group['location'] );
            }

            echo '<tr>';
            echo '<td><strong>' . esc_html( $title ) . '</strong><br><code>' . esc_html( $key ) . '</code></td>';
            echo '<td>' . wp_kses_post( $location_summary ) . '</td>';
            echo '<td><a class="button button-primary" href="' . esc_url( $edit_url ) . '">';
            echo esc_html__( 'Edit Field Group', 'scf-admin-organizer' );
            echo '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Redirect submenu click to the native SCF/ACF field group edit screen.
     *
     * @param array $group
     */
    private function redirect_to_field_group( $group ) {

        if ( empty( $group['ID'] ) ) {
            wp_die( esc_html__( 'Invalid field group.', 'scf-admin-organizer' ) );
        }

        $edit_url = admin_url( 'post.php?post=' . intval( $group['ID'] ) . '&action=edit' );

        wp_safe_redirect( $edit_url );
        exit;
    }

    /**
     * Lightly format location rules for display on the dashboard.
     *
     * This is just a helper for your own overview; it doesn’t change behavior.
     *
     * @param array $location
     * @return string
     */
    private function format_location_rules( $location ) {

        // $location is typically a group of rule groups: [ [ [param, operator, value], ... ], ... ]
        $parts = [];

        foreach ( $location as $group_index => $rule_group ) {
            $group_parts = [];

            if ( ! is_array( $rule_group ) ) {
                continue;
            }

            foreach ( $rule_group as $rule ) {
                if ( empty( $rule['param'] ) || empty( $rule['operator'] ) || ! isset( $rule['value'] ) ) {
                    continue;
                }

                $group_parts[] = sprintf(
                    '%s %s %s',
                    esc_html( $rule['param'] ),
                    esc_html( $rule['operator'] ),
                    esc_html( $rule['value'] )
                );
            }

            if ( ! empty( $group_parts ) ) {
                $parts[] = implode( ' &amp; ', $group_parts );
            }
        }

        if ( empty( $parts ) ) {
            return esc_html__( '—', 'scf-admin-organizer' );
        }

        // OR between rule groups.
        return implode( '<br><em>' . esc_html__( 'OR', 'scf-admin-organizer' ) . '</em><br>', $parts );
    }
}