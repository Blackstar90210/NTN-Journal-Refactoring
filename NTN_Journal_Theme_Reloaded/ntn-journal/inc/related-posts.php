<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ==============================================================================
 * BOX "// CONSIGLIATI" DENTRO IL TESTO (v3.16.0, regola del CEO)
 * ==============================================================================
 *
 * Quanti box dipende dalla lunghezza del testo:
 * - sotto le 150 parole nessuno;
 * - fino a 300 parole uno, a metà (50%);
 * - fino a 500 parole due, al 25% e al 75%;
 * - oltre 500 parole tre, al 25%, 50% e 75%.
 * Ogni box propone un articolo diverso. Il box va dopo il paragrafo più vicino al punto
 * indicato, senza mai spezzare citazioni, immagini, elenchi, tabelle o altri blocchi.
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html (pagina Articolo).
 */

/**
 * Parole del testo: si contano solo le parole lette, senza figure (immagini, gallerie,
 * contenuti incorporati con le loro didascalie), script e stili.
 *
 * @param string $html Testo dell'articolo.
 * @return int
 */
function ntn_journal_count_words( $html ) {
	$html = preg_replace( '#<(figure|script|style)\b[^>]*>.*?</\1>#is', ' ', (string) $html );
	$text = trim( wp_strip_all_tags( (string) $html ) );
	if ( '' === $text ) {
		return 0;
	}
	return count( preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY ) );
}

/**
 * Posizioni dei box per un testo di $words parole (frazioni del testo).
 *
 * @param int $words Parole del testo.
 * @return float[]
 */
function ntn_journal_suggest_positions( $words ) {
	$words = (int) $words;
	if ( $words < 150 ) {
		return array();
	}
	if ( $words <= 300 ) {
		return array( 0.5 );
	}
	if ( $words <= 500 ) {
		return array( 0.25, 0.75 );
	}
	return array( 0.25, 0.5, 0.75 );
}

/**
 * Punti sicuri dove inserire un box: subito dopo un paragrafo che non sta dentro un altro
 * blocco (citazione, figura, elenco, tabella, gruppo…), con le parole lette fino a lì.
 *
 * @param string $content Testo dell'articolo.
 * @return array<int,array{offset:int,words:int}>
 */
function ntn_journal_suggest_break_points( $content ) {
	$content = (string) $content;
	$points  = array();
	if ( ! preg_match_all( '#</p>#i', $content, $closings, PREG_OFFSET_CAPTURE ) ) {
		return $points;
	}
	preg_match_all( '#<(/?)(blockquote|figure|ul|ol|table|div|aside|details|section|pre)\b[^>]*>#i', $content, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );

	$depth    = 0;
	$tag_i    = 0;
	$tag_n    = count( $tags );
	$last_end = 0;
	$words    = 0;
	foreach ( $closings[0] as $closing ) {
		$end = $closing[1] + strlen( $closing[0] );
		// Aggiorna la profondità con i blocchi aperti e chiusi prima di questo paragrafo
		while ( $tag_i < $tag_n && $tags[ $tag_i ][0][1] < $end ) {
			$self_closing = '/>' === substr( $tags[ $tag_i ][0][0], -2 );
			if ( ! $self_closing ) {
				$depth += '/' === $tags[ $tag_i ][1][0] ? -1 : 1;
			}
			++$tag_i;
		}
		$words   += ntn_journal_count_words( substr( $content, $last_end, $end - $last_end ) );
		$last_end = $end;
		if ( $depth > 0 ) {
			continue; // Dentro un altro blocco: qui il box lo spezzerebbe
		}
		$points[] = array(
			'offset' => $end,
			'words'  => $words,
		);
	}
	return $points;
}

/**
 * Articoli da proporre: prima quelli con gli stessi tag, poi quelli della stessa categoria.
 *
 * @param int $post_id ID dell'articolo.
 * @param int $count   Quanti articoli servono.
 * @return WP_Post[]
 */
function ntn_journal_suggest_posts( $post_id, $count ) {
	$posts   = array();
	$exclude = array( (int) $post_id );
	$tags    = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
	if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
		$query = new WP_Query(
			array(
				'tag__in'             => $tags,
				'post__not_in'        => $exclude,
				'posts_per_page'      => $count,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);
		$posts = $query->posts;
	}
	if ( count( $posts ) < $count && function_exists( 'ntn_get_primary_category' ) ) {
		$primary_cat = ntn_get_primary_category( $post_id );
		if ( $primary_cat ) {
			foreach ( $posts as $p ) {
				$exclude[] = (int) $p->ID;
			}
			$query = new WP_Query(
				array(
					'category__in'        => array( (int) $primary_cat->term_id ),
					'post__not_in'        => $exclude,
					'posts_per_page'      => $count - count( $posts ),
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
				)
			);
			$posts = array_merge( $posts, $query->posts );
		}
	}
	return array_slice( $posts, 0, $count );
}

/**
 * Markup di un box "// Consigliati": miniatura a sinistra, etichetta, titolo e "Leggi qui →".
 *
 * @param WP_Post $post Articolo proposto.
 * @return string
 */
function ntn_journal_suggest_box( $post ) {
	$title = get_the_title( $post );
	$thumb = '';
	if ( has_post_thumbnail( $post ) ) {
		$thumb = get_the_post_thumbnail(
			$post,
			'ntn-grid-thumb',
			array(
				'class'   => 'w-full h-full object-cover',
				'alt'     => '',
				'loading' => 'lazy',
			)
		);
	} elseif ( function_exists( 'ntn_journal_default_cover_url' ) ) {
		$thumb = '<img src="' . esc_url( ntn_journal_default_cover_url() ) . '" width="400" height="225" class="w-full h-full object-cover" alt="" loading="lazy" decoding="async" />';
	}

	$out  = '<aside class="ntn-suggest not-prose" aria-label="' . esc_attr__( 'Lettura consigliata', 'ntn-journal' ) . '">';
	$out .= '<a class="ntn-suggest-in" href="' . esc_url( get_permalink( $post ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Leggi l\'articolo: %s', 'ntn-journal' ), $title ) ) . '">';
	$out .= '<span class="ntn-suggest-img" aria-hidden="true">' . $thumb . '</span>';
	$out .= '<span class="ntn-suggest-txt">';
	$out .= '<span class="ntn-suggest-lbl">// Consigliati</span>';
	$out .= '<span class="ntn-suggest-t">' . esc_html( $title ) . '</span>';
	$out .= '<span class="ntn-suggest-go"><span class="ntn-dot" aria-hidden="true"></span>Leggi qui <span aria-hidden="true">→</span></span>';
	$out .= '</span></a></aside>';
	return $out;
}

/**
 * Inserisce i box "// Consigliati" nel testo dell'articolo.
 *
 * @param string $content Testo dell'articolo.
 * @return string
 */
function ntn_journal_inject_related_posts( $content ) {
	// Controlla se la funzione è abilitata nel Customizer
	if ( ! get_theme_mod( 'ntn_journal_enable_related_posts', true ) ) {
		return $content;
	}

	if ( ! is_string( $content ) || ! is_single() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$total     = ntn_journal_count_words( $content );
	$positions = ntn_journal_suggest_positions( $total );
	if ( empty( $positions ) ) {
		return $content;
	}

	$points = ntn_journal_suggest_break_points( $content );
	if ( empty( $points ) ) {
		return $content;
	}

	// Per ogni posizione, il punto sicuro più vicino (mai l'ultimo paragrafo, mai due box nello stesso punto)
	$chosen = array();
	foreach ( $positions as $fraction ) {
		$target = $fraction * $total;
		$best   = null;
		foreach ( $points as $i => $point ) {
			if ( isset( $chosen[ $i ] ) || $point['words'] >= $total ) {
				continue;
			}
			if ( null === $best || abs( $point['words'] - $target ) < abs( $points[ $best ]['words'] - $target ) ) {
				$best = $i;
			}
		}
		if ( null !== $best ) {
			$chosen[ $best ] = true;
		}
	}
	if ( empty( $chosen ) ) {
		return $content;
	}
	ksort( $chosen );

	$posts = ntn_journal_suggest_posts( get_the_ID(), count( $chosen ) );
	if ( empty( $posts ) ) {
		return $content;
	}

	// Salva gli ID dei post consigliati per evitare duplicati in "Leggi anche"
	global $ntn_consigliati_ids;
	$ntn_consigliati_ids = wp_list_pluck( $posts, 'ID' );

	// Abbina i box ai punti nell'ordine del testo, poi inserisce dal fondo per non spostare gli altri punti
	$inserts = array();
	foreach ( array_keys( $chosen ) as $n => $i ) {
		if ( isset( $posts[ $n ] ) ) {
			$inserts[ $points[ $i ]['offset'] ] = ntn_journal_suggest_box( $posts[ $n ] );
		}
	}
	krsort( $inserts );
	foreach ( $inserts as $offset => $html ) {
		$content = substr( $content, 0, $offset ) . $html . substr( $content, $offset );
	}

	return $content;
}
add_filter( 'the_content', 'ntn_journal_inject_related_posts' );

/**
 * ==============================================================================
 * "// LEGGI ANCHE" (FINE ARTICOLO, v3.16.0: tre celle come nel mockup)
 * ==============================================================================
 */
function ntn_journal_bottom_related_posts() {
	if ( ! get_theme_mod( 'ntn_journal_enable_bottom_related_posts', true ) ) {
		return;
	}

	if ( ! is_single() ) {
		return;
	}

	$post_id = get_the_ID();
	$tags    = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );

	global $ntn_consigliati_ids;
	$exclude_ids = array( $post_id );
	if ( ! empty( $ntn_consigliati_ids ) && is_array( $ntn_consigliati_ids ) ) {
		$exclude_ids = array_merge( $exclude_ids, $ntn_consigliati_ids );
	}

	$related_args = array(
		'post__not_in'        => $exclude_ids,
		'posts_per_page'      => 3,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	);

	if ( ! empty( $tags ) ) {
		$related_args['tag__in'] = $tags;
	}

	$related_query = new WP_Query( $related_args );
	$final_posts   = $related_query->posts;

	// Fallback: se non abbiamo trovato 3 articoli (o nessun tag), cerchiamo nella categoria primaria
	if ( count( $final_posts ) < 3 ) {
		$primary_cat = ntn_get_primary_category( $post_id );
		if ( $primary_cat ) {
			// Escludiamo quelli già trovati in questa query e quelli dei consigliati per evitare duplicati
			foreach ( $final_posts as $p ) {
				$exclude_ids[] = $p->ID;
			}

			$fallback_query = new WP_Query(
				array(
					'category__in'        => array( $primary_cat->term_id ),
					'post__not_in'        => $exclude_ids,
					'posts_per_page'      => 3 - count( $final_posts ),
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
				)
			);

			if ( $fallback_query->have_posts() ) {
				$final_posts = array_merge( $final_posts, $fallback_query->posts );
			}
		}
	}

	if ( empty( $final_posts ) || ! function_exists( 'ntn_front_card_data' ) ) {
		return;
	}

	?>
	<section class="ntn-bottom-related" aria-labelledby="ntn-h-related">
		<div class="ntn-sec-head mb-6">
			<h2 id="ntn-h-related" class="ntn-sec-title">// Leggi anche</h2>
			<span class="ntn-sec-aside">Dalla stessa sezione</span>
		</div>
		<div class="ntn-cells ntn-cells--3">
			<?php foreach ( $final_posts as $related_post ) : ?>
				<?php $card = ntn_front_card_data( $related_post->ID ); ?>
				<a class="ntn-cell ntn-card group" href="<?php echo esc_url( $card['permalink'] ); ?>">
					<?php ntn_front_image( $card, 'aspect-[16/10]' ); ?>
					<div class="ntn-meta">
						<?php ntn_front_entity( $card ); ?>
						<span><?php echo esc_html( $card['date'] ); ?></span>
					</div>
					<h3 class="ntn-card-title"><?php echo esc_html( $card['title'] ); ?></h3>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}
