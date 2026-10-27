<?php
/**
 * Hypewinds realtor portal. Every request comes through here (see .htaccess).
 */

// PHP's built-in server, for local testing. It ignores .htaccess, so only
// assets/ is served as files, as on the live server.
if ( 'cli-server' === PHP_SAPI ) {
	$file = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
	if ( 0 === strpos( $file, '/assets/' ) && is_file( __DIR__ . $file ) ) {
		return false;
	}
}

define( 'PORTAL', true );
define( 'PORTAL_ROOT', __DIR__ );

// The folder the portal is served from, e.g. /realtor-portal, or '' at a root.
define( 'PORTAL_BASE', rtrim( str_replace( '\\', '/', dirname( $_SERVER['SCRIPT_NAME'] ) ), '/' ) );

require __DIR__ . '/src/bootstrap.php';
require __DIR__ . '/src/auth.php';
require __DIR__ . '/src/members.php';
require __DIR__ . '/src/commissions.php';
require __DIR__ . '/src/mail.php';
require __DIR__ . '/src/email.php';
require __DIR__ . '/src/layout.php';

header( 'X-Frame-Options: DENY' );
header( 'X-Content-Type-Options: nosniff' );
header( 'Referrer-Policy: same-origin' );

start_session();

$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
if ( '' !== PORTAL_BASE && 0 === strpos( $path, PORTAL_BASE ) ) {
	$path = substr( $path, strlen( PORTAL_BASE ) );
}
$path = trim( $path, '/' );
if ( 'index.php' === $path ) {
	$path = '';
}

$routes = array(
	''                 => 'page_dashboard',
	'login'            => 'page_login',
	'logout'           => 'page_logout',
	'forgot'           => 'page_forgot',
	'reset'            => 'page_reset',
	'register'         => 'page_register',
	'premium-lines'    => 'page_premium_lines',
	'admin'            => 'page_admin_sales',
	'admin/members'    => 'page_admin_members',
	'admin/properties' => 'page_admin_properties',
	'admin/settings'   => 'page_admin_settings',
	'setup'            => 'page_setup',
);

if ( ! isset( $routes[ $path ] ) ) {
	not_found();
}

$routes[ $path ]();

/* ---------------------------------------------------------------------------
   Realtor pages.
   --------------------------------------------------------------------------- */

function page_dashboard() {
	require_login();
	if ( is_admin() ) {
		redirect( 'admin' );
	}

	$user   = current_user();
	$levels = generations( $user['id'], 3 );

	render(
		'dashboard',
		array(
			'user'       => $user,
			'star'       => $user['premium_star_id'] ? find_user( $user['premium_star_id'] ) : null,
			'total'      => total_earned( $user['id'] ),
			'lines'      => $levels[1],
			'network'    => count( $levels[1] ) + count( $levels[2] ) + count( $levels[3] ),
			'earnings'   => earnings( $user['id'] ),
			'notices'    => notifications( $user['id'] ),
			'properties' => properties(),
		)
	);
}

function page_premium_lines() {
	require_login();
	if ( is_admin() ) {
		redirect( 'admin/members' );
	}

	render(
		'premium-lines',
		array(
			'user'   => current_user(),
			'levels' => generations( current_user()['id'], 3 ),
		)
	);
}

/* ---------------------------------------------------------------------------
   Signing in, out, up, and resetting a password.
   --------------------------------------------------------------------------- */

function page_login() {
	if ( current_user() ) {
		redirect( '' );
	}

	$email = '';

	if ( is_post() ) {
		check_csrf( 'login' );
		$email  = input( 'email' );
		$result = attempt_login( $email, $_POST['password'] ?? '' );

		if ( is_array( $result ) ) {
			redirect( 'admin' === $result['role'] ? 'admin' : '' );
		}

		flash( 'error', $result );
	}

	render( 'login', array( 'email' => $email ) );
}

function page_logout() {
	if ( is_post() ) {
		check_csrf( '' );
		sign_out();
		flash( 'ok', 'You have signed out.' );
	}
	redirect( 'login' );
}

function page_forgot() {
	if ( is_post() ) {
		check_csrf( 'forgot' );
		$email = input( 'email' );

		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			flash( 'error', 'Enter the email address you registered with.' );
			redirect( 'forgot' );
		}

		request_password_reset( $email );

		// Deliberately the same message whether or not the address is on file:
		// saying "no such account" would let anyone test which emails exist.
		flash( 'ok', 'If that email is registered, a reset link is on its way.' );
		redirect( 'login' );
	}

	render( 'forgot' );
}

function page_reset() {
	$token = $_GET['token'] ?? '';
	$reset = find_reset( $token );

	if ( ! $reset ) {
		flash( 'error', 'That reset link has expired or was already used. Request a new one.' );
		redirect( 'forgot' );
	}

	if ( is_post() ) {
		check_csrf( 'login' );
		$password = $_POST['password'] ?? '';
		$problem  = password_problem( $password, $_POST['password_confirm'] ?? '' );

		if ( null === $problem ) {
			complete_password_reset( $reset, $password );
			flash( 'ok', 'Your password has been changed. Sign in with the new one.' );
			redirect( 'login' );
		}

		flash( 'error', $problem );
	}

	render( 'reset', array( 'token' => $token ) );
}

function page_register() {
	if ( current_user() ) {
		redirect( '' );
	}

	$ref = isset( $_GET['ref'] ) && is_string( $_GET['ref'] ) ? trim( $_GET['ref'] ) : '';
	$old = array();

	if ( is_post() ) {
		check_csrf( 'register' );
		$old    = array_map( 'trim', array_filter( $_POST, 'is_string' ) );
		$ref    = $old['ref'] ?? '';
		$result = register_member( $old );

		if ( is_array( $result ) ) {
			list( $user, $password, $mailed ) = $result;

			if ( $mailed ) {
				flash( 'ok', 'Your account is ready. Check your email for your login details.' );
			} else {
				// The password is hashed the moment the account is created and
				// cannot be read back. If the mail did not go out, show it once
				// here rather than strand the member with an account they can
				// never reach.
				flash(
					'warn',
					sprintf(
						"Your account is ready, but we could not email your login details. Save them now, they will not be shown again.\n\nCID: %s\nEmail: %s\nPassword: %s",
						$user['ref_code'],
						$user['email'],
						$password
					)
				);
			}
			redirect( 'login' );
		}

		flash( 'error', $result );
	}

	render(
		'register',
		array(
			'ref'  => $ref,
			'star' => '' !== $ref ? find_by_ref_code( $ref ) : null,
			'old'  => $old,
		)
	);
}

/* ---------------------------------------------------------------------------
   Admin.
   --------------------------------------------------------------------------- */

function page_admin_sales() {
	require_admin();

	if ( is_post() ) {
		check_csrf( 'admin' );
		$result = record_sale( $_POST, current_user()['id'] );
		flash( is_int( $result ) ? 'ok' : 'error', is_int( $result ) ? 'Sale recorded.' : $result );
		redirect( 'admin' );
	}

	render(
		'admin/sales',
		array(
			'realtors' => realtors(),
			'sales'    => recent_sales(),
			'names'    => user_names(),
		)
	);
}

function page_admin_members() {
	require_admin();

	render(
		'admin/members',
		array(
			'realtors' => realtors(),
			'names'    => user_names(),
		)
	);
}

function page_admin_properties() {
	require_admin();

	if ( is_post() ) {
		check_csrf( 'admin/properties' );
		$id = (int) ( $_POST['id'] ?? 0 );

		if ( 'delete' === input( 'do' ) ) {
			delete_property( $id );
			flash( 'ok', 'Property removed.' );
		} else {
			$result = save_property( $_POST, $id );
			if ( true !== $result ) {
				flash( 'error', $result );
				redirect( 'admin/properties', $id ? array( 'edit' => $id ) : array() );
			}
			flash( 'ok', $id ? 'Property updated.' : 'Property added.' );
		}
		redirect( 'admin/properties' );
	}

	$editing = isset( $_GET['edit'] ) ? find_property( (int) $_GET['edit'] ) : null;

	render(
		'admin/properties',
		array(
			'properties' => properties(),
			'editing'    => $editing,
		)
	);
}

function page_admin_settings() {
	require_admin();

	if ( is_post() ) {
		check_csrf( 'admin/settings' );
		$rate  = input( 'commission_percent' );
		$group = input( 'whatsapp_group' );

		if ( ! is_numeric( $rate ) || $rate < 0 || $rate > 100 ) {
			flash( 'error', 'The commission rate must be between 0 and 100.' );
		} elseif ( '' !== $group && ! preg_match( '#^https?://#i', $group ) ) {
			flash( 'error', 'The WhatsApp link must start with https://' );
		} else {
			save_setting( 'commission_percent', (float) $rate );
			save_setting( 'whatsapp_group', $group );
			flash( 'ok', 'Settings saved.' );
		}
		redirect( 'admin/settings' );
	}

	render(
		'admin/settings',
		array(
			'rate'  => commission_rate(),
			'group' => setting( 'whatsapp_group' ),
		)
	);
}

/* ---------------------------------------------------------------------------
   First run: create the tables and the first admin.

   Locked behind the setup key from config.php, and gone for good once an
   admin exists, so it cannot be used to take over a running portal.
   --------------------------------------------------------------------------- */

function page_setup() {
	$key = (string) config( 'setup_key' );
	if ( strlen( $key ) < 20 || ! hash_equals( $key, (string) ( $_GET['key'] ?? '' ) ) ) {
		not_found();
	}

	try {
		$has_admin = (bool) q( "SELECT 1 FROM portal_users WHERE role = 'admin' LIMIT 1" )->fetchColumn();
	} catch ( PDOException $e ) {
		$has_admin = false; // Tables not created yet.
	}
	if ( $has_admin ) {
		not_found();
	}

	foreach ( preg_split( '/;\s*(\r?\n|$)/', file_get_contents( PORTAL_ROOT . '/schema.sql' ) ) as $statement ) {
		if ( '' !== trim( preg_replace( '/^\s*--.*$/m', '', $statement ) ) ) {
			db()->exec( $statement );
		}
	}

	migrate_schema();

	if ( is_post() ) {
		check_csrf( 'setup', array( 'key' => $key ) );
		$name     = input( 'full_name' );
		$email    = strtolower( input( 'email' ) );
		$password = $_POST['password'] ?? '';
		$problem  = password_problem( $password, $_POST['password_confirm'] ?? '' );

		if ( '' === $name || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			$problem = 'A name and a valid email are required.';
		} elseif ( find_user_by_email( $email ) ) {
			$problem = 'An account with that email already exists.';
		}

		if ( null === $problem ) {
			q(
				"INSERT INTO portal_users (role, full_name, email, password_hash, created_at) VALUES ('admin', ?, ?, ?, ?)",
				array( $name, $email, password_hash( $password, PASSWORD_DEFAULT ), now() )
			);
			sign_in( find_user( (int) db()->lastInsertId() ) );
			flash( 'ok', 'Admin account created. Setup is now closed.' );
			redirect( 'admin' );
		}

		flash( 'error', $problem );
	}

	render( 'setup', array( 'key' => $key ) );
}
