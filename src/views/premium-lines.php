<?php defined( 'PORTAL' ) || exit; ?>
<?php
$titles = array(
	1 => array( 'First generation', 'Joined with your link' ),
	2 => array( 'Second generation', 'Joined with your premium-lines\' links' ),
	3 => array( 'Third generation', 'One step further down' ),
);
shell_start( 'My premium-lines', $user, 'premium-lines', 'Everyone in your network, three generations deep.' );
?>

<section class="stats stats-3">
	<?php foreach ( $titles as $level => list( $title, $hint ) ) : ?>
		<div class="stat">
			<span class="stat-icon <?php echo array( 1 => 'is-blue', 2 => 'is-violet', 3 => 'is-green' )[ $level ]; ?>"><?php echo icon( 1 === $level ? 'users' : 'network' ); ?></span>
			<span class="stat-label"><?php echo e( $title ); ?></span>
			<strong class="stat-value"><?php echo e( count( $levels[ $level ] ) ); ?></strong>
			<span class="stat-foot"><?php echo e( $hint ); ?></span>
		</div>
	<?php endforeach; ?>
</section>

<?php foreach ( $titles as $level => list( $title ) ) : ?>
	<section class="card">
		<header class="card-head">
			<h2><?php echo e( $title ); ?></h2>
			<span class="badge"><?php echo e( count( $levels[ $level ] ) ); ?></span>
		</header>
		<?php if ( ! $levels[ $level ] ) : ?>
			<?php echo empty_state( 'users', 'Nobody here yet', 1 === $level ? 'Share your referral link from the dashboard to grow your network.' : 'This fills up as your premium-lines bring in people of their own.' ); ?>
		<?php else : ?>
			<div class="table-wrap">
				<table class="table">
					<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>CID</th><th>Joined</th></tr></thead>
					<tbody>
					<?php foreach ( $levels[ $level ] as $member ) : ?>
						<tr>
							<td><span class="cell-person"><span class="avatar avatar-sm"><?php echo e( initials( $member['full_name'] ) ); ?></span><?php echo e( $member['full_name'] ); ?></span></td>
							<td><?php echo e( $member['email'] ); ?></td>
							<td><?php echo e( '' !== $member['phone'] ? $member['phone'] : '-' ); ?></td>
							<td class="code"><?php echo e( $member['ref_code'] ); ?></td>
							<td><?php echo e( format_date( $member['created_at'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</section>
<?php endforeach; ?>

<?php shell_end(); ?>
