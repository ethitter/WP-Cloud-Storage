<?php
/**
 * The Sidebar containing the main widget areas.
 *
 * @package WP Cloud Storage
 */
?>
	<div id="secondary" class="widget-area" role="complementary">
		<?php
			do_action( 'before_sidebar' );
			dynamic_sidebar( 'sidebar-1' );
		?>
	</div><!-- #secondary -->
