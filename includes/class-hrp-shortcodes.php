<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The member-facing pages.
 *
 * Nothing here invents content: every figure is read back from what has
 * actually been recorded, and an empty section says it is empty.
 */
class HRP_Shortcodes {

	public static function init() {
		add_shortcode( 'hrp_dashboard', array( __CLASS__, 'dashboard' ) );
		add_shortcode( 'hrp_register', array( __CLASS__, 'register' ) );
		add_shortcode( 'hrp_premium_lines', array( __CLASS__, 'premium_lines' ) );
	}

	/**
	 * Show/hide control for password fields.
	 *
	 * Added in the browser because wp_login_form() renders its own markup and
	 * offers no filter for the field itself.
	 */
	private static function password_toggle_script() {
		ob_start();
		?>
		<script>
		document.addEventListener( 'DOMContentLoaded', function () {
			var fields = document.querySelectorAll( '.hrp input[type="password"]' );

			Array.prototype.forEach.call( fields, function ( field ) {
				if ( field.dataset.hrpEye ) return;
				field.dataset.hrpEye = '1';

				var wrap = document.createElement( 'span' );
				wrap.className = 'hrp-pass-wrap';
				field.parentNode.insertBefore( wrap, field );
				wrap.appendChild( field );

				var open = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
					+ '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
				var shut = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
					+ '<path d="M17.9 17.9A10.6 10.6 0 0 1 12 19c-7 0-11-7-11-7a19 19 0 0 1 5.1-5.9m3.8-1A10.6 10.6 0 0 1 12 5c7 0 11 7 11 7a19 19 0 0 1-2.2 3.2M1 1l22 22"/></svg>';

				var button = document.createElement( 'button' );
				button.type = 'button';
				button.className = 'hrp-eye';
				button.innerHTML = open;
				button.setAttribute( 'aria-label', 'Show password' );

				button.addEventListener( 'click', function () {
					var hidden = 'password' === field.type;
					field.type = hidden ? 'text' : 'password';
					button.innerHTML = hidden ? shut : open;
					button.setAttribute( 'aria-label', hidden ? 'Hide password' : 'Show password' );
				} );

				wrap.appendChild( button );
			} );
		} );
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Copy-to-clipboard for the referral link and code.
	 *
	 * navigator.clipboard is unavailable on insecure origins and in older
	 * browsers, so a hidden-textarea fallback keeps the button working.
	 */
	private static function copy_script() {
		ob_start();
		?>
		<script>
		document.addEventListener( 'DOMContentLoaded', function () {
			function fallbackCopy( text ) {
				var area = document.createElement( 'textarea' );
				area.value = text;
				area.setAttribute( 'readonly', '' );
				area.style.position = 'fixed';
				area.style.opacity = '0';
				document.body.appendChild( area );
				area.select();
				try { document.execCommand( 'copy' ); } catch ( e ) {}
				document.body.removeChild( area );
			}

			var tick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
				+ ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
				+ '<path d="M20 6 9 17l-5-5"/></svg>';

			var buttons = document.querySelectorAll( '.hrp-copy' );

			Array.prototype.forEach.call( buttons, function ( button ) {
				button.addEventListener( 'click', function () {
					var text = button.getAttribute( 'data-copy' );
					var icon = button.innerHTML;

					// The button carries no label, so the icon itself is the
					// confirmation: swap it for a tick, then put it back.
					function done() {
						button.innerHTML = tick;
						button.classList.add( 'is-copied' );
						setTimeout( function () {
							button.innerHTML = icon;
							button.classList.remove( 'is-copied' );
						}, 1600 );
					}

					if ( navigator.clipboard && window.isSecureContext ) {
						navigator.clipboard.writeText( text ).then( done, function () {
							fallbackCopy( text );
							done();
						} );
					} else {
						fallbackCopy( text );
						done();
					}
				} );
			} );
		} );
		</script>
		<?php
		return ob_get_clean();
	}

	private static function notices() {
		$out = '';

		if ( ! empty( $_GET['hrp_error'] ) ) {
			$out .= '<p class="hrp-alert hrp-alert-error">' . esc_html( urldecode( wp_unslash( $_GET['hrp_error'] ) ) ) . '</p>';
		}
		if ( ! empty( $_GET['hrp_notice'] ) ) {
			$out .= '<p class="hrp-alert hrp-alert-ok">' . esc_html( urldecode( wp_unslash( $_GET['hrp_notice'] ) ) ) . '</p>';
		}

		return $out;
	}

	/** Sidebar + header shell shared by the signed-in pages. */
	private static function shell_open( $user, $active = 'dashboard' ) {
		$portal = hrp_portal_url();
		$lines  = get_permalink( (int) get_option( 'hrp_page_premium_lines' ) );

		ob_start();
		?>
		<div class="hrp hrp-shell">
			<input type="checkbox" id="hrp-nav-toggle" class="hrp-nav-toggle">
			<label for="hrp-nav-toggle" class="hrp-backdrop"></label>

			<aside class="hrp-sidebar">
				<div class="hrp-sidebar-brand"><?php echo hrp_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<p class="hrp-sidebar-user"><?php echo esc_html( $user->display_name ); ?></p>
				<nav class="hrp-nav">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Main website</a>
					<a class="<?php echo 'dashboard' === $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $portal ); ?>">Dashboard</a>
					<a class="<?php echo 'lines' === $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $lines ); ?>">Premium-Lines</a>
					<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">Log out</a>
				</nav>
			</aside>

			<div class="hrp-main">
				<?php // Only reason this bar exists is the drawer toggle, so it is hidden on desktop. ?>
				<header class="hrp-topbar">
					<label for="hrp-nav-toggle" class="hrp-burger" aria-label="Menu">
						<span></span><span></span><span></span>
					</label>
					<span class="hrp-topbar-user"><?php echo esc_html( $user->display_name ); ?></span>
				</header>
				<div class="hrp-content">
					<?php echo self::notices(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php
		return ob_get_clean();
	}

	private static function shell_close() {
		return '</div></div></div>';
	}

	/**
	 * Signed out this is the login form, signed in it is the dashboard, so a
	 * single URL works for the footer link in both states.
	 */
	public static function dashboard() {
		if ( ! is_user_logged_in() ) {
			$view = isset( $_GET['hrp'] ) ? sanitize_key( wp_unslash( $_GET['hrp'] ) ) : '';
			return 'forgot' === $view ? self::forgot_screen() : self::login_screen();
		}

		$user       = wp_get_current_user();
		$user_id    = (int) $user->ID;
		$code       = HRP_Members::ref_code( $user_id );
		$star_id    = HRP_Members::premium_star_id( $user_id );
		$star       = $star_id ? get_userdata( $star_id ) : null;
		$ref_link   = $code ? add_query_arg( 'ref', rawurlencode( $code ), home_url( '/' ) ) : '';
		$earnings   = HRP_Commissions::earnings( $user_id );
		$total      = HRP_Commissions::total_earned( $user_id );
		$levels     = HRP_Members::generations( $user_id, 1 );
		$notices    = HRP_Commissions::notifications( $user_id );
		$properties = HRP_Commissions::properties();

		ob_start();
		echo self::shell_open( $user, 'dashboard' ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>

		<div class="hrp-tiles">
			<div class="hrp-tile"><span>Welcome back</span><strong><?php echo esc_html( $user->display_name ); ?></strong></div>
			<div class="hrp-tile"><span>Your CID</span><strong><?php echo esc_html( $code ? $code : '--' ); ?></strong></div>
			<div class="hrp-tile"><span>Commission earned</span><strong><?php echo esc_html( HRP_Commissions::money( $total ) ); ?></strong></div>
			<div class="hrp-tile"><span>Your premium-lines</span><strong><?php echo esc_html( count( $levels[1] ) ); ?></strong></div>
		</div>

		<?php if ( $ref_link ) : ?>
			<section class="hrp-card">
				<h2>Your referral link</h2>
				<div class="hrp-copy-field">
					<input type="text" class="hrp-input hrp-copy-value" readonly
						value="<?php echo esc_attr( $ref_link ); ?>" onclick="this.select();">
					<button type="button" class="hrp-copy" data-copy="<?php echo esc_attr( $ref_link ); ?>"
						title="Copy referral link" aria-label="Copy referral link">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
							stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<rect x="9" y="9" width="12" height="12" rx="2"></rect>
							<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
						</svg>
					</button>
				</div>
				<p class="hrp-muted">
					Anyone who registers with this link becomes your premium-line.
					Your code: <strong class="hrp-code"><?php echo esc_html( $code ); ?></strong>
					<button type="button" class="hrp-copy hrp-copy-plain" data-copy="<?php echo esc_attr( $code ); ?>"
						title="Copy your code" aria-label="Copy your code">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
							stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<rect x="9" y="9" width="12" height="12" rx="2"></rect>
							<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
						</svg>
					</button>
				</p>
			</section>
			<?php echo self::copy_script(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endif; ?>

		<section class="hrp-card">
			<h2>Your premium-star</h2>
			<?php if ( $star ) : ?>
				<p><strong><?php echo esc_html( $star->display_name ); ?></strong><br>
				<?php echo esc_html( $star->user_email ); ?></p>
			<?php else : ?>
				<p class="hrp-muted">You joined without a referral link, so you have no premium-star.</p>
			<?php endif; ?>
		</section>

		<section class="hrp-card">
			<h2>Our properties and prices</h2>
			<?php if ( ! $properties ) : ?>
				<p class="hrp-muted">No properties are listed yet.</p>
			<?php else : ?>
				<div class="hrp-scroll">
					<table class="hrp-table">
						<thead><tr><th>Property</th><th>Status</th><th>Price</th></tr></thead>
						<tbody>
						<?php foreach ( $properties as $property ) :
							$price = HRP_Commissions::property_price( $property->ID ); ?>
							<tr>
								<td><a href="<?php echo esc_url( get_permalink( $property ) ); ?>"><?php echo esc_html( get_the_title( $property ) ); ?></a></td>
								<td><span class="hrp-pill">Available</span></td>
								<td><?php echo '' === $price ? '&mdash;' : esc_html( $price ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>

		<section class="hrp-card">
			<h2>Your commissions</h2>
			<?php if ( ! $earnings ) : ?>
				<p class="hrp-muted">No commission yet. You earn when a premium-line sells a property.</p>
			<?php else : ?>
				<ul class="hrp-list">
				<?php foreach ( $earnings as $row ) :
					$seller = get_userdata( $row->seller_id ); ?>
					<li>
						<span><?php echo esc_html( $seller ? $seller->display_name : 'Premium-line' ); ?>
							&middot; <?php echo esc_html( $row->rate_percent ); ?>%</span>
						<strong><?php echo esc_html( HRP_Commissions::money( $row->amount ) ); ?></strong>
					</li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>

		<section class="hrp-card">
			<h2>Notifications</h2>
			<?php if ( ! $notices ) : ?>
				<p class="hrp-muted">Nothing yet.</p>
			<?php else : ?>
				<ul class="hrp-list">
				<?php foreach ( $notices as $note ) : ?>
					<li><span><?php echo esc_html( $note->body ); ?></span>
						<em class="hrp-muted"><?php echo esc_html( mysql2date( 'd/m/Y', $note->created_at ) ); ?></em></li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>

		<?php
		echo self::shell_close(); // phpcs:ignore WordPress.Security.EscapeOutput
		return ob_get_clean();
	}

	private static function login_screen() {
		$register = get_permalink( (int) get_option( 'hrp_page_register' ) );

		ob_start();
		?>
		<div class="hrp hrp-auth">
			<div class="hrp-auth-card">
				<div class="hrp-auth-logo"><?php echo hrp_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<?php echo self::notices(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<h2>Realtor login</h2>
				<p class="hrp-auth-sub">Sign in to your realtor portal</p>
				<?php
				wp_login_form(
					array(
						'redirect'       => hrp_portal_url(),
						'label_username' => 'Email',
						'label_password' => 'Password',
						'label_log_in'   => 'Login',
					)
				);
				?>
				<p class="hrp-auth-forgot">
					<?php // Kept inside the portal rather than wp-login.php, which is unbranded. ?>
					<a href="<?php echo esc_url( add_query_arg( 'hrp', 'forgot', hrp_portal_url() ) ); ?>">Forgot your password?</a>
				</p>
				<p class="hrp-auth-alt">
					No account yet? <a href="<?php echo esc_url( $register ); ?>">Register</a>
				</p>
			</div>
		</div>
		<?php
		echo self::password_toggle_script(); // phpcs:ignore WordPress.Security.EscapeOutput
		return ob_get_clean();
	}

	/** Password reset request, in the portal's own styling. */
	private static function forgot_screen() {
		ob_start();
		?>
		<div class="hrp hrp-auth">
			<div class="hrp-auth-card">
				<div class="hrp-auth-logo"><?php echo hrp_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<?php echo self::notices(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<h2>Reset your password</h2>
				<p class="hrp-auth-sub">We will email you a link to choose a new one</p>

				<form method="post">
					<?php wp_nonce_field( 'hrp_lost_password', 'hrp_lost_password_nonce' ); ?>
					<input type="hidden" name="hrp_action" value="lost_password">
					<p class="login-username">
						<label for="hrp-reset-email">Email</label>
						<input type="email" name="user_login" id="hrp-reset-email" required>
					</p>
					<p class="login-submit">
						<input type="submit" value="Send reset link">
					</p>
				</form>

				<p class="hrp-auth-alt">
					Remembered it? <a href="<?php echo esc_url( hrp_portal_url() ); ?>">Back to login</a>
				</p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function register() {
		if ( is_user_logged_in() ) {
			return '<div class="hrp hrp-auth"><div class="hrp-auth-card"><p>You are already signed in. <a href="' . esc_url( hrp_portal_url() ) . '">Go to your portal</a>.</p></div></div>';
		}

		$ref = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
		if ( ! $ref && ! empty( $_COOKIE[ HRP_Members::REF_COOKIE ] ) ) {
			$ref = sanitize_text_field( wp_unslash( $_COOKIE[ HRP_Members::REF_COOKIE ] ) );
		}
		$star = $ref ? HRP_Members::user_by_ref_code( $ref ) : null;

		ob_start();
		?>
		<div class="hrp hrp-auth">
			<div class="hrp-auth-card hrp-auth-wide">
				<div class="hrp-auth-logo"><?php echo hrp_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<?php echo self::notices(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<h2>Realtor registration</h2>
				<p class="hrp-auth-sub">Join the premium realtors network</p>

				<?php if ( $ref && $star ) : ?>
					<p class="hrp-alert hrp-alert-ok">Your premium-star: <strong><?php echo esc_html( $star->display_name ); ?></strong></p>
				<?php elseif ( $ref ) : ?>
					<p class="hrp-alert hrp-alert-error">That referral link is not valid. You can still register without one.</p>
				<?php endif; ?>

				<form method="post">
					<?php wp_nonce_field( 'hrp_register', 'hrp_register_nonce' ); ?>
					<input type="hidden" name="hrp_action" value="register">
					<input type="hidden" name="ref" value="<?php echo esc_attr( $star ? $ref : '' ); ?>">

					<div class="hrp-grid">
						<label>Full Name<input class="hrp-input" type="text" name="full_name" required></label>
						<label>Email<input class="hrp-input" type="email" name="email" required></label>
						<?php foreach ( HRP_Members::fields() as $name => $label ) :
							$type = ( 'date_of_birth' === $name ) ? 'date' : 'text'; ?>
							<label><?php echo esc_html( $label ); ?>
								<input class="hrp-input" type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>"></label>
						<?php endforeach; ?>
					</div>

					<p class="hrp-muted">Your password will be emailed to you once you register.</p>
					<label class="hrp-check"><input type="checkbox" name="terms" value="1" required> I agree to the terms</label>
					<p><button class="hrp-button" type="submit">Register</button></p>
				</form>

				<p>Already have an account? <a href="<?php echo esc_url( hrp_portal_url() ); ?>">Login</a></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function premium_lines() {
		if ( ! is_user_logged_in() ) {
			return self::login_screen();
		}

		$user   = wp_get_current_user();
		$levels = HRP_Members::generations( (int) $user->ID, 3 );
		$titles = array( 1 => 'First Generation', 2 => 'Second Generation', 3 => 'Third Generation' );

		ob_start();
		echo self::shell_open( $user, 'lines' ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
		<h2 class="hrp-page-title">My premium-lines</h2>
		<?php foreach ( $titles as $level => $title ) : ?>
			<section class="hrp-card">
				<h3><?php echo esc_html( $title ); ?> (<?php echo esc_html( count( $levels[ $level ] ) ); ?>)</h3>
				<?php if ( ! $levels[ $level ] ) : ?>
					<p class="hrp-muted">Nobody at this generation yet.</p>
				<?php else : ?>
					<div class="hrp-scroll">
						<table class="hrp-table">
							<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>CID</th><th>Joined</th></tr></thead>
							<tbody>
							<?php foreach ( $levels[ $level ] as $member ) : ?>
								<tr>
									<td><?php echo esc_html( $member->display_name ); ?></td>
									<td><?php echo esc_html( $member->user_email ); ?></td>
									<td><?php echo esc_html( get_user_meta( $member->ID, 'hrp_phone', true ) ); ?></td>
									<td><?php echo esc_html( HRP_Members::ref_code( $member->ID ) ); ?></td>
									<td><?php echo esc_html( mysql2date( 'd/m/Y', $member->user_registered ) ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>
		<?php
		echo self::shell_close(); // phpcs:ignore WordPress.Security.EscapeOutput
		return ob_get_clean();
	}
}
