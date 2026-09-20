<?php
/**
 * Shop sidebar (filters widget area).
 *
 * @package GuruExpertPowerTools
 */

defined( 'ABSPATH' ) || exit;
if ( ! is_active_sidebar( 'shop-sidebar' ) ) {
	return;
}
?>
<aside class="rk-sidebar" aria-label="<?php esc_attr_e( 'Shop filters', 'guruexpertpowertools' ); ?>">
	<?php dynamic_sidebar( 'shop-sidebar' ); ?>
</aside>
