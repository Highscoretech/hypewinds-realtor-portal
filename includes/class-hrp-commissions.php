<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sales, and the commission a sale pays to the seller's premium-star.
 */
class HRP_Commissions {

	public static function init() {}

	public static function rate() {
		return (float) get_option( 'hrp_commission_percent', 5 );
	}

	public static function sales_table() {
		global $wpdb;
		return $wpdb->prefix . 'hrp_sales';
	}

	public static function commissions_table() {
		global $wpdb;
		return $wpdb->prefix . 'hrp_commissions';
	}

	public static function properties_table() {
		global $wpdb;
		return $wpdb->prefix . 'hrp_properties';
	}

	public static function notifications_table() {
		global $wpdb;
		return $wpdb->prefix . 'hrp_notifications';
	}

	/**
	 * Records a sale and, when the seller has a premium-star, the commission
	 * it earns them.
	 *
	 * @return int|WP_Error The sale id.
	 */
	public static function record_sale( $args ) {
		global $wpdb;

		$seller_id = (int) ( $args['seller_id'] ?? 0 );
		$amount    = (float) ( $args['amount'] ?? 0 );

		if ( ! $seller_id || ! get_userdata( $seller_id ) ) {
			return new WP_Error( 'hrp_seller', 'Choose the realtor who made the sale.' );
		}
		if ( $amount <= 0 ) {
			return new WP_Error( 'hrp_amount', 'The sale amount must be greater than zero.' );
		}

		$now = current_time( 'mysql' );

		$wpdb->insert(
			self::sales_table(),
			array(
				'seller_id'     => $seller_id,
				'property_type' => sanitize_text_field( $args['property_type'] ?? 'land' ),
				'description'   => sanitize_text_field( $args['description'] ?? '' ),
				'amount'        => $amount,
				'sold_on'       => ! empty( $args['sold_on'] ) ? sanitize_text_field( $args['sold_on'] ) : current_time( 'Y-m-d' ),
				'recorded_by'   => get_current_user_id(),
				'created_at'    => $now,
			),
			array( '%d', '%s', '%s', '%f', '%s', '%d', '%s' )
		);

		$sale_id = (int) $wpdb->insert_id;
		if ( ! $sale_id ) {
			return new WP_Error( 'hrp_insert', 'The sale could not be saved.' );
		}

		$star_id = HRP_Members::premium_star_id( $seller_id );
		if ( ! $star_id ) {
			return $sale_id; // Seller joined without a referral link; nobody to pay.
		}

		$rate   = self::rate();
		$earned = round( $amount * $rate / 100, 2 );

		$wpdb->insert(
			self::commissions_table(),
			array(
				'sale_id'        => $sale_id,
				'beneficiary_id' => $star_id,
				'seller_id'      => $seller_id,
				'rate_percent'   => $rate,
				'amount'         => $earned,
				'created_at'     => $now,
			),
			array( '%d', '%d', '%d', '%f', '%f', '%s' )
		);

		$seller  = get_userdata( $seller_id );
		$message = sprintf(
			'%s sold a %s for %s. Your commission: %s (%s%%).',
			$seller ? $seller->display_name : 'A premium-line',
			sanitize_text_field( $args['property_type'] ?? 'property' ),
			self::money( $amount ),
			self::money( $earned ),
			$rate
		);

		self::notify( $star_id, $message );
		HRP_Mail::send_commission_notice( $star_id, $message );

		return $sale_id;
	}

	public static function notify( $user_id, $body ) {
		global $wpdb;

		$wpdb->insert(
			self::notifications_table(),
			array(
				'user_id'    => (int) $user_id,
				'body'       => wp_kses_post( $body ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s' )
		);
	}

	public static function notifications( $user_id, $limit = 20 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::notifications_table() . ' WHERE user_id = %d ORDER BY created_at DESC LIMIT %d',
				$user_id,
				$limit
			)
		);
	}

	/** Commission rows earned by a member. */
	public static function earnings( $user_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::commissions_table() . ' WHERE beneficiary_id = %d ORDER BY created_at DESC',
				$user_id
			)
		);
	}

	public static function total_earned( $user_id ) {
		global $wpdb;

		return (float) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(amount), 0) FROM ' . self::commissions_table() . ' WHERE beneficiary_id = %d',
				$user_id
			)
		);
	}

	/**
	 * The site's own property listings.
	 *
	 * Read from whichever post type the theme already uses, so the portal
	 * shows real listings rather than a second, parallel set someone would
	 * have to keep in step.
	 */
	public static function properties( $limit = 20 ) {
		$post_type = self::property_post_type();
		if ( ! $post_type ) {
			return array();
		}

		return get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
			)
		);
	}

	/** The chosen listings post type, or a sensible guess. */
	public static function property_post_type() {
		$chosen = get_option( 'hrp_property_post_type' );
		if ( $chosen && post_type_exists( $chosen ) ) {
			return $chosen;
		}

		foreach ( array( 'property', 'properties', 'listing', 'listings' ) as $guess ) {
			if ( post_type_exists( $guess ) ) {
				return $guess;
			}
		}

		return '';
	}

	/**
	 * Price for a listing. Themes store this in their own meta key, so the
	 * key is configurable and we show nothing rather than a wrong number.
	 */
	public static function property_price( $post_id ) {
		$key = get_option( 'hrp_price_meta_key', '' );
		if ( ! $key ) {
			return '';
		}

		$value = get_post_meta( $post_id, $key, true );
		return ( '' === $value || null === $value ) ? '' : $value;
	}

	public static function money( $amount ) {
		return number_format( (float) $amount, 2 );
	}
}
