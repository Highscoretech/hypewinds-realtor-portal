<?php
/**
 * Plugin Name: Hypewinds Realtor Portal
 * Description: Referral-based realtor portal: premium-stars, premium-lines, sales and commissions, built on the site's own user accounts.
 * Version: 1.3.0
 * Author: Hypewinds
 * Text Domain: hrp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HRP_VERSION', '1.3.0' );
define( 'HRP_FILE', __FILE__ );
define( 'HRP_DIR', plugin_dir_path( __FILE__ ) );
define( 'HRP_URL', plugin_dir_url( __FILE__ ) );

/**
 * The portal brands itself from the site, not from a hardcoded name, so it
 * stays correct if the business name or logo ever changes.
 */
function hrp_site_name() {
	return get_bloginfo( 'name' );
}

/** The site's own logo, or its name when no logo is set. */
function hrp_logo_html() {
	$logo_id = (int) get_theme_mod( 'custom_logo' );

	if ( $logo_id ) {
		$img = wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'hrp-logo-img', 'alt' => hrp_site_name() ) );
		if ( $img ) {
			return $img;
		}
	}

	return '<span class="hrp-logo-text">' . esc_html( hrp_site_name() ) . '</span>';
}

// Member role. Kept separate from Subscriber so portal members are
// identifiable, and so losing the role cannot silently grant more access.
define( 'HRP_ROLE', 'hrp_realtor' );

require_once HRP_DIR . 'includes/class-hrp-install.php';
require_once HRP_DIR . 'includes/class-hrp-members.php';
require_once HRP_DIR . 'includes/class-hrp-mail.php';
require_once HRP_DIR . 'includes/class-hrp-commissions.php';
require_once HRP_DIR . 'includes/class-hrp-shortcodes.php';
require_once HRP_DIR . 'includes/class-hrp-admin.php';

register_activation_hook( __FILE__, array( 'HRP_Install', 'activate' ) );

add_action( 'plugins_loaded', function () {
	HRP_Members::init();
	HRP_Commissions::init();
	HRP_Shortcodes::init();
	HRP_Admin::init();
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'hrp-portal', HRP_URL . 'assets/portal.css', array(), HRP_VERSION );
} );

/** True on any of the portal's own pages. */
function hrp_is_portal_page() {
	$ids = array_filter(
		array(
			(int) get_option( 'hrp_page_dashboard' ),
			(int) get_option( 'hrp_page_register' ),
			(int) get_option( 'hrp_page_premium_lines' ),
		)
	);

	return $ids && is_page( $ids );
}

/**
 * The theme prints page titles in near-black on its dark header, which is
 * unreadable. Tag the portal's pages so the stylesheet can lighten just
 * those titles, without touching the rest of the site.
 */
add_filter( 'body_class', function ( $classes ) {
	if ( hrp_is_portal_page() ) {
		$classes[] = 'hrp-portal-page';
	}

	return $classes;
} );

/**
 * Where the footer link should point.
 *
 * Always the dashboard page: it sends signed-out visitors to the login form
 * itself, so one URL covers both states.
 */
function hrp_portal_url() {
	$page_id = (int) get_option( 'hrp_page_dashboard' );
	return $page_id ? get_permalink( $page_id ) : home_url( '/realtor-portal/' );
}
