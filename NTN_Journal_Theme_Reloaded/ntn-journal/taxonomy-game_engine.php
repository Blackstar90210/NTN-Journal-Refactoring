<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The template for displaying Game Engine Archive pages (/motore-grafico/[slug])
 *
 * Fase 6 del restyling (v3.8.0): intestazione sulla fascia a matrice di punti, contenuti a celle.
 *
 * @package NTN_Journal
 */

get_header();

global $wp_query;
$current_engine = get_queried_object();
$engine_name    = ( $current_engine && isset( $current_engine->name ) ) ? $current_engine->name : 'Motore grafico';

$engine_desc = get_the_archive_description();
if ( ! $engine_desc ) {
	$engine_desc = sprintf(
		'Tutti i videogiochi, le recensioni e gli articoli presenti su %s sviluppati con il motore grafico %s.',
		get_bloginfo( 'name' ),
		$engine_name
	);
}

get_template_part(
	'template-parts/archive-head',
	null,
	array(
		'label'       => 'Motore grafico',
		'title'       => $engine_name,
		'description' => $engine_desc,
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
				<span>Nessun contenuto</span>
				<p>Nessun videogioco o articolo collegato a questo motore grafico.</p>
			</div>
		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
