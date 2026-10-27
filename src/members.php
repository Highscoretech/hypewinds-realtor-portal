<?php
/**
 * Realtors, referral codes and the premium-star relationship.
 */

defined( 'PORTAL' ) || exit;

/** Profile fields collected at registration, column => label. */
function member_fields() {
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

function find_by_ref_code( $code ) {
	$code = trim( (string) $code );
	if ( ! preg_match( '/^\d{6}$/', $code ) ) {
		return null;
	}

	$row = q( "SELECT * FROM portal_users WHERE ref_code = ? AND role = 'realtor'", array( $code ) )->fetch();
	return $row ? $row : null;
}

/**
 * Brings an older database up to date.
 *
 * schema.sql only creates tables that are missing, so a column added after an
 * install was made would never appear there. Run from setup, and safe to run
 * again: it checks before it changes anything.
 */
function migrate_schema() {
	$has_username = (bool) q(
		'SELECT 1 FROM information_schema.COLUMNS
		  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
		array( 'portal_users', 'username' )
	)->fetchColumn();

	if ( ! $has_username ) {
		db()->exec( 'ALTER TABLE portal_users ADD COLUMN username VARCHAR(60) NULL AFTER email' );
		db()->exec( 'ALTER TABLE portal_users ADD UNIQUE KEY uq_users_username (username)' );
	}

	// Anyone registered before the column existed still needs a name to sign
	// in with.
	foreach ( q( 'SELECT id, email FROM portal_users WHERE username IS NULL OR username = ""' )->fetchAll() as $row ) {
		q( 'UPDATE portal_users SET username = ? WHERE id = ?', array( generate_username( $row['email'] ), $row['id'] ) );
	}
}

/**
 * A sign-in name taken from the email, e.g. ada.okoye@gmail.com -> adaokoye.
 *
 * Members are not asked to invent one at registration: it is one more thing
 * to choose and forget. A digit is appended when the name is taken.
 */
function generate_username( $email ) {
	$base = strtolower( (string) strstr( $email . '@', '@', true ) );
	$base = preg_replace( '/[^a-z0-9]/', '', $base );
	$base = '' === $base ? 'realtor' : mb_substr( $base, 0, 50 );

	$name = $base;
	for ( $n = 2; username_taken( $name ); $n++ ) {
		$name = $base . $n;
	}

	return $name;
}

function username_taken( $name ) {
	return (bool) q( 'SELECT 1 FROM portal_users WHERE username = ? LIMIT 1', array( $name ) )->fetchColumn();
}


/** Six digits, the CID format members are given. */
function generate_ref_code() {
	do {
		$code  = (string) random_int( 100000, 999999 );
		$taken = q( 'SELECT 1 FROM portal_users WHERE ref_code = ?', array( $code ) )->fetchColumn();
	} while ( $taken );

	return $code;
}

/** Readable, unambiguous: no 0/O or 1/l/I to misread from an email. */
function generate_password( $length = 12 ) {
	$chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
	$out   = '';
	for ( $i = 0; $i < $length; $i++ ) {
		$out .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
	}
	return $out;
}

function referral_link( $code ) {
	return absolute_url( 'register', array( 'ref' => $code ) );
}

/**
 * Creates a realtor. The member never chooses a password: one is generated
 * and emailed, as the group's existing portal does.
 *
 * @return array|string array( user, password, mailed ) on success, or the error.
 */
function register_member( array $in ) {
	$full_name = trim( $in['full_name'] ?? '' );
	$email     = strtolower( trim( $in['email'] ?? '' ) );
	$ref       = trim( $in['ref'] ?? '' );

	if ( '' === $full_name || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
		return 'A full name and a valid email are required.';
	}
	if ( empty( $in['terms'] ) ) {
		return 'You must agree to the terms.';
	}
	if ( find_user_by_email( $email ) ) {
		return 'An account with that email already exists.';
	}

	$username = generate_username( $email );

	$star = '' !== $ref ? find_by_ref_code( $ref ) : null;
	if ( '' !== $ref && ! $star ) {
		return 'That referral link is not valid.';
	}

	$dob = trim( $in['date_of_birth'] ?? '' );
	if ( '' !== $dob && ! DateTime::createFromFormat( 'Y-m-d', $dob ) ) {
		return 'Enter the date of birth as a date.';
	}

	$password = generate_password();
	$code     = generate_ref_code();

	$columns = array(
		'role'            => 'realtor',
		'full_name'       => mb_substr( $full_name, 0, 190 ),
		'email'           => $email,
		'username'        => $username,
		'password_hash'   => password_hash( $password, PASSWORD_DEFAULT ),
		'ref_code'        => $code,
		'premium_star_id' => $star ? (int) $star['id'] : null,
		'created_at'      => now(),
	);

	foreach ( array_keys( member_fields() ) as $field ) {
		$value             = mb_substr( trim( $in[ $field ] ?? '' ), 0, 100 );
		$columns[ $field ] = ( 'date_of_birth' === $field && '' === $value ) ? null : $value;
	}

	q(
		sprintf(
			'INSERT INTO portal_users (%s) VALUES (%s)',
			implode( ', ', array_keys( $columns ) ),
			implode( ', ', array_fill( 0, count( $columns ), '?' ) )
		),
		array_values( $columns )
	);

	$user = find_user( (int) db()->lastInsertId() );

	if ( $star ) {
		notify( (int) $star['id'], sprintf( '%s joined with your link and is now your premium-line.', $full_name ) );
	}

	$mailed = send_welcome( $user, $password );

	// The referrer hears about it second: the new member's own login details
	// matter more if only one of the two mails gets through.
	if ( $star ) {
		send_new_line_notice( $star, $user );
	}

	return array( $user, $password, $mailed );
}

/**
 * The generations below a member.
 *
 * @return array 1 => rows, 2 => rows, ... up to $depth.
 */
function generations( $user_id, $depth = 3 ) {
	$levels  = array();
	$current = array( (int) $user_id );

	for ( $level = 1; $level <= $depth; $level++ ) {
		if ( ! $current ) {
			$levels[ $level ] = array();
			continue;
		}

		$marks            = implode( ',', array_fill( 0, count( $current ), '?' ) );
		$found            = q( "SELECT * FROM portal_users WHERE premium_star_id IN ($marks) ORDER BY created_at", $current )->fetchAll();
		$levels[ $level ] = $found;
		$current          = array_map( 'intval', array_column( $found, 'id' ) );
	}

	return $levels;
}

function realtors() {
	return q( "SELECT * FROM portal_users WHERE role = 'realtor' ORDER BY full_name" )->fetchAll();
}

/** id => full_name for every account, for labelling rows cheaply. */
function user_names() {
	return q( 'SELECT id, full_name FROM portal_users' )->fetchAll( PDO::FETCH_KEY_PAIR );
}
