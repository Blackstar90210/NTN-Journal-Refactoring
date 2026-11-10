<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The template for displaying the Comunicati Stampa archive
 *
 * Fase 8 del restyling (v3.10.0): intestazione comune sulla fascia e comunicati a celle.
 * Il giallo, presente anche in Nothing OS 4 e 5, resta come accento dei soli comunicati.
 *
 * @package NTN_Journal
 */

get_header();

global $wp_query;
get_template_part(
	'template-parts/archive-head',
	null,
	array(
		'label'       => 'Press room',
		'title'       => 'Comunicati stampa',
		'description' => 'I comunicati ufficiali di editori, studi e agenzie, pubblicati così come li riceviamo. Il testo non è scritto dalla redazione.',
		'count'       => isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0,
		'unit'        => array( 'Comunicato', 'Comunicati' ),
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
					$publisher = get_post_meta( get_the_ID(), 'ntn_press_publisher', true );
					$developer = get_post_meta( get_the_ID(), 'ntn_press_developer', true );
					$agency    = get_post_meta( get_the_ID(), 'ntn_press_agency', true );

					$entities = array();
					foreach ( array( $publisher, $developer, $agency ) as $meta_field ) {
						if ( empty( $meta_field ) ) {
							continue;
						}
						if ( is_array( $meta_field ) ) {
							$entities = array_merge( $entities, array_filter( array_map( 'trim', array_map( 'strval', $meta_field ) ) ) );
						} elseif ( is_scalar( $meta_field ) ) {
							$clean = trim( (string) $meta_field );
							if ( '' !== $clean ) {
								$entities[] = $clean;
							}
						}
					}
					$da_text = implode( ' / ', $entities );
					?>
					<a href="<?php the_permalink(); ?>" id="post-<?php the_ID(); ?>" <?php post_class( 'ntn-cell ntn-card ntn-press-card group' ); ?>>
						<div class="ntn-meta">
							<span class="ntn-chip ntn-chip--press">PR</span>
							<span><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd.m.Y · H:i' ) ); ?></time></span>
						</div>

						<h3 class="ntn-card-title"><?php the_title(); ?></h3>

						<?php if ( ! empty( $da_text ) ) : ?>
							<p class="ntn-press-from">Da: <b><?php echo esc_html( $da_text ); ?></b></p>
						<?php endif; ?>

						<p class="ntn-card-excerpt line-clamp-3"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>

						<span class="ntn-press-more">// Leggi comunicato &rarr;</span>
					</a>
				<?php endwhile; ?>
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
				<span>Nessun comunicato</span>
				<p>Nessun comunicato stampa trovato.</p>
			</div>
		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
