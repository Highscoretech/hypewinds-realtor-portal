<?php
/**
 * Copy to config.php and fill in. config.php is never committed: it holds the
 * database password and the setup key.
 */
return array(
	// Where the portal is served from, without a trailing slash. Used for the
	// links in emails, so it must be the real public address, not a guess
	// taken from the request.
	'base_url' => 'https://hypewindsproperties.com/realtor-portal',

	'db' => array(
		'host'     => 'localhost',
		'name'     => 'hypewind_portal',
		'user'     => 'hypewind_portal',
		'password' => '',
	),

	// Sender for welcome, commission and password-reset emails. Should be an
	// address on the site's own domain so the mail is not marked as spam.
	'mail_from'      => 'noreply@hypewindsproperties.com',
	'mail_from_name' => 'Hypewinds Properties',

	// Send over SMTP. Needed on the live server, whose host disables PHP's
	// mail(). Its own mail service on localhost accepts the site's mail with
	// no login, so user/password stay empty there; fill them in to log in as
	// a mailbox instead. Set 'smtp' => null to use mail() (local testing).
	'smtp' => array(
		'host'     => 'localhost',
		'port'     => 25,
		'user'     => '',
		'password' => '',
	),

	// The website's own database, read so realtors see the same listings the
	// public does. Leave 'name' empty and the portal falls back to the
	// properties kept in its own admin area.
	//
	// A read-only user is enough, and safer: the portal only ever selects.
	'website' => array(
		'host'      => 'localhost',
		'name'      => '',
		'user'      => '',
		'password'  => '',
		// Checked against the tables that exist, and worked out if it is wrong.
		'prefix'    => 'wp_',
		'post_type' => 'property',
	),

	'site_name'  => 'Hypewinds Properties',
	'site_url'   => 'https://hypewindsproperties.com',
	'currency'   => "\u{20A6}", // The naira sign, as an escape so this file stays plain ASCII.

	// Unlocks /setup, which creates the tables and the first admin account.
	// /setup stops working by itself once an admin exists. Use a long random
	// string, e.g. the output of: php -r "echo bin2hex(random_bytes(24));"
	'setup_key' => '',
);
