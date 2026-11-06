<?php
/**
 * NTN Journal Core - Nomi delle cache condivise
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Inizio del nome delle cache degli articoli in evidenza (anche per svuotarle tutte dal pannello).
define( 'NTN_CORE_FEATURED_CACHE_PREFIX', 'ntn_hub_featured_' );

/**
 * Nome della cache degli articoli in evidenza di un Hub del gioco (v4.13.0).
 *
 * Una sola funzione per chi salva la cache (ntn_get_game_featured_content) e per chi la cancella
 * (pannello del plugin, salvataggio degli articoli). Serve anche quando
 * inc/game-data.php non si carica: per questo è in un file a parte, caricato sempre.
 *
 * @param string $type 'post' per la scheda videogioco, 'term' per il tag del gioco.
 * @param int    $id   ID della scheda o del tag.
 * @return string
 */
function ntn_core_featured_cache_key( $type, $id ) {
	return NTN_CORE_FEATURED_CACHE_PREFIX . ( 'term' === $type ? 'term_' : 'post_' ) . (int) $id;
}
