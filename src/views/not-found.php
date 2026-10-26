<?php defined( 'PORTAL' ) || exit; ?>
<?php auth_start( 'Page not found', 'There is nothing at this address.' ); ?>

<a class="btn btn-primary btn-block" href="<?php echo e( url() ); ?>">Go to the realtor portal</a>

<?php auth_end(); ?>
