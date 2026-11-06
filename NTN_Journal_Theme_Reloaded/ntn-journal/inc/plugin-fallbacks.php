<?php
/**
 * Funzioni di riserva per quando il plugin NTN Journal Core è spento.
 *
 * Dalla v4.11.0 le funzioni che leggono i dati dei giochi e degli articoli sono nel plugin
 * (inc/game-data.php). Qui restano solo versioni vuote con lo stesso nome, così le pagine si
 * aprono senza i dati dei giochi invece di dare errore. Con il plugin attivo vengono saltate.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ntn_translate_igdb_genres' ) ) {
	function ntn_translate_igdb_genres( $genres_string ) {
		return $genres_string;
	}
}

if ( ! function_exists( 'get_post_rating' ) ) {
	function get_post_rating( $post_id ) {
		return null;
	}
}

if ( ! function_exists( 'ntn_calculate_reading_time' ) ) {
	function ntn_calculate_reading_time( $post_id ) {
		return '';
	}
}

if ( ! function_exists( 'ntn_get_primary_category' ) ) {
	function ntn_get_primary_category( $post_id = null ) {
		return null;
	}
}

if ( ! function_exists( 'ntn_get_image_alt' ) ) {
	function ntn_get_image_alt( $post_id ) {
		return get_the_title( $post_id );
	}
}

if ( ! function_exists( 'ntn_get_igdb_data' ) ) {
	function ntn_get_igdb_data( $post_id, $meta_key ) {
		return '';
	}
}

if ( ! function_exists( 'ntn_get_game_hub_url' ) ) {
	function ntn_get_game_hub_url( $post_id ) {
		return '';
	}
}

if ( ! function_exists( 'ntn_get_hub_igdb_info' ) ) {
	function ntn_get_hub_igdb_info( $object = null ) {
		return array();
	}
}

if ( ! function_exists( 'ntn_get_tag_igdb_info' ) ) {
	function ntn_get_tag_igdb_info( $term_id = null ) {
		return array();
	}
}

// v4.12.0: giochi e recensioni di un articolo.
if ( ! function_exists( 'ntn_get_post_games' ) ) {
	function ntn_get_post_games( $post_id ) {
		return array();
	}
}

if ( ! function_exists( 'ntn_apply_post_meta_to_game' ) ) {
	function ntn_apply_post_meta_to_game( array $game, $post_id ) {
		return $game;
	}
}
