<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tables, role and pages. Runs once on activation.
 */
class HRP_Install {

	public static function activate() {
		self::tables();
		self::role();
		self::pages();

		if ( false === get_option( 'hrp_commission_percent' ) ) {
			add_option( 'hrp_commission_percent', 5 );
		}

		flush_rewrite_rules();
	}

	private static function tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$p       = $wpdb->prefix;

		// Properties listed on the dashboard.
		dbDelta(
			"CREATE TABLE {$p}hrp_properties (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(190) NOT NULL,
				status varchar(40) NOT NULL DEFAULT 'Selling',
				price decimal(14,2) NOT NULL DEFAULT 0,
				location varchar(190) NOT NULL DEFAULT '',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY created_at (created_at)
			) {$charset};"
		);

		// A sale credited to the seller; the commission row is derived from it.
		dbDelta(
			"CREATE TABLE {$p}hrp_sales (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				seller_id bigint(20) unsigned NOT NULL,
				property_type varchar(20) NOT NULL DEFAULT 'land',
				description varchar(255) NOT NULL DEFAULT '',
				amount decimal(14,2) NOT NULL DEFAULT 0,
				sold_on date NOT NULL,
				recorded_by bigint(20) unsigned NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY seller_id (seller_id)
			) {$charset};"
		);

		// One commission per sale, paid to the seller's premium-star.
		dbDelta(
			"CREATE TABLE {$p}hrp_commissions (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				sale_id bigint(20) unsigned NOT NULL,
				beneficiary_id bigint(20) unsigned NOT NULL,
				seller_id bigint(20) unsigned NOT NULL,
				rate_percent decimal(5,2) NOT NULL DEFAULT 0,
				amount decimal(14,2) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY sale_id (sale_id),
				KEY beneficiary_id (beneficiary_id)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$p}hrp_notifications (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				body text NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id)
			) {$charset};"
		);
	}

	private static function role() {
		// Read-only capability set: members see the portal, nothing in wp-admin.
		add_role( HRP_ROLE, 'Realtor', array( 'read' => true ) );
	}

	/**
	 * Create the portal pages, remembering their IDs so renaming a page
	 * cannot orphan the links.
	 */
	private static function pages() {
		$pages = array(
			'hrp_page_dashboard'     => array( 'Realtor Portal', 'realtor-portal', '[hrp_dashboard]' ),
			'hrp_page_register'      => array( 'Realtor Registration', 'realtor-registration', '[hrp_register]' ),
			'hrp_page_premium_lines' => array( 'My Premium-Lines', 'my-premium-lines', '[hrp_premium_lines]' ),
		);

		foreach ( $pages as $option => $page ) {
			list( $title, $slug, $shortcode ) = $page;

			$existing = (int) get_option( $option );
			if ( $existing && 'page' === get_post_type( $existing ) && 'trash' !== get_post_status( $existing ) ) {
				continue;
			}

			$found = get_page_by_path( $slug );
			if ( $found ) {
				update_option( $option, (int) $found->ID );
				continue;
			}

			$id = wp_insert_post(
				array(
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_content' => $shortcode,
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);

			if ( $id && ! is_wp_error( $id ) ) {
				update_option( $option, (int) $id );
			}
		}
	}
}
