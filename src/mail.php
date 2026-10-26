<?php
/**
 * Outgoing mail through PHP's mail(), which cPanel hosts route through the
 * server's own mailer. Plain text: these are notices, not newsletters.
 */

defined( 'PORTAL' ) || exit;

/**
 * mail() that cannot take the page down. Some hosts disable it outright; the
 * account or sale is already saved by the time we send, so a mail failure is
 * reported, never fatal.
 */
function send_mail( $to, $subject, $body ) {
	$from    = config( 'mail_from' );
	$name    = config( 'mail_from_name' );
	$headers = array(
		'From'                      => sprintf( '%s <%s>', mb_encode_mimeheader( $name, 'UTF-8' ), $from ),
		'Reply-To'                  => $from,
		'MIME-Version'              => '1.0',
		'Content-Type'              => 'text/plain; charset=UTF-8',
		'Content-Transfer-Encoding' => '8bit',
	);

	try {
		if ( ! function_exists( 'mail' ) ) {
			throw new RuntimeException( 'mail() is disabled on this server' );
		}
		$ok = @mail( $to, mb_encode_mimeheader( $subject, 'UTF-8' ), $body, $headers, '-f' . $from );
	} catch ( Throwable $e ) {
		$ok = false;
		error_log( 'Portal mail to ' . $to . ' failed: ' . $e->getMessage() );
	}

	return (bool) $ok;
}

function send_welcome( array $user, $password ) {
	$lines = array(
		sprintf( 'Dear %s,', $user['full_name'] ),
		'',
		sprintf( 'Welcome to %s. Kindly find below your login details.', config( 'site_name' ) ),
		'',
		sprintf( 'CID (USER ID): %s', $user['ref_code'] ),
		sprintf( 'Email: %s', $user['email'] ),
		sprintf( 'Password: %s', $password ),
		'',
		sprintf( 'Your referral link is %s', referral_link( $user['ref_code'] ) ),
		'',
		sprintf( 'Sign in here: %s', absolute_url( 'login' ) ),
	);

	$group = setting( 'whatsapp_group' );
	if ( $group ) {
		$lines[] = '';
		$lines[] = sprintf( 'Kindly join our whatsapp group with the link below: %s', $group );
	}

	return send_mail( $user['email'], sprintf( 'Welcome to %s', config( 'site_name' ) ), implode( "\n", $lines ) );
}

/** Tells a member that someone joined with their referral link. */
function send_new_line_notice( array $star, array $member ) {
	return send_mail(
		$star['email'],
		sprintf( '%s - you have a new premium-line', config( 'site_name' ) ),
		implode(
			"\n",
			array(
				sprintf( 'Dear %s,', $star['full_name'] ),
				'',
				sprintf( '%s has just registered with your referral link and is now your premium-line.', $member['full_name'] ),
				'',
				sprintf( 'Name: %s', $member['full_name'] ),
				sprintf( 'Email: %s', $member['email'] ),
				sprintf( 'Phone: %s', '' !== $member['phone'] ? $member['phone'] : '-' ),
				sprintf( 'CID: %s', $member['ref_code'] ),
				'',
				'You earn commission whenever they sell a property.',
				'',
				sprintf( 'See all your premium-lines: %s', absolute_url( 'premium-lines' ) ),
			)
		)
	);
}

/** Tells a premium-star they have earned commission. */
function send_commission_notice( $user, $message ) {
	if ( ! $user ) {
		return false;
	}

	return send_mail(
		$user['email'],
		sprintf( '%s - commission earned', config( 'site_name' ) ),
		sprintf( "Dear %s,\n\n%s\n\nView your portal: %s", $user['full_name'], $message, absolute_url() )
	);
}

function send_password_reset( array $user, $link ) {
	return send_mail(
		$user['email'],
		sprintf( '%s - reset your password', config( 'site_name' ) ),
		implode(
			"\n",
			array(
				sprintf( 'Dear %s,', $user['full_name'] ),
				'',
				'Someone asked to reset the password for your realtor portal account.',
				sprintf( 'To choose a new password, open this link within %d hour:', RESET_LIFETIME_HOURS ),
				'',
				$link,
				'',
				'If this was not you, ignore this email. Your password has not changed.',
			)
		)
	);
}
