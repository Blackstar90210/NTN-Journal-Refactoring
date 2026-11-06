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
		'release_raw' => '', // v4.14.0: data come salvata (AAAA-MM-GG), per datePublished
		'release_precision' => '',
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
	$data['release_raw']       = $release_raw;
	$data['release_precision'] = $get_meta( '_ntn_game_release_precision' );
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
		if ( $raw && 'genres' === $field && function_exists( 'ntn_translate_igdb_genres' ) ) {
			$raw = ntn_translate_igdb_genres( $raw ); // v4.14.0: in italiano, come nella pagina
		}
		if ( $raw ) {
			$arr = explode( ',', $raw );
			$data[ $field ] = array_values( array_filter( array_map( 'trim', $arr ) ) );
		}
	}
	$data['platforms'] = ntn_core_schema_platform_names( $data['platforms'] );

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

	// v4.14.0: i campi vuoti si completano con i dati del gioco che mostra la pagina (tag del gioco,
	// scheda videogioco), così i dati strutturati coincidono con quello che vede il lettore.
	// Solo i campi della scheda: trailer e screenshot restano quelli scritti sull'oggetto.
	$source = ntn_core_view_model_source( $is_term ? get_term( $id ) : get_post( $id ) );
	if ( $source && function_exists( 'ntn_get_hub_igdb_info' ) ) {
		$data = ntn_core_fill_view_model( $data, ntn_get_hub_igdb_info( $source ) );
	}

	return $data;
}

/**
 * Trova il gioco da cui la pagina prende i dati (v4.14.0).
 *
 * Tag del gioco e scheda videogioco sono già il gioco: ntn_get_hub_igdb_info() li collega fra loro.
 * Per un articolo vale il gioco della scheda nella colonna laterale: quello di cui l'articolo è la
 * recensione, oppure l'unico gioco dell'articolo. Con più giochi e nessuna recensione, nessuno.
 *
 * @param WP_Post|WP_Term|null $object Oggetto di partenza.
 * @return WP_Post|WP_Term|null
 */
function ntn_core_view_model_source( $object ) {
	if ( $object instanceof WP_Term ) {
		return $object;
	}
	if ( ! ( $object instanceof WP_Post ) ) {
		return null;
	}
	if ( 'videogioco' === $object->post_type ) {
		return $object;
	}
	if ( ! function_exists( 'ntn_get_post_games' ) ) {
		return null;
	}
	$games = ntn_get_post_games( $object->ID );
	foreach ( $games as $game ) {
		if ( ! empty( $game['is_current_review'] ) ) {
			return ( isset( $game['term'] ) && $game['term'] instanceof WP_Term ) ? $game['term'] : null;
		}
	}
	if ( 1 === count( $games ) && isset( $games[0]['term'] ) && $games[0]['term'] instanceof WP_Term ) {
		return $games[0]['term'];
	}
	return null;
}

/**
 * Riempie i campi vuoti del view model con i dati di ntn_get_hub_igdb_info() (v4.14.0).
 * I campi già pieni, letti dall'oggetto stesso, non cambiano. Si completano solo i campi che la scheda
 * del gioco mostra al lettore (titolo, copertina, uscita, sviluppatore, editore, piattaforme, generi,
 * motore): trailer e screenshot no, perché nella pagina di un articolo non si vedono.
 *
 * @param array $data View model di ntn_get_game_view_model().
 * @param array $info Dati di ntn_get_hub_igdb_info().
 * @return array
 */
function ntn_core_fill_view_model( array $data, $info ) {
	if ( empty( $info ) || ! is_array( $info ) ) {
		return $data;
	}
	$to_list = function ( $value ) {
		return array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ) ) );
	};

	foreach ( array( 'title', 'cover', 'url' ) as $field ) {
		if ( '' === $data[ $field ] && ! empty( $info[ $field ] ) ) {
			$data[ $field ] = (string) $info[ $field ];
		}
	}
	if ( '' === $data['release'] && ! empty( $info['release'] ) ) {
		$timestamp                 = strtotime( $info['release'] );
		$data['release']           = $timestamp ? wp_date( get_option( 'date_format' ), $timestamp ) : $info['release'];
		$data['release_raw']       = (string) $info['release'];
		$data['release_precision'] = isset( $info['release_precision'] ) ? (string) $info['release_precision'] : '';
	}
	foreach ( array( 'developer', 'publisher', 'platforms', 'genres', 'engine' ) as $field ) {
		if ( empty( $data[ $field ] ) && ! empty( $info[ $field ] ) ) {
			$data[ $field ] = $to_list( $info[ $field ] );
		}
	}
	$data['platforms'] = ntn_core_schema_platform_names( $data['platforms'] );
	return $data;
}

/**
 * Piattaforme di un articolo, prese solo dalle sue categorie (v4.14.3).
 *
 * Una categoria è una piattaforma se è marcata "È una piattaforma", oppure se non ha sottocategorie e sta dentro
 * (o è) una Hub di tipo "Piattaforme" (Console, Mobile, PC Gaming...). Marchi come Nintendo, Sony e Microsoft, e
 * categorie come Indie, Guide, Hardware, Notizie o Recensioni, non sono piattaforme. Vince il sito: le piattaforme
 * IGDB già salvate (se ci sono) si aggiungono, senza doppioni anche se scritte con le maiuscole diverse.
 *
 * @param int   $post_id        Articolo.
 * @param array $igdb_platforms Piattaforme IGDB già salvate: completano l'elenco, non lo sostituiscono.
 * @return array Nomi delle piattaforme, senza doppioni. Vuoto se l'articolo non ne ha.
 */
function ntn_core_post_platform_names( $post_id, array $igdb_platforms = array() ) {
	$categories = get_the_category( (int) $post_id );
	if ( empty( $categories ) || is_wp_error( $categories ) ) {
		$categories = array();
	}
	$is_platform_hub = static function ( $term_id ) {
		return function_exists( 'ntn_core_category_hub_meta' ) && ntn_core_category_hub_meta( (int) $term_id, 'enabled' ) && 'console' === ntn_core_category_hub_meta( (int) $term_id, 'type' );
	};
	$names = array();
	foreach ( $categories as $cat ) {
		$marked = function_exists( 'ntn_core_category_is_platform' ) && ntn_core_category_is_platform( $cat->term_id );
		$children = $marked ? array() : get_term_children( (int) $cat->term_id, 'category' );
		if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
			continue; // un marchio o una Hub con sottocategorie: lo sono le sottocategorie
		}
		$inside = $marked || $is_platform_hub( $cat->term_id );
		if ( ! $inside ) {
			foreach ( get_ancestors( (int) $cat->term_id, 'category', 'taxonomy' ) as $ancestor_id ) {
				if ( $is_platform_hub( $ancestor_id ) ) {
					$inside = true;
					break;
				}
			}
		}
		if ( ! $inside ) {
			continue;
		}
		$name = trim( wp_strip_all_tags( wp_specialchars_decode( $cat->name, ENT_QUOTES ) ) );
		if ( 'pc gaming' === strtolower( $name ) ) {
			$name = 'PC';
		}
		if ( '' !== $name ) {
			$names[] = $name;
		}
	}
	foreach ( $igdb_platforms as $igdb_platform ) {
		$igdb_platform = trim( wp_strip_all_tags( (string) $igdb_platform ) );
		if ( '' !== $igdb_platform ) {
			$names[] = $igdb_platform;
		}
	}
	// Senza doppioni anche con maiuscole diverse ("Xbox Series X|S" e "XBOX Series X|S"): resta la prima scritta
	$out  = array();
	$seen = array();
	foreach ( ntn_core_schema_platform_names( $names ) as $name ) {
		$key = strtolower( $name );
		if ( ! isset( $seen[ $key ] ) ) {
			$seen[ $key ] = true;
			$out[]        = $name;
		}
	}
	return $out;
}

/**
 * Nomi delle piattaforme IGDB come le categorie del sito (v4.14.0).
 *
 * I dati strutturati uniscono le piattaforme del gioco a quelle prese dalle categorie, dove il PC si
 * chiama "PC": senza questo passaggio comparirebbero sia "PC" sia "PC (Microsoft Windows)".
 *
 * @param array $platforms Piattaforme.
 * @return array Piattaforme senza doppioni.
 */
function ntn_core_schema_platform_names( array $platforms ) {
	$names = array(
		'pc (microsoft windows)' => 'PC',
		'pc (windows)'           => 'PC',
		'windows'                => 'PC',
		'microsoft windows'      => 'PC',
	);
	$out = array();
	foreach ( $platforms as $platform ) {
		$key   = strtolower( trim( (string) $platform ) );
		$out[] = isset( $names[ $key ] ) ? $names[ $key ] : $platform;
	}
	return array_values( array_unique( $out ) );
}
