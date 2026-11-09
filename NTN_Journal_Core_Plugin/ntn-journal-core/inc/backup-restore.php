<?php
/**
 * Logica di Backup e Ripristino Unificato per NTN Journal Core
 * Esportazione e importazione All-in-One in formato streaming JSONL (e legacy JSON):
 * - Opzioni Core (IGDB, Redirects, Breaking News, SEO Extras, AI Summaries, Comunicati)
 * - Custom Post Types (videogioco, comunicato_stampa) completi di contenuti, date e tassonomie
 * - Metadati Recensioni (rating, pros, cons, verdict, reviewed_item, ntn_review_source)
 * - Metadati Hub Page e IGDB (_ntn_game_*, _ntn_igdb_*, _ntn_hub_*, _ntn_featured_review_id, _ntn_review_url)
 * - Tassonomie e Termini Core (game_engine, game_franchise, game_developer, game_publisher, game_year, etc.)
 *
 * @package NTN_Journal_Core
 * @version 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elenco esaustivo di tutte le option gestite dall'ecosistema NTN Journal Core
 */
function ntn_get_core_backup_options_keys() {
	return array(
		// IGDB Settings (v3.17.1: tolta "ntn_journal_enable_igdb", che nessuno leggeva)
		'ntn_igdb_client_id',
		'ntn_igdb_client_secret',
		'ntn_igdb_scanner_active',
		'ntn_journal_enable_videogame_links',
		'ntn_journal_enable_hub_cache',

		// Redirects
		'ntn_redirects_map',
		'ntn_journal_redirects',
		'ntn_redirect_folder_rules',
		'ntn_redirect_paged_fallback',
		'ntn_redirect_no_guess',

		// Breaking News
		'ntn_breaking_news_active',
		'ntn_breaking_news_label',
		'ntn_breaking_news_text',
		'ntn_breaking_news_url',
		'ntn_breaking_news_badge_color',
		'ntn_breaking_news_sticky',
		'ntn_breaking_news_dismissable',

		// SEO & OpenGraph
		'ntn_seo_enable_base',
		'ntn_seo_enable_auto_toc',
		'ntn_seo_enable_faq_schema',
		'ntn_seo_enable_news_sitemap',
		'ntn_seo_enable_review_schema',
		'ntn_seo_enable_video_schema',
		'ntn_seo_cleanup_og_duplicates',
		'ntn_seo_min_posts_terms',
		'ntn_seo_min_posts_games',

		// AI Summaries
		'ntn_gemini_api_key',
		'ntn_groq_api_key',
		'ntn_youtube_api_key',

		// Comunicati Stampa
		'ntn_press_disclaimer',

		// General Core Settings (v3.17.1: tolta "ntn_journal_enable_tags", senza casella e mai usata)
		'ntn_journal_enable_comments',
	);
}

/**
 * Gestisce l'esportazione unificata Core via AJAX
 */
function ntn_ajax_backup_export() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Accesso negato.' );
	}
	check_ajax_referer( 'ntn_backup_nonce', '_ajax_nonce' );

	$phase       = isset( $_POST['phase'] ) ? sanitize_key( $_POST['phase'] ) : 'init';
	$offset      = isset( $_POST['offset'] ) ? max( 0, intval( $_POST['offset'] ) ) : 0;
	$batch_size  = isset( $_POST['batch_size'] ) ? max( 1, min( 200, intval( $_POST['batch_size'] ) ) ) : 100;
	$jsonl_lines = array();
	global $wpdb;

	// Fase 1: Inizializzazione - Header + Opzioni Core + Conteggi
	if ( 'init' === $phase ) {
		$jsonl_lines[] = wp_json_encode( array(
			'type'      => 'header',
			'subtype'   => 'ntn_journal_unified',
			'timestamp' => current_time( 'mysql' ),
			'version'   => defined( 'NTN_JOURNAL_CORE_VERSION' ) ? NTN_JOURNAL_CORE_VERSION : '3.1.0',
		) );

		foreach ( ntn_get_core_backup_options_keys() as $opt ) {
			$val = get_option( $opt );
			if ( false !== $val && null !== $val ) {
				$jsonl_lines[] = wp_json_encode( array(
					'type'  => 'option',
					'key'   => $opt,
					'value' => $val,
				) );
			}
		}

		// v3.18.0: la Redazione (gruppi, ordine e persone, riconosciute dal nome utente)
		if ( function_exists( 'ntn_core_team_page' ) ) {
			$team_page = ntn_core_team_page();
			if ( $team_page ) {
				$members = array();
				foreach ( get_users( array( 'meta_key' => '_ntn_team_group', 'fields' => array( 'ID', 'user_login' ) ) ) as $member ) { // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- poche persone.
					$members[] = array(
						'login' => (string) $member->user_login,
						'group' => (string) get_user_meta( $member->ID, '_ntn_team_group', true ),
						'order' => (int) get_user_meta( $member->ID, '_ntn_team_order', true ),
					);
				}
				$jsonl_lines[] = wp_json_encode( array(
					'type'      => 'team',
					'page_slug' => (string) $team_page->post_name,
					'groups'    => ntn_core_team_groups( (int) $team_page->ID ),
					'order'     => ntn_core_team_order_mode(),
					'members'   => $members,
				) );
			}
		}

		$post_count_sql = "
			SELECT COUNT(DISTINCT p.ID)
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE p.post_type IN ('videogioco', 'comunicato_stampa')
			   OR pm.meta_key LIKE %s
			   OR pm.meta_key LIKE %s
			   OR pm.meta_key LIKE %s
			   OR pm.meta_key IN ('rating', 'pros', 'cons', 'verdict', 'reviewed_item', 'ntn_review_source', '_ntn_featured_review_id', '_ntn_review_url', '_ntn_ai_summary')
		";
		$total_posts = (int) $wpdb->get_var( $wpdb->prepare(
			$post_count_sql,
			$wpdb->esc_like( '_ntn_game_' ) . '%',
			$wpdb->esc_like( '_ntn_igdb_' ) . '%',
			$wpdb->esc_like( 'ntn_press_' ) . '%'
		) );

		$term_count_sql = "
			SELECT COUNT(DISTINCT t.term_id)
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			LEFT JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id
			WHERE tt.taxonomy IN ('game_engine', 'game_franchise', 'game_developer', 'game_publisher', 'game_year')
			   OR tm.meta_key LIKE %s
			   OR tm.meta_key LIKE %s
			   OR tm.meta_key LIKE %s
			   OR tm.meta_key IN ('_ntn_featured_review_id', '_ntn_review_url')
		";
		$total_terms = (int) $wpdb->get_var( $wpdb->prepare(
			$term_count_sql,
			$wpdb->esc_like( '_ntn_game_' ) . '%',
			$wpdb->esc_like( '_ntn_igdb_' ) . '%',
			$wpdb->esc_like( '_ntn_hub_' ) . '%'
		) );

		$has_posts = $total_posts > 0;
		$has_terms = $total_terms > 0;

		wp_send_json_success( array(
			'lines'       => implode( "\n", $jsonl_lines ) . ( ! empty( $jsonl_lines ) ? "\n" : '' ),
			'total_posts' => $total_posts,
			'total_terms' => $total_terms,
			'next_phase'  => $has_posts ? 'posts' : ( $has_terms ? 'terms' : 'done' ),
			'offset'      => 0,
			'phase_done'  => true,
			'done'        => ( ! $has_posts && ! $has_terms ),
		) );
	}

	// Fase 2: Esportazione Contenuti Core (CPT, Recensioni, IGDB)
	if ( 'posts' === $phase ) {
		$post_ids_query = $wpdb->prepare( "
			SELECT DISTINCT p.ID
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE p.post_type IN ('videogioco', 'comunicato_stampa')
			   OR pm.meta_key LIKE %s
			   OR pm.meta_key LIKE %s
			   OR pm.meta_key LIKE %s
			   OR pm.meta_key IN ('rating', 'pros', 'cons', 'verdict', 'reviewed_item', 'ntn_review_source', '_ntn_featured_review_id', '_ntn_review_url', '_ntn_ai_summary')
			ORDER BY p.ID ASC
			LIMIT %d OFFSET %d
		", $wpdb->esc_like( '_ntn_game_' ) . '%', $wpdb->esc_like( '_ntn_igdb_' ) . '%', $wpdb->esc_like( 'ntn_press_' ) . '%', $batch_size, $offset );

		$post_ids = $wpdb->get_col( $post_ids_query );

		if ( ! empty( $post_ids ) ) {
			foreach ( $post_ids as $pid ) {
				$post = get_post( $pid );
				if ( ! $post ) {
					continue;
				}

				// Metadati Core
				$all_metas  = get_post_meta( $post->ID );
				$core_metas = array();
				if ( is_array( $all_metas ) ) {
					foreach ( $all_metas as $k => $vals ) {
						if ( strpos( $k, '_ntn_' ) === 0 || strpos( $k, 'ntn_' ) === 0 || in_array( $k, array( 'rating', 'pros', 'cons', 'verdict', 'reviewed_item', 'ntn_review_source' ), true ) ) {
							$core_metas[ $k ] = maybe_unserialize( $vals[0] );
						}
					}
				}

				// Tassonomie assegnate
				$post_taxes = array();
				$tax_names  = array( 'game_engine', 'game_franchise', 'game_developer', 'game_publisher', 'game_year', 'category', 'post_tag' );
				foreach ( $tax_names as $tax ) {
					if ( taxonomy_exists( $tax ) ) {
						$terms = wp_get_object_terms( $post->ID, $tax, array( 'fields' => 'names' ) );
						if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
							$post_taxes[ $tax ] = $terms;
						}
					}
				}

				$jsonl_lines[] = wp_json_encode( array(
					'type'         => 'post',
					'post_id'      => (int) $post->ID,
					'post_name'    => (string) $post->post_name,
					'post_title'   => (string) $post->post_title,
					'post_type'    => (string) $post->post_type,
					'post_status'  => (string) $post->post_status,
					'post_date'    => (string) $post->post_date,
					'post_content' => (string) $post->post_content,
					'post_excerpt' => (string) $post->post_excerpt,
					'metas'        => $core_metas,
					'taxonomies'   => $post_taxes,
				) );
			}
		}

		$fetched_count = count( $post_ids );
		$new_offset    = $offset + $fetched_count;
		$posts_done    = $fetched_count < $batch_size;

		wp_send_json_success( array(
			'lines'      => implode( "\n", $jsonl_lines ) . ( ! empty( $jsonl_lines ) ? "\n" : '' ),
			'offset'     => $posts_done ? 0 : $new_offset,
			'phase_done' => $posts_done,
			'done'       => false,
			'next_phase' => $posts_done ? 'terms' : 'posts',
		) );
	}

	// Fase 3: Esportazione Tassonomie & Hub Page
	if ( 'terms' === $phase ) {
		$term_ids_query = $wpdb->prepare( "
			SELECT DISTINCT t.term_id
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			LEFT JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id
			WHERE tt.taxonomy IN ('game_engine', 'game_franchise', 'game_developer', 'game_publisher', 'game_year')
			   OR tm.meta_key LIKE %s
			   OR tm.meta_key LIKE %s
			   OR tm.meta_key LIKE %s
			   OR tm.meta_key IN ('_ntn_featured_review_id', '_ntn_review_url')
			ORDER BY t.term_id ASC
			LIMIT %d OFFSET %d
		", $wpdb->esc_like( '_ntn_game_' ) . '%', $wpdb->esc_like( '_ntn_igdb_' ) . '%', $wpdb->esc_like( '_ntn_hub_' ) . '%', $batch_size, $offset );

		$term_ids = $wpdb->get_col( $term_ids_query );

		if ( ! empty( $term_ids ) ) {
			foreach ( $term_ids as $tid ) {
				$term = get_term( $tid );
				if ( ! $term || is_wp_error( $term ) ) {
					continue;
				}

				$all_metas  = get_term_meta( $term->term_id );
				$term_metas = array();
				if ( is_array( $all_metas ) ) {
					foreach ( $all_metas as $k => $vals ) {
						if ( strpos( $k, '_ntn_' ) === 0 || strpos( $k, 'ntn_' ) === 0 ) {
							$term_metas[ $k ] = maybe_unserialize( $vals[0] );
						}
					}
				}

				$jsonl_lines[] = wp_json_encode( array(
					'type'        => 'termmeta',
					'term_id'     => (int) $term->term_id,
					'term_slug'   => (string) $term->slug,
					'term_name'   => (string) $term->name,
					'taxonomy'    => (string) $term->taxonomy,
					'description' => (string) $term->description,
					'metas'       => $term_metas,
				) );
			}
		}

		$fetched_count = count( $term_ids );
		$new_offset    = $offset + $fetched_count;
		$terms_done    = $fetched_count < $batch_size;

		wp_send_json_success( array(
			'lines'      => implode( "\n", $jsonl_lines ) . ( ! empty( $jsonl_lines ) ? "\n" : '' ),
			'offset'     => $terms_done ? 0 : $new_offset,
			'phase_done' => $terms_done,
			'done'       => $terms_done,
			'next_phase' => $terms_done ? 'done' : 'terms',
		) );
	}

	wp_send_json_error( 'Fase di esportazione non valida.' );
}
add_action( 'wp_ajax_ntn_backup_export', 'ntn_ajax_backup_export' );

/**
 * Helper per la sanitizzazione ricorsiva type-safe dei valori meta (compatibile PHP 8.2+)
 */
function _ntn_sanitize_meta_value( $value ) {
	if ( is_array( $value ) ) {
		$cleaned = array();
		foreach ( $value as $k => $v ) {
			$cleaned[ sanitize_key( (string) $k ) ] = _ntn_sanitize_meta_value( $v );
		}
		return $cleaned;
	} elseif ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
		return $value;
	} elseif ( is_string( $value ) ) {
		return wp_kses_post( $value );
	}
	return '';
}

/**
 * Helper per la sanitizzazione e il ripristino di una Option Core
 */
function _ntn_restore_option( $key, $value ) {
	$key = sanitize_key( $key );

	if ( 'ntn_journal_redirects' === $key ) {
		$sanitized_redirects = array();
		$map = get_option( 'ntn_redirects_map', array() );
		if ( ! is_array( $map ) ) {
			$map = array();
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $red ) {
				if ( is_array( $red ) && ! empty( $red['source'] ) && is_scalar( $red['source'] ) ) {
					$source = sanitize_text_field( (string) $red['source'] );
					$target = isset( $red['target'] ) && is_scalar( $red['target'] ) ? esc_url_raw( (string) $red['target'] ) : '';
					$code   = isset( $red['code'] ) && is_numeric( $red['code'] ) ? absint( $red['code'] ) : 301;
					$entry  = array(
						'source' => $source,
						'target' => $target,
						'code'   => $code,
					);
					$sanitized_redirects[] = $entry;
					$map[ md5( $source ) ] = array(
						'source'        => $source,
						'target'        => $target,
						'code'          => $code,
						'hits'          => 0,
						'last_accessed' => '',
					);
				}
			}
		}
		update_option( 'ntn_journal_redirects', $sanitized_redirects );
		update_option( 'ntn_redirects_map', $map, 'yes' );
	} elseif ( 'ntn_redirects_map' === $key ) {
		$sanitized_map = array();
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $red ) {
				if ( is_array( $red ) && ! empty( $red['source'] ) && is_scalar( $red['source'] ) ) {
					$source = sanitize_text_field( (string) $red['source'] );
					$target = isset( $red['target'] ) && is_scalar( $red['target'] ) ? esc_url_raw( (string) $red['target'] ) : '';
					$code   = isset( $red['code'] ) && is_numeric( $red['code'] ) ? absint( $red['code'] ) : 301;
					$hits   = isset( $red['hits'] ) && is_numeric( $red['hits'] ) ? absint( $red['hits'] ) : 0;
					$last   = isset( $red['last_accessed'] ) && is_scalar( $red['last_accessed'] ) ? sanitize_text_field( (string) $red['last_accessed'] ) : '';

					$entry = array(
						'source'        => $source,
						'target'        => $target,
						'code'          => $code,
						'hits'          => $hits,
						'last_accessed' => $last,
					);
					$hash  = ( is_string( $k ) && 32 === strlen( $k ) ) ? $k : md5( $source );
					$sanitized_map[ $hash ] = $entry;
				}
			}
		}
		update_option( 'ntn_redirects_map', $sanitized_map, 'yes' );
	} elseif ( 'ntn_redirect_folder_rules' === $key ) {
		// v4.0.0: regole per cartella, con gli stessi controlli della scheda Redirect
		$sanitized_rules = array();
		if ( is_array( $value ) ) {
			foreach ( $value as $rule ) {
				if ( ! is_array( $rule ) || ! isset( $rule['source'], $rule['target'] ) || ! is_scalar( $rule['source'] ) || ! is_scalar( $rule['target'] ) ) {
					continue;
				}
				$source = ntn_redirect_folder_normalize( sanitize_text_field( (string) $rule['source'] ) );
				$target = ntn_redirect_folder_normalize( sanitize_text_field( (string) $rule['target'] ) );
				if ( '' !== ntn_redirect_folder_validate( $source, $target, $sanitized_rules ) ) {
					continue;
				}
				$sanitized_rules[ md5( $source ) ] = array(
					'source'        => $source,
					'target'        => $target,
					'enabled'       => ! empty( $rule['enabled'] ),
					'hits'          => isset( $rule['hits'] ) && is_numeric( $rule['hits'] ) ? absint( $rule['hits'] ) : 0,
					'created_at'    => isset( $rule['created_at'] ) && is_scalar( $rule['created_at'] ) ? sanitize_text_field( (string) $rule['created_at'] ) : '',
					'last_accessed' => isset( $rule['last_accessed'] ) && is_scalar( $rule['last_accessed'] ) ? sanitize_text_field( (string) $rule['last_accessed'] ) : '',
				);
			}
		}
		update_option( 'ntn_redirect_folder_rules', $sanitized_rules, 'yes' );
	} elseif ( in_array( $key, array( 'ntn_seo_min_posts_terms', 'ntn_seo_min_posts_games' ), true ) ) {
		// v4.0.0: soglie di articoli, numeri da 0 a 100 come nella scheda SEO (prima diventavano "no", cioè 0)
		if ( is_numeric( $value ) ) {
			update_option( $key, min( 100, absint( $value ) ) );
		}
	} elseif ( in_array( $key, array(
		'ntn_breaking_news_active',
		'ntn_breaking_news_sticky',
		'ntn_breaking_news_dismissable',
		'ntn_igdb_scanner_active',
		'ntn_journal_enable_videogame_links',
		'ntn_journal_enable_hub_cache',
		'ntn_seo_enable_base',
		'ntn_seo_enable_auto_toc',
		'ntn_seo_enable_faq_schema',
		'ntn_seo_enable_news_sitemap',
		'ntn_seo_enable_review_schema',
		'ntn_seo_enable_video_schema',
		'ntn_seo_cleanup_og_duplicates',
		'ntn_journal_enable_comments',
		'ntn_redirect_paged_fallback',
		'ntn_redirect_no_guess',
	), true ) ) {
		update_option( $key, ( 'yes' === $value || true === $value || '1' === (string) $value ) ? 'yes' : 'no' );
	} elseif ( 'ntn_breaking_news_url' === $key ) {
		update_option( $key, is_scalar( $value ) ? esc_url_raw( (string) $value ) : '' );
	} elseif ( 'ntn_breaking_news_badge_color' === $key ) {
		update_option( $key, is_scalar( $value ) ? sanitize_hex_color( (string) $value ) : '' );
	} elseif ( 'ntn_press_disclaimer' === $key ) {
		update_option( $key, is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '' );
	} elseif ( in_array( $key, array(
		'ntn_igdb_client_id',
		'ntn_igdb_client_secret',
		'ntn_gemini_api_key',
		'ntn_groq_api_key',
		'ntn_youtube_api_key',
		'ntn_breaking_news_label',
		'ntn_breaking_news_text',
	), true ) ) {
		update_option( $key, is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '' );
	}
}

/**
 * Helper per la risoluzione sicura del Post ID
 * Previene sovrascritture di post non correlati e verifica corrispondenze esatte
 */
function _ntn_resolve_post_id( $post_data, $post_id_map = array() ) {
	$old_post_id = isset( $post_data['post_id'] ) ? absint( $post_data['post_id'] ) : 0;
	$post_name   = isset( $post_data['post_name'] ) ? sanitize_title( $post_data['post_name'] ) : '';
	$post_title  = isset( $post_data['post_title'] ) ? sanitize_text_field( $post_data['post_title'] ) : '';
	$post_type   = isset( $post_data['post_type'] ) && ! empty( $post_data['post_type'] ) ? sanitize_key( $post_data['post_type'] ) : 'post';

	// 1. Mappa di migrazione esplicita
	if ( $old_post_id > 0 && isset( $post_id_map[ $old_post_id ] ) ) {
		return absint( $post_id_map[ $old_post_id ] );
	}

	// 2. Risoluzione tramite _ntn_igdb_id univoco se presente nei meta
	if ( isset( $post_data['metas']['_ntn_igdb_id'] ) && is_scalar( $post_data['metas']['_ntn_igdb_id'] ) ) {
		$igdb_id = sanitize_text_field( (string) $post_data['metas']['_ntn_igdb_id'] );
		if ( ! empty( $igdb_id ) ) {
			$by_igdb = get_posts( array(
				'post_type'   => $post_type,
				'post_status' => 'any',
				'meta_key'    => '_ntn_igdb_id',
				'meta_value'  => $igdb_id,
				'numberposts' => 1,
				'fields'      => 'ids',
			) );
			if ( ! empty( $by_igdb ) ) {
				$matched_post = $by_igdb[0];
				return ( $matched_post instanceof WP_Post ) ? (int) $matched_post->ID : (int) $matched_post;
			}
		}
	}

	// 3. Risoluzione tramite slug (post_name) e post_type
	if ( ! empty( $post_name ) ) {
		$posts = get_posts( array(
			'name'        => $post_name,
			'post_type'   => $post_type,
			'post_status' => 'any',
			'numberposts' => 1,
			'fields'      => 'ids',
		) );
		if ( ! empty( $posts ) ) {
			$matched_post = $posts[0];
			return ( $matched_post instanceof WP_Post ) ? (int) $matched_post->ID : (int) $matched_post;
		}
		if ( 'post' !== $post_type ) {
			$posts = get_posts( array(
				'name'        => $post_name,
				'post_type'   => 'any',
				'post_status' => 'any',
				'numberposts' => 1,
				'fields'      => 'ids',
			) );
			if ( ! empty( $posts ) ) {
				$matched_post = $posts[0];
				return ( $matched_post instanceof WP_Post ) ? (int) $matched_post->ID : (int) $matched_post;
			}
		}
	}

	// 4. Risoluzione tramite titolo esatto se fornito
	if ( ! empty( $post_title ) ) {
		$posts = get_posts( array(
			'title'       => $post_title,
			'post_type'   => $post_type,
			'post_status' => 'any',
			'numberposts' => 1,
			'fields'      => 'ids',
		) );
		if ( ! empty( $posts ) ) {
			$matched_post = $posts[0];
			return ( $matched_post instanceof WP_Post ) ? (int) $matched_post->ID : (int) $matched_post;
		}
	}

	// 5. Fallback ID originale: SOLO se il post esistente coincide per slug, titolo o post_type
	if ( $old_post_id > 0 ) {
		$existing = get_post( $old_post_id );
		if ( $existing && $existing->post_type === $post_type && ( $existing->post_name === $post_name || $existing->post_title === $post_title ) ) {
			return $old_post_id;
		}
	}

	return 0;
}

/**
 * Helper per la sanitizzazione e il ripristino sicuro di un Post Meta
 */
function _ntn_restore_postmeta( $post_id, $meta_key, $meta_value, $post_id_map = array() ) {
	$post_id = absint( $post_id );
	if ( $post_id <= 0 ) {
		return;
	}
	$meta_key = sanitize_key( $meta_key );

	$is_allowed = false;
	if ( strpos( $meta_key, '_ntn_' ) === 0 || strpos( $meta_key, 'ntn_' ) === 0 ) {
		$is_allowed = true;
	} elseif ( in_array( $meta_key, array( 'rating', 'pros', 'cons', 'verdict', 'reviewed_item', 'ntn_review_source' ), true ) ) {
		$is_allowed = true;
	}

	if ( ! $is_allowed ) {
		return;
	}

	// Riassegnazione recensione in evidenza tramite mappa di migrazione
	if ( '_ntn_featured_review_id' === $meta_key ) {
		$meta_value = absint( $meta_value );
		if ( isset( $post_id_map[ $meta_value ] ) ) {
			$meta_value = absint( $post_id_map[ $meta_value ] );
		}
		update_post_meta( $post_id, $meta_key, $meta_value );
		return;
	}

	if ( '_ntn_review_url' === $meta_key ) {
		update_post_meta( $post_id, $meta_key, is_scalar( $meta_value ) ? esc_url_raw( (string) $meta_value ) : '' );
		return;
	}

	if ( 'rating' === $meta_key ) {
		$rating_val = is_numeric( $meta_value ) ? intval( $meta_value ) : '';
		update_post_meta( $post_id, $meta_key, $rating_val );
		return;
	}

	$sanitized_value = _ntn_sanitize_meta_value( $meta_value );
	update_post_meta( $post_id, $meta_key, $sanitized_value );
}

/**
 * Helper per il ripristino completo di un Post (inclusa auto-creazione di CPT e assegnazione tassonomie)
 */
function _ntn_restore_post( $post_data, &$post_id_map ) {
	$old_post_id  = isset( $post_data['post_id'] ) ? absint( $post_data['post_id'] ) : 0;
	$post_name    = isset( $post_data['post_name'] ) ? sanitize_title( $post_data['post_name'] ) : '';
	$post_title   = isset( $post_data['post_title'] ) ? sanitize_text_field( $post_data['post_title'] ) : '';
	$post_type    = isset( $post_data['post_type'] ) && ! empty( $post_data['post_type'] ) ? sanitize_key( $post_data['post_type'] ) : 'post';
	$post_status  = isset( $post_data['post_status'] ) && ! empty( $post_data['post_status'] ) ? sanitize_key( $post_data['post_status'] ) : 'publish';
	$post_content = isset( $post_data['post_content'] ) ? wp_kses_post( $post_data['post_content'] ) : '';
	$post_excerpt = isset( $post_data['post_excerpt'] ) ? sanitize_textarea_field( $post_data['post_excerpt'] ) : '';
	$post_date    = isset( $post_data['post_date'] ) ? sanitize_text_field( $post_data['post_date'] ) : current_time( 'mysql' );

	$resolved_id = _ntn_resolve_post_id( $post_data, $post_id_map );

	// Auto-creazione CPT videogioco o comunicato_stampa se non esistente sul sito di destinazione
	if ( 0 === $resolved_id && in_array( $post_type, array( 'videogioco', 'comunicato_stampa' ), true ) ) {
		$insert_args = array(
			'post_title'   => ! empty( $post_title ) ? $post_title : ( ! empty( $post_name ) ? $post_name : 'Untitled' ),
			'post_name'    => $post_name,
			'post_type'    => $post_type,
			'post_status'  => $post_status,
			'post_content' => $post_content,
			'post_excerpt' => $post_excerpt,
			'post_date'    => $post_date,
		);
		$new_id = wp_insert_post( $insert_args );
		if ( ! is_wp_error( $new_id ) && $new_id > 0 ) {
			$resolved_id = (int) $new_id;
			if ( $old_post_id > 0 ) {
				$post_id_map[ $old_post_id ] = $resolved_id;
			}
		}
	}

	if ( $resolved_id > 0 ) {
		// Ripristino Metadati
		if ( isset( $post_data['metas'] ) && is_array( $post_data['metas'] ) ) {
			foreach ( $post_data['metas'] as $meta_key => $meta_value ) {
				_ntn_restore_postmeta( $resolved_id, $meta_key, $meta_value, $post_id_map );
			}
		}

		// Ripristino e sincronizzazione Tassonomie
		if ( isset( $post_data['taxonomies'] ) && is_array( $post_data['taxonomies'] ) ) {
			foreach ( $post_data['taxonomies'] as $tax => $terms ) {
				$tax = sanitize_key( $tax );
				if ( taxonomy_exists( $tax ) && is_array( $terms ) ) {
					$term_ids = array();
					foreach ( $terms as $term_val ) {
						if ( is_scalar( $term_val ) ) {
							$term_name_or_slug = sanitize_text_field( (string) $term_val );
							$term_obj          = get_term_by( 'name', $term_name_or_slug, $tax );
							if ( ! $term_obj || is_wp_error( $term_obj ) ) {
								$term_obj = get_term_by( 'slug', sanitize_title( $term_name_or_slug ), $tax );
							}
							if ( ! $term_obj || is_wp_error( $term_obj ) ) {
								$new_t = wp_insert_term( $term_name_or_slug, $tax );
								if ( ! is_wp_error( $new_t ) && isset( $new_t['term_id'] ) ) {
									$term_ids[] = (int) $new_t['term_id'];
								}
							} else {
								$term_ids[] = (int) $term_obj->term_id;
							}
						}
					}
					if ( ! empty( $term_ids ) ) {
						wp_set_object_terms( $resolved_id, $term_ids, $tax );
					}
				}
			}
		}
	}

	return $resolved_id;
}

/**
 * Helper per la risoluzione del Term ID tramite slug, nome o ID
 */
function _ntn_resolve_term_id( $term_data ) {
	$term_id   = isset( $term_data['term_id'] ) ? absint( $term_data['term_id'] ) : 0;
	$term_slug = isset( $term_data['term_slug'] ) ? sanitize_title( $term_data['term_slug'] ) : '';
	$term_name = isset( $term_data['term_name'] ) ? sanitize_text_field( $term_data['term_name'] ) : '';
	$taxonomy  = isset( $term_data['taxonomy'] ) && ! empty( $term_data['taxonomy'] ) ? sanitize_key( $term_data['taxonomy'] ) : 'post_tag';

	// 1. Cerca per slug nella tassonomia
	if ( ! empty( $term_slug ) ) {
		$term = get_term_by( 'slug', $term_slug, $taxonomy );
		if ( $term && ! is_wp_error( $term ) ) {
			return (int) $term->term_id;
		}

		if ( 'post_tag' !== $taxonomy && taxonomy_exists( 'post_tag' ) ) {
			$term = get_term_by( 'slug', $term_slug, 'post_tag' );
			if ( $term && ! is_wp_error( $term ) ) {
				return (int) $term->term_id;
			}
		}
	}

	// 2. Cerca per nome
	if ( ! empty( $term_name ) ) {
		$term = get_term_by( 'name', $term_name, $taxonomy );
		if ( $term && ! is_wp_error( $term ) ) {
			return (int) $term->term_id;
		}
		if ( 'post_tag' !== $taxonomy && taxonomy_exists( 'post_tag' ) ) {
			$term = get_term_by( 'name', $term_name, 'post_tag' );
			if ( $term && ! is_wp_error( $term ) ) {
				return (int) $term->term_id;
			}
		}
	}

	// 3. Se fornito uno slug ma non esiste, crealo automaticamente
	if ( ! empty( $term_slug ) && taxonomy_exists( $taxonomy ) ) {
		$name_to_insert = ! empty( $term_name ) ? $term_name : $term_slug;
		$new_term       = wp_insert_term( $name_to_insert, $taxonomy, array( 'slug' => $term_slug ) );
		if ( ! is_wp_error( $new_term ) && isset( $new_term['term_id'] ) ) {
			return (int) $new_term['term_id'];
		} elseif ( is_wp_error( $new_term ) && isset( $new_term->error_data['term_exists'] ) ) {
			return (int) $new_term->error_data['term_exists'];
		}
	}

	// 4. Fallback legacy per ID diretto se il termine coincide per tassonomia
	if ( $term_id > 0 ) {
		$term = get_term( $term_id );
		if ( $term && ! is_wp_error( $term ) && $term->taxonomy === $taxonomy ) {
			return (int) $term->term_id;
		}
	}

	return 0;
}

/**
 * Helper per la sanitizzazione e il ripristino di un Term Meta
 */
function _ntn_restore_termmeta( $term_id, $meta_key, $meta_value, $post_id_map = array() ) {
	$term_id = absint( $term_id );
	if ( $term_id <= 0 ) {
		return;
	}
	$meta_key = sanitize_key( $meta_key );

	if ( '_ntn_featured_review_id' === $meta_key ) {
		$meta_value = absint( $meta_value );
		if ( isset( $post_id_map[ $meta_value ] ) ) {
			$meta_value = absint( $post_id_map[ $meta_value ] );
		}
		update_term_meta( $term_id, $meta_key, $meta_value );
		return;
	}

	if ( '_ntn_review_url' === $meta_key ) {
		update_term_meta( $term_id, $meta_key, is_scalar( $meta_value ) ? esc_url_raw( (string) $meta_value ) : '' );
		return;
	}

	if ( strpos( $meta_key, '_ntn_' ) === 0 || strpos( $meta_key, 'ntn_' ) === 0 ) {
		$sanitized_value = _ntn_sanitize_meta_value( $meta_value );
		update_term_meta( $term_id, $meta_key, $sanitized_value );
	}
}

/**
 * Helper per il ripristino di un record termmeta strutturato
 */
/**
 * Ripristina la Redazione (v3.18.0): gruppi nella pagina con il modello "Redazione",
 * ordine dentro i gruppi e gruppo di ogni persona (riconosciuta dal nome utente).
 * Se la pagina non esiste sul sito, i gruppi si salvano comunque nelle persone e
 * compaiono appena si crea la pagina con quei nomi di gruppo.
 *
 * @param array $row Riga del backup.
 */
function _ntn_restore_team( $row ) {
	if ( ! function_exists( 'ntn_core_team_save' ) || ! isset( $row['groups'] ) || ! is_array( $row['groups'] ) ) {
		return;
	}
	$page_id = 0;
	if ( function_exists( 'ntn_core_team_page' ) && ntn_core_team_page() ) {
		$page_id = (int) ntn_core_team_page()->ID;
	} elseif ( ! empty( $row['page_slug'] ) ) {
		$page = get_page_by_path( sanitize_title( (string) $row['page_slug'] ) );
		$page_id = $page instanceof WP_Post ? (int) $page->ID : 0;
	}

	$groups = array();
	foreach ( $row['groups'] as $name ) {
		$groups[ (string) $name ] = array( 'name' => (string) $name, 'people' => array() );
	}
	$members = isset( $row['members'] ) && is_array( $row['members'] ) ? $row['members'] : array();
	usort( $members, static function ( $a, $b ) {
		return (int) ( $a['order'] ?? 0 ) <=> (int) ( $b['order'] ?? 0 );
	} );
	foreach ( $members as $member ) {
		$user = isset( $member['login'] ) ? get_user_by( 'login', sanitize_user( (string) $member['login'] ) ) : false;
		$name = isset( $member['group'] ) ? (string) $member['group'] : '';
		if ( $user && isset( $groups[ $name ] ) ) {
			$groups[ $name ]['people'][] = (int) $user->ID;
		}
	}
	ntn_core_team_save( $page_id, array_values( $groups ), isset( $row['order'] ) ? sanitize_key( (string) $row['order'] ) : 'alpha' );
}

function _ntn_restore_termmeta_row( $term_data, $post_id_map = array() ) {
	$resolved_term_id = _ntn_resolve_term_id( $term_data );
	if ( $resolved_term_id > 0 && isset( $term_data['metas'] ) && is_array( $term_data['metas'] ) ) {
		foreach ( $term_data['metas'] as $meta_key => $meta_value ) {
			_ntn_restore_termmeta( $resolved_term_id, $meta_key, $meta_value, $post_id_map );
		}
	}
}

/**
 * Helper per invalidare in modo completo tutte le cache transitorie e le regole Core
 */
function _ntn_clear_all_backup_caches() {
	delete_transient( 'ntn_igdb_post_count' );
	delete_transient( 'ntn_igdb_tag_count' );
	delete_transient( 'ntn_vg_link_map' );

	global $wpdb;

	$like_patterns = array(
		$wpdb->esc_like( '_transient_ntn_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_ntn_' ) . '%',
		$wpdb->esc_like( '_transient_feed_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_feed_' ) . '%',
	);

	foreach ( $like_patterns as $pattern ) {
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$pattern
		) );
	}

	delete_option( 'ntn_rewrite_rules_flushed_v3' );
}

/**
 * Gestisce l'importazione unificata Core via AJAX
 */
function ntn_ajax_backup_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Accesso negato.' );
	}
	check_ajax_referer( 'ntn_backup_nonce', '_ajax_nonce' );

	if ( empty( $_FILES['ntn_backup_file']['tmp_name'] ) && empty( $_POST['file_path'] ) ) {
		wp_send_json_error( 'Nessun file selezionato per l\'importazione.' );
	}

	$upload_dir = wp_upload_dir();
	$file_path  = '';

	if ( ! empty( $_POST['file_path'] ) ) {
		$safe_filename = basename( sanitize_text_field( wp_unslash( $_POST['file_path'] ) ) );
		$file_path     = $upload_dir['basedir'] . '/' . $safe_filename;
		if ( ! file_exists( $file_path ) ) {
			wp_send_json_error( 'File temporaneo non trovato.' );
		}
	} else {
		$file_path = $_FILES['ntn_backup_file']['tmp_name'];
	}

	$filename = isset( $_FILES['ntn_backup_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['ntn_backup_file']['name'] ) ) : 'backup.jsonl';

	// Mappa ID per post migrati
	global $wpdb;
	$migrated_posts = $wpdb->get_results( $wpdb->prepare(
		"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
		'_slow_migration_source_post_id'
	), ARRAY_A );
	$post_id_map = array();
	if ( ! empty( $migrated_posts ) ) {
		foreach ( $migrated_posts as $row ) {
			$post_id_map[ $row['meta_value'] ] = $row['post_id'];
		}
	}

	// Supporto formato legacy JSON (.json)
	if ( str_ends_with( strtolower( $filename ), '.json' ) ) {
		if ( filesize( $file_path ) > 15 * 1024 * 1024 ) {
			wp_send_json_error( 'File troppo grande per l\'importazione JSON singola. Utilizzare il formato JSONL.' );
		}

		$file_content = file_get_contents( $file_path );
		$data         = json_decode( $file_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
			wp_send_json_error( 'Il file caricato non è un JSON valido o è corrotto.' );
		}

		$imported_count = 0;

		// 1. Ripristina Options Core
		if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
			foreach ( $data['options'] as $key => $value ) {
				_ntn_restore_option( $key, $value );
				$imported_count++;
			}
		}

		// 2. Ripristina Post / Postmeta
		if ( isset( $data['postmeta'] ) && is_array( $data['postmeta'] ) ) {
			foreach ( $data['postmeta'] as $old_post_id => $metas ) {
				$post_data = is_array( $metas ) && isset( $metas['post_name'] ) ? $metas : array( 'post_id' => $old_post_id, 'metas' => $metas );
				_ntn_restore_post( $post_data, $post_id_map );
				$imported_count++;
			}
		}

		// 3. Ripristina Term Meta
		if ( isset( $data['termmeta'] ) && is_array( $data['termmeta'] ) ) {
			foreach ( $data['termmeta'] as $term_key => $metas ) {
				$term_data = is_array( $metas ) && ( isset( $metas['term_slug'] ) || isset( $metas['term_id'] ) ) ? $metas : array( 'term_id' => $term_key, 'metas' => $metas );
				_ntn_restore_termmeta_row( $term_data, $post_id_map );
				$imported_count++;
			}
		}

		_ntn_clear_all_backup_caches();

		wp_send_json_success( array(
			'done'     => true,
			'imported' => $imported_count,
			'message'  => 'Importazione completata con successo (formato Legacy JSON)!',
		) );
	}

	// Formato JSONL in streaming a batch
	$offset     = isset( $_POST['offset'] ) ? max( 0, intval( $_POST['offset'] ) ) : 0;
	$batch_size = isset( $_POST['batch_size'] ) ? max( 1, min( 200, intval( $_POST['batch_size'] ) ) ) : 50;

	if ( empty( $_POST['file_path'] ) ) {
		$safe_filename = 'ntn_core_backup_' . md5( uniqid( (string) wp_rand(), true ) ) . '.jsonl';
		$new_file_path = wp_normalize_path( $upload_dir['basedir'] . '/' . $safe_filename );

		if ( move_uploaded_file( $file_path, $new_file_path ) ) {
			$file_path = $new_file_path;
		} else {
			wp_send_json_error( 'Impossibile salvare il file caricato nella directory temporanea.' );
		}
	}

	$handle = fopen( $file_path, 'r' );
	if ( ! $handle ) {
		wp_send_json_error( 'Impossibile leggere il file caricato.' );
	}

	if ( $offset > 0 ) {
		fseek( $handle, $offset );
	}

	$count = 0;
	while ( $count < $batch_size && ( $line = fgets( $handle ) ) !== false ) {
		$line = trim( $line );
		if ( empty( $line ) ) {
			continue;
		}

		$row = json_decode( $line, true );
		if ( ! $row || ! isset( $row['type'] ) ) {
			continue;
		}

		switch ( $row['type'] ) {
			case 'option':
				if ( isset( $row['key'], $row['value'] ) ) {
					_ntn_restore_option( $row['key'], $row['value'] );
					$count++;
				}
				break;

			case 'post':
			case 'postmeta':
				_ntn_restore_post( $row, $post_id_map );
				$count++;
				break;

			case 'termmeta':
				_ntn_restore_termmeta_row( $row, $post_id_map );
				$count++;
				break;

			case 'team':
				_ntn_restore_team( $row );
				$count++;
				break;
		}
	}

	$new_offset = ftell( $handle );
	$done       = feof( $handle );
	fclose( $handle );

	if ( $done ) {
		_ntn_clear_all_backup_caches();
		if ( file_exists( $file_path ) && strpos( wp_normalize_path( $file_path ), wp_normalize_path( $upload_dir['basedir'] ) ) === 0 ) {
			unlink( $file_path );
		}
	}

	wp_send_json_success( array(
		'imported'  => $count,
		'offset'    => $new_offset,
		'done'      => $done,
		'file_path' => basename( $file_path ),
	) );
}
add_action( 'wp_ajax_ntn_backup_import', 'ntn_ajax_backup_import' );
