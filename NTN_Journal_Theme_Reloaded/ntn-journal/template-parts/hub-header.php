<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Header per la Hub Page Videogioco (CPT videogioco e archivio Tag con dati IGDB).
 *
 * Fase 6 del restyling (v3.8.0): fascia a matrice di punti che segue il tema, copertina
 * IGDB intera in cornice al posto dello sfondo velato, contatori degli articoli e scheda
 * tecnica a celle. Riferimento visivo: docs/mockup/ntn-mockup-completo.html (Hub gioco).
 */

$current_obj = isset( $args['info'] ) ? null : ( isset( $args['id'] ) ? $args['id'] : ( isset( $args['object'] ) ? $args['object'] : get_queried_object() ) );
$igdb_info   = isset( $args['info'] ) ? $args['info'] : ( function_exists( 'ntn_get_hub_igdb_info' ) ? ntn_get_hub_igdb_info( $current_obj ) : ( function_exists( 'ntn_get_tag_igdb_info' ) ? ntn_get_tag_igdb_info( $current_obj ) : array() ) );

if ( empty( $igdb_info['title'] ) ) {
	if ( isset( $args['object'] ) ) {
		if ( $args['object'] instanceof WP_Post ) {
			$igdb_info['title'] = $args['object']->post_title;
		} elseif ( $args['object'] instanceof WP_Term ) {
			$igdb_info['title'] = $args['object']->name;
		}
	} elseif ( isset( $args['id'] ) ) {
		$igdb_info['title'] = get_the_title( $args['id'] );
	} elseif ( is_tag() ) {
		$igdb_info['title'] = single_tag_title( '', false );
	} elseif ( is_singular() ) {
		$igdb_info['title'] = get_the_title();
	} else {
		$queried = get_queried_object();
		if ( $queried instanceof WP_Post ) {
			$igdb_info['title'] = $queried->post_title;
		} elseif ( $queried instanceof WP_Term ) {
			$igdb_info['title'] = $queried->name;
		}
	}
}

$igdb_link = ! empty( $igdb_info['igdb_url'] ) ? $igdb_info['igdb_url'] : ( ! empty( $igdb_info['url'] ) ? $igdb_info['url'] : '' );

// Tag del gioco: il tag stesso (archivio tag) oppure quello con lo stesso slug del CPT
$hub_tag = null;
if ( isset( $args['object'] ) && $args['object'] instanceof WP_Term ) {
	$hub_tag = $args['object'];
} elseif ( isset( $args['id'] ) ) {
	$hub_tag_slug = get_post_field( 'post_name', $args['id'] );
	$hub_tag      = $hub_tag_slug ? get_term_by( 'slug', $hub_tag_slug, 'post_tag' ) : null;
	if ( ! $hub_tag && ! empty( $igdb_info['title'] ) && is_string( $igdb_info['title'] ) ) {
		$hub_tag = get_term_by( 'name', $igdb_info['title'], 'post_tag' );
	}
}
if ( is_wp_error( $hub_tag ) ) {
	$hub_tag = null;
}

// Contatori della Hub (articoli, recensioni, anteprime, ultimo articolo), in cache per un'ora
$hub_counts = array();
if ( $hub_tag instanceof WP_Term ) {
	$hub_counts = get_transient( 'ntn_hub_counts_' . $hub_tag->term_id );
	if ( ! is_array( $hub_counts ) ) {
		$hub_count_for = static function ( $category_slugs ) use ( $hub_tag ) {
			$q = new WP_Query(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'tax_query'      => array(
						'relation' => 'AND',
						array(
							'taxonomy' => 'post_tag',
							'field'    => 'term_id',
							'terms'    => $hub_tag->term_id,
						),
						array(
							'taxonomy' => 'category',
							'field'    => 'slug',
							'terms'    => $category_slugs,
						),
					),
				)
			);
			return (int) $q->found_posts;
		};
		$hub_latest    = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'tag_id'         => $hub_tag->term_id,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$hub_counts    = array(
			'posts'    => (int) $hub_tag->count,
			'reviews'  => $hub_count_for( array( 'recensioni', 'recensione' ) ),
			'previews' => $hub_count_for( array( 'anteprime', 'anteprima' ) ),
			'latest'   => ! empty( $hub_latest ) ? get_the_date( 'd.m.Y', $hub_latest[0] ) : '',
		);
		set_transient( 'ntn_hub_counts_' . $hub_tag->term_id, $hub_counts, HOUR_IN_SECONDS );
	}
}

// Testo di presentazione automatico dai dati IGDB (v3.11.0): dà alla Hub un contenuto proprio per Google.
// Sta nella scheda tecnica, come ultima cella a tutta larghezza. Se la redazione ha scritto un testo
// nell'editor, quello compare come "// Panoramica" nel corpo e la cella non serve.
$hub_summary = ( isset( $args['id'] ) && empty( $args['has_intro'] ) && function_exists( 'ntn_get_hub_auto_summary' ) ) ? ntn_get_hub_auto_summary( (int) $args['id'] ) : '';

// Data di uscita: solo le parti note (v3.21.0), es. "23/10/2026", "Q3 2027", "2028"
$release_text = ! empty( $igdb_info['release'] ) ? ntn_journal_release_label( $igdb_info['release'], $igdb_info['release_precision'] ?? '' ) : '';
?>

<header class="ntn-band ntn-hub-band">
	<div class="ntn-wrap">

		<?php if ( function_exists( 'ntn_journal_breadcrumb' ) ) : ?>
			<div class="ntn-art-crumbs"><?php ntn_journal_breadcrumb(); ?></div>
		<?php endif; ?>

		<div class="ntn-ghero">
			<div class="min-w-0">
				<div class="flex flex-wrap gap-2">
					<span class="ntn-chip">Hub page</span>
					<?php if ( ! empty( $igdb_link ) ) : ?>
						<a href="<?php echo esc_url( $igdb_link ); ?>" target="_blank" rel="noopener noreferrer nofollow" class="ntn-chip ntn-chip--red ntn-chip-link">Vedi su IGDB.com →</a>
					<?php endif; ?>
				</div>

				<h1 class="ntn-ghero-title"><?php echo esc_html( $igdb_info['title'] ); ?></h1>

				<?php if ( ! empty( $hub_counts ) ) : ?>
					<div class="ntn-gcounts">
						<span><b><?php echo esc_html( number_format_i18n( $hub_counts['posts'] ) ); ?></b>Articoli</span>
						<?php if ( $hub_counts['reviews'] ) : ?>
							<span><b><?php echo esc_html( number_format_i18n( $hub_counts['reviews'] ) ); ?></b><?php echo 1 === $hub_counts['reviews'] ? 'Recensione' : 'Recensioni'; ?></span>
						<?php endif; ?>
						<?php if ( $hub_counts['previews'] ) : ?>
							<span><b><?php echo esc_html( number_format_i18n( $hub_counts['previews'] ) ); ?></b><?php echo 1 === $hub_counts['previews'] ? 'Anteprima' : 'Anteprime'; ?></span>
						<?php endif; ?>
						<?php if ( $hub_counts['latest'] ) : ?>
							<span><b><?php echo esc_html( $hub_counts['latest'] ); ?></b>Ultimo articolo</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<!-- Scheda tecnica -->
				<div class="ntn-specs">
					<?php if ( $release_text ) : ?>
						<div><span class="ntn-specs-k">Uscita</span><span class="ntn-specs-v"><?php echo esc_html( $release_text ); ?></span></div>
					<?php endif; ?>
					<?php if ( ! empty( $igdb_info['developer'] ) ) : ?>
						<div><span class="ntn-specs-k">Sviluppatore</span><span class="ntn-specs-v is-link"><?php echo ntn_link_companies_to_tags( $igdb_info['developer'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- link già sanificati dalla funzione. ?></span></div>
					<?php endif; ?>
					<?php if ( ! empty( $igdb_info['publisher'] ) ) : ?>
						<div><span class="ntn-specs-k">Editore</span><span class="ntn-specs-v is-link"><?php echo ntn_link_companies_to_tags( $igdb_info['publisher'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- link già sanificati dalla funzione. ?></span></div>
					<?php endif; ?>
					<?php if ( ! empty( $igdb_info['genres'] ) ) : ?>
						<div><span class="ntn-specs-k">Genere</span><span class="ntn-specs-v"><?php echo esc_html( is_array( $igdb_info['genres'] ) ? implode( ', ', $igdb_info['genres'] ) : (string) $igdb_info['genres'] ); ?></span></div>
					<?php endif; ?>
					<?php if ( ! empty( $igdb_info['platforms'] ) ) : ?>
						<div class="is-wide">
							<span class="ntn-specs-k">Piattaforme</span>
							<div class="ntn-plats">
								<?php
								$platforms_raw = $igdb_info['platforms'];
								$platforms     = is_array( $platforms_raw ) ? $platforms_raw : ( is_scalar( $platforms_raw ) ? explode( ',', (string) $platforms_raw ) : array() );
								if ( function_exists( 'ntn_core_schema_platform_names' ) ) {
									$platforms = ntn_core_schema_platform_names( array_filter( $platforms, 'is_scalar' ) ); // v4.14.0: "PC (Microsoft Windows)" diventa "PC"
								}
								foreach ( array_slice( $platforms, 0, 8 ) as $platform ) {
									if ( is_scalar( $platform ) ) {
										echo '<span class="ntn-plat">' . esc_html( trim( (string) $platform ) ) . '</span>';
									}
								}
								?>
							</div>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $igdb_info['engine'] ) ) : ?>
						<div>
							<span class="ntn-specs-k">Motore grafico</span>
							<div class="ntn-engine"><?php echo ntn_link_engines_to_terms( $igdb_info['engine'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- link già sanificati dalla funzione. ?></div>
						</div>
					<?php endif; ?>
					<?php if ( '' !== $hub_summary ) : ?>
						<div class="is-full">
							<span class="ntn-specs-k">In breve</span>
							<p class="ntn-specs-p"><?php echo esc_html( $hub_summary ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! empty( $igdb_info['cover'] ) ) : ?>
				<!-- Copertina IGDB intera, in cornice -->
				<div class="ntn-gcover">
					<img src="<?php echo esc_url( $igdb_info['cover'] ); ?>" alt="<?php echo esc_attr( sprintf( 'Copertina di %s', $igdb_info['title'] ) ); ?>" width="600" height="800" class="w-full h-full object-cover" />
				</div>
			<?php endif; ?>
		</div>

	</div>
</header>
