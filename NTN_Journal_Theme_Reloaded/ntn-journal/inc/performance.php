<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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
 * Caricamento differito degli script (dalla 4.26.0).
 *
 * Il filtro script_loader_tag che aggiungeva "defer" agli script del tema non c'è più: ogni script dichiara
 * da sé la strategia con l'argomento 'strategy' => 'defer' di wp_enqueue_script (inc/enqueue.php,
 * inc/gallery-lightbox.php, inc/category-hub-template.php), come previsto da WordPress 6.3 in poi.
 * Con WordPress 6.0-6.2 l'argomento viene ignorato e gli script restano in fondo alla pagina, senza defer.
 */
