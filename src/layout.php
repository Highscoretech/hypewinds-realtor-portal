<?php
/**
 * Page chrome shared by every view: document head, the signed-in sidebar
 * shell, and the centred card the sign-in screens sit in.
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
	<title><?php echo e( $title . ' - ' . config( 'site_name' ) ); ?></title>
	<link rel="icon" href="<?php echo e( asset( 'logo.png' ) ); ?>">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap">
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

function logo_html() {
	return '<img class="hrp-logo-img" src="' . e( asset( 'logo.png' ) ) . '" alt="' . e( config( 'site_name' ) ) . '">';
}

function flashes_html() {
	$out = '';
	foreach ( take_flashes() as list( $type, $message ) ) {
		$class = in_array( $type, array( 'ok', 'warn' ), true ) ? $type : 'error';
		$out  .= '<p class="hrp-alert hrp-alert-' . $class . '">' . e( $message ) . '</p>';
	}
	return $out;
}

/** Sidebar navigation for the signed-in user's role. */
function nav_items( array $user ) {
	if ( 'admin' === $user['role'] ) {
		return array(
			'admin'            => 'Sales',
			'admin/members'    => 'Members',
			'admin/properties' => 'Properties',
			'admin/settings'   => 'Settings',
		);
	}

	return array(
		''              => 'Dashboard',
		'premium-lines' => 'Premium-Lines',
	);
}

function shell_start( $title, array $user, $active ) {
	page_start( $title );
	?>
	<div class="hrp hrp-shell">
		<input type="checkbox" id="hrp-nav-toggle" class="hrp-nav-toggle">
		<label for="hrp-nav-toggle" class="hrp-backdrop"></label>

		<aside class="hrp-sidebar">
			<div class="hrp-sidebar-brand"><a href="<?php echo e( url() ); ?>"><?php echo logo_html(); ?></a></div>
			<p class="hrp-sidebar-user"><?php echo e( $user['full_name'] ); ?></p>
			<nav class="hrp-nav">
				<?php foreach ( nav_items( $user ) as $path => $label ) : ?>
					<a class="<?php echo $path === $active ? 'is-active' : ''; ?>" href="<?php echo e( url( $path ) ); ?>"><?php echo e( $label ); ?></a>
				<?php endforeach; ?>
				<a href="<?php echo e( config( 'site_url' ) ); ?>">Main website</a>
				<form method="post" action="<?php echo e( url( 'logout' ) ); ?>">
					<?php echo csrf_field(); ?>
					<button type="submit" class="hrp-nav-button">Log out</button>
				</form>
			</nav>
		</aside>

		<div class="hrp-main">
			<header class="hrp-topbar">
				<label for="hrp-nav-toggle" class="hrp-burger" aria-label="Menu">
					<span></span><span></span><span></span>
				</label>
				<span class="hrp-topbar-user"><?php echo e( $user['full_name'] ); ?></span>
			</header>
			<main class="hrp-content">
				<h1 class="hrp-page-title"><?php echo e( $title ); ?></h1>
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
	<div class="hrp hrp-auth">
		<div class="hrp-auth-card<?php echo $wide ? ' hrp-auth-wide' : ''; ?>">
			<div class="hrp-auth-logo"><a href="<?php echo e( config( 'site_url' ) ); ?>"><?php echo logo_html(); ?></a></div>
			<?php echo flashes_html(); ?>
			<h2><?php echo e( $title ); ?></h2>
			<p class="hrp-auth-sub"><?php echo e( $subtitle ); ?></p>
	<?php
}

function auth_end() {
	?>
		</div>
	</div>
	<?php
	page_end();
}

/** The two-square "copy" icon used on the referral link and code. */
function copy_icon() {
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
		. '<rect x="9" y="9" width="12" height="12" rx="2"></rect>'
		. '<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
}
