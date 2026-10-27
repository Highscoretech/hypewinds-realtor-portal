<?php
/**
 * Sales, the commission a sale pays the seller's premium-star, notifications
 * and the property list.
 */

defined( 'PORTAL' ) || exit;

function commission_rate() {
	return (float) setting( 'commission_percent', '5' );
}

/**
 * Records a sale and, when the seller has a premium-star, the commission it
 * earns them. Both rows are written together or not at all.
 *
 * @return int|string The sale id, or the error.
 */
function record_sale( array $in, $recorded_by ) {
	$seller = find_user( (int) ( $in['seller_id'] ?? 0 ) );
	$amount = (float) ( $in['amount'] ?? 0 );
	$type   = in_array( $in['property_type'] ?? '', array( 'land', 'house' ), true ) ? $in['property_type'] : 'land';
	$sold   = trim( $in['sold_on'] ?? '' );

	if ( ! $seller || 'realtor' !== $seller['role'] ) {
		return 'Choose the realtor who made the sale.';
	}
	if ( $amount <= 0 ) {
		return 'The sale amount must be greater than zero.';
	}
	if ( '' === $sold ) {
		$sold = date( 'Y-m-d' );
	} elseif ( ! DateTime::createFromFormat( 'Y-m-d', $sold ) ) {
		return 'Enter the date sold as a date.';
	}

	$now = now();
	$pdo = db();
	$pdo->beginTransaction();

	q(
		'INSERT INTO portal_sales (seller_id, property_type, description, amount, sold_on, recorded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
		array( $seller['id'], $type, mb_substr( trim( $in['description'] ?? '' ), 0, 255 ), $amount, $sold, (int) $recorded_by, $now )
	);
	$sale_id = (int) $pdo->lastInsertId();

	$star_id = (int) $seller['premium_star_id'];
	$message = null;
	$notice  = null;

	// Seller joined without a referral link: nobody to pay.
	if ( $star_id ) {
		$rate   = commission_rate();
		$earned = round( $amount * $rate / 100, 2 );

		q(
			'INSERT INTO portal_commissions (sale_id, beneficiary_id, seller_id, rate_percent, amount, created_at) VALUES (?, ?, ?, ?, ?, ?)',
			array( $sale_id, $star_id, $seller['id'], $rate, $earned, $now )
		);

		$message = sprintf(
			'%s sold a %s for %s. Your commission: %s (%s%%).',
			$seller['full_name'],
			$type,
			money( $amount ),
			money( $earned ),
			rtrim( rtrim( number_format( $rate, 2 ), '0' ), '.' )
		);
		notify( $star_id, $message );

		$notice = array(
			'seller' => $seller['full_name'],
			'type'   => $type,
			'amount' => $amount,
			'earned' => $earned,
			'rate'   => rtrim( rtrim( number_format( $rate, 2 ), '0' ), '.' ),
		);
	}

	$pdo->commit();

	// Mail goes out only once the sale is safely stored.
	if ( $notice ) {
		send_commission_notice( find_user( $star_id ), $notice );
	}

	return $sale_id;
}

function recent_sales( $limit = 100 ) {
	return q(
		'SELECT s.*, c.beneficiary_id, c.amount AS commission
		 FROM portal_sales s LEFT JOIN portal_commissions c ON c.sale_id = s.id
		 ORDER BY s.created_at DESC, s.id DESC LIMIT ' . (int) $limit
	)->fetchAll();
}

function notify( $user_id, $body ) {
	q( 'INSERT INTO portal_notifications (user_id, body, created_at) VALUES (?, ?, ?)', array( (int) $user_id, $body, now() ) );
}

function notifications( $user_id, $limit = 20 ) {
	return q(
		'SELECT * FROM portal_notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . (int) $limit,
		array( (int) $user_id )
	)->fetchAll();
}

/** Commission rows a member has earned, with the seller's name. */
function earnings( $user_id ) {
	return q(
		'SELECT c.*, u.full_name AS seller_name
		 FROM portal_commissions c JOIN portal_users u ON u.id = c.seller_id
		 WHERE c.beneficiary_id = ? ORDER BY c.created_at DESC, c.id DESC',
		array( (int) $user_id )
	)->fetchAll();
}

function total_earned( $user_id ) {
	return (float) q( 'SELECT COALESCE(SUM(amount), 0) FROM portal_commissions WHERE beneficiary_id = ?', array( (int) $user_id ) )->fetchColumn();
}

/* ---------------------------------------------------------------------------
   Properties, kept by the admin inside the portal.
   --------------------------------------------------------------------------- */

function property_statuses() {
	return array( 'Available', 'Selling fast', 'Sold out' );
}

/**
 * The properties realtors see.
 *
 * When the site's own listings are reachable they are the source: a realtor
 * should see what the public sees, and nobody should have to enter a property
 * twice and keep the two copies in step. The portal's own table is the
 * fallback for an install with no website beside it.
 */
function properties() {
	$listings = website_listings();
	if ( null !== $listings ) {
		return $listings;
	}

	return q( 'SELECT * FROM portal_properties ORDER BY created_at DESC, id DESC' )->fetchAll();
}

/** Read-only connection to the website's database, or null when not configured. */
function website_db() {
	static $pdo = false;

	if ( false !== $pdo ) {
		return $pdo;
	}

	$c = config( 'website' );
	if ( ! is_array( $c ) || empty( $c['name'] ) || empty( $c['user'] ) ) {
		return $pdo = null;
	}

	try {
		$pdo = new PDO(
			sprintf( 'mysql:host=%s;dbname=%s;charset=utf8mb4', $c['host'] ?? 'localhost', $c['name'] ),
			$c['user'],
			$c['password'] ?? '',
			array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC )
		);
	} catch ( PDOException $e ) {
		error_log( 'Portal could not read the website database: ' . $e->getMessage() );
		$pdo = null;
	}

	return $pdo;
}

/**
 * The table prefix WordPress is using.
 *
 * Taken from config when given, but checked: a prefix that does not match the
 * tables that exist would make every listing query fail silently, and the
 * portal would quietly show nothing rather than say why.
 */
function website_prefix( PDO $db ) {
	static $prefix = null;

	if ( null !== $prefix ) {
		return $prefix;
	}

	$configured = (string) ( config( 'website' )['prefix'] ?? '' );
	if ( '' !== $configured && website_has_table( $db, $configured . 'posts' ) ) {
		return $prefix = $configured;
	}

	$row = $db->query( "SELECT TABLE_NAME FROM information_schema.TABLES
		WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE '%posts' LIMIT 1" )->fetch();

	return $prefix = $row ? substr( $row['TABLE_NAME'], 0, -strlen( 'posts' ) ) : '';
}

function website_has_table( PDO $db, $table ) {
	$check = $db->prepare( 'SELECT 1 FROM information_schema.TABLES
		WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1' );
	$check->execute( array( $table ) );

	return (bool) $check->fetchColumn();
}

/**
 * Published listings from the website, shaped like the portal's own rows.
 *
 * @return array|null Null when there is no website database to read.
 */
function website_listings( $limit = 30 ) {
	$db = website_db();
	if ( ! $db ) {
		return null;
	}

	try {
		$p    = website_prefix( $db );
		$type = (string) ( config( 'website' )['post_type'] ?? 'property' );
		if ( '' === $p ) {
			return null;
		}

		$rows = $db->prepare(
			"SELECT ID, post_title, guid FROM {$p}posts
			  WHERE post_type = ? AND post_status = 'publish'
			  ORDER BY post_date DESC LIMIT " . (int) $limit
		);
		$rows->execute( array( $type ) );
		$posts = $rows->fetchAll();

		if ( ! $posts ) {
			return array();
		}

		$ids   = array_column( $posts, 'ID' );
		$marks = implode( ',', array_fill( 0, count( $ids ), '?' ) );

		// The plugin's price key is its own business, so rather than hardcode
		// one, take whichever meta key on the listing mentions price.
		$meta = $db->prepare(
			"SELECT post_id, meta_value FROM {$p}postmeta
			  WHERE post_id IN ($marks) AND meta_key LIKE '%price%' AND meta_value <> ''
			  ORDER BY meta_id"
		);
		$meta->execute( $ids );

		$prices = array();
		foreach ( $meta->fetchAll() as $row ) {
			if ( ! isset( $prices[ $row['post_id'] ] ) ) {
				$prices[ $row['post_id'] ] = $row['meta_value'];
			}
		}

		$locations = website_locations( $db, $p, $ids );

		$out = array();
		foreach ( $posts as $post ) {
			$price = $prices[ $post['ID'] ] ?? '';
			$out[] = array(
				'id'       => (int) $post['ID'],
				'name'     => $post['post_title'],
				'location' => $locations[ $post['ID'] ] ?? '',
				'price'    => is_numeric( str_replace( array( ',', ' ' ), '', $price ) )
					? (float) str_replace( array( ',', ' ' ), '', $price )
					: null,
				'status'   => 'Available',
				'url'      => $post['guid'],
			);
		}

		return $out;
	} catch ( PDOException $e ) {
		error_log( 'Portal could not list website properties: ' . $e->getMessage() );
		return null;
	}
}

/** post id => location name, from whichever taxonomy holds it. */
function website_locations( PDO $db, $prefix, array $ids ) {
	$marks = implode( ',', array_fill( 0, count( $ids ), '?' ) );

	try {
		$rows = $db->prepare(
			"SELECT tr.object_id, t.name
			   FROM {$prefix}term_relationships tr
			   JOIN {$prefix}term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			   JOIN {$prefix}terms t ON t.term_id = tt.term_id
			  WHERE tr.object_id IN ($marks) AND tt.taxonomy LIKE '%location%'"
		);
		$rows->execute( $ids );
	} catch ( PDOException $e ) {
		return array();
	}

	$out = array();
	foreach ( $rows->fetchAll() as $row ) {
		if ( ! isset( $out[ $row['object_id'] ] ) ) {
			$out[ $row['object_id'] ] = $row['name'];
		}
	}

	return $out;
}

function find_property( $id ) {
	$row = q( 'SELECT * FROM portal_properties WHERE id = ?', array( (int) $id ) )->fetch();
	return $row ? $row : null;
}

/** @return true|string True on success, or the error. */
function save_property( array $in, $id = 0 ) {
	$name   = trim( $in['name'] ?? '' );
	$price  = trim( str_replace( ',', '', $in['price'] ?? '' ) );
	$status = trim( $in['status'] ?? '' );
	$link   = trim( $in['url'] ?? '' );

	// A new property with no status given takes the default; an edit that sends
	// an unknown one is refused rather than quietly reset, which would turn a
	// sold-out listing back into an available one without anyone noticing.
	if ( '' === $status && ! $id ) {
		$status = 'Available';
	}

	if ( '' === $name ) {
		return 'Give the property a name.';
	}
	if ( ! in_array( $status, property_statuses(), true ) ) {
		return 'Choose a status from the list.';
	}
	if ( '' !== $price && ( ! is_numeric( $price ) || $price < 0 ) ) {
		return 'The price must be a number.';
	}
	if ( '' !== $link && ! preg_match( '#^https?://#i', $link ) ) {
		return 'The link must start with http:// or https://';
	}

	$values = array(
		mb_substr( $name, 0, 190 ),
		mb_substr( trim( $in['location'] ?? '' ), 0, 190 ),
		'' === $price ? null : (float) $price,
		$status,
		mb_substr( $link, 0, 500 ),
	);

	if ( $id ) {
		q( 'UPDATE portal_properties SET name = ?, location = ?, price = ?, status = ?, url = ? WHERE id = ?', array_merge( $values, array( (int) $id ) ) );
	} else {
		q( 'INSERT INTO portal_properties (name, location, price, status, url, created_at) VALUES (?, ?, ?, ?, ?, ?)', array_merge( $values, array( now() ) ) );
	}

	return true;
}

function delete_property( $id ) {
	q( 'DELETE FROM portal_properties WHERE id = ?', array( (int) $id ) );
}
