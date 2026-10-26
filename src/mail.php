<?php
/**
 * Outgoing mail. Plain text: these are notices, not newsletters.
 *
 * Sent over SMTP with a real mailbox's login when config.php has an 'smtp'
 * block, which is the case on the live server: its host disables PHP's
 * mail() outright. Without one, falls back to mail() (local testing).
 */

defined( 'PORTAL' ) || exit;

/**
 * Sends a message and cannot take the page down: the account or sale is
 * already saved by the time we send, so a failure is logged and reported to
 * the caller, never fatal.
 */
function send_mail( $to, $subject, $body ) {
	try {
		if ( config( 'smtp' ) ) {
			smtp_send( $to, $subject, $body );
		} else {
			if ( ! function_exists( 'mail' ) ) {
				throw new RuntimeException( 'mail() is disabled on this server and no smtp settings are configured' );
			}
			$from    = config( 'mail_from' );
			$headers = array(
				'From'                      => sprintf( '%s <%s>', mb_encode_mimeheader( config( 'mail_from_name' ), 'UTF-8' ), $from ),
				'MIME-Version'              => '1.0',
				'Content-Type'              => 'text/plain; charset=UTF-8',
				'Content-Transfer-Encoding' => '8bit',
			);
			if ( ! @mail( $to, mb_encode_mimeheader( $subject, 'UTF-8' ), $body, $headers, '-f' . $from ) ) {
				throw new RuntimeException( 'mail() returned false' );
			}
		}
		return true;
	} catch ( Throwable $e ) {
		error_log( 'Portal mail to ' . $to . ' failed: ' . $e->getMessage() );
		return false;
	}
}

/**
 * A minimal SMTP client: EHLO, STARTTLS when offered, AUTH LOGIN when a user
 * is configured, one message. Enough for the server's own mail system, without pulling in a
 * library.
 *
 * @throws RuntimeException On any refusal, with the server's reply.
 */
function smtp_send( $to, $subject, $body ) {
	$c    = config( 'smtp' );
	$from = config( 'mail_from' );
	$host = $c['host'];

	// The certificate names the server, not "localhost". Over loopback there
	// is no network to protect, so the name is not checked there.
	$local   = in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );
	$context = stream_context_create( array( 'ssl' => array( 'verify_peer' => ! $local, 'verify_peer_name' => ! $local ) ) );

	$sock = @stream_socket_client( 'tcp://' . $host . ':' . (int) $c['port'], $errno, $error, 15, STREAM_CLIENT_CONNECT, $context );
	if ( ! $sock ) {
		throw new RuntimeException( "could not connect to $host: $error" );
	}
	stream_set_timeout( $sock, 20 );

	$talk = function ( $line, array $expect ) use ( $sock ) {
		if ( null !== $line ) {
			fwrite( $sock, $line . "\r\n" );
		}
		$reply = '';
		while ( false !== ( $part = fgets( $sock, 1024 ) ) ) {
			$reply .= $part;
			if ( ! isset( $part[3] ) || ' ' === $part[3] ) {
				break; // Last line of a multi-line reply.
			}
		}
		if ( ! in_array( (int) substr( $reply, 0, 3 ), $expect, true ) ) {
			throw new RuntimeException( 'SMTP said: ' . trim( $reply ) );
		}
		return $reply;
	};

	try {
		$me = parse_url( config( 'base_url' ), PHP_URL_HOST );

		$talk( null, array( 220 ) );
		$ehlo = $talk( 'EHLO ' . $me, array( 250 ) );

		if ( false !== stripos( $ehlo, 'STARTTLS' ) ) {
			$talk( 'STARTTLS', array( 220 ) );
			if ( ! stream_socket_enable_crypto( $sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT ) ) {
				throw new RuntimeException( 'TLS handshake failed' );
			}
			$talk( 'EHLO ' . $me, array( 250 ) );
		}

		// The live server's own mail service takes mail from the site over
		// loopback without a login, so a mailbox login is optional.
		if ( ! empty( $c['user'] ) ) {
			$talk( 'AUTH LOGIN', array( 334 ) );
			$talk( base64_encode( $c['user'] ), array( 334 ) );
			$talk( base64_encode( $c['password'] ), array( 235 ) );
		}

		$talk( 'MAIL FROM:<' . $from . '>', array( 250 ) );
		$talk( 'RCPT TO:<' . $to . '>', array( 250, 251 ) );
		$talk( 'DATA', array( 354 ) );

		$message = implode(
			"\r\n",
			array(
				'Date: ' . date( 'r' ),
				'From: ' . mb_encode_mimeheader( config( 'mail_from_name' ), 'UTF-8' ) . ' <' . $from . '>',
				'To: <' . $to . '>',
				'Subject: ' . mb_encode_mimeheader( $subject, 'UTF-8' ),
				'Message-ID: <' . bin2hex( random_bytes( 12 ) ) . '@' . $me . '>',
				'MIME-Version: 1.0',
				'Content-Type: text/plain; charset=UTF-8',
				'Content-Transfer-Encoding: base64',
				'',
				rtrim( chunk_split( base64_encode( $body ), 76, "\r\n" ) ),
			)
		);

		// Base64 lines never start with ".", so no dot-stuffing is needed.
		$talk( $message . "\r\n.", array( 250 ) );
		$talk( 'QUIT', array( 221 ) );
	} finally {
		fclose( $sock );
	}
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
