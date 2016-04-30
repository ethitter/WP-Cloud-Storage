<?php
/**
 * @package WP Cloud Storage
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<h1 class="entry-title">
		<?php if ( is_singular() ) : ?>
			<?php the_title(); ?>
		<?php else : ?>
			<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>
		<?php endif; ?>
		</h1>
	</header><!-- .entry-header -->

	<?php if ( is_search() ) : // Only display Excerpts for Search ?>
	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div><!-- .entry-summary -->
	<?php else : ?>
	<div class="entry-content">
		<?php echo wp_get_attachment_link( get_the_ID(), ( is_singular() ? 'full' : 'thumbnail' ), ! is_singular(), true ); ?>

		<?php if ( has_excerpt() ) : ?>
			<p class="excerpt"><?php the_excerpt(); ?></p>
		<?php endif; ?>

		<?php
			$removed = remove_filter( 'the_content', 'prepend_attachment' );

			the_content();

			if ( $removed )
				add_filter( 'the_content', 'prepend_attachment' );
		?>

		<p class="single-download">
			<a href="<?php echo wp_cloud_storage_get_download_link( get_the_ID() ); ?>"><?php _e( 'Download', 'wp_cloud_storage' ); ?></a>
		</p>
	</div><!-- .entry-content -->
	<?php endif; ?>

	<footer class="entry-footer">
		<div class="entry-meta">
			<?php wp_cloud_storage_posted_on(); ?>
		</div><!-- .entry-meta -->
	</footer>

</article><!-- #post-<?php the_ID(); ?> -->
