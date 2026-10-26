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
	'mail_from'      => 'portal@hypewindsproperties.com',
	'mail_from_name' => 'Hypewinds Properties',

	// Send through a real mailbox over SMTP. Needed on the live server, whose
	// host disables PHP's mail(). The user must be the mailbox for mail_from.
	// Leave as null to use mail() instead (local testing).
	'smtp' => array(
		'host'     => 'localhost',
		'port'     => 587,
		'user'     => 'portal@hypewindsproperties.com',
		'password' => '',
	),

	'site_name'  => 'Hypewinds Properties',
	'site_url'   => 'https://hypewindsproperties.com',
	'currency'   => '₦',

	// Unlocks /setup, which creates the tables and the first admin account.
	// /setup stops working by itself once an admin exists. Use a long random
	// string, e.g. the output of: php -r "echo bin2hex(random_bytes(24));"
	'setup_key' => '',
);
