<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Percorso dell'immagine di copertina predefinita, inclusa localmente nel
 * tema, usata quando un articolo non ha un'immagine in evidenza.
 * Sostituisce i precedenti riferimenti hotlinkati a images.unsplash.com,
 * non ammessi dalle linee guida WordPress.org (tutte le risorse vanno
 * incluse nel pacchetto del tema).
 */
/**
 * Foto dell'autore con le iniziali a matrice di punti come riserva (v3.17.1, scelta del CEO).
 *
 * Le iniziali stanno sempre sotto; sopra c'è la foto del profilo chiesta a Gravatar senza
 * immagine di riserva ("404"). Se la foto non esiste, l'immagine si toglie da sola e restano
 * le iniziali, al posto della sagoma grigia di WordPress.
 *
 * @param int    $user_id ID dell'utente.
 * @param string $name    Nome mostrato (per le iniziali).
 * @param int    $size    Lato della foto in pixel (doppio per gli schermi ad alta densità).
 * @return string Markup da mettere dentro .ntn-author-avatar.
 */
function ntn_journal_author_avatar( $user_id, $name, $size = 112 ) {
	$initials = '';
	foreach ( array_slice( (array) preg_split( '/\s+/', trim( (string) $name ) ), 0, 2 ) as $word ) {
		$initials .= function_exists( 'mb_substr' ) ? mb_substr( $word, 0, 1 ) : substr( $word, 0, 1 );
	}
	$initials = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $initials ) : strtoupper( $initials );
	$out      = '<span>' . esc_html( $initials ) . '</span>';

	$url = ( $user_id && get_option( 'show_avatars', 1 ) ) ? get_avatar_url( (int) $user_id, array( 'size' => (int) $size, 'default' => '404' ) ) : '';
	if ( $url ) {
		$out .= '<img src="' . esc_url( $url ) . '" alt="" width="' . (int) $size . '" height="' . (int) $size . '" loading="lazy" decoding="async" onerror="this.remove()">';
	}
	return $out;
}

function ntn_journal_default_cover_url() {
	return get_template_directory_uri() . '/assets/images/default-cover.png';
}

/**
 * Stampa la riga con il link alla recensione di un gioco, o l'avviso che non è stato recensito.
 *
 * @param array    $game        Dati del gioco nel formato di ntn_get_post_games().
 * @param string   $class       Classe della riga.
 * @param int|null $panel_index Nelle schede a linguette, il pannello del gioco: dal secondo in poi la riga parte nascosta.
 */
function ntn_journal_game_review_row( array $game, $class, $panel_index = null ) {
	$attributes = '';
	if ( null !== $panel_index ) {
		$attributes = ' data-i="' . esc_attr( (string) $panel_index ) . '"' . ( $panel_index > 0 ? ' hidden' : '' );
	}

	if ( ! $game['review'] ) {
		?>
		<div class="<?php echo esc_attr( $class ); ?> is-none"<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- già sanificato qui sopra. ?>>
			<span>// Non recensito</span>
			<span class="ntn-leds" aria-hidden="true"><?php echo str_repeat( '<i class="ntn-led is-off"></i>', 5 ); ?></span>
		</div>
		<?php
		return;
	}

	$review_rating = (int) $game['review_rating'];
	?>
	<a href="<?php echo esc_url( get_permalink( $game['review'] ) ); ?>" class="<?php echo esc_attr( $class ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Leggi la recensione di %s', $game['title'] ) ); ?>"<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- già sanificato qui sopra. ?>>
		<span>Leggi la recensione →</span>
		<?php if ( $review_rating > 0 ) : ?>
			<span class="ntn-leds" aria-hidden="true">
				<?php
				for ( $i = 1; $i <= 5; $i++ ) {
					$led_class = 'ntn-led';
					if ( $i <= $review_rating ) {
						$led_class .= ' is-on';
					}
					if ( $i === $review_rating ) {
						$led_class .= ' is-last';
					}
					echo '<i class="' . esc_attr( $led_class ) . '"></i>';
				}
				?>
			</span>
		<?php endif; ?>
	</a>
	<?php
}

/**
 * Trasforma una stringa di sviluppatori o publisher in link se esiste il tag corrispondente.
 *
 * @param mixed $companies_string Stringa o array di sviluppatori/publisher.
 * @return string HTML con i link o testo semplice se il tag non esiste.
 */
function ntn_link_companies_to_tags( $companies_string ) {
	if ( empty( $companies_string ) ) {
		return '';
	}

	if ( is_array( $companies_string ) ) {
		$companies = $companies_string;
	} elseif ( is_object( $companies_string ) && ! method_exists( $companies_string, '__toString' ) ) {
		return '';
	} else {
		$companies = explode( ',', (string) $companies_string );
	}

	$output_array = array();

	foreach ( $companies as $company ) {
		if ( ! is_scalar( $company ) && ( ! is_object( $company ) || ! method_exists( $company, '__toString' ) ) ) {
			continue;
		}
		$company = trim( (string) $company );
		if ( empty( $company ) ) continue;

		// Cerca il tag per nome o per slug
		$term = get_term_by( 'name', $company, 'post_tag' );
		if ( ! $term || is_wp_error( $term ) ) {
			$term = get_term_by( 'slug', sanitize_title( $company ), 'post_tag' );
		}

		if ( $term && ! is_wp_error( $term ) && $term instanceof WP_Term ) {
			// Se il tag esiste, crea il link
			$link = get_term_link( $term );
			$formatted_name = '// ' . strtoupper( $company );

			if ( ! empty( $link ) && ! is_wp_error( $link ) && is_string( $link ) ) {
				$output_array[] = '<a href="' . esc_url( $link ) . '" class="inline-flex items-center gap-1.5 hover:text-red-500 dark:hover:text-red-400 transition-colors group"><span>' . esc_html( $formatted_name ) . '</span><span class="w-1.5 h-1.5 bg-red-600 rounded-full animate-pulse shrink-0"></span></a>';
			} else {
				$output_array[] = esc_html( $company );
			}
		} else {
			// Nessun tag trovato, restituisci testo semplice
			$output_array[] = esc_html( $company );
		}
	}

	return implode( ', ', $output_array );
}

/**
 * CSS Class per i badge del motore grafico.
 */
define( 'NTN_ENGINE_BADGE_CSS', 'font-mono text-[9px] text-zinc-300 border border-zinc-700 px-2 py-1 leading-none inline-flex items-center justify-center uppercase hover:border-red-500 hover:text-white transition-colors' );

/**
 * Trasforma una stringa di motori grafici (es. "Unity, Unreal Engine 5") in badge cliccabili collegati all'archivio della tassonomia game_engine.
 *
 * @param mixed $engines_string Stringa o array di motori grafici.
 * @param string $badge_class Classe CSS opzionale per il badge.
 * @return string HTML dei badge linkati.
 */
function ntn_link_engines_to_terms( $engines_string, $badge_class = NTN_ENGINE_BADGE_CSS ) {
	if ( empty( $engines_string ) ) {
		return '';
	}

	if ( is_array( $engines_string ) ) {
		$engines = $engines_string;
	} elseif ( is_object( $engines_string ) && ! method_exists( $engines_string, '__toString' ) ) {
		return '';
	} else {
		$engines = explode( ',', (string) $engines_string );
	}

	$output_array = array();
	$enable_links = get_option( 'ntn_journal_enable_game_engine_links', 'yes' );

	foreach ( $engines as $eng ) {
		if ( ! is_scalar( $eng ) && ( ! is_object( $eng ) || ! method_exists( $eng, '__toString' ) ) ) {
			continue;
		}
		$eng_name = trim( (string) $eng );
		if ( empty( $eng_name ) ) continue;

		if ( 'yes' === $enable_links ) {
			// Cerca il termine nella tassonomia game_engine
			$term = get_term_by( 'name', $eng_name, 'game_engine' );
			if ( ! $term || is_wp_error( $term ) ) {
				$term = get_term_by( 'slug', sanitize_title( $eng_name ), 'game_engine' );
			}

			if ( $term && ! is_wp_error( $term ) && $term instanceof WP_Term ) {
				$link = get_term_link( $term );
				if ( ! empty( $link ) && ! is_wp_error( $link ) && is_string( $link ) ) {
					$output_array[] = '<a href="' . esc_url( $link ) . '" class="' . esc_attr( $badge_class ) . '">' . esc_html( $eng_name ) . '</a>';
					continue;
				}
			}
		}

		$span_class     = trim( preg_replace( '/\bhover:[^\s]+/', '', $badge_class ) );
		$output_array[] = '<span class="' . esc_attr( $span_class ) . '">' . esc_html( $eng_name ) . '</span>';
	}

	return implode( ' ', $output_array );
}

/**
 * Data sotto la copertina nel catalogo (v3.19.1), secondo la precisione salvata dal plugin:
 * giorno "23.10.2026", mese "Ott 2027", trimestre "Q3 2027", altrimenti solo l'anno, come prima.
 * I giochi non ancora aggiornati dallo Scanner IGDB non hanno la precisione e mostrano l'anno.
 *
 * @param int   $post_id ID del videogioco.
 * @param mixed $release Data salvata (Y-m-d, anno, timestamp in secondi o millisecondi).
 * @return string Data da mostrare, oppure stringa vuota.
 */
function ntn_journal_game_card_date( $post_id, $release ): string {
	$rel_clean = is_scalar( $release ) ? trim( (string) $release ) : ( is_array( $release ) ? trim( (string) reset( $release ) ) : '' );
	if ( '' === $rel_clean ) {
		return '';
	}

	$precision = function_exists( 'ntn_get_igdb_data' ) ? ntn_get_igdb_data( $post_id, '_ntn_game_release_precision' ) : get_post_meta( $post_id, '_ntn_game_release_precision', true );
	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $rel_clean, $parts ) ) {
		$month = (int) $parts[2];
		if ( 'day' === $precision ) {
			return $parts[3] . '.' . $parts[2] . '.' . $parts[1];
		}
		if ( 'month' === $precision && $month >= 1 && $month <= 12 ) {
			$months = array( 'Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic' );
			return $months[ $month - 1 ] . ' ' . $parts[1];
		}
		if ( 'quarter' === $precision && $month >= 1 && $month <= 12 ) {
			// IGDB può segnare il trimestre al primo o all'ultimo giorno: il mese basta a riconoscerlo
			return 'Q' . (int) ceil( $month / 3 ) . ' ' . $parts[1];
		}
	}

	if ( is_numeric( $rel_clean ) ) {
		if ( strlen( $rel_clean ) === 4 ) {
			return $rel_clean;
		}
		return date( 'Y', strlen( $rel_clean ) === 13 ? intval( $rel_clean / 1000 ) : intval( $rel_clean ) );
	}
	$rel_time = strtotime( $rel_clean );
	return $rel_time ? date( 'Y', $rel_time ) : $rel_clean;
}

/**
 * Data di uscita nelle schede del gioco (v3.21.0): solo le parti note.
 * Giorno "23/10/2026" (formato a scelta), mese "Ottobre 2027", trimestre "Q3 2027", anno "2028".
 * Senza precisione salvata (gioco non ancora aggiornato dallo Scanner IGDB) un 31 dicembre vale "solo l'anno",
 * perché è il segnaposto di IGDB; le altre date restano complete, come prima.
 *
 * @param mixed  $release    Data salvata (Y-m-d, anno, timestamp in secondi o millisecondi, testo).
 * @param mixed  $precision  Precisione salvata dal plugin (day, month, quarter, year, tbd), anche vuota.
 * @param string $day_format Formato con il giorno noto.
 * @return string
 */
function ntn_journal_release_label( $release, $precision = '', string $day_format = 'd/m/Y' ): string {
	$rel = is_scalar( $release ) ? trim( (string) $release ) : ( is_array( $release ) ? trim( (string) reset( $release ) ) : '' );
	if ( '' === $rel ) {
		return '';
	}
	if ( is_numeric( $rel ) ) {
		if ( 4 === strlen( $rel ) ) {
			return $rel;
		}
		$time = 13 === strlen( $rel ) ? intval( $rel / 1000 ) : intval( $rel );
	} else {
		$date = date_create( $rel );
		if ( ! $date ) {
			return $rel;
		}
		$time = (int) $date->format( 'U' );
	}

	$precision = is_string( $precision ) ? $precision : '';
	if ( ! in_array( $precision, array( 'day', 'month', 'quarter', 'year' ), true ) ) {
		$precision = ( 'tbd' === $precision || '12-31' === date( 'm-d', $time ) ) ? 'year' : 'day';
	}
	$month = (int) date( 'n', $time );
	if ( 'day' === $precision ) {
		return date( $day_format, $time );
	}
	if ( 'month' === $precision ) {
		$months = array( 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre' );
		return $months[ $month - 1 ] . ' ' . date( 'Y', $time );
	}
	if ( 'quarter' === $precision ) {
		return 'Q' . (int) ceil( $month / 3 ) . ' ' . date( 'Y', $time );
	}
	return date( 'Y', $time );
}
