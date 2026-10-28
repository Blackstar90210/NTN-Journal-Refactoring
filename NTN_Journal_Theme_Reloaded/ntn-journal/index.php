<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the posts page and the search results.
 *
 * Fase 6 del restyling (v3.8.0): intestazione sulla fascia a matrice di punti, articoli a celle.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package NTN_Journal
 */

get_header();

global $wp_query;

if ( is_search() ) {
	get_template_part(
		'template-parts/archive-head',
		null,
		array(
			'label' => 'Ricerca',
			'title' => sprintf( '«%s»', get_search_query( false ) ),
			'count' => isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0,
			'unit'  => array( 'Risultato', 'Risultati' ), // v3.19.0
		)
	);
} elseif ( is_home() && ! is_front_page() ) {
	get_template_part(
		'template-parts/archive-head',
		null,
		array(
			'label' => 'Tutti gli articoli',
			'title' => single_post_title( '', false ),
			'count' => isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0,
		)
	);
}
?>

<main id="primary" class="site-main ntn-main">
	<div class="ntn-wrap ntn-arch-body">
		<?php
		if ( have_posts() ) :
			?>

			<div class="ntn-cells ntn-cells--3">
				<?php
				/* Start the Loop */
				while ( have_posts() ) :
					the_post();

					/*
					 * Include the Post-Type-specific template for the content.
					 */
					get_template_part( 'template-parts/content', get_post_type() );

				endwhile;
				?>
			</div>

			<div class="ntn-pager-wrap">
				<?php
				the_posts_pagination( array(
					'mid_size'  => 2,
					'prev_text' => '&larr; Precedente',
					'next_text' => 'Successivo &rarr;',
				) );
				?>
			</div>

			<?php
		else :

			get_template_part( 'template-parts/content', 'none' );

		endif;
		?>
	</div>
</main><!-- #main -->

<?php
get_footer();
