<?php
/**
 * Signing in and out, who is signed in, and password resets.
 */

defined( 'PORTAL' ) || exit;

const LOGIN_WINDOW_MINUTES = 15;
const LOGIN_MAX_PER_EMAIL  = 5;
const LOGIN_MAX_PER_IP     = 20;
const RESET_LIFETIME_HOURS = 1;

function current_user() {
	static $user = false;

	if ( false === $user ) {
		$id   = (int) ( $_SESSION['user_id'] ?? 0 );
		$user = $id ? find_user( $id ) : null;
	}

	return $user;
}

function is_admin() {
	$user = current_user();
	return $user && 'admin' === $user['role'];
}

function require_login() {
	if ( ! current_user() ) {
		redirect( 'login' );
	}
}

function require_admin() {
	require_login();
	if ( ! is_admin() ) {
		not_found();
	}
}

function find_user( $id ) {
	$row = q( 'SELECT * FROM portal_users WHERE id = ?', array( (int) $id ) )->fetch();
	return $row ? $row : null;
}

function find_user_by_email( $email ) {
	$row = q( 'SELECT * FROM portal_users WHERE email = ?', array( strtolower( $email ) ) )->fetch();
	return $row ? $row : null;
}

/**
 * A realtor signs in with either their email or the six-digit CID from their
 * welcome email, which is the only identifier most of them keep to hand.
 */
function find_user_by_login( $login ) {
	$login = trim( $login );

	if ( preg_match( '/^\d{6}$/', $login ) ) {
		$row = q( 'SELECT * FROM portal_users WHERE ref_code = ?', array( $login ) )->fetch();
		return $row ? $row : null;
	}

	return find_user_by_email( $login );
}

/**
 * Too many recent failures for this address or this IP. Counted per email so
 * one account cannot be guessed at, and per IP so many accounts cannot be
 * sprayed from one place.
 */
function login_throttled( $email ) {
	$since = date( 'Y-m-d H:i:s', time() - LOGIN_WINDOW_MINUTES * 60 );

	$by_email = (int) q(
		'SELECT COUNT(*) FROM portal_login_failures WHERE email = ? AND created_at > ?',
		array( $email, $since )
	)->fetchColumn();

	$by_ip = (int) q(
		'SELECT COUNT(*) FROM portal_login_failures WHERE ip = ? AND created_at > ?',
		array( client_ip(), $since )
	)->fetchColumn();

	return $by_email >= LOGIN_MAX_PER_EMAIL || $by_ip >= LOGIN_MAX_PER_IP;
}

/** @return array|string The user on success, or the error to show. */
function attempt_login( $login, $password ) {
	$login = strtolower( trim( $login ) );

	if ( login_throttled( $login ) ) {
		return sprintf( 'Too many failed attempts. Please wait %d minutes and try again.', LOGIN_WINDOW_MINUTES );
	}

	$user = find_user_by_login( $login );

	// The same message whether the login or the password was wrong, so the
	// form cannot be used to find out who has an account.
	if ( ! $user || ! password_verify( $password, $user['password_hash'] ) ) {
		q(
			'INSERT INTO portal_login_failures (email, ip, created_at) VALUES (?, ?, ?)',
			array( $login, client_ip(), now() )
		);
		return 'Wrong login or password.';
	}

	if ( password_needs_rehash( $user['password_hash'], PASSWORD_DEFAULT ) ) {
		q( 'UPDATE portal_users SET password_hash = ? WHERE id = ?', array( password_hash( $password, PASSWORD_DEFAULT ), $user['id'] ) );
	}

	sign_in( $user );
	return $user;
}

function sign_in( array $user ) {
	// New session id on sign-in, so an id planted before login is worthless.
	session_regenerate_id( true );
	$_SESSION['user_id'] = (int) $user['id'];

	q( 'UPDATE portal_users SET last_login_at = ? WHERE id = ?', array( now(), $user['id'] ) );

	// Failures are recorded under whatever was typed, so clear the CID too or
	// a member who mistyped their password while using it stays throttled.
	q( 'DELETE FROM portal_login_failures WHERE email = ?', array( $user['email'] ) );
	if ( ! empty( $user['ref_code'] ) ) {
		q( 'DELETE FROM portal_login_failures WHERE email = ?', array( $user['ref_code'] ) );
	}
}

function sign_out() {
	$_SESSION = array();
	session_regenerate_id( true );
}

/**
 * Emails a reset link when the address belongs to an account. The caller
 * shows the same message either way.
 */
function request_password_reset( $email ) {
	$user = find_user_by_email( $email );
	if ( ! $user ) {
		return;
	}

	$token = bin2hex( random_bytes( 32 ) );

	q(
		'INSERT INTO portal_password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
		array( $user['id'], hash( 'sha256', $token ), date( 'Y-m-d H:i:s', time() + RESET_LIFETIME_HOURS * 3600 ) )
	);

	send_password_reset( $user, absolute_url( 'reset', array( 'token' => $token ) ) );
}

/** The unused, unexpired reset row for a token, or null. */
function find_reset( $token ) {
	if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
		return null;
	}

	$row = q(
		'SELECT * FROM portal_password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?',
		array( hash( 'sha256', $token ), now() )
	)->fetch();

	return $row ? $row : null;
}

function complete_password_reset( array $reset, $password ) {
	$pdo = db();
	$pdo->beginTransaction();

	q( 'UPDATE portal_users SET password_hash = ? WHERE id = ?', array( password_hash( $password, PASSWORD_DEFAULT ), $reset['user_id'] ) );
	// Every outstanding link for this account dies with the one just used.
	q( 'UPDATE portal_password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL', array( now(), $reset['user_id'] ) );

	$pdo->commit();
}

/** @return string|null The problem with a chosen password, or null if it is fine. */
function password_problem( $password, $confirm ) {
	if ( strlen( $password ) < 8 ) {
		return 'Use at least 8 characters.';
	}
	if ( $password !== $confirm ) {
		return 'The two passwords do not match.';
	}
	return null;
}
