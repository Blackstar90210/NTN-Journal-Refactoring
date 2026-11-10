<?php
/**
 * NTN Journal - IGDB AJAX Handlers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handler AJAX per il Reset di tutti i dati IGDB
 */
add_action( 'wp_ajax_ntn_reset_igdb_data', 'ntn_core_ajax_reset_igdb_data' );
function ntn_core_ajax_reset_igdb_data() {
	check_ajax_referer( 'ntn_batch_scan_igdb_nonce', '_ajax_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permessi insufficienti' );
	}

	global $wpdb;

	// Delete all post meta keys related to IGDB
	$keys = array(
		'_ntn_game_title',
		'_ntn_game_cover',
		'_ntn_game_platforms',
		'_ntn_game_release',
		'_ntn_game_release_precision',
		'_ntn_game_genres',
		'_ntn_game_engine',
		'_ntn_game_developer',
		'_ntn_game_publisher',
		'_ntn_game_video',
		'_ntn_game_screenshots',
		'_ntn_game_url',
		'_ntn_igdb_not_found',
	);

	// Sostituito $wpdb diretto con funzione nativa per preservare la object cache
	foreach ( $keys as $key ) {
		delete_post_meta_by_key( $key );
	}

	wp_send_json_success( 'Reset completato.' );
}

/**
 * Handler AJAX per ottenere la lista degli articoli collegati
 */
add_action( 'wp_ajax_ntn_get_igdb_connections', 'ntn_core_ajax_get_igdb_connections' );
function ntn_core_ajax_get_igdb_connections() {
	check_ajax_referer( 'ntn_batch_scan_igdb_nonce', '_ajax_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permessi insufficienti' );
	}

	global $wpdb;

	$query_args = array(
		'post_type'      => 'any',
		'posts_per_page' => 1000, // Limite di sicurezza invece di -1
		'post_status'    => 'any',
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'     => '_ntn_game_title',
				'value'   => '',
				'compare' => '!=',
			),
		),
	);
	$post_ids   = get_posts( $query_args );

	$html  = '<table class="wp-list-table widefat fixed striped">';
	$html .= '<thead><tr><th style="width: 80px;">ID</th><th>Articolo</th><th>Collegamento IGDB</th><th style="width: 190px;">Azione</th></tr></thead><tbody>';

	if ( ! empty( $post_ids ) ) {
		foreach ( $post_ids as $post_id ) {
			$post_title = get_the_title( $post_id );
			$edit_url   = get_edit_post_link( $post_id );
			$view_url   = get_permalink( $post_id );
			$game_title = get_post_meta( $post_id, '_ntn_game_title', true );

			$html .= '<tr>';
			$html .= '<td>' . esc_html( $post_id ) . '</td>';
			$html .= '<td><strong><a href="' . esc_url( $edit_url ) . '" target="_blank">' . esc_html( $post_title ) . '</a></strong><br><a href="' . esc_url( $view_url ) . '" target="_blank" style="font-size: 11px;">Visualizza</a></td>';
			$html .= '<td>' . esc_html( $game_title ) . '</td>';
			$html .= '<td><button class="button ntn-remove-single-igdb" data-id="' . esc_attr( $post_id ) . '">Scollega</button></td>';
			$html .= '</tr>';
		}
	} else {
		$html .= '<tr><td colspan="4">Nessun articolo collegato a IGDB.</td></tr>';
	}
	$html .= '</tbody></table>';

	wp_send_json_success( $html );
}

/**
 * Handler AJAX per il Reset di un singolo post
 */
add_action( 'wp_ajax_ntn_reset_single_igdb', 'ntn_core_ajax_reset_single_igdb' );
function ntn_core_ajax_reset_single_igdb() {
	check_ajax_referer( 'ntn_batch_scan_igdb_nonce', '_ajax_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permessi insufficienti' );
	}

	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	if ( ! $post_id ) {
		wp_send_json_error( 'ID non valido' );
	}

	$keys = array(
		'_ntn_game_title',
		'_ntn_game_cover',
		'_ntn_game_platforms',
		'_ntn_game_release',
		'_ntn_game_release_precision',
		'_ntn_game_genres',
		'_ntn_game_engine',
		'_ntn_game_developer',
		'_ntn_game_publisher',
		'_ntn_game_video',
		'_ntn_game_screenshots',
		'_ntn_game_url',
		'_ntn_igdb_not_found',
	);

	foreach ( $keys as $key ) {
		delete_post_meta( $post_id, $key );
	}

	wp_send_json_success( 'Scollegato' );
}

/**
 * Handler AJAX per ottenere la lista dei tag collegati a IGDB
 */
add_action( 'wp_ajax_ntn_get_igdb_tag_connections', 'ntn_core_ajax_get_igdb_tag_connections' );
function ntn_core_ajax_get_igdb_tag_connections() {
	check_ajax_referer( 'ntn_batch_scan_igdb_nonce', '_ajax_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permessi insufficienti' );
	}

	global $wpdb;

	$terms = get_terms(
		array(
			'taxonomy'   => get_taxonomies(),
			'hide_empty' => false,
			'meta_query' => array(
				array(
					'key'     => '_ntn_game_title',
					'value'   => '',
					'compare' => '!=',
				),
			),
		)
	);

	$html  = '<table class="wp-list-table widefat fixed striped">';
	$steam_col = function_exists( 'ntn_core_steam_admin_cell' );
	$html     .= '<thead><tr><th style="width: 80px;">ID Tag</th><th>Nome Tag</th><th>Slug</th><th>Collegamento IGDB</th>' . ( $steam_col ? '<th>Introiti Steam (stima netta)</th>' : '' ) . '<th style="width: 190px;">Azione</th></tr></thead><tbody>';

	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$term_name  = $term->name;
			$term_slug  = $term->slug;
			$game_title = get_term_meta( $term->term_id, '_ntn_game_title', true );
			$igdb_id    = get_term_meta( $term->term_id, '_ntn_igdb_game_id', true );
			$last_sync  = get_term_meta( $term->term_id, '_ntn_igdb_last_sync', true );
			if ( ! $last_sync ) {
				$last_sync = 0;
			}

			$html .= '<tr>';
			$html .= '<td>' . esc_html( $term->term_id ) . '</td>';
			$html .= '<td><strong>' . esc_html( $term_name ) . '</strong></td>';
			$html .= '<td><code>' . esc_html( $term_slug ) . '</code></td>';
			$html .= '<td>' . esc_html( $game_title ) . '</td>';
			if ( $steam_col ) {
				$html .= '<td>' . ntn_core_steam_admin_cell( $term->term_id ) . '</td>'; // Testo già protetto dalla funzione.
			}
			$html .= '<td>
				<button class="button ntn-update-tag-igdb" data-id="' . esc_attr( $term->term_id ) . '" data-igdb-id="' . esc_attr( $igdb_id ) . '" data-last-sync="' . esc_attr( $last_sync ) . '" style="margin-right: 5px;">Aggiorna</button>
				<button class="button nadm-btn is-danger ntn-remove-tag-igdb" data-id="' . esc_attr( $term->term_id ) . '">Scollega</button>
			</td>';
			$html .= '</tr>';
		}
	} else {
		$html .= '<tr><td colspan="' . ( $steam_col ? 6 : 5 ) . '">Nessun tag collegato a IGDB.</td></tr>';
	}
	$html .= '</tbody></table>';

	wp_send_json_success( $html );
}

/**
 * Handler AJAX per scollegare un tag da IGDB
 */
add_action( 'wp_ajax_ntn_reset_tag_igdb', 'ntn_core_ajax_reset_tag_igdb' );
function ntn_core_ajax_reset_tag_igdb() {
	check_ajax_referer( 'ntn_batch_scan_igdb_nonce', '_ajax_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permessi insufficienti' );
	}

	$tag_id = isset( $_POST['tag_id'] ) ? intval( $_POST['tag_id'] ) : 0;
	if ( ! $tag_id ) {
		wp_send_json_error( 'ID non valido' );
	}

	$keys = array(
		'_ntn_igdb_game_id',
		'_ntn_game_title',
		'_ntn_game_cover',
		'_ntn_game_platforms',
		'_ntn_game_release',
		'_ntn_game_release_precision',
		'_ntn_game_genres',
		'_ntn_game_engine',
		'_ntn_game_developer',
		'_ntn_game_publisher',
		'_ntn_game_video',
		'_ntn_game_screenshots',
		'_ntn_game_url',
		'_ntn_featured_review_id',
	);
	if ( function_exists( 'ntn_core_steam_meta_keys' ) ) {
		$keys = array_merge( $keys, ntn_core_steam_meta_keys() ); // Senza gioco non resta nemmeno la stima Steam.
	}

	foreach ( $keys as $key ) {
		delete_term_meta( $tag_id, $key );
	}

	wp_send_json_success( 'Scollegato' );
}

/**
 * Handler AJAX per aggiornare un singolo tag
 */
add_action( 'wp_ajax_ntn_update_single_tag_igdb', 'ntn_core_ajax_update_single_tag_igdb' );
function ntn_core_ajax_update_single_tag_igdb() {
	check_ajax_referer( 'ntn_batch_scan_igdb_nonce', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permessi insufficienti' );
	}

	$tag_id  = isset( $_POST['tag_id'] ) ? intval( $_POST['tag_id'] ) : 0;
	$igdb_id = isset( $_POST['igdb_id'] ) ? intval( $_POST['igdb_id'] ) : 0;

	if ( ! $tag_id || ! $igdb_id ) {
		wp_send_json_error( 'Dati mancanti' );
	}

	$apicalypse = 'fields id, name, url, cover.url, first_release_date, release_dates.date, release_dates.human, platforms.name, involved_companies.company.name, involved_companies.developer, involved_companies.publisher, genres.name, game_engines.name, videos.video_id, screenshots.image_id, external_games.uid, external_games.external_game_source; where id = ' . $igdb_id . '; limit 1;';

	$data = ntn_core_igdb_request( $apicalypse );
	if ( is_wp_error( $data ) ) {
		wp_send_json_error( 'ntn_igdb_credentials' === $data->get_error_code() ? 'Credenziali IGDB mancanti o errate' : $data->get_error_message() );
	}
	if ( empty( $data ) ) {
		wp_send_json_error( 'Gioco non trovato su IGDB' );
	}

	$game = $data[0];

	// Extract Developers and Publishers
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
		$raw_cover = $game['cover']['url'];
		$cover_url = function_exists( 'ntn_normalize_game_cover_url' ) ? ntn_normalize_game_cover_url( $raw_cover ) : str_replace( 't_thumb', 't_1080p', esc_url_raw( $raw_cover ) );
	}

	$release = isset( $game['first_release_date'] ) ? date( 'Y-m-d', $game['first_release_date'] ) : '';
	// v3.19.1: quanto è precisa la data (giorno, mese, trimestre, anno), vedi ajax-igdb.php
	$release_precision = function_exists( 'ntn_igdb_release_precision' ) ? ntn_igdb_release_precision( $game ) : '';

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

	update_term_meta( $tag_id, '_ntn_igdb_game_id', $game['id'] );
	update_term_meta( $tag_id, '_ntn_game_title', sanitize_text_field( $game['name'] ) );
	update_term_meta( $tag_id, '_ntn_game_cover', $cover_url );
	update_term_meta( $tag_id, '_ntn_game_release', $release );
	update_term_meta( $tag_id, '_ntn_game_release_precision', $release_precision );
	update_term_meta( $tag_id, '_ntn_game_developer', implode( ', ', $developers ) );
	update_term_meta( $tag_id, '_ntn_game_publisher', implode( ', ', $publishers ) );
	update_term_meta( $tag_id, '_ntn_game_platforms', implode( ', ', $platforms ) );
	update_term_meta( $tag_id, '_ntn_game_genres', implode( ', ', $genres ) );
	update_term_meta( $tag_id, '_ntn_game_engine', implode( ', ', $engines ) );
	update_term_meta( $tag_id, '_ntn_game_video', $video_id );
	update_term_meta( $tag_id, '_ntn_game_screenshots', implode( ',', $screenshots ) );
	update_term_meta( $tag_id, '_ntn_igdb_last_sync', time() );
	if ( isset( $game['url'] ) ) {
		update_term_meta( $tag_id, '_ntn_game_url', esc_url_raw( $game['url'] ) );
	}
	// ID Steam e stima degli introiti, aggiornata in secondo piano (inc/steam-revenue.php).
	if ( function_exists( 'ntn_core_steam_after_igdb_sync' ) ) {
		ntn_core_steam_after_igdb_sync( $tag_id, ntn_core_steam_appid_from_igdb( $game ) );
	}

	// Sincronizza anche con il CPT videogioco corrispondente se esiste
	$tag = get_term( $tag_id, 'post_tag' );
	if ( $tag && ! is_wp_error( $tag ) ) {
		$cpt_post = get_posts(
			array(
				'name'        => $tag->slug,
				'post_type'   => 'videogioco',
				'post_status' => 'any',
				'numberposts' => 1,
			)
		);
		if ( ! empty( $cpt_post ) ) {
			$post_id = $cpt_post[0]->ID;
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => sanitize_text_field( $game['name'] ),
				)
			);
			update_post_meta( $post_id, '_ntn_igdb_game_id', $game['id'] );
			update_post_meta( $post_id, '_ntn_game_title', sanitize_text_field( $game['name'] ) );
			update_post_meta( $post_id, '_ntn_game_cover', $cover_url );
			update_post_meta( $post_id, '_ntn_game_release', $release );
			update_post_meta( $post_id, '_ntn_game_release_precision', $release_precision );
			update_post_meta( $post_id, '_ntn_game_developer', implode( ', ', $developers ) );
			update_post_meta( $post_id, '_ntn_game_publisher', implode( ', ', $publishers ) );
			update_post_meta( $post_id, '_ntn_game_platforms', implode( ', ', $platforms ) );
			update_post_meta( $post_id, '_ntn_game_genres', implode( ', ', $genres ) );
			update_post_meta( $post_id, '_ntn_game_engine', implode( ', ', $engines ) );
			update_post_meta( $post_id, '_ntn_game_video', $video_id );
			update_post_meta( $post_id, '_ntn_game_screenshots', implode( ',', $screenshots ) );
			update_post_meta( $post_id, '_ntn_igdb_last_sync', time() );
			if ( isset( $game['url'] ) ) {
				update_post_meta( $post_id, '_ntn_game_url', esc_url_raw( $game['url'] ) );
			}
			if ( ! empty( $engines ) && taxonomy_exists( 'game_engine' ) ) {
				foreach ( $engines as $eng_name ) {
					if ( ! term_exists( $eng_name, 'game_engine' ) ) {
						wp_insert_term( $eng_name, 'game_engine' );
					}
				}
				wp_set_object_terms( $post_id, $engines, 'game_engine', false );
			}
		}
	}

	wp_send_json_success( 'Aggiornato' );
}

/**
 * Handler AJAX per scollegare tutti i tag
 */
add_action( 'wp_ajax_ntn_unlink_all_tags_igdb', 'ntn_core_ajax_unlink_all_tags_igdb' );
function ntn_core_ajax_unlink_all_tags_igdb() {
	check_ajax_referer( 'ntn_batch_scan_igdb_nonce', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permessi insufficienti' );
	}

	global $wpdb;

	$keys = array(
		'_ntn_igdb_game_id',
		'_ntn_game_title',
		'_ntn_game_cover',
		'_ntn_game_platforms',
		'_ntn_game_release',
		'_ntn_game_release_precision',
		'_ntn_game_genres',
		'_ntn_game_engine',
		'_ntn_game_developer',
		'_ntn_game_publisher',
		'_ntn_game_video',
		'_ntn_game_screenshots',
		'_ntn_game_url',
	);
	if ( function_exists( 'ntn_core_steam_meta_keys' ) ) {
		$keys = array_merge( $keys, ntn_core_steam_meta_keys() ); // Senza gioco non resta nemmeno la stima Steam.
	}

	foreach ( $keys as $key ) {
		// delete_metadata con $delete_all=true rimuove per tutti i term (gestendo la cache)
		delete_metadata( 'term', 0, $key, '', true );
	}

	wp_send_json_success( 'Scollegati tutti i tag' );
}

/**
 * Handler AJAX per rinfrescare la cache dei conteggi IGDB
 */
add_action( 'wp_ajax_ntn_igdb_refresh_cache', 'ntn_core_ajax_igdb_refresh_cache' );
function ntn_core_ajax_igdb_refresh_cache() {
	check_ajax_referer( 'ntn_batch_scan_igdb_nonce', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permessi insufficienti' );
	}

	global $wpdb;

	$posts_with_igdb = $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_ntn_game_title' AND meta_value != ''" );
	if ( ! $posts_with_igdb ) {
		$posts_with_igdb = 0;
	}
	set_transient( 'ntn_igdb_post_count', $posts_with_igdb, 12 * HOUR_IN_SECONDS );

	$tags_with_igdb = $wpdb->get_var( "SELECT COUNT(DISTINCT term_id) FROM {$wpdb->termmeta} WHERE meta_key = '_ntn_game_title' AND meta_value != ''" );
	if ( ! $tags_with_igdb ) {
		$tags_with_igdb = 0;
	}
	set_transient( 'ntn_igdb_tag_count', $tags_with_igdb, 12 * HOUR_IN_SECONDS );

	wp_send_json_success( 'Cache rinfrescata' );
}

/**
 * Handler AJAX per svuotare la cache dei conteggi IGDB
 */
add_action( 'wp_ajax_ntn_igdb_clear_cache', 'ntn_core_ajax_igdb_clear_cache' );
function ntn_core_ajax_igdb_clear_cache() {
	check_ajax_referer( 'ntn_batch_scan_igdb_nonce', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permessi insufficienti' );
	}

	delete_transient( 'ntn_igdb_post_count' );
	delete_transient( 'ntn_igdb_tag_count' );

	wp_send_json_success( 'Cache svuotata' );
}

/**
 * Handler AJAX per riallineare, riconnettere metadati orfani e unificare tag duplicati
 */
add_action( 'wp_ajax_ntn_repair_reconnect_igdb_tags', 'ntn_core_ajax_repair_reconnect_igdb_tags' );
function ntn_core_ajax_repair_reconnect_igdb_tags() {
	check_ajax_referer( 'ntn_batch_scan_igdb_nonce', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permessi insufficienti' );
	}

	global $wpdb;
	$reconnected = 0;
	$merged      = 0;

	// 1. Riconnetti record orfani di wp_termmeta
	$orphaned_metas = $wpdb->get_results(
		"
		SELECT tm.term_id as old_term_id, tm.meta_key, tm.meta_value
		FROM {$wpdb->termmeta} tm
		LEFT JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
		WHERE t.term_id IS NULL AND tm.meta_key = '_ntn_game_title'
	"
	);

	foreach ( $orphaned_metas as $item ) {
		$game_title  = $item->meta_value;
		$old_id      = $item->old_term_id;
		$clean_title = html_entity_decode( $game_title, ENT_QUOTES, 'UTF-8' );
		$slug        = sanitize_title( $clean_title );

		$live_tag = $wpdb->get_row(
			$wpdb->prepare(
				"
			SELECT t.term_id, t.name, t.slug 
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id AND tt.taxonomy = 'post_tag'
			WHERE LOWER(t.name) = LOWER(%s) 
			   OR t.slug = %s 
			   OR t.slug = %s
			   OR LOWER(t.name) = LOWER(%s)
			ORDER BY tt.count DESC
			LIMIT 1
		",
				$game_title,
				$slug,
				sanitize_title( $game_title ),
				$clean_title
			)
		);

		if ( ! $live_tag ) {
			$live_tag = $wpdb->get_row(
				$wpdb->prepare(
					"
				SELECT t.term_id, t.name, t.slug 
				FROM {$wpdb->terms} t
				INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id AND tt.taxonomy = 'post_tag'
				WHERE t.slug LIKE %s
				ORDER BY tt.count DESC
				LIMIT 1
			",
					$slug . '%'
				)
			);
		}

		if ( $live_tag ) {
			$all_old_metas = $wpdb->get_results(
				$wpdb->prepare(
					"
				SELECT meta_key, meta_value FROM {$wpdb->termmeta} WHERE term_id = %d
			",
					$old_id
				)
			);

			foreach ( $all_old_metas as $m ) {
				update_term_meta( $live_tag->term_id, $m->meta_key, maybe_unserialize( $m->meta_value ) );
			}
			$wpdb->delete( $wpdb->termmeta, array( 'term_id' => $old_id ) );
			++$reconnected;
		}
	}

	// 2. Unifica tag duplicati con suffisso numerico
	$duplicate_tags = $wpdb->get_results(
		"
		SELECT t1.term_id as duplicate_id, t1.name as duplicate_name, t1.slug as duplicate_slug,
			   t2.term_id as master_id, t2.name as master_name, t2.slug as master_slug
		FROM {$wpdb->terms} t1
		INNER JOIN {$wpdb->term_taxonomy} tt1 ON t1.term_id = tt1.term_id AND tt1.taxonomy = 'post_tag'
		INNER JOIN {$wpdb->terms} t2 ON t2.slug = REGEXP_REPLACE(t1.slug, '-\\d+$', '') AND t1.term_id != t2.term_id
		INNER JOIN {$wpdb->term_taxonomy} tt2 ON t2.term_id = tt2.term_id AND tt2.taxonomy = 'post_tag'
		WHERE t1.slug REGEXP '-\\d+$'
	"
	);

	foreach ( $duplicate_tags as $dup ) {
		$dup_metas = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->termmeta} WHERE term_id = %d", $dup->duplicate_id ) );
		foreach ( $dup_metas as $dm ) {
			$existing = get_term_meta( $dup->master_id, $dm->meta_key, true );
			if ( empty( $existing ) ) {
				update_term_meta( $dup->master_id, $dm->meta_key, maybe_unserialize( $dm->meta_value ) );
			}
		}

		$dup_tt_id    = $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'post_tag'", $dup->duplicate_id ) );
		$master_tt_id = $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'post_tag'", $dup->master_id ) );

		if ( $dup_tt_id && $master_tt_id ) {
			$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d", $dup_tt_id ) );
			foreach ( $post_ids as $pid ) {
				$wpdb->replace(
					$wpdb->term_relationships,
					array(
						'object_id'        => $pid,
						'term_taxonomy_id' => $master_tt_id,
						'term_order'       => 0,
					)
				);
			}
			$wpdb->delete( $wpdb->term_relationships, array( 'term_taxonomy_id' => $dup_tt_id ) );
			wp_delete_term( $dup->duplicate_id, 'post_tag' );
			wp_update_term_count_now( array( $master_tt_id ), 'post_tag' );
			++$merged;
		}
	}

	delete_transient( 'ntn_igdb_post_count' );
	delete_transient( 'ntn_igdb_tag_count' );

	wp_send_json_success(
		array(
			'message'     => "Operazione completata: $reconnected metadati riconnessi, $merged tag unificati.",
			'reconnected' => $reconnected,
			'merged'      => $merged,
		)
	);
}
