<?php
/**
 * Script di migrazione per convertire i Tag con dati IGDB in CPT Videogioco.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggiunge una pagina sottomenu sotto "Strumenti"
 */
function ntn_igdb_migration_menu() {
	add_management_page(
		'Migrazione IGDB',
		'Migrazione IGDB',
		'manage_options',
		'ntn-igdb-migration',
		'ntn_igdb_migration_page_html'
	);
}
add_action( 'admin_menu', 'ntn_igdb_migration_menu' );

/**
 * HTML e JS della pagina di migrazione
 */
function ntn_igdb_migration_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Migrazione e Strumenti IGDB</h1>
		<p>Questa utility permette di migrare tutti i Tag attualmente collegati a un videogioco tramite IGDB nel nuovo formato "Custom Post Type (Videogioco)".</p>
		
		<button id="ntn-run-migration" class="button button-primary">Esegui Migrazione Ora</button>
		
		<hr style="margin: 20px 0;">
		
		<h2>Sincronizzazione Franchise</h2>
		<p>Usa questo strumento se i vecchi giochi non hanno il Franchise assegnato. Questo strumento si collegherà a IGDB e scaricherà le informazioni mancanti sui Franchise (e sulle Collection).</p>
		<button id="ntn-run-franchise-sync" class="button button-secondary">Sincronizza Franchise Mancanti</button>
		
		<div id="ntn-migration-results" style="margin-top:20px; padding:15px; background:#fff; border:1px solid #ccc; display:none;"></div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		$('#ntn-run-migration').on('click', function(e) {
			e.preventDefault();
			var btn = $(this);
			
			if ( ! confirm("Sei sicuro di voler avviare la migrazione? Potrebbe richiedere qualche secondo.") ) {
				return;
			}
			
			btn.prop('disabled', true).text('Migrazione in corso...');
			$('#ntn-migration-results').show().html('<p>Elaborazione in corso, attendere...</p>');

			$.post(ajaxurl, {
				action: 'ntn_migrate_tags_to_cpt',
				_ajax_nonce: '<?php echo wp_create_nonce( 'ntn_igdb_migrate_nonce' ); ?>'
			}, function(response) {
				if ( response.success ) {
					$('#ntn-migration-results').html('<p style="color:green;"><strong>' + response.data.message + '</strong><br>Giochi migrati: ' + response.data.count + '</p>');
				} else {
					$('#ntn-migration-results').html('<p style="color:red;"><strong>Errore:</strong> ' + response.data + '</p>');
				}
				btn.prop('disabled', false).text('Esegui Migrazione Ora');
			}).fail(function() {
				$('#ntn-migration-results').html('<p style="color:red;"><strong>Errore di rete o timeout.</strong></p>');
				btn.prop('disabled', false).text('Esegui Migrazione Ora');
			});
		});

		function runFranchiseSync( offset ) {
			$.post(ajaxurl, {
				action: 'ntn_sync_franchises',
				offset: offset,
				_ajax_nonce: '<?php echo wp_create_nonce( 'ntn_igdb_migrate_nonce' ); ?>'
			}, function(response) {
				if ( response.success ) {
					if ( response.data.done ) {
						$('#ntn-migration-results').append('<p style="color:green;"><strong>' + response.data.message + '</strong></p>');
						$('#ntn-run-franchise-sync').prop('disabled', false).text('Sincronizza Franchise Mancanti');
					} else {
						$('#ntn-migration-results').append('<p>Elaborati ' + response.data.processed + ' giochi. Procedo con i successivi...</p>');
						runFranchiseSync( response.data.next_offset );
					}
				} else {
					$('#ntn-migration-results').append('<p style="color:red;"><strong>Errore:</strong> ' + response.data + '</p>');
					$('#ntn-run-franchise-sync').prop('disabled', false).text('Sincronizza Franchise Mancanti');
				}
			}).fail(function() {
				$('#ntn-migration-results').append('<p style="color:red;"><strong>Errore di rete o timeout.</strong></p>');
				$('#ntn-run-franchise-sync').prop('disabled', false).text('Sincronizza Franchise Mancanti');
			});
		}

		$('#ntn-run-franchise-sync').on('click', function(e) {
			e.preventDefault();
			var btn = $(this);
			btn.prop('disabled', true).text('Sincronizzazione in corso...');
			$('#ntn-migration-results').show().html('<p>Sincronizzazione Franchise avviata...</p>');
			runFranchiseSync(0);
		});
	});
	</script>
	<?php
}

/**
 * Ajax endpoint per la migrazione.
 */
function ntn_ajax_migrate_tags_to_cpt() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Non autorizzato' );
	}

	check_ajax_referer( 'ntn_igdb_migrate_nonce', '_ajax_nonce' );

	global $wpdb;

	$offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
	$limit  = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 25;

	$total_tags = (int) $wpdb->get_var(
		"
		SELECT COUNT(DISTINCT t.term_id)
		FROM {$wpdb->terms} AS t
		INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
		INNER JOIN {$wpdb->termmeta} AS tm ON t.term_id = tm.term_id
		WHERE tt.taxonomy = 'post_tag'
		AND (
			tm.meta_key = '_ntn_igdb_game_id' 
			OR (tm.meta_key = '_ntn_game_title' AND tm.meta_value != '')
		)
	"
	);

	if ( $total_tags === 0 ) {
		wp_send_json_success(
			array(
				'message' => 'Nessun tag da migrare trovato.',
				'count'   => 0,
				'done'    => true,
			)
		);
	}

	$batch_tags = $wpdb->get_results(
		$wpdb->prepare(
			"
		SELECT t.*, tt.*
		FROM {$wpdb->terms} AS t
		INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
		INNER JOIN {$wpdb->termmeta} AS tm ON t.term_id = tm.term_id
		WHERE tt.taxonomy = 'post_tag'
		AND (
			tm.meta_key = '_ntn_igdb_game_id' 
			OR (tm.meta_key = '_ntn_game_title' AND tm.meta_value != '')
		)
		GROUP BY t.term_id
		ORDER BY t.term_id ASC
		LIMIT %d OFFSET %d
	",
			$limit,
			$offset
		)
	);

	if ( empty( $batch_tags ) ) {
		wp_send_json_success(
			array(
				'message'  => 'Migrazione completata con successo!',
				'count'    => 0,
				'migrated' => 0,
				'offset'   => $offset,
				'total'    => $total_tags,
				'done'     => true,
			)
		);
	}

	$count = 0;

	foreach ( $batch_tags as $tag ) {
		$tag_slug = $tag->slug;
		$tag_id   = $tag->term_id;

		// Recupera i meta dal tag
		$igdb_id     = get_term_meta( $tag_id, '_ntn_igdb_game_id', true );
		$title       = get_term_meta( $tag_id, '_ntn_game_title', true );
		$cover       = get_term_meta( $tag_id, '_ntn_game_cover', true );
		$release     = get_term_meta( $tag_id, '_ntn_game_release', true );
		$developer   = get_term_meta( $tag_id, '_ntn_game_developer', true );
		$publisher   = get_term_meta( $tag_id, '_ntn_game_publisher', true );
		$platforms   = get_term_meta( $tag_id, '_ntn_game_platforms', true );
		$genres      = get_term_meta( $tag_id, '_ntn_game_genres', true );
		$engine      = get_term_meta( $tag_id, '_ntn_game_engine', true );
		$video       = get_term_meta( $tag_id, '_ntn_game_video', true );
		$screenshots = get_term_meta( $tag_id, '_ntn_game_screenshots', true );
		$url         = get_term_meta( $tag_id, '_ntn_game_url', true );
		$review_id   = get_term_meta( $tag_id, '_ntn_featured_review_id', true );

		if ( ! $title ) {
			$title = $tag->name;
		}

		// Controlla se il CPT esiste già
		$cpt_post = get_posts(
			array(
				'name'        => $tag_slug,
				'post_type'   => 'videogioco',
				'post_status' => 'any',
				'numberposts' => 1,
			)
		);

		$post_id = 0;
		if ( $cpt_post ) {
			$post_id = $cpt_post[0]->ID;
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $title,
				)
			);
		} else {
			$post_id = wp_insert_post(
				array(
					'post_title'  => $title,
					'post_name'   => $tag_slug,
					'post_type'   => 'videogioco',
					'post_status' => 'publish',
				)
			);
		}

		if ( ! is_wp_error( $post_id ) && $post_id ) {
			update_post_meta( $post_id, '_ntn_igdb_game_id', $igdb_id );
			update_post_meta( $post_id, '_ntn_game_title', $title );
			update_post_meta( $post_id, '_ntn_game_cover', $cover );
			update_post_meta( $post_id, '_ntn_game_release', $release );
			update_post_meta( $post_id, '_ntn_game_developer', $developer );
			update_post_meta( $post_id, '_ntn_game_publisher', $publisher );
			update_post_meta( $post_id, '_ntn_game_platforms', $platforms );
			update_post_meta( $post_id, '_ntn_game_genres', $genres );
			update_post_meta( $post_id, '_ntn_game_engine', $engine );
			update_post_meta( $post_id, '_ntn_game_video', $video );
			update_post_meta( $post_id, '_ntn_game_screenshots', $screenshots );

			if ( $url ) {
				update_post_meta( $post_id, '_ntn_game_url', $url );
			}
			if ( $review_id ) {
				update_post_meta( $post_id, '_ntn_featured_review_id', $review_id );
			}
			if ( ! empty( $engine ) && taxonomy_exists( 'game_engine' ) ) {
				$engines = array_filter( array_map( 'trim', explode( ',', $engine ) ) );
				foreach ( $engines as $eng_name ) {
					if ( ! term_exists( $eng_name, 'game_engine' ) ) {
						wp_insert_term( $eng_name, 'game_engine' );
					}
				}
				wp_set_object_terms( $post_id, $engines, 'game_engine', false );
			}
			++$count;
		}
	}

	$is_done = ( $offset + count( $batch_tags ) >= $total_tags ) || ( count( $batch_tags ) < $limit );

	unset( $batch_tags );
	if ( function_exists( 'gc_collect_cycles' ) ) {
		gc_collect_cycles();
	}

	wp_send_json_success(
		array(
			'message'  => $is_done ? 'Migrazione completata con successo!' : 'Migrazione in corso...',
			'count'    => $count,
			'migrated' => $count,
			'offset'   => $offset + $limit,
			'total'    => $total_tags,
			'done'     => $is_done,
		)
	);
}
add_action( 'wp_ajax_ntn_migrate_tags_to_cpt', 'ntn_ajax_migrate_tags_to_cpt' );

/**
 * Funzione una-tantum per popolare le nuove tassonomie a partire dai meta già esistenti sui CPT.
 */
function ntn_populate_taxonomies_from_meta() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Non autorizzato' );
	}

	check_ajax_referer( 'ntn_igdb_nonce', 'nonce' );

	if ( get_option( 'ntn_taxonomies_populated_v2' ) ) {
		wp_send_json_success(
			array(
				'processed' => 0,
				'offset'    => 0,
				'done'      => true,
			)
		);
	}

	$offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
	$limit  = 50;

	// Usiamo wpdb per estrarre tutti gli ID CPT e risparmiare memoria
	global $wpdb;
	$games_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'videogioco' ORDER BY ID ASC LIMIT %d OFFSET %d",
			$limit,
			$offset
		)
	);

	if ( empty( $games_ids ) ) {
		update_option( 'ntn_taxonomies_populated_v2', '1' );
		wp_send_json_success(
			array(
				'processed' => 0,
				'offset'    => $offset,
				'done'      => true,
			)
		);
	}

	update_meta_cache( 'post', $games_ids );

	$count = 0;
	foreach ( $games_ids as $post_id ) {
		// Populate Developers
		$developer = get_post_meta( $post_id, '_ntn_game_developer', true );
		if ( ! empty( $developer ) && taxonomy_exists( 'game_developer' ) ) {
			$developers = array_filter( array_map( 'trim', explode( ',', $developer ) ) );
			foreach ( $developers as $dev_name ) {
				if ( ! term_exists( $dev_name, 'game_developer' ) ) {
					wp_insert_term( $dev_name, 'game_developer' );
				}
			}
			wp_set_object_terms( $post_id, $developers, 'game_developer', false );
		}

		// Populate Publishers
		$publisher = get_post_meta( $post_id, '_ntn_game_publisher', true );
		if ( ! empty( $publisher ) && taxonomy_exists( 'game_publisher' ) ) {
			$publishers = array_filter( array_map( 'trim', explode( ',', $publisher ) ) );
			foreach ( $publishers as $pub_name ) {
				if ( ! term_exists( $pub_name, 'game_publisher' ) ) {
					wp_insert_term( $pub_name, 'game_publisher' );
				}
			}
			wp_set_object_terms( $post_id, $publishers, 'game_publisher', false );
		}

		// Populate Franchise
		$franchise = get_post_meta( $post_id, '_ntn_game_franchise', true );
		if ( ! empty( $franchise ) && taxonomy_exists( 'game_franchise' ) ) {
			$franchises = array_filter( array_map( 'trim', explode( ',', $franchise ) ) );
			foreach ( $franchises as $fran_name ) {
				if ( ! term_exists( $fran_name, 'game_franchise' ) ) {
					wp_insert_term( $fran_name, 'game_franchise' );
				}
			}
			wp_set_object_terms( $post_id, $franchises, 'game_franchise', false );
		}

		// Populate Year
		$release = get_post_meta( $post_id, '_ntn_game_release', true );
		if ( ! empty( $release ) && taxonomy_exists( 'game_year' ) ) {
			$year = substr( trim( $release ), 0, 4 );
			if ( ! empty( $year ) && is_numeric( $year ) ) {
				if ( ! term_exists( $year, 'game_year' ) ) {
					wp_insert_term( $year, 'game_year' );
				}
				wp_set_object_terms( $post_id, $year, 'game_year', false );
			}
		}
		++$count;
	}

	$new_offset = $offset + $limit;
	$is_done    = count( $games_ids ) < $limit;
	if ( $is_done ) {
		update_option( 'ntn_taxonomies_populated_v2', '1' );
	}

	unset( $games_ids );
	if ( function_exists( 'gc_collect_cycles' ) ) {
		gc_collect_cycles();
	}

	wp_send_json_success(
		array(
			'processed' => $count,
			'offset'    => $new_offset,
			'done'      => $is_done,
		)
	);
}
add_action( 'wp_ajax_ntn_populate_taxonomies', 'ntn_populate_taxonomies_from_meta' );

/**
 * Ajax endpoint per sincronizzare i Franchise mancanti da IGDB.
 */
function ntn_ajax_sync_franchises() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Non autorizzato' );
	}
	check_ajax_referer( 'ntn_igdb_migrate_nonce', '_ajax_nonce' );

	$offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
	$limit  = 10;

	global $wpdb;

	// Ottimizzazione: Usiamo query SQL diretta invece di WP_Query per evitare il table scan su wp_postmeta
	$games_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p 
		INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
		WHERE p.post_type = 'videogioco' AND pm.meta_key = '_ntn_igdb_game_id' 
		ORDER BY p.ID ASC LIMIT %d OFFSET %d",
			$limit,
			$offset
		)
	);

	$games = array();
	if ( ! empty( $games_ids ) ) {
		foreach ( $games_ids as $id ) {
			$games[] = (object) array( 'ID' => $id );
		}
	}

	if ( empty( $games ) ) {
		wp_send_json_success(
			array(
				'done'    => true,
				'message' => 'Sincronizzazione Franchise completata!',
			)
		);
	}

	if ( empty( get_option( 'ntn_igdb_client_id', '' ) ) || ! ntn_get_igdb_token() ) {
		wp_send_json_error( 'Credenziali IGDB mancanti o errate.' );
	}

	$processed = 0;
	foreach ( $games as $game ) {
		$post_id = $game->ID;
		$igdb_id = get_post_meta( $post_id, '_ntn_igdb_game_id', true );

		// Verifica se ha già il franchise popolato
		$existing_franchises = wp_get_object_terms( $post_id, 'game_franchise', array( 'fields' => 'ids' ) );
		if ( ! empty( $existing_franchises ) && ! is_wp_error( $existing_franchises ) ) {
			++$processed;
			continue; // Già aggiornato
		}

		if ( ! empty( $igdb_id ) ) {
			// Chiama IGDB
			$apicalypse = 'fields franchises.name, collections.name; where id = ' . intval( $igdb_id ) . '; limit 1;';
			$data       = ntn_core_igdb_request( $apicalypse );

			if ( ! is_wp_error( $data ) ) {
				if ( is_array( $data ) && ! empty( $data[0] ) ) {
					$game_data  = $data[0];
					$franchises = array();
					if ( isset( $game_data['franchises'] ) && is_array( $game_data['franchises'] ) ) {
						foreach ( $game_data['franchises'] as $franchise ) {
							if ( ! empty( $franchise['name'] ) ) {
								$franchises[] = sanitize_text_field( $franchise['name'] );
							}
						}
					}
					if ( isset( $game_data['collections'] ) && is_array( $game_data['collections'] ) ) {
						foreach ( $game_data['collections'] as $collection ) {
							if ( ! empty( $collection['name'] ) ) {
								$franchises[] = sanitize_text_field( $collection['name'] );
							}
						}
					}
					$franchises = array_unique( $franchises );

					if ( ! empty( $franchises ) ) {
						update_post_meta( $post_id, '_ntn_game_franchise', implode( ', ', $franchises ) );
						if ( taxonomy_exists( 'game_franchise' ) ) {
							foreach ( $franchises as $fran_name ) {
								if ( ! term_exists( $fran_name, 'game_franchise' ) ) {
									wp_insert_term( $fran_name, 'game_franchise' );
								}
							}
							wp_set_object_terms( $post_id, $franchises, 'game_franchise', false );
						}
					}
				}
			}
		}
		++$processed;
	}

	unset( $games_ids, $games );
	if ( function_exists( 'gc_collect_cycles' ) ) {
		gc_collect_cycles();
	}

	wp_send_json_success(
		array(
			'done'        => false,
			'processed'   => $offset + $processed,
			'next_offset' => $offset + $limit,
		)
	);
}
add_action( 'wp_ajax_ntn_sync_franchises', 'ntn_ajax_sync_franchises' );
