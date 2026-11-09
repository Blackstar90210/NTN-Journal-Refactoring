<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Ottimizzazioni delle pagine del tema (dalla 4.20.0).
 *
 * Le ottimizzazioni del sito non legate al tema (qualità JPEG, conversione dei PNG, emoji, intestazioni,
 * Gravatar, enumerazione degli autori) sono nel plugin NTN Journal Core: inc/site-optimizations.php.
 */
/**
 * PROP-02 & PROP-16: Preload Immagine in Evidenza (LCP)
 * Inietta un tag <link rel="preload"> nell'head per gli articoli, le pagine e i comunicati stampa.
 *
 * Dalla 4.16.3 il preload usa la stessa misura ("large") e lo stesso srcset della pagina: prima scaricava
 * la misura "full" e il browser poteva scaricare l'immagine due volte. Solo sui modelli che mostrano
 * davvero l'immagine in evidenza in testata (single.php, page.php, single-comunicato_stampa.php).
 */
function ntn_preload_lcp_image() {
	if ( ! is_singular( array( 'post', 'page', 'comunicato_stampa' ) ) || is_front_page() || ! has_post_thumbnail() ) {
		return;
	}
	$thumb_id = (int) get_post_thumbnail_id();
	$image    = $thumb_id ? wp_get_attachment_image_src( $thumb_id, 'large' ) : false;
	if ( empty( $image[0] ) ) {
		return;
	}
	$srcset = wp_get_attachment_image_srcset( $thumb_id, 'large' );
	$sizes  = wp_get_attachment_image_sizes( $thumb_id, 'large' );
	$extra  = '';
	if ( $srcset ) {
		$extra .= ' imagesrcset="' . esc_attr( $srcset ) . '"';
		if ( $sizes ) {
			$extra .= ' imagesizes="' . esc_attr( $sizes ) . '"';
		}
	}
	echo '<link rel="preload" as="image" href="' . esc_url( $image[0] ) . '"' . $extra . ' fetchpriority="high">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- attributi già filtrati sopra.
}
add_action( 'wp_head', 'ntn_preload_lcp_image', 1 );

/**
 * PROP-20: DNS Prefetch per Risorse Esterne
 * Pre-connette images.igdb.com e img.youtube.com per velocizzare il caricamento
 * delle risorse esterne quando richieste (thumbnail, copertine).
 */
function ntn_dns_prefetch_external_resources() {
	// Esterni
	echo '<link rel="preconnect" href="https://images.igdb.com" crossorigin>' . "\n";
	echo '<link rel="dns-prefetch" href="https://images.igdb.com">' . "\n";

	echo '<link rel="preconnect" href="https://img.youtube.com" crossorigin>' . "\n";
	echo '<link rel="dns-prefetch" href="https://img.youtube.com">' . "\n";
}
add_action( 'wp_head', 'ntn_dns_prefetch_external_resources', 2 );


/**
 * ==============================================================================
 * JAVASCRIPT DEFERRING (FCP & TBT Optimization)
 * ==============================================================================
 */
function ntn_journal_defer_scripts( $tag, $handle, $src ) {
	// Dalla 4.16.3 solo gli script del tema: prima il filtro metteva defer a tutti gli script del sito,
	// anche a quelli di altri plugin, che a volte dipendono da script inline (ordine di esecuzione).
	// Gli script con "strategy" nell'enqueue (main.js, front-page.js, dot-art.js) sono già gestiti da WordPress.
	$own_handles = apply_filters( 'ntn_journal_deferred_script_handles', array( 'ntn-lazy-video', 'ntn-live-search', 'ntn-catalog-filters', 'glightbox' ) );
	if ( is_admin() || ! in_array( $handle, (array) $own_handles, true ) ) {
		return $tag;
	}

	// Se ha già defer o async, non toccarlo
	if ( strpos( $tag, 'defer' ) !== false || strpos( $tag, 'async' ) !== false ) {
		return $tag;
	}

	// Aggiunge defer
	return str_replace( '<script ', '<script defer="defer" ', $tag );
}
add_filter( 'script_loader_tag', 'ntn_journal_defer_scripts', 10, 3 );
