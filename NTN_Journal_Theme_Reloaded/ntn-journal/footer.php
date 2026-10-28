<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * Fase 2 del restyling (v3.5.0): barra di stato su una griglia che va a capo da telefono,
 * così copyright, menu e indicatori non si sovrappongono più.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package NTN_Journal
 */

?>

	<!-- Barra di stato in stile Nothing OS -->
	<footer class="ntn-footer">
		<div class="ntn-wrap ntn-footer-in">
			<span class="ntn-footer-copy">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>

			<div class="ntn-footer-center">
				<?php
				if ( has_nav_menu( 'menu-footer' ) ) {
					wp_nav_menu( array(
						'theme_location'  => 'menu-footer',
						'container'       => 'nav',
						'container_class' => 'footer-navigation',
						'menu_class'      => 'ntn-footer-menu',
						'fallback_cb'     => false,
						'depth'           => 1,
					) );
				} else {
					?>
					<span>Tema by <?php echo esc_html( wp_get_theme()->get( 'Author' ) ); ?></span>
					<?php
				}
				?>
			</div>

			<div class="ntn-footer-leds" aria-hidden="true">
				<span class="is-on"></span>
				<span></span>
				<span></span>
			</div>
		</div>
	</footer>


</div><!-- #page -->



<?php wp_footer(); ?>

</body>
</html>
