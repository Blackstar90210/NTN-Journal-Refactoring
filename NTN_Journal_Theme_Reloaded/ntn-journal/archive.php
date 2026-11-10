<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The template for displaying archive pages (Categories, Tags, Dates, etc.)
 *
 * Fase 6 del restyling (v3.8.0): intestazione sulla fascia a matrice di punti, articoli a celle.
 * I voti nelle schede sono gestiti da template-parts/content.php (con function_exists( 'ntn_get_rating_initials' )).
 *
 * @package NTN_Journal
 */

get_header();

global $wp_query;

// Etichetta e titolo dell'archivio, senza il prefisso automatico ("Categoria:", "Tag:")
if ( is_category() ) {
	$ntn_arch_label = 'Categoria';
} elseif ( is_tag() ) {
	$ntn_arch_label = 'Tag';
} elseif ( is_date() ) {
	$ntn_arch_label = 'Archivio per data';
} elseif ( is_tax() ) {
	$ntn_arch_tax   = get_taxonomy( get_queried_object()->taxonomy ?? '' );
	$ntn_arch_label = $ntn_arch_tax ? $ntn_arch_tax->labels->singular_name : 'Archivio';
} elseif ( is_post_type_archive() ) {
	$ntn_arch_label = 'Archivio';
} else {
	$ntn_arch_label = 'Archivio';
}
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
$ntn_arch_title = wp_strip_all_tags( get_the_archive_title() );
remove_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

get_template_part(
	'template-parts/archive-head',
	null,
	array(
		'label'       => $ntn_arch_label,
		'title'       => $ntn_arch_title,
		'description' => get_the_archive_description(),
		'count'       => isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0,
	)
);
?>

<main id="primary" class="site-main ntn-main">
	<div class="ntn-wrap ntn-arch-body">

		<?php if ( have_posts() ) : ?>
			<div class="ntn-cells ntn-cells--3">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content' );
				endwhile;
				?>
			</div>

			<!-- Navigation pagination -->
			<div class="ntn-pager-wrap">
				<?php
				the_posts_pagination(
					array(
						'mid_size'  => 2,
						'prev_text' => '&larr; Precedente',
						'next_text' => 'Successivo &rarr;',
					)
				);
				?>
			</div>

		<?php else : ?>
			<div class="ntn-empty">
				<span>Nessun articolo</span>
				<p>Nessun articolo trovato in questo archivio. Riprova più tardi.</p>
			</div>
		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
