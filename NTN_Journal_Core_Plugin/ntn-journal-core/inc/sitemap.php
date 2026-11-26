<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * ==============================================================================
 * GOOGLE NEWS SITEMAP GENERATOR
 * ==============================================================================
 */
function ntn_journal_news_sitemap() {
	if ( get_option( 'ntn_seo_enable_news_sitemap', 'yes' ) !== 'yes' ) {
		return;
	}
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- solo cercata con strpos, mai stampata.
	if ( strpos( $request_uri, '/news-sitemap.xml' ) !== false ) {
		status_header( 200 );
		header( 'Content-Type: text/xml; charset=utf-8' );

		$cached_xml = get_transient( 'ntn_news_sitemap_cache' );
		if ( false === $cached_xml ) {
			ob_start();
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

			$args  = array(
				'post_type'              => array( 'post', 'comunicato_stampa' ),
				'post_status'            => 'publish',
				'posts_per_page'         => 1000,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'fields'                 => 'ids',
				'date_query'             => array(
					array(
						'after' => '48 hours ago',
					),
				),
			);
			$query = new WP_Query( $args );

			$publisher_name = get_bloginfo( 'name' );
			$language       = get_bloginfo( 'language' );

			if ( ! empty( $query->posts ) ) {
				foreach ( $query->posts as $post_id ) {
					echo "\t<url>\n";
					echo "\t\t<loc>" . esc_url( get_permalink( $post_id ) ) . "</loc>\n";
					echo "\t\t<lastmod>" . esc_html( get_the_modified_date( 'c', $post_id ) ) . "</lastmod>\n";
					echo "\t\t<news:news>\n";
					echo "\t\t\t<news:publication>\n";
					echo "\t\t\t\t<news:name>" . esc_html( $publisher_name ) . "</news:name>\n";
					echo "\t\t\t\t<news:language>" . esc_html( substr( $language, 0, 2 ) ) . "</news:language>\n";
					echo "\t\t\t</news:publication>\n";
					echo "\t\t\t<news:publication_date>" . get_the_date( 'c', $post_id ) . "</news:publication_date>\n";
					echo "\t\t\t<news:title>" . esc_html( get_the_title( $post_id ) ) . "</news:title>\n";
					echo "\t\t</news:news>\n";

					if ( has_post_thumbnail( $post_id ) ) {
						$thumb_id  = get_post_thumbnail_id( $post_id );
						$thumb_url = wp_get_attachment_image_url( $thumb_id, 'full' );
						if ( $thumb_url ) {
							echo "\t\t<image:image>\n";
							echo "\t\t\t<image:loc>" . esc_url( $thumb_url ) . "</image:loc>\n";
							echo "\t\t</image:image>\n";
						}
					}

					echo "\t</url>\n";
				}
			}
			echo '</urlset>';

			$cached_xml = ob_get_clean();
			set_transient( 'ntn_news_sitemap_cache', $cached_xml, 15 * MINUTE_IN_SECONDS );
		}

		echo $cached_xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML costruito qui sopra con esc_url / esc_html.
		exit;
	}
}
add_action( 'template_redirect', 'ntn_journal_news_sitemap' );

function ntn_clear_news_sitemap_transient( $post_id ) {
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	delete_transient( 'ntn_news_sitemap_cache' );
}
add_action( 'save_post', 'ntn_clear_news_sitemap_transient' );

/**
 * Normalizza un valore di snapshot IGDB per confrontare prima e dopo senza falsi positivi (v4.35.0).
 *
 * @param mixed $value Valore meta o titolo.
 * @return string
 */
function ntn_core_igdb_normalize_snapshot_value( $value ) {
	if ( is_array( $value ) ) {
		$items = array();
		foreach ( $value as $item ) {
			$clean = trim( (string) $item );
			if ( '' !== $clean ) {
				$items[] = $clean;
			}
		}
		return implode( ', ', $items );
	}
	return is_scalar( $value ) ? trim( (string) $value ) : '';
}

/**
 * Raccoglie lo stato attuale dei dati IGDB salvati su una scheda videogioco (v4.35.0).
 *
 * @param int $post_id ID del CPT videogioco.
 * @return array<string, string>
 */
function ntn_core_igdb_videogioco_snapshot( $post_id ) {
	$post_id = (int) $post_id;
	$keys    = array(
		'_ntn_igdb_game_id',
		'_ntn_game_title',
		'_ntn_game_cover',
		'_ntn_game_release',
		'_ntn_game_release_precision',
		'_ntn_game_developer',
		'_ntn_game_publisher',
		'_ntn_game_platforms',
		'_ntn_game_genres',
		'_ntn_game_engine',
		'_ntn_game_franchise',
		'_ntn_game_video',
		'_ntn_game_screenshots',
	);
	$snap    = array(
		'post_title' => ntn_core_igdb_normalize_snapshot_value( get_post_field( 'post_title', $post_id ) ),
	);
	foreach ( $keys as $key ) {
		$snap[ $key ] = ntn_core_igdb_normalize_snapshot_value( get_post_meta( $post_id, $key, true ) );
	}
	return $snap;
}

/**
 * Aggiorna il post videogioco (e quindi la sua data lastmod nella sitemap) solo se i dati
 * essenziali sono davvero cambiati (v4.35.0). Evita di muovere centinaia di schede a vuoto.
 *
 * @param int                   $post_id      ID del CPT videogioco.
 * @param string                $new_title    Titolo aggiornato del gioco.
 * @param array<string, string> $old_snapshot Snapshot raccolto prima del salvataggio dei meta.
 * @return bool True se il post è stato aggiornato, false se i dati erano identici.
 */
function ntn_core_igdb_maybe_update_videogioco_post( $post_id, $new_title, array $old_snapshot ) {
	$post_id      = (int) $post_id;
	$new_snapshot = ntn_core_igdb_videogioco_snapshot( $post_id );
	$clean_title  = ntn_core_igdb_normalize_snapshot_value( $new_title );
	if ( '' !== $clean_title ) {
		$new_snapshot['post_title'] = $clean_title;
	}
	if ( $old_snapshot === $new_snapshot ) {
		return false;
	}
	wp_update_post(
		array(
			'ID'         => $post_id,
			'post_title' => '' !== $clean_title ? $new_title : get_post_field( 'post_title', $post_id ),
		)
	);
	return true;
}

/**
 * Svuota i transient della sitemap di The SEO Framework quando cambia la data di una pagina o scheda (v4.35.0).
 */
function ntn_core_clear_tsf_sitemap_cache() {
	if ( class_exists( '\The_SEO_Framework\Sitemap\Cache' ) && method_exists( '\The_SEO_Framework\Sitemap\Cache', 'clear_sitemap_caches' ) ) {
		\The_SEO_Framework\Sitemap\Cache::clear_sitemap_caches();
	}
}

/**
 * Aggiorna post_modified / post_modified_gmt senza creare revisioni e solo per avanzare nel tempo (v4.35.0).
 *
 * @param int    $target_id       ID del post da aggiornare (es. pagina Home o scheda videogioco).
 * @param string $modified_local  Data locale MySQL (Y-m-d H:i:s).
 * @param string $modified_gmt    Data GMT MySQL (Y-m-d H:i:s).
 * @return bool True se aggiornato, false altrimenti.
 */
function ntn_core_bump_post_modified_direct( $target_id, $modified_local, $modified_gmt ) {
	global $wpdb;

	$target_id      = (int) $target_id;
	$modified_local = (string) $modified_local;
	$modified_gmt   = (string) $modified_gmt;
	if ( $target_id <= 0 || '' === $modified_gmt || '0000-00-00 00:00:00' === $modified_gmt ) {
		return false;
	}

	$target = get_post( $target_id );
	if ( ! $target instanceof WP_Post ) {
		return false;
	}

	$current_ts = strtotime( (string) $target->post_modified_gmt );
	$new_ts     = strtotime( $modified_gmt );
	if ( false === $new_ts || ( false !== $current_ts && $new_ts <= $current_ts ) ) {
		return false;
	}

	$updated = $wpdb->update(
		$wpdb->posts,
		array(
			'post_modified'     => $modified_local,
			'post_modified_gmt' => $modified_gmt,
		),
		array( 'ID' => $target_id ),
		array( '%s', '%s' ),
		array( '%d' )
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- aggiornamento mirato della sola data lastmod senza creare revisioni.

	if ( $updated ) {
		clean_post_cache( $target_id );
		return true;
	}
	return false;
}

/**
 * Risolve l'ID della scheda CPT videogioco collegata a un tag gioco (v4.35.0).
 *
 * @param WP_Term $term Tag gioco risolto.
 * @return int ID della scheda videogioco pubblicata, oppure 0 se assente.
 */
function ntn_core_find_linked_videogioco_id( WP_Term $term ) {
	$direct_id = (int) get_term_meta( $term->term_id, '_ntn_linked_videogioco_id', true );
	if ( $direct_id > 0 && 'videogioco' === get_post_type( $direct_id ) && 'publish' === get_post_status( $direct_id ) ) {
		return $direct_id;
	}

	$igdb_id = (int) get_term_meta( $term->term_id, '_ntn_igdb_game_id', true );
	if ( $igdb_id > 0 ) {
		$by_igdb = get_posts(
			array(
				'post_type'        => 'videogioco',
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'meta_key'         => '_ntn_igdb_game_id',
				'meta_value'       => (string) $igdb_id,
				'suppress_filters' => true,
			)
		);
		if ( ! empty( $by_igdb[0] ) ) {
			return (int) $by_igdb[0];
		}
	}

	$by_slug = get_posts(
		array(
			'name'             => $term->slug,
			'post_type'        => 'videogioco',
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);
	return ! empty( $by_slug[0] ) ? (int) $by_slug[0] : 0;
}

/**
 * Aggiorna il post_modified delle schede videogioco collegate a un articolo pubblicato (v4.35.0).
 *
 * @param int     $post_id ID dell'articolo.
 * @param WP_Post $post    Oggetto post.
 * @return bool True se almeno una scheda Hub è stata aggiornata.
 */
function ntn_core_bump_linked_videogioco_hubs( $post_id, WP_Post $post ) {
	if ( 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return false;
	}

	$tags = get_the_tags( $post_id );
	if ( empty( $tags ) || is_wp_error( $tags ) ) {
		return false;
	}

	$changed  = false;
	$seen_hub = array();
	foreach ( $tags as $tag ) {
		if ( ! $tag instanceof WP_Term ) {
			continue;
		}
		$game_term = function_exists( 'ntn_resolve_game_term' ) ? ntn_resolve_game_term( $tag ) : $tag;
		if ( ! $game_term instanceof WP_Term ) {
			continue;
		}
		$hub_id = ntn_core_find_linked_videogioco_id( $game_term );
		if ( $hub_id > 0 && empty( $seen_hub[ $hub_id ] ) ) {
			$seen_hub[ $hub_id ] = true;
			if ( ntn_core_bump_post_modified_direct( $hub_id, $post->post_date, $post->post_date_gmt ) ) {
				$changed = true;
			}
		}
	}
	return $changed;
}

/**
 * Mantiene aggiornata la data lastmod della Homepage statica e delle Hub videogioco collegate
 * quando viene pubblicato un articolo o comunicato stampa (v4.35.0).
 *
 * @param int          $post_id ID del post salvato.
 * @param WP_Post|null $post    Oggetto post.
 */
function ntn_core_sync_sitemap_lastmod_on_save( $post_id, $post = null ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$post = $post instanceof WP_Post ? $post : get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		return;
	}
	if ( ! in_array( $post->post_type, array( 'post', 'comunicato_stampa' ), true ) ) {
		return;
	}

	$changed = false;

	// 1. Homepage statica: allinea la sua lastmod alla data di pubblicazione dell'articolo.
	if ( 'page' === get_option( 'show_on_front' ) ) {
		$front_id = (int) get_option( 'page_on_front' );
		if ( $front_id > 0 && ntn_core_bump_post_modified_direct( $front_id, $post->post_date, $post->post_date_gmt ) ) {
			$changed = true;
		}
	}

	// 2. Schede /videogioco/ collegate all'articolo: un nuovo articolo arricchisce la Hub.
	if ( ntn_core_bump_linked_videogioco_hubs( $post_id, $post ) ) {
		$changed = true;
	}

	if ( $changed ) {
		ntn_core_clear_tsf_sitemap_cache();
	}
}
add_action( 'save_post', 'ntn_core_sync_sitemap_lastmod_on_save', 30, 2 );

/**
 * Aggiorna la lastmod delle Hub collegate anche quando i tag vengono salvati separatamente via REST/Gutenberg (v4.35.0).
 *
 * @param int    $object_id ID del post.
 * @param array  $terms     Termini assegnati.
 * @param array  $tt_ids    Term taxonomy IDs.
 * @param string $taxonomy  Tassonomia modificata.
 */
function ntn_core_sync_sitemap_lastmod_on_terms( $object_id, $terms, $tt_ids, $taxonomy ) {
	if ( 'post_tag' !== $taxonomy ) {
		return;
	}
	$post = get_post( (int) $object_id );
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status || 'post' !== $post->post_type ) {
		return;
	}
	if ( ntn_core_bump_linked_videogioco_hubs( (int) $object_id, $post ) ) {
		ntn_core_clear_tsf_sitemap_cache();
	}
}
add_action( 'set_object_terms', 'ntn_core_sync_sitemap_lastmod_on_terms', 20, 4 );

/**
 * Riallinea una sola volta in bacheca la data lastmod della Homepage statica all'ultimo articolo pubblicato (v4.35.0).
 */
function ntn_core_maybe_sync_front_page_lastmod_once() {
	if ( 'page' !== get_option( 'show_on_front' ) ) {
		return;
	}
	$synced_ver = get_option( 'ntn_core_front_lastmod_synced', '' );
	if ( NTN_JOURNAL_CORE_VERSION === $synced_ver ) {
		return;
	}
	$front_id = (int) get_option( 'page_on_front' );
	if ( $front_id > 0 ) {
		$latest = get_posts(
			array(
				'post_type'        => array( 'post', 'comunicato_stampa' ),
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => true,
			)
		);
		if ( ! empty( $latest[0] ) && $latest[0] instanceof WP_Post ) {
			if ( ntn_core_bump_post_modified_direct( $front_id, $latest[0]->post_date, $latest[0]->post_date_gmt ) ) {
				ntn_core_clear_tsf_sitemap_cache();
			}
		}
	}
	update_option( 'ntn_core_front_lastmod_synced', NTN_JOURNAL_CORE_VERSION, false );
}
add_action( 'admin_init', 'ntn_core_maybe_sync_front_page_lastmod_once' );

/**
 * Percorsi di servizio da escludere in robots.txt per evitare sprechi di scansione (v4.35.0).
 *
 * @param string $site_path Eventuale sottocartella del sito (es. "/blog").
 * @return string[]
 */
function ntn_core_robots_crawl_disallow_paths( $site_path = '' ) {
	$base = trim( (string) $site_path );
	if ( '' === $base ) {
		$parsed = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$base   = rtrim( $parsed, '/' );
	} else {
		$base = rtrim( $base, '/' );
	}
	if ( '/' === $base ) {
		$base = '';
	}
	return array(
		$base . '/?s=',
		$base . '/search/',
		$base . '/*?replytocom=',
		$base . '/wp-login.php',
		$base . '/wp-register.php',
	);
}

/**
 * URL della Google News Sitemap se attiva nelle impostazioni SEO (v4.35.0).
 *
 * @return string
 */
function ntn_core_robots_news_sitemap_url() {
	if ( 'yes' !== get_option( 'ntn_seo_enable_news_sitemap', 'yes' ) ) {
		return '';
	}
	return home_url( '/news-sitemap.xml' );
}

/**
 * Arricchisce il robots.txt generato da The SEO Framework con news-sitemap.xml e i divieti di servizio (v4.35.0).
 *
 * @param array  $sections  Sezioni robots.txt di TSF.
 * @param string $site_path Percorso base del sito.
 * @return array
 */
function ntn_core_tsf_robots_txt_sections( $sections, $site_path = '' ) {
	if ( ! is_array( $sections ) ) {
		return $sections;
	}

	if ( ! isset( $sections['default'] ) || ! is_array( $sections['default'] ) ) {
		$sections['default'] = array(
			'user-agent' => array( '*' ),
			'disallow'   => array(),
		);
	}
	$existing_disallow = isset( $sections['default']['disallow'] ) ? (array) $sections['default']['disallow'] : array();
	foreach ( ntn_core_robots_crawl_disallow_paths( $site_path ) as $rule ) {
		if ( ! in_array( $rule, $existing_disallow, true ) ) {
			$existing_disallow[] = $rule;
		}
	}
	$sections['default']['disallow'] = $existing_disallow;

	$news_sitemap = ntn_core_robots_news_sitemap_url();
	if ( '' !== $news_sitemap ) {
		if ( ! isset( $sections['sitemaps'] ) || ! is_array( $sections['sitemaps'] ) ) {
			$sections['sitemaps'] = array(
				'sitemaps' => array(),
				'priority' => 1000,
			);
		}
		$sitemaps = isset( $sections['sitemaps']['sitemaps'] ) ? (array) $sections['sitemaps']['sitemaps'] : array();
		if ( ! in_array( $news_sitemap, $sitemaps, true ) ) {
			$sitemaps[] = $news_sitemap;
		}
		$sections['sitemaps']['sitemaps'] = $sitemaps;
	}

	return $sections;
}
add_filter( 'the_seo_framework_robots_txt_sections', 'ntn_core_tsf_robots_txt_sections', 20, 2 );

/**
 * Riserva per robots.txt nativo di WordPress quando The SEO Framework non è attivo (v4.35.0).
 *
 * @param string $output Contenuto robots.txt.
 * @param bool   $public Flag visibilità motori di ricerca.
 * @return string
 */
function ntn_core_wp_robots_txt( $output, $public = true ) {
	if ( ! $public || '0' === (string) $public ) {
		return $output;
	}
	if ( defined( 'THE_SEO_FRAMEWORK_PRESENT' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
		return $output;
	}

	$output = (string) $output;
	$extra  = array();
	foreach ( ntn_core_robots_crawl_disallow_paths() as $rule ) {
		if ( false === strpos( $output, 'Disallow: ' . $rule ) ) {
			$extra[] = 'Disallow: ' . $rule;
		}
	}
	if ( ! empty( $extra ) ) {
		if ( false === stripos( $output, 'User-agent:' ) ) {
			$output = "User-agent: *\n" . $output;
		}
		$output = rtrim( $output ) . "\n" . implode( "\n", $extra ) . "\n";
	}

	$news_sitemap = ntn_core_robots_news_sitemap_url();
	if ( '' !== $news_sitemap && false === strpos( $output, $news_sitemap ) ) {
		$output = rtrim( $output ) . "\n\nSitemap: " . $news_sitemap . "\n";
	}

	return $output;
}
add_filter( 'robots_txt', 'ntn_core_wp_robots_txt', 20, 2 );
