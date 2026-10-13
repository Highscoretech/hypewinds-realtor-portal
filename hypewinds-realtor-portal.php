<?php
/**
 * Plugin Name: Hypewinds Realtor Portal
 * Description: Referral-based realtor portal: premium-stars, premium-lines, sales and commissions, built on the site's own user accounts.
 * Version: 1.0.0
 * Author: Hypewinds
 * Text Domain: hrp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HRP_VERSION', '1.0.0' );
define( 'HRP_FILE', __FILE__ );
define( 'HRP_DIR', plugin_dir_path( __FILE__ ) );
define( 'HRP_URL', plugin_dir_url( __FILE__ ) );

// The group name shown across the portal.
define( 'HRP_GROUP_NAME', 'HYPEWINDS PREMIUM REALTORS HUP' );

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


/**
 * The brand name used in portal emails and headings.
 *
 * RECONSTRUCTED 13 Oct 2026. The original file was deleted on 8 Oct and the
 * copy recovered from cPanel's trash predates these two helpers, which the
 * current includes call. Returns the WordPress site title, which is what the
 * mail subjects read as ("Welcome to Hypewinds properties"); change it to
 * HRP_GROUP_NAME if the group name was intended instead.
 */
function hrp_site_name() {
	$name = get_bloginfo( 'name' );
	return $name ? $name : HRP_GROUP_NAME;
}

/**
 * Logo markup for the portal sidebar and the signed-out screens.
 *
 * RECONSTRUCTED 13 Oct 2026, same reason as above. Uses the theme's custom
 * logo when one is set and falls back to the name as text, so the portal
 * never renders a broken image.
 */
function hrp_logo_html() {
	$logo_id = (int) get_theme_mod( 'custom_logo' );

	if ( $logo_id ) {
		$img = wp_get_attachment_image(
			$logo_id,
			'full',
			false,
			array(
				'alt'   => esc_attr( hrp_site_name() ),
				'class' => 'hrp-logo-img',
			)
		);
		if ( $img ) {
			return $img;
		}
	}

	return '<span class="hrp-logo-text">' . esc_html( hrp_site_name() ) . '</span>';
}
