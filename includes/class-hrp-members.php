<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Members, referral codes and the premium-star relationship.
 */
class HRP_Members {

	const META_REF_CODE    = 'hrp_ref_code';
	const META_PREMIUM_STAR = 'hrp_premium_star';
	const REF_COOKIE       = 'hrp_ref';

	/** Profile fields collected at registration. */
	public static function fields() {
		return array(
			'phone'           => 'Phone',
			'city'            => 'City',
			'date_of_birth'   => 'Date of birth',
			'state_of_origin' => 'State of Origin',
			'gender'          => 'Gender',
			'country'         => 'Country',
			'bank_name'       => 'Bank Name',
			'account_name'    => 'Account Name',
			'account_number'  => 'Account Number',
		);
	}

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'capture_referral' ) );
		add_action( 'init', array( __CLASS__, 'handle_registration' ) );
		add_action( 'init', array( __CLASS__, 'handle_lost_password' ) );

		// A member's premium-star is decided once, at sign-up. Nothing in the
		// admin screens should be able to move them afterwards, because that
		// would rewrite who earned commission on their past sales.
		add_filter( 'update_user_metadata', array( __CLASS__, 'block_premium_star_change' ), 10, 4 );
	}

	/**
	 * Referral links point at the site root: /?ref=CODE
	 *
	 * The code is remembered in a cookie and applied at registration, so the
	 * visitor can browse the site first without losing their referrer.
	 */
	public static function capture_referral() {
		if ( empty( $_GET['ref'] ) ) {
			return;
		}

		$code  = sanitize_text_field( wp_unslash( $_GET['ref'] ) );
		$owner = self::user_by_ref_code( $code );
		if ( ! $owner ) {
			return;
		}

		setcookie( self::REF_COOKIE, $code, time() + 30 * DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );

		$register = (int) get_option( 'hrp_page_register' );
		if ( ! $register || is_user_logged_in() ) {
			return;
		}

		// Already on the registration page: redirecting again would send it to
		// itself, since the ?ref= it carries re-triggers this same handler.
		if ( (int) get_queried_object_id() === $register ) {
			return;
		}

		$target = add_query_arg( 'ref', rawurlencode( $code ), get_permalink( $register ) );

		// Last line of defence. If the destination is where we already are,
		// redirecting can only loop, whatever the page checks above decided.
		$current = ( is_ssl() ? 'https://' : 'http://' )
			. ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' )
			. ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );

		if ( untrailingslashit( $target ) === untrailingslashit( $current ) ) {
			return;
		}

		wp_safe_redirect( $target );
		exit;
	}

	public static function user_by_ref_code( $code ) {
		if ( '' === trim( (string) $code ) ) {
			return null;
		}

		$users = get_users(
			array(
				'meta_key'   => self::META_REF_CODE,
				'meta_value' => trim( $code ),
				'number'     => 1,
				'fields'     => 'all',
			)
		);

		return $users ? $users[0] : null;
	}

	/** Six digits, matching the CID format members are given. */
	public static function generate_ref_code() {
		global $wpdb;

		do {
			$code  = (string) wp_rand( 100000, 999999 );
			$taken = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
					self::META_REF_CODE,
					$code
				)
			);
		} while ( $taken );

		return $code;
	}

	public static function ref_code( $user_id ) {
		return (string) get_user_meta( $user_id, self::META_REF_CODE, true );
	}

	public static function premium_star_id( $user_id ) {
		return (int) get_user_meta( $user_id, self::META_PREMIUM_STAR, true );
	}

	/**
	 * Refuses to change a premium-star once it holds a value. Returning a
	 * non-null value from this filter short-circuits the write.
	 */
	public static function block_premium_star_change( $check, $object_id, $meta_key, $meta_value ) {
		if ( self::META_PREMIUM_STAR !== $meta_key ) {
			return $check;
		}

		$current = get_user_meta( $object_id, self::META_PREMIUM_STAR, true );
		if ( '' === $current || null === $current ) {
			return $check; // First assignment, allow it.
		}

		if ( (int) $current === (int) $meta_value ) {
			return $check; // No change.
		}

		return false; // Already has an upline; keep it.
	}

	/**
	 * Registration. The member never chooses a password: one is generated
	 * and emailed, as the group's existing portal does.
	 */
	public static function handle_registration() {
		if ( empty( $_POST['hrp_action'] ) || 'register' !== $_POST['hrp_action'] ) {
			return;
		}

		$register_url = get_permalink( (int) get_option( 'hrp_page_register' ) );

		if ( ! isset( $_POST['hrp_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hrp_register_nonce'] ) ), 'hrp_register' ) ) {
			self::bail( $register_url, 'Your session expired. Please try again.' );
		}

		$full_name = sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) );
		$email     = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$ref       = sanitize_text_field( wp_unslash( $_POST['ref'] ?? '' ) );

		if ( '' === $full_name || ! is_email( $email ) ) {
			self::bail( $register_url, 'A full name and a valid email are required.', $ref );
		}
		if ( empty( $_POST['terms'] ) ) {
			self::bail( $register_url, 'You must agree to the terms.', $ref );
		}
		if ( email_exists( $email ) ) {
			self::bail( $register_url, 'An account with that email already exists.', $ref );
		}

		$star = $ref ? self::user_by_ref_code( $ref ) : null;
		if ( $ref && ! $star ) {
			self::bail( $register_url, 'That referral link is not valid.' );
		}

		$password = wp_generate_password( 12, false );
		$user_id  = wp_insert_user(
			array(
				'user_login'   => self::unique_login( $email ),
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $full_name,
				'first_name'   => $full_name,
				'role'         => HRP_ROLE,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			self::bail( $register_url, $user_id->get_error_message(), $ref );
		}

		$code = self::generate_ref_code();
		update_user_meta( $user_id, self::META_REF_CODE, $code );

		if ( $star ) {
			update_user_meta( $user_id, self::META_PREMIUM_STAR, (int) $star->ID );
			HRP_Commissions::notify(
				(int) $star->ID,
				sprintf( '%s joined with your link and is now your premium-line.', $full_name )
			);
		}

		foreach ( array_keys( self::fields() ) as $field ) {
			update_user_meta( $user_id, 'hrp_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) ) );
		}

		// The password exists nowhere else: it is hashed the moment the account
		// is created and cannot be read back. If the mail does not go out, show
		// it once here rather than stranding the member with an account they
		// can never reach.
		$sent = HRP_Mail::send_welcome( $user_id, $password, $code );

		$notice = $sent
			? 'Check your email for your login details.'
			: sprintf(
				'Your account is ready, but our email could not be sent. Save these now, they will not be shown again - CID: %s, Email: %s, Password: %s',
				$code,
				$email,
				$password
			);

		wp_safe_redirect( add_query_arg( 'hrp_notice', rawurlencode( $notice ), hrp_portal_url() ) );
		exit;
	}

	/**
	 * Password reset request raised from the portal.
	 *
	 * retrieve_password() sends through wp_mail, which raises a fatal Error on
	 * a host with PHP's mail() disabled, so the call is guarded: a broken
	 * mailer must not take the page down the way it does on wp-login.php.
	 */
	public static function handle_lost_password() {
		if ( empty( $_POST['hrp_action'] ) || 'lost_password' !== $_POST['hrp_action'] ) {
			return;
		}

		$forgot_url = add_query_arg( 'hrp', 'forgot', hrp_portal_url() );

		if ( ! isset( $_POST['hrp_lost_password_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hrp_lost_password_nonce'] ) ), 'hrp_lost_password' ) ) {
			self::bail( $forgot_url, 'Your session expired. Please try again.' );
		}

		$email = sanitize_email( wp_unslash( $_POST['user_login'] ?? '' ) );
		if ( ! is_email( $email ) ) {
			self::bail( $forgot_url, 'Enter the email address you registered with.' );
		}

		// retrieve_password() reads the address from this key.
		$_POST['user_login'] = $email;

		try {
			$result = retrieve_password( $email );
		} catch ( \Throwable $e ) {
			error_log( 'HRP password reset failed: ' . $e->getMessage() );
			self::bail( $forgot_url, 'We could not send the email just now. Please contact the group admin.' );
		}

		if ( is_wp_error( $result ) ) {
			self::bail( $forgot_url, $result->get_error_message() );
		}

		// Deliberately the same message whether or not the address is on file:
		// saying "no such account" would let anyone test which emails exist.
		wp_safe_redirect(
			add_query_arg(
				'hrp_notice',
				rawurlencode( 'If that email is registered, a reset link is on its way.' ),
				hrp_portal_url()
			)
		);
		exit;
	}

	private static function unique_login( $email ) {
		$base  = sanitize_user( current( explode( '@', $email ) ), true );
		$base  = $base ? $base : 'realtor';
		$login = $base;
		$n     = 1;

		while ( username_exists( $login ) ) {
			$login = $base . ++$n;
		}

		return $login;
	}

	private static function bail( $url, $message, $ref = '' ) {
		$args = array( 'hrp_error' => rawurlencode( $message ) );
		if ( $ref ) {
			$args['ref'] = rawurlencode( $ref );
		}

		wp_safe_redirect( add_query_arg( $args, $url ) );
		exit;
	}

	/**
	 * The three generations below a member.
	 *
	 * Returns array( 1 => WP_User[], 2 => ..., 3 => ... ).
	 */
	public static function generations( $user_id, $depth = 3 ) {
		$levels  = array();
		$current = array( (int) $user_id );

		for ( $level = 1; $level <= $depth; $level++ ) {
			if ( ! $current ) {
				$levels[ $level ] = array();
				continue;
			}

			$found = get_users(
				array(
					'meta_key'     => self::META_PREMIUM_STAR,
					'meta_value'   => $current,
					'meta_compare' => 'IN',
					'orderby'      => 'registered',
				)
			);

			$levels[ $level ] = $found;
			$current          = wp_list_pluck( $found, 'ID' );
		}

		return $levels;
	}
}
