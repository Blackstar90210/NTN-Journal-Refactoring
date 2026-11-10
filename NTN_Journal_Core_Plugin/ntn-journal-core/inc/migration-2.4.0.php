<?php
/**
 * NTN Journal Core - Migrazione 2.4.0
 * Migra le chiavi API da theme_mods a options globali
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ntn_journal_core_migration_240() {
	$force = isset( $_GET['force_ntn_migration'] ) && current_user_can( 'manage_options' );
	if ( ! get_option( 'ntn_api_keys_migrated' ) || $force ) {
		$keys_to_migrate = array(
			'ntn_gemini_api_key',
			'ntn_groq_api_key',
			'ntn_igdb_client_id',
			'ntn_igdb_client_secret',
		);

		foreach ( $keys_to_migrate as $key ) {
			$theme_mod_value = get_theme_mod( $key );
			if ( $theme_mod_value !== false ) {
				update_option( $key, $theme_mod_value );
				remove_theme_mod( $key );
			}
		}

		update_option( 'ntn_api_keys_migrated', true );
	}

	// Migrazione 2.4.1: Riconnessione automatica metadati IGDB orfani
	if ( ! get_option( 'ntn_igdb_tags_reconnected_v241' ) || $force ) {
		global $wpdb;

		$orphaned_metas = $wpdb->get_results(
			"
			SELECT tm.term_id as old_term_id, tm.meta_key, tm.meta_value
			FROM {$wpdb->termmeta} tm
			LEFT JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
			WHERE t.term_id IS NULL AND tm.meta_key = '_ntn_game_title'
		"
		);

		if ( ! empty( $orphaned_metas ) ) {
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
				}
			}
		}

		delete_transient( 'ntn_igdb_post_count' );
		delete_transient( 'ntn_igdb_tag_count' );
		update_option( 'ntn_igdb_tags_reconnected_v241', true );
	}
}

// Esecuzione manuale on-demand solo se esplicitamente richiesta via URL
if ( is_admin() && isset( $_GET['force_ntn_migration'] ) ) {
	add_action( 'admin_init', 'ntn_journal_core_migration_240' );
}
