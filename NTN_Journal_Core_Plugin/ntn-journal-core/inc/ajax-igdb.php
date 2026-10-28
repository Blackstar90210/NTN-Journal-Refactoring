<?php
/**
 * Ajax endpoints for IGDB API communication
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper to get IGDB Token
 */
function ntn_get_igdb_token() {
	$client_id     = get_option( 'ntn_igdb_client_id', '' );
	$client_secret = get_option( 'ntn_igdb_client_secret', '' );

	if ( empty( $client_id ) || empty( $client_secret ) ) {
		return false;
	}

	$cached_token = get_transient( 'ntn_igdb_access_token' );
	if ( $cached_token ) {
		return $cached_token;
	}

	$response = wp_remote_post( 'https://id.twitch.tv/oauth2/token', array(
		'body' => array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'grant_type'    => 'client_credentials',
		),
		'timeout' => 10,
	) );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		// v3.17.1: lo stato della chiave nella scheda "Servizi API"
		if ( function_exists( 'ntn_core_api_status_set' ) ) {
			ntn_core_api_status_set( 'igdb', false, ntn_core_api_error_reason( $response ) );
		}
		return false;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( isset( $data['access_token'] ) ) {
		$expiration = isset( $data['expires_in'] ) ? intval( $data['expires_in'] ) - 60 : 3600;
		set_transient( 'ntn_igdb_access_token', $data['access_token'], $expiration );
		if ( function_exists( 'ntn_core_api_status_set' ) ) {
			ntn_core_api_status_set( 'igdb', true );
		}
		return $data['access_token'];
	}

	if ( function_exists( 'ntn_core_api_status_set' ) ) {
		ntn_core_api_status_set( 'igdb', false, 'Twitch non ha restituito l\'accesso: controlla Client ID e Client Secret.' );
	}
	return false;
}

/**
 * Quanto è precisa la data di uscita (v3.19.1). IGDB mette al 31 dicembre i giochi di cui conosce solo
 * l'anno, quindi la data da sola non basta: si legge la voce di "release_dates" con la stessa data di
 * "first_release_date" e il suo testo "human" ("Oct 23, 2026", "Oct 2026", "Q4 2026", "2026", "TBD").
 *
 * @param array $game Gioco restituito da IGDB (con release_dates.date e release_dates.human).
 * @return string day|month|quarter|year|tbd, oppure '' se non si sa.
 */
function ntn_igdb_release_precision( $game ) {
	if ( ! is_array( $game ) || empty( $game['first_release_date'] ) || empty( $game['release_dates'] ) || ! is_array( $game['release_dates'] ) ) {
		return '';
	}
	$rank  = array( 'tbd' => 0, 'year' => 1, 'quarter' => 2, 'month' => 3, 'day' => 4 );
	$first = (int) $game['first_release_date'];
	$best  = '';
	foreach ( $game['release_dates'] as $entry ) {
		if ( ! is_array( $entry ) || (int) ( $entry['date'] ?? 0 ) !== $first ) {
			continue;
		}
		$found = ntn_igdb_precision_from_human( (string) ( $entry['human'] ?? '' ) );
		// Più piattaforme con la stessa data: vale la meno precisa. Se una dice "2027" e un'altra
		// "Dec 31, 2027", quel 31 dicembre è il segnaposto di IGDB, non un giorno vero.
		if ( '' !== $found && ( '' === $best || $rank[ $found ] < $rank[ $best ] ) ) {
			$best = $found;
		}
	}
	return $best;
}

/**
 * Traduce il testo "human" di IGDB nella precisione della data.
 *
 * @param string $human Per esempio "Oct 23, 2026", "Oct 2026", "Q4 2026", "2026", "TBD".
 * @return string day|month|quarter|year|tbd, oppure ''.
 */
function ntn_igdb_precision_from_human( $human ) {
	$human = trim( (string) $human );
	if ( preg_match( '/^[A-Za-z]{3,9}\.? \d{1,2}, \d{4}$/', $human ) ) {
		return 'day';
	}
	if ( preg_match( '/^[A-Za-z]{3,9}\.? \d{4}$/', $human ) ) {
		return 'month';
	}
	if ( preg_match( '/^Q[1-4] \d{4}$/i', $human ) ) {
		return 'quarter';
	}
	if ( preg_match( '/^\d{4}$/', $human ) ) {
		return 'year';
	}
	if ( false !== stripos( $human, 'TBD' ) ) {
		return 'tbd';
	}
	return '';
}

/**
 * Helper per estrarre e formattare i dati di un gioco da IGDB.
 */
function ntn_igdb_parse_game_data( $game ) {
	$developers = array();
	$publishers = array();
	if ( isset( $game['involved_companies'] ) && is_array( $game['involved_companies'] ) ) {
		foreach ( $game['involved_companies'] as $company ) {
			if ( ! empty( $company['developer'] ) && ! empty( $company['company']['name'] ) ) {
				$developers[] = sanitize_text_field( $company['company']['name'] );
			}
			if ( ! empty( $company['publisher'] ) && ! empty( $company['company']['name'] ) ) {
				$publishers[] = sanitize_text_field( $company['company']['name'] );
			}
		}
	}

	$platforms = array();
	if ( isset( $game['platforms'] ) && is_array( $game['platforms'] ) ) {
		foreach ( $game['platforms'] as $platform ) {
			$platforms[] = sanitize_text_field( $platform['name'] );
		}
	}

	$genres = array();
	if ( isset( $game['genres'] ) && is_array( $game['genres'] ) ) {
		foreach ( $game['genres'] as $genre ) {
			$genres[] = sanitize_text_field( $genre['name'] );
		}
	}

	$engines = array();
	if ( isset( $game['game_engines'] ) && is_array( $game['game_engines'] ) ) {
		foreach ( $game['game_engines'] as $engine ) {
			if ( ! empty( $engine['name'] ) ) {
				$engines[] = sanitize_text_field( $engine['name'] );
			}
		}
	}

	$cover_url = '';
	if ( isset( $game['cover']['url'] ) ) {
		$cover_url = str_replace( 't_thumb', 't_1080p', esc_url_raw( $game['cover']['url'] ) );
		if ( strpos( $cover_url, 'https:' ) === false ) {
			$cover_url = 'https:' . $cover_url;
		}
	}

	$release = isset( $game['first_release_date'] ) ? date( 'Y-m-d', $game['first_release_date'] ) : '';

	$video_id = '';
	if ( isset( $game['videos'] ) && is_array( $game['videos'] ) && ! empty( $game['videos'] ) ) {
		$video_id = sanitize_text_field( $game['videos'][0]['video_id'] );
	}

	$screenshots = array();
	if ( isset( $game['screenshots'] ) && is_array( $game['screenshots'] ) ) {
		foreach ( array_slice( $game['screenshots'], 0, 4 ) as $scr ) {
			if ( ! empty( $scr['image_id'] ) ) {
				$screenshots[] = sanitize_text_field( $scr['image_id'] );
			}
		}
	}

	// Raccoglie franchise da 'franchises' e da 'collections' (fallback molto comune su IGDB).
	$franchises = array();
	if ( isset( $game['franchises'] ) && is_array( $game['franchises'] ) ) {
		foreach ( $game['franchises'] as $franchise ) {
			if ( ! empty( $franchise['name'] ) ) {
				$franchises[] = sanitize_text_field( $franchise['name'] );
			}
		}
	}
	// 'collections' su IGDB corrisponde a serie/franchise per molti titoli.
	if ( isset( $game['collections'] ) && is_array( $game['collections'] ) ) {
		foreach ( $game['collections'] as $collection ) {
			if ( ! empty( $collection['name'] ) ) {
				$franchises[] = sanitize_text_field( $collection['name'] );
			}
		}
	}
	$franchises = array_unique( array_filter( $franchises ) );

	return array(
		'title'       => isset( $game['name'] ) ? sanitize_text_field( $game['name'] ) : '',
		'cover'       => $cover_url,
		'release'     => $release,
		'release_precision' => ntn_igdb_release_precision( $game ),
		'developer'   => implode( ', ', $developers ),
		'publisher'   => implode( ', ', $publishers ),
		'platforms'   => implode( ', ', $platforms ),
		'genres'      => implode( ', ', $genres ),
		'engine'      => implode( ', ', $engines ),
		'franchise'   => implode( ', ', $franchises ),
		'url'         => isset( $game['url'] ) ? esc_url_raw( $game['url'] ) : '',
		'video'       => $video_id,
		'screenshots' => implode( ',', $screenshots ),
	);
}

/**
 * Endpoint for searching games on IGDB
 */
function ntn_journal_ajax_search_igdb() {
	check_ajax_referer( 'ntn_igdb_nonce', '_ajax_nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Non autorizzato' ) );
	}

	$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
	if ( empty( $query ) ) {
		wp_send_json_error( array( 'message' => 'Query vuota' ) );
	}

	$client_id = get_option( 'ntn_igdb_client_id', '' );
	if ( empty( $client_id ) ) {
		wp_send_json_error( array( 'message' => 'Client ID IGDB non configurato' ) );
	}

	$token = ntn_get_igdb_token();
	if ( ! $token ) {
		wp_send_json_error( array( 'message' => 'Impossibile ottenere il token IGDB. Controlla le credenziali.' ) );
	}

	if ( is_numeric( $query ) ) {
		$apicalypse = 'fields id, name, cover.url, first_release_date; where id = ' . intval( $query ) . '; limit 1;';
	} else {
		$clean_query = preg_replace( '/[\r\n\t]+/', ' ', $query );
		$safe_query  = addcslashes( sanitize_text_field( $clean_query ), '"\\' );
		$apicalypse  = 'fields id, name, cover.url, first_release_date; search "' . $safe_query . '"; limit 50;';
	}

	$transient_key = 'ntn_igdb_search_' . md5( $apicalypse );
	$cached_results = get_transient( $transient_key );
	if ( false !== $cached_results ) {
		wp_send_json_success( $cached_results );
	}

	$response = wp_remote_post( 'https://api.igdb.com/v4/games', array(
		'headers' => array(
			'Client-ID'     => $client_id,
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'text/plain',
		),
		'body'    => $apicalypse,
		'timeout' => 15,
	) );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		$error_code = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
		if ( $error_code === 401 ) {
			delete_transient( 'ntn_igdb_access_token' );
			wp_send_json_error( array( 'message' => 'Token IGDB scaduto (401). È stato invalidato, riprova la ricerca.' ) );
		}
		$error_msg = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP Error: ' . $error_code;
		wp_send_json_error( array( 'message' => $error_msg ) );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $data ) ) {
		wp_send_json_error( array( 'message' => 'Risposta API non valida' ) );
	}

	// Format results for JS
	$results = array();
	foreach ( $data as $game ) {
		// Convert UNIX timestamp to YYYY-MM-DD
		$release = isset( $game['first_release_date'] ) ? date( 'Y-m-d', $game['first_release_date'] ) : '';
		$cover_url = '';
		if ( isset( $game['cover']['url'] ) ) {
			// Get high-res cover instead of thumb
			$cover_url = str_replace( 't_thumb', 't_1080p', esc_url_raw( $game['cover']['url'] ) );
			if ( strpos( $cover_url, 'https:' ) === false ) {
				$cover_url = 'https:' . $cover_url;
			}
		}

		$results[] = array(
			'id'               => $game['id'],
			'name'             => sanitize_text_field( $game['name'] ),
			'background_image' => $cover_url,
			'released'         => $release
		);
	}

	set_transient( $transient_key, $results, DAY_IN_SECONDS );
	wp_send_json_success( $results );
}
add_action( 'wp_ajax_ntn_search_igdb', 'ntn_journal_ajax_search_igdb' );

/**
 * Shared helper to fetch and parse game details from IGDB
 */
function ntn_get_igdb_game_details( $game_id ) {
	$transient_key = 'ntn_igdb_game2_' . $game_id; // v3.19.1: "2" perché ora c'è anche la precisione della data
	$cached = get_transient( $transient_key );
	if ( false !== $cached ) {
		return $cached;
	}
	
	$client_id = get_option( 'ntn_igdb_client_id', '' );
	$token = ntn_get_igdb_token();

	if ( empty( $client_id ) || ! $token ) {
		return new WP_Error( 'igdb_error', 'Credenziali IGDB mancanti o errate' );
	}

	$apicalypse = 'fields id, name, url, cover.url, first_release_date, release_dates.date, release_dates.human, platforms.name, involved_companies.company.name, involved_companies.developer, involved_companies.publisher, genres.name, game_engines.name, franchises.name, collections.name, videos.video_id, screenshots.image_id; where id = ' . intval( $game_id ) . '; limit 1;';

	$response = wp_remote_post( 'https://api.igdb.com/v4/games', array(
		'headers' => array(
			'Client-ID'     => $client_id,
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'text/plain',
		),
		'body'    => $apicalypse,
		'timeout' => 15,
	) );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		$error_code = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
		if ( $error_code === 401 ) {
			delete_transient( 'ntn_igdb_access_token' );
			return new WP_Error( 'igdb_401', 'Token IGDB scaduto (401). È stato invalidato, riprova.' );
		}
		$error_msg = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP Error: ' . $error_code;
		return new WP_Error( 'igdb_api_error', $error_msg );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || empty( $data ) ) {
		return new WP_Error( 'igdb_not_found', 'Gioco non trovato su IGDB' );
	}

	$game = $data[0];
	$details = ntn_igdb_parse_game_data( $game );
	
	// Aggiungi l'ID grezzo e l'URL
	$details['igdb_id'] = $game['id'];
	if ( isset( $game['url'] ) ) {
		$details['raw_url'] = esc_url_raw( $game['url'] );
	}

	set_transient( $transient_key, $details, DAY_IN_SECONDS );
	return $details;
}

/**
 * Endpoint for getting full details of a specific game from IGDB
 */
function ntn_journal_ajax_get_igdb_details() {
	check_ajax_referer( 'ntn_igdb_nonce', '_ajax_nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Non autorizzato' ) );
	}

	$game_id = isset( $_POST['game_id'] ) ? intval( $_POST['game_id'] ) : 0;
	if ( empty( $game_id ) ) {
		wp_send_json_error( array( 'message' => 'ID Gioco mancante' ) );
	}

	$details = ntn_get_igdb_game_details( $game_id );
	if ( is_wp_error( $details ) ) {
		wp_send_json_error( array( 'message' => $details->get_error_message() ) );
	}
	
	wp_send_json_success( $details );
}
add_action( 'wp_ajax_ntn_get_igdb_details', 'ntn_journal_ajax_get_igdb_details' );

/**
 * Ajax endpoint per la ricerca dei tag di WordPress (kept for compatibility)
 */
function ntn_ajax_search_wp_tags() {
	check_ajax_referer( 'ntn_igdb_nonce', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Non autorizzato' );
	}
	
	$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
	if ( empty( $query ) ) {
		wp_send_json_error( 'Nessuna query' );
	}
	
	$tags = get_terms( array(
		'taxonomy'   => 'post_tag',
		'name__like' => $query,
		'hide_empty' => false,
		'number'     => 10,
	) );
	
	if ( is_wp_error( $tags ) || empty( $tags ) ) {
		wp_send_json_success( array() );
	}
	
	$results = array();
	foreach ( $tags as $tag ) {
		$results[] = array(
			'term_id' => $tag->term_id,
			'name'    => $tag->name,
			'slug'    => $tag->slug,
		);
	}
	
	wp_send_json_success( $results );
}
add_action( 'wp_ajax_ntn_search_wp_tags', 'ntn_ajax_search_wp_tags' );

/**
 * Ajax endpoint per salvare l'associazione Tag -> IGDB
 */
function ntn_ajax_save_tag_igdb_assoc() {
	check_ajax_referer( 'ntn_igdb_nonce', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Non autorizzato' );
	}
	
	$tag_id = isset( $_POST['tag_id'] ) ? intval( $_POST['tag_id'] ) : 0;
	$igdb_id = isset( $_POST['igdb_id'] ) ? intval( $_POST['igdb_id'] ) : 0;
	
	if ( ! $tag_id || ! $igdb_id ) {
		wp_send_json_error( 'Dati mancanti' );
	}
	
	$details = ntn_get_igdb_game_details( $igdb_id );
	if ( is_wp_error( $details ) ) {
		wp_send_json_error( $details->get_error_message() );
	}
	
	$tag = get_term( $tag_id, 'post_tag' );
	if ( is_wp_error( $tag ) || ! $tag ) {
		wp_send_json_error( 'Tag non trovato' );
	}
	$tag_slug = $tag->slug;

	// Salva nei term meta per compatibilità
	update_term_meta( $tag_id, '_ntn_igdb_game_id', $details['igdb_id'] );
	update_term_meta( $tag_id, '_ntn_game_title', $details['title'] );
	update_term_meta( $tag_id, '_ntn_game_cover', $details['cover'] );
	update_term_meta( $tag_id, '_ntn_game_release', $details['release'] );
	update_term_meta( $tag_id, '_ntn_game_release_precision', $details['release_precision'] ?? '' );
	update_term_meta( $tag_id, '_ntn_game_developer', $details['developer'] );
	update_term_meta( $tag_id, '_ntn_game_publisher', $details['publisher'] );
	update_term_meta( $tag_id, '_ntn_game_platforms', $details['platforms'] );
	update_term_meta( $tag_id, '_ntn_game_genres', $details['genres'] );
	update_term_meta( $tag_id, '_ntn_game_engine', $details['engine'] );
	update_term_meta( $tag_id, '_ntn_game_franchise', $details['franchise'] );
	update_term_meta( $tag_id, '_ntn_game_video', $details['video'] );
	update_term_meta( $tag_id, '_ntn_game_screenshots', $details['screenshots'] );
	update_term_meta( $tag_id, '_ntn_igdb_last_sync', time() );
	if ( isset( $details['raw_url'] ) ) {
		update_term_meta( $tag_id, '_ntn_game_url', $details['raw_url'] );
	}
	
	// Sincronizza con CPT "videogioco"
	$cpt_post = get_posts( array(
		'name'        => $tag_slug,
		'post_type'   => 'videogioco',
		'post_status' => 'any',
		'numberposts' => 1
	) );
	
	$post_id = 0;
	if ( $cpt_post ) {
		$post_id = $cpt_post[0]->ID;
		wp_update_post( array(
			'ID'         => $post_id,
			'post_title' => $details['title']
		) );
	} else {
		$post_id = wp_insert_post( array(
			'post_title'  => $details['title'],
			'post_name'   => $tag_slug,
			'post_type'   => 'videogioco',
			'post_status' => 'publish'
		) );
	}
	
	if ( ! is_wp_error( $post_id ) && $post_id ) {
		update_post_meta( $post_id, '_ntn_igdb_game_id', $details['igdb_id'] );
		update_post_meta( $post_id, '_ntn_game_title', $details['title'] );
		update_post_meta( $post_id, '_ntn_game_cover', $details['cover'] );
		update_post_meta( $post_id, '_ntn_game_release', $details['release'] );
		update_post_meta( $post_id, '_ntn_game_release_precision', $details['release_precision'] ?? '' );
		update_post_meta( $post_id, '_ntn_game_developer', $details['developer'] );
		update_post_meta( $post_id, '_ntn_game_publisher', $details['publisher'] );
		update_post_meta( $post_id, '_ntn_game_platforms', $details['platforms'] );
		update_post_meta( $post_id, '_ntn_game_genres', $details['genres'] );
		update_post_meta( $post_id, '_ntn_game_engine', $details['engine'] );
		update_post_meta( $post_id, '_ntn_game_franchise', $details['franchise'] );
		update_post_meta( $post_id, '_ntn_game_video', $details['video'] );
		update_post_meta( $post_id, '_ntn_game_screenshots', $details['screenshots'] );
		update_post_meta( $post_id, '_ntn_igdb_last_sync', time() );
		if ( isset( $details['raw_url'] ) ) {
			update_post_meta( $post_id, '_ntn_game_url', $details['raw_url'] );
		}

		if ( ! empty( $details['release'] ) && taxonomy_exists( 'game_year' ) ) {
			$year = substr( trim( $details['release'] ), 0, 4 );
			if ( ! empty( $year ) && is_numeric( $year ) ) {
				if ( ! term_exists( $year, 'game_year' ) ) {
					wp_insert_term( $year, 'game_year' );
				}
				wp_set_object_terms( $post_id, $year, 'game_year', false );
			}
		}

		if ( ! empty( $details['engine'] ) && taxonomy_exists( 'game_engine' ) ) {
			$engines = array_filter( array_map( 'trim', explode( ',', $details['engine'] ) ) );
			foreach ( $engines as $eng_name ) {
				if ( ! term_exists( $eng_name, 'game_engine' ) ) {
					wp_insert_term( $eng_name, 'game_engine' );
				}
			}
			wp_set_object_terms( $post_id, $engines, 'game_engine', false );
		}

		if ( ! empty( $details['franchise'] ) && taxonomy_exists( 'game_franchise' ) ) {
			$franchises = array_filter( array_map( 'trim', explode( ',', $details['franchise'] ) ) );
			foreach ( $franchises as $fran_name ) {
				if ( ! term_exists( $fran_name, 'game_franchise' ) ) {
					wp_insert_term( $fran_name, 'game_franchise' );
				}
			}
			wp_set_object_terms( $post_id, $franchises, 'game_franchise', false );
		}

		if ( ! empty( $details['developer'] ) && taxonomy_exists( 'game_developer' ) ) {
			$developers = array_filter( array_map( 'trim', explode( ',', $details['developer'] ) ) );
			foreach ( $developers as $dev_name ) {
				if ( ! term_exists( $dev_name, 'game_developer' ) ) {
					wp_insert_term( $dev_name, 'game_developer' );
				}
			}
			wp_set_object_terms( $post_id, $developers, 'game_developer', false );
		}

		if ( ! empty( $details['publisher'] ) && taxonomy_exists( 'game_publisher' ) ) {
			$publishers = array_filter( array_map( 'trim', explode( ',', $details['publisher'] ) ) );
			foreach ( $publishers as $pub_name ) {
				if ( ! term_exists( $pub_name, 'game_publisher' ) ) {
					wp_insert_term( $pub_name, 'game_publisher' );
				}
			}
			wp_set_object_terms( $post_id, $publishers, 'game_publisher', false );
		}
	}
	
	if ( ! empty( $_POST['review_url'] ) && is_string( $_POST['review_url'] ) ) {
		$input_val = trim( sanitize_text_field( wp_unslash( $_POST['review_url'] ) ) );
		$review_id = 0;
		
		if ( is_numeric( $input_val ) ) {
			$review_id = absint( $input_val );
		} else {
			$review_id = url_to_postid( esc_url_raw( $input_val ) );
			if ( ! $review_id ) {
				$path = parse_url( $input_val, PHP_URL_PATH );
				$slug = trim( $path, '/' );
				$slug_parts = explode( '/', $slug );
				$last_slug = sanitize_title( end( $slug_parts ) );
				
				$found = get_posts( array(
					'name'        => $last_slug,
					'post_type'   => 'post',
					'post_status' => 'publish',
					'numberposts' => 1
				) );
				if ( $found ) {
					$review_id = $found[0]->ID;
				}
			}
		}
		
		if ( $review_id ) {
			update_term_meta( $tag_id, '_ntn_featured_review_id', $review_id );
			if ( $post_id && ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, '_ntn_featured_review_id', $review_id );
			}
		}
	} else {
		delete_term_meta( $tag_id, '_ntn_featured_review_id' );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			delete_post_meta( $post_id, '_ntn_featured_review_id' );
		}
	}
	
	if ( ob_get_length() ) {
		ob_clean();
	}
	
	wp_send_json_success( 'Associazione IGDB salvata con successo sul Tag' );
}
add_action( 'wp_ajax_ntn_save_tag_igdb_assoc', 'ntn_ajax_save_tag_igdb_assoc' );

/**
 * Enqueue scripts for WP Admin (IGDB)
 */
function ntn_journal_admin_scripts( $hook ) {
	global $post;
	$post_type = get_post_type( $post );
	if ( ! in_array( $post_type, array( 'post', 'videogioco' ) ) ) { return; }
	
	if ( $hook === 'post-new.php' || $hook === 'post.php' ) {
		wp_enqueue_script( 'ntn-admin-igdb-js', NTN_JOURNAL_CORE_URL . 'assets/js/admin-igdb.js', array( 'jquery' ), '1.0.0', true );
		wp_localize_script( 'ntn-admin-igdb-js', 'ntn_igdb_vars', array(
			'nonce' => wp_create_nonce( 'ntn_igdb_nonce' )
		) );
	}
}
add_action( 'admin_enqueue_scripts', 'ntn_journal_admin_scripts' );

/**
 * Ajax endpoint per controllare gli aggiornamenti in blocco su IGDB
 */
function ntn_ajax_check_igdb_updates() {
	check_ajax_referer( 'ntn_batch_scan_igdb_nonce', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Non autorizzato' );
	}

	$igdb_ids = isset( $_POST['igdb_ids'] ) ? $_POST['igdb_ids'] : array();
	if ( empty( $igdb_ids ) || ! is_array( $igdb_ids ) ) {
		wp_send_json_error( 'Nessun ID fornito' );
	}

	$client_id = get_option( 'ntn_igdb_client_id', '' );
	$token = ntn_get_igdb_token();

	if ( empty( $client_id ) || ! $token ) {
		wp_send_json_error( 'Credenziali IGDB mancanti o errate' );
	}

	// IGDB API accetta query con `where id = (1,2,3)`
	$ids_string = implode( ',', array_map( 'intval', $igdb_ids ) );
	$apicalypse = 'fields id, updated_at; where id = (' . $ids_string . '); limit 500;';

	$response = wp_remote_post( 'https://api.igdb.com/v4/games', array(
		'headers' => array(
			'Client-ID'     => $client_id,
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'text/plain',
		),
		'body'    => $apicalypse,
		'timeout' => 15,
	) );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		wp_send_json_error( 'Errore di comunicazione con IGDB' );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) ) {
		wp_send_json_error( 'Risposta API non valida' );
	}

	$updates = array();
	foreach ( $data as $game ) {
		$updates[ $game['id'] ] = isset( $game['updated_at'] ) ? intval( $game['updated_at'] ) : 0;
	}

	wp_send_json_success( $updates );
}
add_action( 'wp_ajax_ntn_check_igdb_updates', 'ntn_ajax_check_igdb_updates' );
