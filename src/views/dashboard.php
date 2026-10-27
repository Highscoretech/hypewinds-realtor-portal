<?php defined( 'PORTAL' ) || exit; ?>
<?php
// Nothing here invents content: every figure is read back from what has
// actually been recorded, and an empty section says it is empty.
$link     = referral_link( $user['ref_code'] );
$first    = strtok( $user['full_name'], ' ' );
$whatsapp = 'https://wa.me/?text=' . rawurlencode( sprintf( 'Join %s as a realtor with my referral link: %s', config( 'site_name' ), $link ) );
shell_start( 'Welcome back, ' . $first, $user, '', 'Here is how your realtor network is doing.' );
?>

<section class="stats">
	<div class="stat">
		<span class="stat-icon is-green"><?php echo icon( 'wallet' ); ?></span>
		<span class="stat-label">Commission earned</span>
		<strong class="stat-value"><?php echo e( money( $total ) ); ?></strong>
	</div>
	<div class="stat">
		<span class="stat-icon is-blue"><?php echo icon( 'users' ); ?></span>
		<span class="stat-label">Direct premium-lines</span>
		<strong class="stat-value"><?php echo e( count( $lines ) ); ?></strong>
	</div>
	<div class="stat">
		<span class="stat-icon is-violet"><?php echo icon( 'network' ); ?></span>
		<span class="stat-label">Total network</span>
		<strong class="stat-value"><?php echo e( $network ); ?></strong>
		<span class="stat-foot">Across 3 generations</span>
	</div>
	<div class="stat">
		<span class="stat-icon is-red"><?php echo icon( 'id' ); ?></span>
		<span class="stat-label">Your CID</span>
		<strong class="stat-value stat-code"><?php echo e( $user['ref_code'] ); ?></strong>
		<?php // Shown here because it is a sign-in detail the member has nowhere else to look up. ?>
		<span class="stat-foot">Username: <strong><?php echo e( $user['username'] ); ?></strong></span>
	</div>
</section>

<section class="card referral">
	<div class="referral-text">
		<h2><?php echo icon( 'link' ); ?> Your referral link</h2>
		<p>Share it with people who want to sell with us. Everyone who registers through it becomes your premium-line, and you earn commission on their sales.</p>
	</div>
	<div class="referral-actions">
		<div class="copy-field">
			<input type="text" class="input" readonly value="<?php echo e( $link ); ?>" aria-label="Your referral link">
			<button type="button" class="btn btn-primary" data-copy="<?php echo e( $link ); ?>"><?php echo icon( 'copy' ); ?><span>Copy link</span></button>
		</div>
		<div class="referral-row">
			<a class="btn btn-whatsapp" href="<?php echo e( $whatsapp ); ?>" target="_blank" rel="noopener"><?php echo icon( 'whatsapp' ); ?><span>Share on WhatsApp</span></a>
			<span class="muted">Your code <strong class="code"><?php echo e( $user['ref_code'] ); ?></strong>
				<button type="button" class="icon-button icon-button-sm" data-copy="<?php echo e( $user['ref_code'] ); ?>" aria-label="Copy your code"><?php echo icon( 'copy' ); ?></button></span>
		</div>
	</div>
</section>

<div class="columns">
	<div class="column-main">
		<section class="card">
			<header class="card-head">
				<h2><?php echo icon( 'wallet' ); ?> Your commissions</h2>
				<?php if ( $earnings ) : ?><span class="badge"><?php echo e( count( $earnings ) ); ?></span><?php endif; ?>
			</header>
			<?php if ( ! $earnings ) : ?>
				<?php echo empty_state( 'wallet', 'No commission yet', 'You earn whenever one of your premium-lines sells a property.' ); ?>
			<?php else : ?>
				<ul class="rows">
				<?php foreach ( $earnings as $row ) : ?>
					<li class="row">
						<span class="row-icon is-green"><?php echo icon( 'wallet' ); ?></span>
						<span class="row-main">
							<span class="row-title"><?php echo e( $row['seller_name'] ); ?> made a sale</span>
							<span class="row-meta"><?php echo e( format_date( $row['created_at'] ) ); ?> &middot; <?php echo e( (float) $row['rate_percent'] ); ?>% commission</span>
						</span>
						<strong class="row-amount">+<?php echo e( money( $row['amount'] ) ); ?></strong>
					</li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>

		<section class="card">
			<header class="card-head">
				<h2><?php echo icon( 'building' ); ?> Our properties and prices</h2>
				<?php if ( $properties ) : ?><span class="badge"><?php echo e( count( $properties ) ); ?></span><?php endif; ?>
			</header>
			<?php if ( ! $properties ) : ?>
				<?php echo empty_state( 'building', 'No properties listed yet', 'Listings and prices will appear here as soon as they are added.' ); ?>
			<?php else : ?>
				<div class="table-wrap">
					<table class="table table-fit">
						<thead><tr><th>Property</th><th class="hide-sm">Location</th><th>Status</th><th class="num">Price</th></tr></thead>
						<tbody>
						<?php foreach ( $properties as $property ) : ?>
							<tr>
								<td class="strong">
									<?php if ( $property['url'] ) : ?>
										<a href="<?php echo e( $property['url'] ); ?>" target="_blank" rel="noopener"><?php echo e( $property['name'] ); ?> <?php echo icon( 'external' ); ?></a>
									<?php else : ?>
										<?php echo e( $property['name'] ); ?>
									<?php endif; ?>
								</td>
								<td class="hide-sm"><?php echo e( '' !== $property['location'] ? $property['location'] : '-' ); ?></td>
								<td><span class="pill<?php echo 'Sold out' === $property['status'] ? ' pill-muted' : ( 'Selling fast' === $property['status'] ? ' pill-warn' : '' ); ?>"><?php echo e( $property['status'] ); ?></span></td>
								<td class="num"><?php echo null === $property['price'] ? '&mdash;' : e( money( $property['price'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
	</div>

	<div class="column-side">
		<section class="card">
			<header class="card-head"><h2><?php echo icon( 'star' ); ?> Your premium-star</h2></header>
			<?php if ( $star ) : ?>
				<div class="person">
					<span class="avatar"><?php echo e( initials( $star['full_name'] ) ); ?></span>
					<span>
						<span class="person-name"><?php echo e( $star['full_name'] ); ?></span>
						<span class="person-meta"><?php echo e( $star['email'] ); ?></span>
					</span>
				</div>
			<?php else : ?>
				<?php echo empty_state( 'star', 'No premium-star', 'You joined without a referral link.' ); ?>
			<?php endif; ?>
		</section>

		<section class="card">
			<header class="card-head">
				<h2><?php echo icon( 'bell' ); ?> Notifications</h2>
				<?php if ( $notices ) : ?><span class="badge"><?php echo e( count( $notices ) ); ?></span><?php endif; ?>
			</header>
			<?php if ( ! $notices ) : ?>
				<?php echo empty_state( 'bell', 'You are all caught up', 'New premium-lines and commissions will show here.' ); ?>
			<?php else : ?>
				<ul class="feed">
				<?php foreach ( $notices as $note ) : ?>
					<li>
						<span class="feed-dot"></span>
						<span class="feed-body"><?php echo e( $note['body'] ); ?>
							<span class="feed-time"><?php echo e( format_date( $note['created_at'] ) ); ?></span></span>
					</li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	</div>
</div>

<?php shell_end(); ?>
