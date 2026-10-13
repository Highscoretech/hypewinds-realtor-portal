<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outgoing mail. Uses wp_mail, so whatever SMTP the site already has
 * configured is reused and nothing new needs credentials.
 */
class HRP_Mail {

	/**
	 * wp_mail that cannot take the page down.
	 *
	 * Some hosts disable PHP's mail() outright, and PHPMailer then raises a
	 * fatal Error rather than returning false. Registration must survive that:
	 * the account is already created by the time we send.
	 */
	public static function safe_mail( $to, $subject, $message ) {
		try {
			return (bool) wp_mail( $to, $subject, $message );
		} catch ( \Throwable $e ) {
			error_log( 'HRP mail failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Login details for a newly registered member.
	 *
	 * @return bool Whether wp_mail accepted the message for delivery.
	 */
	public static function send_welcome( $user_id, $password, $ref_code ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		$ref_link = add_query_arg( 'ref', rawurlencode( $ref_code ), home_url( '/' ) );

		$lines = array(
			sprintf( 'Dear %s,', $user->display_name ),
			'',
			sprintf( 'Welcome to %s. Kindly find below your login details.', hrp_site_name() ),
			'',
			sprintf( 'CID (USER ID): %s', $ref_code ),
			sprintf( 'Email: %s', $user->user_email ),
			sprintf( 'Password: %s', $password ),
			'',
			sprintf( 'Your referral link is %s', $ref_link ),
			'',
			sprintf( 'Sign in here: %s', hrp_portal_url() ),
		);

		$group = get_option( 'hrp_whatsapp_group' );
		if ( $group ) {
			$lines[] = '';
			$lines[] = sprintf( 'Kindly join our whatsapp group with the link below: %s', $group );
		}

		return self::safe_mail(
			$user->user_email,
			sprintf( 'Welcome %s', hrp_site_name() ),
			implode( "\n", $lines )
		);
	}

	/** Tells a premium-star they have earned commission. */
	public static function send_commission_notice( $user_id, $message ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		return self::safe_mail(
			$user->user_email,
			sprintf( '%s - commission earned', hrp_site_name() ),
			sprintf( "Dear %s,\n\n%s\n\nView your portal: %s", $user->display_name, $message, hrp_portal_url() )
		);
	}
}
