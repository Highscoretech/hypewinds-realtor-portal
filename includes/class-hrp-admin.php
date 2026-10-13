<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin screens: record sales, list properties, set the commission rate.
 */
class HRP_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_posts' ) );
	}

	public static function menu() {
		add_menu_page(
			'Realtor Portal',
			'Realtor Portal',
			'manage_options',
			'hrp',
			array( __CLASS__, 'page_sales' ),
			'dashicons-networking',
			30
		);

		add_submenu_page( 'hrp', 'Sales & Commissions', 'Sales', 'manage_options', 'hrp', array( __CLASS__, 'page_sales' ) );
		add_submenu_page( 'hrp', 'Members', 'Members', 'manage_options', 'hrp-members', array( __CLASS__, 'page_members' ) );
		add_submenu_page( 'hrp', 'Settings', 'Settings', 'manage_options', 'hrp-settings', array( __CLASS__, 'page_settings' ) );
	}

	public static function handle_posts() {
		if ( empty( $_POST['hrp_admin_action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['hrp_admin_action'] ) );
		check_admin_referer( 'hrp_admin_' . $action );

		global $wpdb;

		if ( 'record_sale' === $action ) {
			$result = HRP_Commissions::record_sale(
				array(
					'seller_id'     => (int) ( $_POST['seller_id'] ?? 0 ),
					'property_type' => sanitize_text_field( wp_unslash( $_POST['property_type'] ?? 'land' ) ),
					'description'   => sanitize_text_field( wp_unslash( $_POST['description'] ?? '' ) ),
					'amount'        => (float) ( $_POST['amount'] ?? 0 ),
					'sold_on'       => sanitize_text_field( wp_unslash( $_POST['sold_on'] ?? '' ) ),
				)
			);

			$notice = is_wp_error( $result ) ? $result->get_error_message() : 'Sale recorded.';
			self::redirect( 'hrp', $notice, is_wp_error( $result ) );
		}



		if ( 'save_settings' === $action ) {
			$rate = (float) ( $_POST['commission_percent'] ?? 0 );
			if ( $rate < 0 || $rate > 100 ) {
				self::redirect( 'hrp-settings', 'The commission rate must be between 0 and 100.', true );
			}

			update_option( 'hrp_commission_percent', $rate );
			update_option( 'hrp_whatsapp_group', esc_url_raw( wp_unslash( $_POST['whatsapp_group'] ?? '' ) ) );
			update_option( 'hrp_property_post_type', sanitize_key( wp_unslash( $_POST['property_post_type'] ?? '' ) ) );
			update_option( 'hrp_price_meta_key', sanitize_text_field( wp_unslash( $_POST['price_meta_key'] ?? '' ) ) );
			self::redirect( 'hrp-settings', 'Settings saved.' );
		}
	}

	private static function redirect( $page, $message, $is_error = false ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => $page,
					'hrp_message'  => rawurlencode( $message ),
					'hrp_is_error' => $is_error ? 1 : 0,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private static function notice() {
		if ( empty( $_GET['hrp_message'] ) ) {
			return;
		}

		$class = ! empty( $_GET['hrp_is_error'] ) ? 'notice-error' : 'notice-success';
		printf(
			'<div class="notice %s"><p>%s</p></div>',
			esc_attr( $class ),
			esc_html( urldecode( wp_unslash( $_GET['hrp_message'] ) ) )
		);
	}

	private static function members() {
		return get_users( array( 'role' => HRP_ROLE, 'orderby' => 'display_name' ) );
	}

	public static function page_sales() {
		global $wpdb;

		$sales = $wpdb->get_results( 'SELECT * FROM ' . HRP_Commissions::sales_table() . ' ORDER BY created_at DESC LIMIT 100' );
		$commissions = $wpdb->get_results( 'SELECT * FROM ' . HRP_Commissions::commissions_table(), OBJECT_K );
		$by_sale = array();
		foreach ( $commissions as $row ) {
			$by_sale[ (int) $row->sale_id ] = $row;
		}
		?>
		<div class="wrap">
			<h1>Sales &amp; Commissions</h1>
			<?php self::notice(); ?>

			<h2>Record a sale</h2>
			<form method="post">
				<?php wp_nonce_field( 'hrp_admin_record_sale' ); ?>
				<input type="hidden" name="hrp_admin_action" value="record_sale">
				<table class="form-table">
					<tr><th><label for="seller_id">Realtor who sold</label></th><td>
						<select name="seller_id" id="seller_id" required>
							<option value="">Select</option>
							<?php foreach ( self::members() as $member ) : ?>
								<option value="<?php echo esc_attr( $member->ID ); ?>">
									<?php echo esc_html( $member->display_name . ' (' . HRP_Members::ref_code( $member->ID ) . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td></tr>
					<tr><th><label for="property_type">Property</label></th><td>
						<select name="property_type" id="property_type">
							<option value="land">Land</option>
							<option value="house">House</option>
						</select>
					</td></tr>
					<tr><th><label for="amount">Sale amount</label></th>
						<td><input type="number" step="0.01" min="0.01" name="amount" id="amount" required></td></tr>
					<tr><th><label for="sold_on">Date sold</label></th>
						<td><input type="date" name="sold_on" id="sold_on"></td></tr>
					<tr><th><label for="description">Description</label></th>
						<td><input type="text" class="regular-text" name="description" id="description"></td></tr>
				</table>
				<?php submit_button( 'Record sale' ); ?>
			</form>

			<h2>Recent sales</h2>
			<table class="widefat striped">
				<thead><tr><th>Date</th><th>Realtor</th><th>Property</th><th>Amount</th><th>Premium-star</th><th>Commission</th></tr></thead>
				<tbody>
				<?php if ( ! $sales ) : ?>
					<tr><td colspan="6">No sales recorded yet.</td></tr>
				<?php else : ?>
					<?php foreach ( $sales as $sale ) :
						$seller = get_userdata( $sale->seller_id );
						$commission = $by_sale[ (int) $sale->id ] ?? null;
						$star = $commission ? get_userdata( $commission->beneficiary_id ) : null; ?>
						<tr>
							<td><?php echo esc_html( mysql2date( 'd/m/Y', $sale->sold_on ) ); ?></td>
							<td><?php echo esc_html( $seller ? $seller->display_name : '-' ); ?></td>
							<td><?php echo esc_html( $sale->property_type . ': ' . $sale->description ); ?></td>
							<td><?php echo esc_html( HRP_Commissions::money( $sale->amount ) ); ?></td>
							<td><?php echo esc_html( $star ? $star->display_name : '-' ); ?></td>
							<td><?php echo esc_html( $commission ? HRP_Commissions::money( $commission->amount ) : '-' ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}


	public static function page_members() {
		?>
		<div class="wrap">
			<h1>Members</h1>
			<?php self::notice(); ?>
			<table class="widefat striped">
				<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>CID</th><th>Premium-star</th><th>Bank</th></tr></thead>
				<tbody>
				<?php $members = self::members(); ?>
				<?php if ( ! $members ) : ?>
					<tr><td colspan="6">No realtors registered yet.</td></tr>
				<?php else : ?>
					<?php foreach ( $members as $member ) :
						$star_id = HRP_Members::premium_star_id( $member->ID );
						$star    = $star_id ? get_userdata( $star_id ) : null; ?>
						<tr>
							<td><?php echo esc_html( $member->display_name ); ?></td>
							<td><?php echo esc_html( $member->user_email ); ?></td>
							<td><?php echo esc_html( get_user_meta( $member->ID, 'hrp_phone', true ) ); ?></td>
							<td><?php echo esc_html( HRP_Members::ref_code( $member->ID ) ); ?></td>
							<td><?php echo esc_html( $star ? $star->display_name : '-' ); ?></td>
							<td><?php
								echo esc_html( trim(
									get_user_meta( $member->ID, 'hrp_bank_name', true ) . ' ' .
									get_user_meta( $member->ID, 'hrp_account_number', true )
								) );
							?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function page_settings() {
		?>
		<div class="wrap">
			<h1>Settings</h1>
			<?php self::notice(); ?>
			<form method="post">
				<?php wp_nonce_field( 'hrp_admin_save_settings' ); ?>
				<input type="hidden" name="hrp_admin_action" value="save_settings">
				<table class="form-table">
					<tr><th><label for="commission_percent">Commission rate (%)</label></th><td>
						<input type="number" step="0.01" min="0" max="100" name="commission_percent" id="commission_percent"
							value="<?php echo esc_attr( HRP_Commissions::rate() ); ?>">
						<p class="description">Applies to sales recorded from now on.</p>
					</td></tr>
					<tr><th><label for="whatsapp_group">WhatsApp group link</label></th><td>
						<input type="url" class="regular-text" name="whatsapp_group" id="whatsapp_group"
							value="<?php echo esc_attr( get_option( 'hrp_whatsapp_group', '' ) ); ?>">
						<p class="description">Included in the welcome email when set.</p>
					</td></tr>
					<tr><th><label for="property_post_type">Property listings</label></th><td>
						<?php $chosen = HRP_Commissions::property_post_type(); ?>
						<select name="property_post_type" id="property_post_type">
							<option value="">None - hide the properties table</option>
							<?php foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) :
								if ( in_array( $type->name, array( 'attachment' ), true ) ) {
									continue;
								} ?>
								<option value="<?php echo esc_attr( $type->name ); ?>" <?php selected( $chosen, $type->name ); ?>>
									<?php echo esc_html( $type->labels->singular_name . ' (' . $type->name . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">The portal lists the site's own properties. Nothing is duplicated or invented.</p>
					</td></tr>
					<tr><th><label for="price_meta_key">Price field name</label></th><td>
						<input type="text" class="regular-text" name="price_meta_key" id="price_meta_key"
							value="<?php echo esc_attr( get_option( 'hrp_price_meta_key', '' ) ); ?>">
						<p class="description">Custom field the theme stores a listing's price in. Left blank, the price column shows a dash rather than a guess.</p>
					</td></tr>
					<tr><th>Portal page</th><td>
						<a href="<?php echo esc_url( hrp_portal_url() ); ?>" target="_blank" rel="noopener">
							<?php echo esc_html( hrp_portal_url() ); ?></a>
						<p class="description">Use this URL for the footer link.</p>
					</td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
