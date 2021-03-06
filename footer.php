<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the id=main div and all content after
 *
 * @package WP Cloud Storage
 */
?>

	</div><!-- #main -->

	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="site-info">
			<?php do_action( 'wp_cloud_storage_credits' ); ?>
			<a href="http://wordpress.org/" title="<?php esc_attr_e( 'A Semantic Personal Publishing Platform', 'wp_cloud_storage' ); ?>" rel="generator"><?php printf( __( 'Proudly powered by %s', 'wp_cloud_storage' ), 'WordPress' ); ?></a>
			<?php printf( __( '%1$s %2$s %3$s by %4$s.', 'wp_cloud_storage' ), __( 'using the', 'wp_cloud_storage' ), 'WP Cloud Storage', 'system', '<a href="http://ethitter.com/" rel="designer">Erick Hitter</a>' ); ?>
		</div><!-- .site-info -->
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>