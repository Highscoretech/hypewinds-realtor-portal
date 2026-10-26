<?php
/**
 * Loaded first on every request: config, database, session and the small
 * helpers every page uses.
 */

defined( 'PORTAL' ) || exit;

date_default_timezone_set( 'Africa/Lagos' );

function config( $key = null ) {
	static $config = null;

	if ( null === $config ) {
		$file = PORTAL_ROOT . '/config.php';
		if ( ! is_file( $file ) ) {
			http_response_code( 500 );
			exit( 'The portal is not configured: config.php is missing.' );
		}
		$config = require $file;
	}

	return null === $key ? $config : ( $config[ $key ] ?? null );
}

function db() {
	static $pdo = null;

	if ( null === $pdo ) {
		$c   = config( 'db' );
		$pdo = new PDO(
			sprintf( 'mysql:host=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['name'] ),
			$c['user'],
			$c['password'],
			array(
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES   => false,
			)
		);
		$pdo->exec( "SET time_zone = '+01:00'" );
	}

	return $pdo;
}

/** Runs a prepared statement and returns it. */
function q( $sql, array $params = array() ) {
	$stmt = db()->prepare( $sql );
	$stmt->execute( $params );
	return $stmt;
}

function now() {
	return date( 'Y-m-d H:i:s' );
}

function e( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

/** Path from the portal's root, e.g. url('register') -> /realtor-portal/register */
function url( $path = '', array $query = array() ) {
	$out = PORTAL_BASE . '/' . ltrim( $path, '/' );
	return $query ? $out . '?' . http_build_query( $query ) : $out;
}

/** Absolute URL for emails, built from config rather than the request's Host header. */
function absolute_url( $path = '', array $query = array() ) {
	$out = rtrim( config( 'base_url' ), '/' ) . '/' . ltrim( $path, '/' );
	return $query ? $out . '?' . http_build_query( $query ) : $out;
}

function asset( $file ) {
	$path = PORTAL_ROOT . '/assets/' . $file;
	$ver  = is_file( $path ) ? filemtime( $path ) : 0;
	return url( 'assets/' . $file, array( 'v' => $ver ) );
}

function redirect( $path, array $query = array() ) {
	header( 'Location: ' . url( $path, $query ) );
	exit;
}

function money( $amount ) {
	return config( 'currency' ) . number_format( (float) $amount, 2 );
}

function format_date( $value ) {
	return $value ? date( 'd/m/Y', strtotime( $value ) ) : '';
}

function is_post() {
	return 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' );
}

function input( $key, $default = '' ) {
	return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? trim( $_POST[ $key ] ) : $default;
}

function client_ip() {
	return $_SERVER['REMOTE_ADDR'] ?? '';
}

/* ---------------------------------------------------------------------------
   Session, flash messages and CSRF.
   --------------------------------------------------------------------------- */

function start_session() {
	$https = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] )
		|| 'https' === ( $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '' );

	session_name( 'hw_portal' );
	session_set_cookie_params(
		array(
			'lifetime' => 0,
			'path'     => PORTAL_BASE . '/',
			'secure'   => $https,
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
	session_start();
}

/** Message shown once on the next page. Kept in the session, never the URL. */
function flash( $type, $message ) {
	$_SESSION['flash'][] = array( $type, $message );
}

function take_flashes() {
	$out = $_SESSION['flash'] ?? array();
	unset( $_SESSION['flash'] );
	return $out;
}

function csrf_token() {
	if ( empty( $_SESSION['csrf'] ) ) {
		$_SESSION['csrf'] = bin2hex( random_bytes( 32 ) );
	}
	return $_SESSION['csrf'];
}

function csrf_field() {
	return '<input type="hidden" name="_csrf" value="' . e( csrf_token() ) . '">';
}

/** Rejects a POST that did not come from one of our own forms. */
function check_csrf( $back, array $query = array() ) {
	$sent = $_POST['_csrf'] ?? '';
	if ( ! is_string( $sent ) || ! hash_equals( csrf_token(), $sent ) ) {
		flash( 'error', 'Your session expired. Please try again.' );
		redirect( $back, $query );
	}
}

/* ---------------------------------------------------------------------------
   Views.
   --------------------------------------------------------------------------- */

function render( $view, array $data = array() ) {
	extract( $data, EXTR_SKIP );
	require PORTAL_ROOT . '/src/views/' . $view . '.php';
}

function not_found() {
	http_response_code( 404 );
	render( 'not-found' );
	exit;
}

/* ---------------------------------------------------------------------------
   Settings stored in the database, editable by an admin.
   --------------------------------------------------------------------------- */

function setting( $name, $default = '' ) {
	$value = q( 'SELECT value FROM portal_settings WHERE name = ?', array( $name ) )->fetchColumn();
	return false === $value ? $default : $value;
}

function save_setting( $name, $value ) {
	q(
		'INSERT INTO portal_settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)',
		array( $name, (string) $value )
	);
}
