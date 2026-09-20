<?php
/**
 * Generic fallback template (blog home and archives).
 *
 * @package GuruExpertPowerTools
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="site-main container rk-content">
	<?php if ( have_posts() ) : ?>
		<?php if ( is_home() && ! is_front_page() ) : ?>
			<header class="rk-content__head"><h1 class="page-title"><?php single_post_title(); ?></h1></header>
		<?php elseif ( is_archive() ) : ?>
			<header class="rk-content__head">
				<h1 class="page-title"><?php the_archive_title(); ?></h1>
				<?php the_archive_description( '<div class="rk-content__meta">', '</div>' ); ?>
			</header>
		<?php endif; ?>

		<div class="rk-postlist">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'rk-post' ); ?>>
					<h2 class="rk-post__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="rk-post__meta"><?php echo esc_html( get_the_date() ); ?></div>
					<div class="rk-post__excerpt"><?php the_excerpt(); ?></div>
					<a class="rk-post__more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read more', 'guruexpertpowertools' ); ?> &rarr;</a>
				</article>
				<?php
			endwhile;
			?>
		</div>
		<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
	<?php else : ?>
		<div class="rk-empty">
			<h1><?php esc_html_e( 'Nothing here yet', 'guruexpertpowertools' ); ?></h1>
			<p><?php esc_html_e( 'There is nothing to show here right now. Try a search or head back to the homepage.', 'guruexpertpowertools' ); ?></p>
			<div class="rk-empty__actions">
				<a class="rk-btn rk-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'guruexpertpowertools' ); ?></a>
			</div>
			<div class="rk-empty__search"><?php get_search_form(); ?></div>
		</div>
	<?php endif; ?>
</main>
<?php
get_footer();
