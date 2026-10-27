<?php
/**
 * Outgoing mail: each message carries a branded HTML version and a plain-text
 * one (see email.php), so it reads well in any mail app.
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
function send_mail( $to, $subject, array $content ) {
	try {
		list( $headers, $body ) = mime_message( $content[0], $content[1] );

		if ( config( 'smtp' ) ) {
			smtp_send( $to, $subject, $headers, $body );
		} else {
			if ( ! function_exists( 'mail' ) ) {
				throw new RuntimeException( 'mail() is disabled on this server and no smtp settings are configured' );
			}
			$from    = config( 'mail_from' );
			$headers = array_merge( array( 'From: ' . mb_encode_mimeheader( config( 'mail_from_name' ), 'UTF-8' ) . ' <' . $from . '>' ), $headers );
			if ( ! @mail( $to, mb_encode_mimeheader( $subject, 'UTF-8' ), $body, implode( "\r\n", $headers ), '-f' . $from ) ) {
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
 * A multipart/alternative body: plain text first, HTML last, so clients that
 * can show HTML do and the rest fall back to the text.
 *
 * @return array array( header lines, body )
 */
function mime_message( $text, $html ) {
	$boundary = 'hw-' . bin2hex( random_bytes( 12 ) );
	$part     = function ( $type, $content ) use ( $boundary ) {
		return '--' . $boundary . "\r\n"
			. 'Content-Type: ' . $type . '; charset=UTF-8' . "\r\n"
			. 'Content-Transfer-Encoding: base64' . "\r\n\r\n"
			// Base64 lines never start with ".", so no SMTP dot-stuffing is needed.
			. rtrim( chunk_split( base64_encode( $content ), 76, "\r\n" ) ) . "\r\n";
	};

	return array(
		array( 'MIME-Version: 1.0', 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' ),
		$part( 'text/plain', $text ) . $part( 'text/html', $html ) . '--' . $boundary . '--',
	);
}

/**
 * A minimal SMTP client: EHLO, STARTTLS when offered, AUTH LOGIN when a user
 * is configured, one message. Enough for the server's own mail system, without pulling in a
 * library.
 *
 * @throws RuntimeException On any refusal, with the server's reply.
 */
function smtp_send( $to, $subject, array $headers, $body ) {
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
			array_merge(
				array(
					'Date: ' . date( 'r' ),
					'From: ' . mb_encode_mimeheader( config( 'mail_from_name' ), 'UTF-8' ) . ' <' . $from . '>',
					'To: <' . $to . '>',
					'Subject: ' . mb_encode_mimeheader( $subject, 'UTF-8' ),
					'Message-ID: <' . bin2hex( random_bytes( 12 ) ) . '@' . $me . '>',
				),
				$headers,
				array( '', $body )
			)
		);

		$talk( $message . "\r\n.", array( 250 ) );
		$talk( 'QUIT', array( 221 ) );
	} finally {
		fclose( $sock );
	}
}

function send_welcome( array $user, $password ) {
	$site  = config( 'site_name' );
	$group = setting( 'whatsapp_group' );

	return send_mail(
		$user['email'],
		sprintf( 'Welcome to %s - your login details', $site ),
		email_content(
			array(
				'preheader'     => 'Your realtor portal account is ready. Here are your login details.',
				'heading'       => 'Welcome to ' . $site,
				'greeting'      => sprintf( 'Dear %s,', $user['full_name'] ),
				'paragraphs'    => array( 'Your realtor portal account is ready. Below are your login details. Please keep them private.' ),
				'details_title' => 'Your login details',
				'details'       => array(
					'CID (User ID)' => $user['ref_code'],
					'Username'      => $user['username'],
					'Email'         => $user['email'],
					'Password'      => $password,
				),
				'button'        => array( 'Sign in to your portal', absolute_url( 'login' ) ),
				'link'          => array( 'Your referral link. Anyone who registers with it becomes your premium-line:', referral_link( $user['ref_code'] ) ),
				'extra'         => $group ? array( 'Join our WhatsApp group', $group ) : null,
				'note'          => 'Forgot your password later? Use "Forgot your password?" on the sign-in page.',
			)
		)
	);
}

/** Tells a member that someone joined with their referral link. */
function send_new_line_notice( array $star, array $member ) {
	return send_mail(
		$star['email'],
		sprintf( 'New premium-line: %s joined with your link', $member['full_name'] ),
		email_content(
			array(
				'preheader'     => sprintf( '%s registered with your referral link.', $member['full_name'] ),
				'heading'       => 'You have a new premium-line',
				'greeting'      => sprintf( 'Dear %s,', $star['full_name'] ),
				'paragraphs'    => array(
					sprintf( '%s has just registered with your referral link and is now your premium-line.', $member['full_name'] ),
					'You earn commission whenever they sell a property.',
				),
				'details_title' => 'Your new premium-line',
				'details'       => array(
					'Name'  => $member['full_name'],
					'Email' => $member['email'],
					'Phone' => '' !== $member['phone'] ? $member['phone'] : '-',
					'CID'   => $member['ref_code'],
				),
				'button'        => array( 'View your premium-lines', absolute_url( 'premium-lines' ) ),
			)
		)
	);
}

/**
 * Tells a premium-star they have earned commission.
 *
 * @param array $sale seller, type, amount, earned, rate.
 */
function send_commission_notice( $user, array $sale ) {
	if ( ! $user ) {
		return false;
	}

	return send_mail(
		$user['email'],
		sprintf( 'You earned %s in commission', money( $sale['earned'] ) ),
		email_content(
			array(
				'preheader'     => sprintf( '%s sold a %s. Your commission: %s.', $sale['seller'], $sale['type'], money( $sale['earned'] ) ),
				'heading'       => 'You earned a commission',
				'greeting'      => sprintf( 'Dear %s,', $user['full_name'] ),
				'paragraphs'    => array( sprintf( 'Good news: %s, your premium-line, has sold a property.', $sale['seller'] ) ),
				'highlight'     => array( 'Your commission', money( $sale['earned'] ) ),
				'details_title' => 'The sale',
				'details'       => array(
					'Sold by'         => $sale['seller'],
					'Property'        => ucfirst( $sale['type'] ),
					'Sale amount'     => money( $sale['amount'] ),
					'Commission rate' => $sale['rate'] . '%',
				),
				'button'        => array( 'View your earnings', absolute_url() ),
			)
		)
	);
}

function send_password_reset( array $user, $link ) {
	return send_mail(
		$user['email'],
		sprintf( '%s - reset your password', config( 'site_name' ) ),
		email_content(
			array(
				'preheader'  => 'Choose a new password for your realtor portal account.',
				'heading'    => 'Reset your password',
				'greeting'   => sprintf( 'Dear %s,', $user['full_name'] ),
				'paragraphs' => array(
					'We received a request to reset the password for your realtor portal account.',
					sprintf( 'Click the button below to choose a new one. The link works once and expires in %d hour.', RESET_LIFETIME_HOURS ),
				),
				'button'     => array( 'Choose a new password', $link ),
				'link'       => array( 'If the button does not work, copy this link into your browser:', $link ),
				'note'       => 'If you did not ask for this, you can ignore this email. Your password has not changed.',
			)
		)
	);
}
