<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Pagina "Redazione" (v3.17.0): Hub degli autori, divisa in gruppi personalizzati.
 *
 * - v3.18.0: gruppi e persone si governano dalla scheda "Redazione" di NTN Journal Funzioni
 *   (gruppi da riordinare, persone da aggiungere e togliere, ordine dentro i gruppi).
 * - I dati restano dove erano: i gruppi nella pagina con il modello "Redazione"
 *   (_ntn_team_groups, uno per riga), il gruppo di ogni persona nel suo profilo (_ntn_team_group).
 * - Dentro ogni gruppo l'ordine è alfabetico, a mano o per numero di articoli; un gruppo senza persone non compare.
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html (pagina Redazione, versione 25).
 *
 * @package NTN_Journal_Core
 */

define( 'NTN_CORE_TEAM_TEMPLATE', 'page-redazione.php' );

/*
==========================================================================
	1. Gruppi: scritti nella pagina Redazione, uno per riga
	========================================================================== */

/**
 * La pagina che usa il modello "Redazione" (la prima, se sono più di una).
 *
 * @return WP_Post|null
 */
function ntn_core_team_page() {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- una sola pagina, in cache.
			'meta_value'     => NTN_CORE_TEAM_TEMPLATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	return ! empty( $pages ) && $pages[0] instanceof WP_Post ? $pages[0] : null;
}

/**
 * Gruppi della redazione, nell'ordine scelto.
 *
 * @param int $page_id ID della pagina Redazione (0 = la cerca).
 * @return string[]
 */
function ntn_core_team_groups( $page_id = 0 ) {
	if ( ! $page_id ) {
		$page    = ntn_core_team_page();
		$page_id = $page ? (int) $page->ID : 0;
	}
	if ( ! $page_id ) {
		return array();
	}
	$raw    = (string) get_post_meta( (int) $page_id, '_ntn_team_groups', true );
	$groups = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( wp_strip_all_tags( $line ) );
		if ( '' !== $line && ! in_array( $line, $groups, true ) ) {
			$groups[] = $line;
		}
	}
	return $groups;
}

/*
==========================================================================
	2. Salvataggio dalla scheda "Redazione" di NTN Journal Funzioni (v3.18.0)
	Il riquadro nella pagina e la tendina nel profilo sono stati tolti: i dati restano
	dove erano (gruppi nella pagina, gruppo di ogni persona nel suo profilo).
	========================================================================== */

/**
 * Ordine delle persone dentro i gruppi.
 *
 * @return string alpha | manual | posts
 */
function ntn_core_team_order_mode() {
	$mode = (string) get_option( 'ntn_team_order', 'alpha' );
	return in_array( $mode, array( 'alpha', 'manual', 'posts' ), true ) ? $mode : 'alpha';
}

/**
 * Salva tutta la redazione (chiamata dalla scheda, che controlla nonce e permessi).
 *
 * @param int    $page_id ID della pagina Redazione.
 * @param array  $groups  Gruppi nell'ordine voluto: array( array( 'name' => 'Fondatori', 'people' => array( 3, 7 ) ), ... ).
 * @param string $order   alpha | manual | posts.
 */
function ntn_core_team_save( $page_id, array $groups, $order = 'alpha' ) {
	$names    = array();
	$assigned = array();
	foreach ( $groups as $group ) {
		$name = trim( sanitize_text_field( isset( $group['name'] ) ? (string) $group['name'] : '' ) );
		if ( '' === $name || in_array( $name, $names, true ) ) {
			continue;
		}
		$names[] = $name;
		$pos     = 0;
		foreach ( isset( $group['people'] ) ? (array) $group['people'] : array() as $user_id ) {
			$user_id = absint( $user_id );
			if ( ! $user_id || isset( $assigned[ $user_id ] ) || ! get_userdata( $user_id ) ) {
				continue; // Una persona sta in un solo gruppo
			}
			$assigned[ $user_id ] = true;
			update_user_meta( $user_id, '_ntn_team_group', $name );
			update_user_meta( $user_id, '_ntn_team_order', $pos++ );
		}
	}

	// Chi non è più in nessun gruppo torna "Non mostrare"
	foreach ( get_users(
		array(
			'meta_key' => '_ntn_team_group',
			'fields'   => 'ID',
		)
	) as $user_id ) { // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- poche persone.
		if ( ! isset( $assigned[ (int) $user_id ] ) ) {
			delete_user_meta( (int) $user_id, '_ntn_team_group' );
			delete_user_meta( (int) $user_id, '_ntn_team_order' );
		}
	}

	if ( $page_id ) {
		update_post_meta( (int) $page_id, '_ntn_team_groups', implode( "\n", $names ) );
	}
	update_option( 'ntn_team_order', in_array( $order, array( 'alpha', 'manual', 'posts' ), true ) ? $order : 'alpha', false );
	ntn_core_team_flush();
}

/*
==========================================================================
	3. Dati della pagina (in cache)
	========================================================================== */

/**
 * Invalida la cache della pagina Redazione.
 */
function ntn_core_team_flush() {
	update_option( 'ntn_team_version', (string) microtime( true ), false );
}
add_action( 'save_post_post', 'ntn_core_team_flush' );
add_action( 'deleted_post', 'ntn_core_team_flush' );
add_action( 'profile_update', 'ntn_core_team_flush' );
add_action( 'deleted_user', 'ntn_core_team_flush' );

/**
 * Gruppi con le loro persone, pronti per il modello del tema.
 *
 * @param int $page_id ID della pagina Redazione.
 * @return array{groups: array<int,array{name:string,people:array}>, count:int, updated:string}
 */
function ntn_core_get_team_data( $page_id ) {
	$page_id   = (int) $page_id;
	$cache_key = 'ntn_team_' . md5( $page_id . '|' . get_option( 'ntn_team_version', '0' ) );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$groups = ntn_core_team_groups( $page_id );
	$data   = array(
		'groups'  => array(),
		'count'   => 0,
		'updated' => '',
	);
	if ( empty( $groups ) ) {
		return $data;
	}

	$users = get_users(
		array(
			'meta_key'     => '_ntn_team_group', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- poche persone, in cache.
			'meta_value'   => $groups, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_compare' => 'IN',
			'fields'       => array( 'ID', 'display_name' ),
		)
	);

	$by_group = array_fill_keys( $groups, array() );
	$latest   = 0;
	foreach ( $users as $user ) {
		$group = (string) get_user_meta( $user->ID, '_ntn_team_group', true );
		if ( ! isset( $by_group[ $group ] ) ) {
			continue;
		}
		$last  = get_posts(
			array(
				'author'         => (int) $user->ID,
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);
		$entry = array(
			'id'    => (int) $user->ID,
			'name'  => (string) $user->display_name,
			'url'   => (string) get_author_posts_url( $user->ID ),
			'last'  => null,
			'order' => (int) get_user_meta( $user->ID, '_ntn_team_order', true ),
			'posts' => (int) count_user_posts( $user->ID, 'post', true ),
		);
		if ( ! empty( $last ) ) {
			$entry['last'] = array(
				'title' => get_the_title( $last[0] ),
				'url'   => (string) get_permalink( $last[0] ),
				'date'  => (string) get_the_date( 'd.m.Y', $last[0] ),
			);
			$latest        = max( $latest, (int) get_post_timestamp( $last[0] ) );
		}
		$by_group[ $group ][] = $entry;
	}

	foreach ( $by_group as $name => $people ) {
		if ( empty( $people ) ) {
			continue; // Un gruppo senza persone non compare
		}
		$mode = ntn_core_team_order_mode();
		usort(
			$people,
			static function ( $a, $b ) use ( $mode ) {
				if ( 'manual' === $mode && $a['order'] !== $b['order'] ) {
					return $a['order'] <=> $b['order'];
				}
				if ( 'posts' === $mode && $a['posts'] !== $b['posts'] ) {
					return $b['posts'] <=> $a['posts'];
				}
				return strnatcasecmp( remove_accents( $a['name'] ), remove_accents( $b['name'] ) );
			}
		);
		$data['groups'][] = array(
			'name'   => (string) $name,
			'people' => $people,
		);
		$data['count']   += count( $people );
	}
	$data['updated'] = $latest ? (string) wp_date( 'd.m.Y', $latest ) : '';

	set_transient( $cache_key, $data, 6 * HOUR_IN_SECONDS );
	return $data;
}

/*
==========================================================================
	4. Dati per Google: la redazione come elenco di persone
	========================================================================== */

/**
 * ItemList di Person sulla pagina Redazione.
 */
function ntn_core_team_schema() {
	// Obbedisce al freno d'emergenza dei dati per Google (v3.17.1)
	if ( ! is_page() || ( function_exists( 'ntn_core_schema_enabled' ) && ! ntn_core_schema_enabled() ) ) {
		return;
	}
	$page = get_queried_object();
	if ( ! $page instanceof WP_Post || NTN_CORE_TEAM_TEMPLATE !== get_page_template_slug( $page ) ) {
		return;
	}
	$team = ntn_core_get_team_data( $page->ID );
	if ( empty( $team['groups'] ) ) {
		return;
	}
	$items = array();
	$pos   = 1;
	foreach ( $team['groups'] as $group ) {
		foreach ( $group['people'] as $person ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $pos++,
				'item'     => array(
					'@type'    => 'Person',
					'name'     => $person['name'],
					'url'      => $person['url'],
					'jobTitle' => $group['name'],
					'worksFor' => array(
						'@type' => 'Organization',
						'name'  => wp_strip_all_tags( (string) get_bloginfo( 'name' ) ),
						'url'   => home_url( '/' ),
					),
				),
			);
		}
	}
	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => wp_strip_all_tags( get_the_title( $page ) ),
		'url'             => (string) get_permalink( $page ),
		'numberOfItems'   => count( $items ),
		'itemListElement' => $items,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_footer', 'ntn_core_team_schema' );
