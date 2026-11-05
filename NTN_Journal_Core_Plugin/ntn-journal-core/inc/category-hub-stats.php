<?php
/**
 * NTN Journal - Statistiche dell'Hub di categoria (v4.1.0)
 *
 * Solo dati, nessuna interfaccia: articoli totali e dell'anno, autore più prolifico dell'anno,
 * data dell'ultimo articolo e videogiochi distinti collegati agli articoli. Il tema legge
 * tramite ntn_core_get_category_hub_stats(), sempre dietro function_exists().
 *
 * I conteggi sono fatti dal database con poche query, senza scorrere gli articoli uno a uno.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calcola le statistiche di un insieme di categorie, senza cache.
 *
 * Un gioco è un tag con ID IGDB o titolo del gioco, come in ntn_core_category_hub_is_game_tag().
 * Due tag con lo stesso ID IGDB (o, senza ID, con lo stesso titolo) contano come un solo gioco.
 *
 * @param int[] $cat_ids Categorie (di solito l'albero restituito da ntn_core_category_hub_tree()).
 * @param int   $year    Anno solare, nel fuso orario del sito.
 * @return array{total: int, in_year: int, latest: string, author_id: int, author_count: int, games: int}
 */
function ntn_core_category_hub_stats_query( array $cat_ids, int $year ): array {
	global $wpdb;

	$stats   = array( 'total' => 0, 'in_year' => 0, 'latest' => '', 'author_id' => 0, 'author_count' => 0, 'games' => 0 );
	$cat_ids = array_values( array_unique( array_filter( array_map( 'intval', $cat_ids ) ) ) );
	if ( empty( $cat_ids ) ) {
		return $stats;
	}

	$in         = implode( ',', $cat_ids );
	$year_start = sprintf( '%04d-01-01 00:00:00', $year );
	$year_end   = sprintf( '%04d-01-01 00:00:00', $year + 1 );

	$joins = "INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id";
	$where = "WHERE p.post_type = 'post' AND p.post_status = 'publish' AND tt.taxonomy = 'category' AND tt.term_id IN ({$in})";

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nomi di tabella di WordPress e ID già convertiti in interi.
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID) AS total,
				COUNT(DISTINCT CASE WHEN p.post_date >= %s AND p.post_date < %s THEN p.ID END) AS in_year,
				MAX(p.post_date) AS latest
			FROM {$wpdb->posts} p {$joins} {$where}",
			$year_start,
			$year_end
		)
	);
	if ( ! $row || 0 === (int) $row->total ) {
		return $stats;
	}
	$stats['total']   = (int) $row->total;
	$stats['in_year'] = (int) $row->in_year;
	$stats['latest']  = (string) $row->latest;

	if ( $stats['in_year'] > 0 ) {
		// A parità di articoli vince chi ha pubblicato per ultimo
		$author = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT p.post_author AS author_id, COUNT(DISTINCT p.ID) AS posts
				FROM {$wpdb->posts} p {$joins} {$where} AND p.post_date >= %s AND p.post_date < %s
				GROUP BY p.post_author
				ORDER BY posts DESC, MAX(p.post_date) DESC, p.post_author ASC
				LIMIT 1",
				$year_start,
				$year_end
			)
		);
		if ( $author ) {
			$stats['author_id']    = (int) $author->author_id;
			$stats['author_count'] = (int) $author->posts;
		}
	}

	$stats['games'] = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT CASE WHEN gid.meta_value NOT IN ('', '0') THEN CONCAT('id:', gid.meta_value) ELSE CONCAT('title:', gtitle.meta_value) END)
			FROM {$wpdb->posts} p {$joins}
			INNER JOIN {$wpdb->term_relationships} gtr ON gtr.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} gtt ON gtt.term_taxonomy_id = gtr.term_taxonomy_id AND gtt.taxonomy = 'post_tag'
			LEFT JOIN {$wpdb->termmeta} gid ON gid.term_id = gtt.term_id AND gid.meta_key = %s
			LEFT JOIN {$wpdb->termmeta} gtitle ON gtitle.term_id = gtt.term_id AND gtitle.meta_key = %s
			{$where} AND ( gid.meta_value NOT IN ('', '0') OR gtitle.meta_value NOT IN ('', '0') )",
			'_ntn_igdb_game_id',
			'_ntn_game_title'
		)
	);
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	return $stats;
}

/**
 * Statistiche di una Hub di categoria (o del marchio scelto di una Hub Console), sottocategorie comprese.
 * Restano in cache per 24 ore o fino alla prossima invalidazione delle Hub; a inizio anno si ricalcolano da sole.
 *
 * @param WP_Term|int|null $term Categoria (null = corrente).
 * @return array{year: int, articles_total: int, articles_year: int, top_author: ?array{id: int, name: string, count: int, url: string}, latest_date: string, games_total: int}|null
 *         Null se la categoria non è una Hub. latest_date è nel formato d.m.Y, vuota se non ci sono articoli.
 */
function ntn_core_get_category_hub_stats( $term = null ): ?array {
	$ctx = ntn_core_category_hub_context( $term );
	if ( ! $ctx ) {
		return null;
	}
	$focus = $ctx['selected'] ? $ctx['selected'] : $ctx['root'];
	$year  = (int) wp_date( 'Y' );

	$cache_key = 'ntn_cat_hub_stats_' . md5( $focus->term_id . '|' . $year . '|' . get_option( 'ntn_cat_hub_version', '0' ) . '|' . ( defined( 'NTN_JOURNAL_CORE_VERSION' ) ? NTN_JOURNAL_CORE_VERSION : '' ) );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$raw        = ntn_core_category_hub_stats_query( ntn_core_category_hub_tree( (int) $focus->term_id ), $year );
	$top_author = null;
	if ( $raw['author_id'] > 0 ) {
		$author_name = (string) get_the_author_meta( 'display_name', $raw['author_id'] );
		if ( '' !== $author_name ) {
			$top_author = array(
				'id'    => $raw['author_id'],
				'name'  => $author_name,
				'count' => $raw['author_count'],
				'url'   => (string) get_author_posts_url( $raw['author_id'] ),
			);
		}
	}

	$data = array(
		'year'           => $year,
		'articles_total' => $raw['total'],
		'articles_year'  => $raw['in_year'],
		'top_author'     => $top_author,
		'latest_date'    => '' !== $raw['latest'] ? (string) mysql2date( 'd.m.Y', $raw['latest'] ) : '',
		'games_total'    => $raw['games'],
	);

	set_transient( $cache_key, $data, DAY_IN_SECONDS );
	return $data;
}

/**
 * La pagina corrente è una Hub di tipo Statistiche?
 *
 * @return bool
 */
function ntn_core_category_hub_stats_is_current(): bool {
	$ctx = is_category() ? ntn_core_category_hub_context() : null;
	return is_array( $ctx ) && 'statistiche' === $ctx['type'];
}

/**
 * Google, v4.4.0: la pagina Statistiche non elenca articoli, quindi /page/2/ e seguenti ripetono la prima.
 * Restano raggiungibili ma fuori dai risultati, con "segui i collegamenti", come le pagine sottili (seo-thin-pages.php).
 * La canonical non punta alla prima pagina: insieme al noindex darebbe a Google due segnali contrastanti.
 *
 * @param array      $meta Direttive robots di The SEO Framework.
 * @param array|null $args Argomenti di TSF (null = pagina corrente).
 * @return array
 */
function ntn_core_category_hub_stats_tsf_robots( $meta, $args = null ) {
	if ( is_array( $meta ) && null === $args && (int) get_query_var( 'paged' ) > 1 && ntn_core_category_hub_stats_is_current() ) {
		$meta['noindex'] = 'noindex';
	}
	return $meta;
}
add_filter( 'the_seo_framework_robots_meta_array', 'ntn_core_category_hub_stats_tsf_robots', 10, 2 );

/**
 * Stessa regola senza plugin SEO, con le direttive robots di WordPress.
 *
 * @param array $robots Direttive di WordPress.
 * @return array
 */
function ntn_core_category_hub_stats_wp_robots( $robots ) {
	if ( is_array( $robots ) && (int) get_query_var( 'paged' ) > 1 && ntn_core_category_hub_stats_is_current() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'ntn_core_category_hub_stats_wp_robots' );

/**
 * The SEO Framework: niente rel="prev" e rel="next", perché la pagina non ha paginazione visibile.
 *
 * @param array $tags Dati dei tag di TSF, per nome.
 * @return array
 */
function ntn_core_category_hub_stats_tsf_tags( $tags ) {
	if ( is_array( $tags ) && ntn_core_category_hub_stats_is_current() ) {
		unset( $tags['prev'], $tags['next'] );
	}
	return $tags;
}
add_filter( 'the_seo_framework_meta_render_data', 'ntn_core_category_hub_stats_tsf_tags' );

/**
 * Distanza di una data da oggi, da calcolare a ogni visita perché le statistiche restano in cache per un giorno.
 *
 * @param string $date Data nel formato d.m.Y.
 * @return string "oggi", "ieri", "N giorni fa"; vuota se la data non è valida o è nel futuro.
 */
function ntn_core_category_hub_days_ago( string $date ): string {
	$then  = DateTimeImmutable::createFromFormat( '!d.m.Y', $date );
	$today = DateTimeImmutable::createFromFormat( '!d.m.Y', (string) wp_date( 'd.m.Y' ) );
	if ( ! $then || ! $today || $then > $today ) {
		return '';
	}
	$days = (int) $then->diff( $today )->days;
	if ( 0 === $days ) {
		return 'oggi';
	}
	return 1 === $days ? 'ieri' : $days . ' giorni fa';
}

/**
 * Collegare o scollegare un gioco IGDB cambia i meta del tag, non il tag né l'articolo:
 * le cache delle Hub vanno invalidate anche in questo caso.
 *
 * @param int|int[] $meta_id  ID del meta (non usato).
 * @param int       $term_id  ID del tag (non usato).
 * @param string    $meta_key Chiave del meta.
 */
function ntn_core_category_hub_stats_flush_on_meta( $meta_id, $term_id, $meta_key ): void {
	if ( in_array( $meta_key, array( '_ntn_igdb_game_id', '_ntn_game_title' ), true ) ) {
		ntn_core_category_hub_flush();
	}
}
add_action( 'added_term_meta', 'ntn_core_category_hub_stats_flush_on_meta', 10, 3 );
add_action( 'updated_term_meta', 'ntn_core_category_hub_stats_flush_on_meta', 10, 3 );
add_action( 'deleted_term_meta', 'ntn_core_category_hub_stats_flush_on_meta', 10, 3 );
