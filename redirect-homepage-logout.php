<?php
/*
 * Plugin Name: Redirect to Homepage After Logout
 * Description: Redirects users to a custom URL, page, or homepage after they log out.
 * Author: Sarangan Thillaiampalam
 * Version: 1.0
 * Author URI: https://sarangan.dk
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register settings and admin menu
 */
function rhal_settings_init() {
    register_setting( 'rhal_settings_group', 'rhal_settings' );

    add_settings_section(
        'rhal_main_section',
        __( 'Redirect Settings', 'redirect-to-homepage-after-logout' ),
        'rhal_section_callback',
        'rhal-settings-page'
    );

    add_settings_field(
        'rhal_enable',
        __( 'Enable Redirect', 'redirect-to-homepage-after-logout' ),
        'rhal_enable_render',
        'rhal-settings-page',
        'rhal_main_section'
    );

    add_settings_field(
        'rhal_type',
        __( 'Redirect To', 'redirect-to-homepage-after-logout' ),
        'rhal_type_render',
        'rhal-settings-page',
        'rhal_main_section'
    );

    add_settings_field(
        'rhal_page_id',
        __( 'Select Page', 'redirect-to-homepage-after-logout' ),
        'rhal_page_render',
        'rhal-settings-page',
        'rhal_main_section'
    );

    add_settings_field(
        'rhal_custom_url',
        __( 'Custom URL', 'redirect-to-homepage-after-logout' ),
        'rhal_url_render',
        'rhal-settings-page',
        'rhal_main_section'
    );
}
add_action( 'admin_init', 'rhal_settings_init' );

function rhal_section_callback() {
    echo __( 'Configure where users should be sent after logging out.', 'redirect-to-homepage-after-logout' );
}

function rhal_enable_render() {
    $options = get_option( 'rhal_settings' );
    // Default to '1' if the option doesn't exist yet (new install)
    $enabled = ( $options === false ) ? '1' : ( isset( $options['rhal_enable'] ) ? $options['rhal_enable'] : '0' );
    ?>
    <input type='checkbox' name='rhal_settings[rhal_enable]' <?php checked( $enabled, '1' ); ?> value='1'>
    <?php
}

function rhal_type_render() {
    $options = get_option( 'rhal_settings' );
    $type = isset( $options['rhal_type'] ) ? $options['rhal_type'] : 'home';
    ?>
    <select name='rhal_settings[rhal_type]'>
        <option value='home' <?php selected( $type, 'home' ); ?>>Homepage</option>
        <option value='page' <?php selected( $type, 'page' ); ?>>A Specific Page</option>
        <option value='custom' <?php selected( $type, 'custom' ); ?>>Custom URL</option>
    </select>
    <?php
}

function rhal_page_render() {
    $options = get_option( 'rhal_settings' );
    $page_id = isset( $options['rhal_page_id'] ) ? $options['rhal_page_id'] : 0;
    wp_dropdown_pages( array(
        'name'              => 'rhal_settings[rhal_page_id]',
        'selected'          => $page_id,
        'show_option_none'  => '-- Select Page --',
        'option_none_value' => '0',
    ) );
}

function rhal_url_render() {
    $options = get_option( 'rhal_settings' );
    $url = isset( $options['rhal_custom_url'] ) ? $options['rhal_custom_url'] : '';
    ?>
    <input type='url' name='rhal_settings[rhal_custom_url]' value='<?php echo esc_url( $url ); ?>' class='regular-text' placeholder='https://example.com'>
    <?php
}

/**
 * Add Admin Menu
 */
function rhal_add_admin_menu() {
    add_options_page(
        'Logout Redirect',
        'Logout Redirect',
        'manage_options',
        'logout-redirect',
        'rhal_options_page'
    );
}
add_action( 'admin_menu', 'rhal_add_admin_menu' );

function rhal_options_page() {
    ?>
    <div class="wrap">
        <h1>Logout Redirect Settings</h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields( 'rhal_settings_group' );
            do_settings_sections( 'rhal-settings-page' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Add Settings link to plugin page
 */
function rhal_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=logout-redirect">' . __( 'Settings', 'redirect-to-homepage-after-logout' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
$plugin_file = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin_file", 'rhal_add_settings_link' );

/**
 * Perform Redirection
 */
function wpc_auto_redirect_after_logout() {
    $options = get_option( 'rhal_settings' );

    // If no settings exist yet, default to enabled + homepage
    if ( $options === false ) {
        wp_safe_redirect( home_url() );
        exit();
    }

    // Check if explicitly enabled
    if ( ! isset( $options['rhal_enable'] ) ) {
        return;
    }

    $redirect_url = home_url(); // Default
    $type = isset( $options['rhal_type'] ) ? $options['rhal_type'] : 'home';

    if ( $type === 'page' && ! empty( $options['rhal_page_id'] ) ) {
        $redirect_url = get_permalink( $options['rhal_page_id'] );
    } elseif ( $type === 'custom' && ! empty( $options['rhal_custom_url'] ) ) {
        $redirect_url = $options['rhal_custom_url'];
        // Use wp_redirect for custom URLs to allow external sites
        wp_redirect( $redirect_url );
        exit();
    }

    // Default/Homepage/Page redirects use safe redirect
    wp_safe_redirect( $redirect_url );
    exit();
}
add_action( 'wp_logout', 'wpc_auto_redirect_after_logout' );
