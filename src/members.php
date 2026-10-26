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
