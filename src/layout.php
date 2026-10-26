<?php
/**
 * Page chrome shared by every view: document head, the signed-in app shell
 * (sidebar + page header), the centred card the sign-in screens sit in, and
 * the small UI pieces (icons, empty states) the views reuse.
 */

defined( 'PORTAL' ) || exit;

function page_start( $title ) {
	?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex">
	<meta name="theme-color" content="#0b1220">
	<title><?php echo e( $title . ' - ' . config( 'site_name' ) ); ?></title>
	<link rel="icon" href="<?php echo e( asset( 'logo-email.png' ) ); ?>">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap">
	<link rel="stylesheet" href="<?php echo e( asset( 'portal.css' ) ); ?>">
</head>
<body>
	<?php
}

function page_end() {
	?>
	<script src="<?php echo e( asset( 'portal.js' ) ); ?>"></script>
</body>
</html>
	<?php
}

function logo_html( $class = 'logo' ) {
	return '<img class="' . e( $class ) . '" src="' . e( asset( 'logo-email.png' ) ) . '" alt="' . e( config( 'site_name' ) ) . '">';
}

function flashes_html() {
	$out = '';
	foreach ( take_flashes() as list( $type, $message ) ) {
		$class = in_array( $type, array( 'ok', 'warn' ), true ) ? $type : 'error';
		$out  .= '<div class="alert alert-' . $class . '" role="status">' . icon( 'ok' === $class ? 'check' : 'alert' ) . '<p>' . e( $message ) . '</p></div>';
	}
	return $out;
}

/**
 * Inline SVG icons (Lucide shapes), so the portal loads no icon font and
 * every icon inherits the text colour.
 */
function icon( $name ) {
	$paths = array(
		'home'      => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
		'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
		'network'   => '<circle cx="12" cy="5" r="3"/><circle cx="5" cy="19" r="3"/><circle cx="19" cy="19" r="3"/><path d="M12 8v4M12 12l-5 4.5M12 12l5 4.5"/>',
		'wallet'    => '<path d="M20 7H5a2 2 0 0 1 0-4h13v4"/><path d="M3 5v14a2 2 0 0 0 2 2h15V7"/><circle cx="16" cy="14" r="1.5"/>',
		'id'        => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="11" r="2.5"/><path d="M5.5 17a3.5 3.5 0 0 1 7 0"/><path d="M15 10h3M15 14h3"/>',
		'building'  => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>',
		'bell'      => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
		'star'      => '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
		'link'      => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
		'copy'      => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
		'check'     => '<path d="M20 6 9 17l-5-5"/>',
		'alert'     => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
		'globe'     => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
		'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
		'menu'      => '<path d="M4 6h16M4 12h16M4 18h16"/>',
		'chart'     => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 6-6"/>',
		'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
		'whatsapp'  => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/>',
		'external'  => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
		'plus'      => '<path d="M12 5v14M5 12h14"/>',
		'inbox'     => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
	);

	return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
		. ( $paths[ $name ] ?? '' ) . '</svg>';
}

/** Up to two initials for the avatar. */
function initials( $name ) {
	$words = preg_split( '/\s+/', trim( $name ) );
	$out   = '';
	foreach ( array_slice( array_filter( $words ), 0, 2 ) as $word ) {
		$out .= mb_strtoupper( mb_substr( $word, 0, 1 ) );
	}
	return '' !== $out ? $out : '?';
}

/** A friendly, centred "nothing here yet" block. */
function empty_state( $icon, $title, $text ) {
	return '<div class="empty"><span class="empty-icon">' . icon( $icon ) . '</span>'
		. '<p class="empty-title">' . e( $title ) . '</p><p class="empty-text">' . e( $text ) . '</p></div>';
}

/** Sidebar navigation for the signed-in user's role: path => array( label, icon ). */
function nav_items( array $user ) {
	if ( 'admin' === $user['role'] ) {
		return array(
			'admin'            => array( 'Sales', 'chart' ),
			'admin/members'    => array( 'Members', 'users' ),
			'admin/properties' => array( 'Properties', 'building' ),
			'admin/settings'   => array( 'Settings', 'settings' ),
		);
	}

	return array(
		''              => array( 'Dashboard', 'home' ),
		'premium-lines' => array( 'Premium-lines', 'network' ),
	);
}

function shell_start( $title, array $user, $active, $subtitle = '' ) {
	page_start( $title );
	$role = 'admin' === $user['role'] ? 'Administrator' : 'CID ' . $user['ref_code'];
	?>
	<div class="app">
		<input type="checkbox" id="nav-toggle" class="nav-toggle" aria-hidden="true">

		<aside class="sidebar">
			<a class="sidebar-brand" href="<?php echo e( url() ); ?>"><?php echo logo_html(); ?></a>

			<div class="profile">
				<span class="avatar"><?php echo e( initials( $user['full_name'] ) ); ?></span>
				<span class="profile-text">
					<span class="profile-name"><?php echo e( $user['full_name'] ); ?></span>
					<span class="profile-meta"><?php echo e( $role ); ?></span>
				</span>
			</div>

			<nav class="nav" aria-label="Portal">
				<span class="nav-label">Menu</span>
				<?php foreach ( nav_items( $user ) as $path => list( $label, $icon_name ) ) : ?>
					<a class="nav-link<?php echo $path === $active ? ' is-active' : ''; ?>" href="<?php echo e( url( $path ) ); ?>"<?php echo $path === $active ? ' aria-current="page"' : ''; ?>>
						<?php echo icon( $icon_name ); ?><span><?php echo e( $label ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="nav nav-bottom">
				<a class="nav-link" href="<?php echo e( config( 'site_url' ) ); ?>"><?php echo icon( 'globe' ); ?><span>Main website</span></a>
				<form method="post" action="<?php echo e( url( 'logout' ) ); ?>">
					<?php echo csrf_field(); ?>
					<button type="submit" class="nav-link nav-logout"><?php echo icon( 'logout' ); ?><span>Log out</span></button>
				</form>
			</div>
		</aside>
		<label for="nav-toggle" class="backdrop" aria-hidden="true"></label>

		<div class="main">
			<header class="topbar">
				<label for="nav-toggle" class="icon-button" aria-label="Open menu"><?php echo icon( 'menu' ); ?></label>
				<a class="topbar-brand" href="<?php echo e( url() ); ?>"><?php echo logo_html(); ?></a>
				<span class="avatar avatar-sm"><?php echo e( initials( $user['full_name'] ) ); ?></span>
			</header>

			<main class="content">
				<div class="page-head">
					<div>
						<h1><?php echo e( $title ); ?></h1>
						<?php if ( '' !== $subtitle ) : ?><p><?php echo e( $subtitle ); ?></p><?php endif; ?>
					</div>
					<span class="page-date"><?php echo e( date( 'l, j F Y' ) ); ?></span>
				</div>
				<?php echo flashes_html(); ?>
	<?php
}

function shell_end() {
	?>
			</main>
		</div>
	</div>
	<?php
	page_end();
}

function auth_start( $title, $subtitle, $wide = false ) {
	page_start( $title );
	?>
	<div class="auth">
		<div class="auth-card<?php echo $wide ? ' auth-wide' : ''; ?>">
			<a class="auth-logo" href="<?php echo e( config( 'site_url' ) ); ?>"><?php echo logo_html(); ?></a>
			<?php echo flashes_html(); ?>
			<h1><?php echo e( $title ); ?></h1>
			<p class="auth-sub"><?php echo e( $subtitle ); ?></p>
	<?php
}

function auth_end() {
	?>
		</div>
	</div>
	<?php
	page_end();
}
