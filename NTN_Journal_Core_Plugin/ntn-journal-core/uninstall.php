<?php
/**
 * NTN Journal Core - Pulizia alla cancellazione del plugin (v4.26.0).
 *
 * WordPress esegue questo file solo quando il plugin viene CANCELLATO dalla pagina Plugin (non quando
 * lo si disattiva, e non quando lo si aggiorna).
 *
 * Di base toglie solo il superfluo: le memorie (cache) del plugin e le operazioni programmate.
 * Impostazioni, chiavi API, redirect e backup restano nel database, così reinstallare il plugin
 * non fa perdere niente. Gli articoli, i comunicati stampa, le schede videogioco, i tag e le categorie
 * non vengono mai cancellati da qui.
 *
 * Per togliere anche le impostazioni (chiavi API, redirect, interruttori) aggiungi
 * questa riga a wp-config.php PRIMA di cancellare il plugin:
 *   define( 'NTN_CORE_REMOVE_ALL_DATA', true );
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Pulisce il sito corrente.
 *
 * @param bool $remove_settings true per togliere anche le impostazioni del plugin.
 */
function ntn_core_uninstall_current_site( $remove_settings ) {
	global $wpdb;

	// 1. Memorie (cache): tutte le transient del plugin, con il loro "timeout".
	foreach ( array( '_transient_', '_transient_timeout_' ) as $transient_prefix ) {
		$like = $wpdb->esc_like( $transient_prefix . 'ntn_' ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- pulizia in cancellazione del plugin.
	}

	// 2. Operazioni programmate dal plugin.
	wp_unschedule_hook( 'ntn_async_prefetch_oembed' );
	wp_unschedule_hook( 'ntn_core_youtube_date_lookup' );
	wp_unschedule_hook( 'ntn_core_steam_revenue_weekly' );
	wp_unschedule_hook( 'ntn_core_steam_revenue_batch' );

	if ( ! $remove_settings ) {
		return;
	}

	// 3. Impostazioni (solo con NTN_CORE_REMOVE_ALL_DATA).
	$options = array(
		'ntn_api_keys_migrated',
		'ntn_api_status',
		'ntn_breaking_news_active',
		'ntn_breaking_news_badge_color',
		'ntn_breaking_news_dismissable',
		'ntn_breaking_news_label',
		'ntn_breaking_news_sticky',
		'ntn_breaking_news_text',
		'ntn_breaking_news_url',
		'ntn_cat_hub_version',
		'ntn_gemini_api_key',
		'ntn_groq_api_key',
		'ntn_igdb_client_id',
		'ntn_igdb_client_secret',
		'ntn_igdb_scanner_active',
		'ntn_image_report',
		'ntn_journal_enable_comments',
		'ntn_journal_enable_hub_cache',
		'ntn_journal_enable_videogame_links',
		'ntn_journal_redirects',
		'ntn_press_disclaimer',
		'ntn_redirect_folder_rules',
		'ntn_redirect_no_guess',
		'ntn_redirect_paged_fallback',
		'ntn_redirects_map',
		'ntn_redirects_migrated',
		'ntn_rewrite_rules_version',
		'ntn_seo_cleanup_og_duplicates',
		'ntn_seo_enable_auto_toc',
		'ntn_seo_enable_base',
		'ntn_seo_enable_faq_schema',
		'ntn_seo_enable_news_sitemap',
		'ntn_seo_enable_review_schema',
		'ntn_seo_enable_video_schema',
		'ntn_seo_min_posts_games',
		'ntn_seo_min_posts_terms',
		'ntn_team_order',
		'ntn_team_version',
		'ntn_youtube_api_key',
	);
	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Impostazioni a nome con un numero o un codice in fondo (lotti di redirect, stato della pulizia PNG).
	foreach ( array( 'ntn_folder_redir_batch_', 'ntn_png_repair_' ) as $name_prefix ) {
		$like = $wpdb->esc_like( $name_prefix ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- pulizia in cancellazione del plugin.
	}
}

$ntn_core_remove_settings = defined( 'NTN_CORE_REMOVE_ALL_DATA' ) && true === NTN_CORE_REMOVE_ALL_DATA;

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $ntn_core_site_id ) {
		switch_to_blog( (int) $ntn_core_site_id );
		ntn_core_uninstall_current_site( $ntn_core_remove_settings );
		restore_current_blog();
	}
} else {
	ntn_core_uninstall_current_site( $ntn_core_remove_settings );
}
