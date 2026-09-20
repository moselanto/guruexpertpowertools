<?php
/**
 * Default page template (About, Policies, Contact, etc.).
 *
 * @package GuruExpertPowerTools
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<nav class="rk-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'guruexpertpowertools' ); ?>">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'guruexpertpowertools' ); ?></a>
	<span class="rk-crumb-sep" aria-hidden="true">/</span>
	<span><?php the_title(); ?></span>
</nav>
<main id="primary" class="site-main container rk-content rk-content--narrow">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'rk-page' ); ?>>
			<h1 class="page-title"><?php the_title(); ?></h1>
			<div class="rk-page__content"><?php the_content(); ?></div>
			<?php wp_link_pages(); ?>
		</article>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
