<?php
/**
 * NTN Journal Core - API Helpers
 * 
 * Funzioni di supporto (View Models) per estrarre e formattare in modo pulito
 * i dati estratti da IGDB e memorizzati nei CPT o Tag.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restituisce un array strutturato con i dati IGDB associati a un Post o a un Term.
 * Costruisce il View Model pulito da usare nel frontend.
 * 
 * @param mixed $object L'oggetto da cui estrarre i dati (CPT videogioco o Tag, o ID)
 * @return array View Model con i dati formattati
 */
function ntn_get_game_view_model( $object ) {
	$data = array(
		'title'       => '',
		'cover'       => '',
		'release'     => '',
		'developer'   => array(),
		'publisher'   => array(),
		'platforms'   => array(),
		'genres'      => array(),
		'engine'      => array(),
		'franchise'   => array(),
		'url'         => '',
		'video_url'   => '',
		'screenshots' => array(),
	);

	if ( ! $object ) {
		return $data;
	}

	$is_term = ( $object instanceof WP_Term ) || ( is_array( $object ) && isset( $object['term_id'] ) );
	$id = 0;

	if ( $is_term ) {
		$id = is_object( $object ) ? $object->term_id : ( isset( $object['term_id'] ) ? (int) $object['term_id'] : 0 );
		if ( ! $id ) return $data;
		$get_meta = function( $key ) use ( $id ) { return (string) get_term_meta( $id, $key, true ); };
	} else {
		$id = is_object( $object ) ? $object->ID : (int) $object;
		if ( ! $id ) return $data;
		$get_meta = function( $key ) use ( $id ) { return (string) get_post_meta( $id, $key, true ); };
	}

	$data['title'] = $get_meta( '_ntn_game_title' );
	
	// Cover
	$cover = $get_meta( '_ntn_game_cover' );
	if ( $cover && function_exists( 'ntn_normalize_game_cover_url' ) ) {
		$data['cover'] = ntn_normalize_game_cover_url( $cover );
	} else {
		$data['cover'] = $cover;
	}

	// Release
	$release_raw = $get_meta( '_ntn_game_release' );
	if ( $release_raw ) {
		$timestamp = strtotime( $release_raw );
		if ( $timestamp ) {
			$data['release'] = wp_date( get_option( 'date_format' ), $timestamp );
		} else {
			$data['release'] = $release_raw;
		}
	}

	// Converti campi comma-separated in array
	$fields_to_array = array( 'developer', 'publisher', 'platforms', 'genres', 'engine', 'franchise' );
	foreach ( $fields_to_array as $field ) {
		$raw = $get_meta( '_ntn_game_' . $field );
		if ( $raw ) {
			$arr = explode( ',', $raw );
			$data[ $field ] = array_values( array_filter( array_map( 'trim', $arr ) ) );
		}
	}

	$data['url'] = $get_meta( '_ntn_game_url' );

	// Video Trailer
	$video_id = $get_meta( '_ntn_game_video' );
	if ( $video_id ) {
		$data['video_url'] = 'https://www.youtube.com/watch?v=' . esc_attr( $video_id );
	}

	// Screenshots
	$screens_raw = $get_meta( '_ntn_game_screenshots' );
	if ( $screens_raw ) {
		$screens = array_map( 'trim', explode( ',', $screens_raw ) );
		foreach ( $screens as $scr_id ) {
			if ( $scr_id ) {
				$data['screenshots'][] = 'https://images.igdb.com/igdb/image/upload/t_1080p/' . esc_attr( $scr_id ) . '.jpg';
			}
		}
	}

	return $data;
}
