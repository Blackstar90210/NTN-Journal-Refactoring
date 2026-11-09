<?php
/**
 * NTN Journal - Custom Taxonomies
 *
 * Registra la tassonomia "Motore Grafico" (game_engine) con URL rewrite /motore-grafico/[slug]
 *
 * @package NTN_Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ntn_register_game_engine_taxonomy() {
	$labels = array(
		'name'                       => 'Motori Grafici',
		'singular_name'              => 'Motore Grafico',
		'search_items'               => 'Cerca Motori Grafici',
		'popular_items'              => 'Motori Grafici Popolari',
		'all_items'                  => 'Tutti i Motori Grafici',
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => 'Modifica Motore Grafico',
		'update_item'                => 'Aggiorna Motore Grafico',
		'add_new_item'               => 'Aggiungi Nuovo Motore Grafico',
		'new_item_name'              => 'Nome Nuovo Motore Grafico',
		'separate_items_with_commas' => 'Separa i motori grafici con le virgole',
		'add_or_remove_items'        => 'Aggiungi o rimuovi motori grafici',
		'choose_from_most_used'      => 'Scegli tra i motori grafici più usati',
		'not_found'                  => 'Nessun motore grafico trovato.',
		'menu_name'                  => 'Motori Grafici',
	);

	$args = array(
		'hierarchical'          => false,
		'labels'                => $labels,
		'show_ui'               => true,
		'show_admin_column'     => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'show_in_rest'          => true,
		'rewrite'               => array(
			'slug'         => 'motore-grafico',
			'with_front'   => false,
			'hierarchical' => false,
		),
	);

	register_taxonomy( 'game_engine', array( 'post', 'videogioco' ), $args );
}
add_action( 'init', 'ntn_register_game_engine_taxonomy', 0 );

function ntn_register_additional_game_taxonomies() {
	$taxonomies = array(
		'game_franchise' => array(
			'name' => 'Franchise',
			'singular' => 'Franchise',
			'slug' => 'franchise',
		),
		'game_developer' => array(
			'name' => 'Sviluppatori',
			'singular' => 'Sviluppatore',
			'slug' => 'sviluppatore',
		),
		'game_publisher' => array(
			'name' => 'Editori',
			'singular' => 'Editore',
			'slug' => 'editore',
		),
		'game_year' => array(
			'name' => 'Anno di Uscita',
			'singular' => 'Anno',
			'slug' => 'anno-uscita',
		),
	);

	foreach ( $taxonomies as $tax_key => $tax_data ) {
		$labels = array(
			'name'                       => $tax_data['name'],
			'singular_name'              => $tax_data['singular'],
			'search_items'               => 'Cerca ' . $tax_data['name'],
			'all_items'                  => 'Tutti ' . $tax_data['name'],
			'edit_item'                  => 'Modifica ' . $tax_data['singular'],
			'update_item'                => 'Aggiorna ' . $tax_data['singular'],
			'add_new_item'               => 'Aggiungi Nuovo ' . $tax_data['singular'],
			'new_item_name'              => 'Nome Nuovo ' . $tax_data['singular'],
			'menu_name'                  => $tax_data['name'],
		);

		$args = array(
			'hierarchical'          => false,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'query_var'             => true,
			'show_in_rest'          => true,
			'rewrite'               => array(
				'slug'         => $tax_data['slug'],
				'with_front'   => false,
				'hierarchical' => false,
			),
		);

		register_taxonomy( $tax_key, array( 'post', 'videogioco' ), $args );
	}
}
add_action( 'init', 'ntn_register_additional_game_taxonomies', 0 );


/**
 * Aggiunge la regola di riscrittura fallback per le Hub Page (Videogioco)
 * In modo che /videogioco/slug porti al tag solo se il CPT videogioco non esiste.
 */
function ntn_add_videogioco_rewrite_rule() {
	if ( get_option( 'ntn_journal_enable_videogame_links', 'yes' ) === 'yes' ) {
		add_rewrite_rule( '^videogioco/([^/]+)/?$', 'index.php?tag=$matches[1]', 'bottom' );
	}
}
add_action( 'init', 'ntn_add_videogioco_rewrite_rule', 10 );

function ntn_get_cpt_videogioco_link_by_slug( $slug ) {
	$cache_key = 'ntn_vg_link_' . md5( $slug );
	$link = get_transient( $cache_key );

	if ( false === $link ) {
		$posts = get_posts( array(
			'name'           => $slug,
			'post_type'      => 'videogioco',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );

		if ( ! empty( $posts ) ) {
			$link = get_permalink( $posts[0] );
		} else {
			$link = '';
		}

		set_transient( $cache_key, $link, 12 * HOUR_IN_SECONDS );
	}

	return $link;
}

/**
 * Modifica il link dei tag se sono Hub Page (Videogioco)
 */
function ntn_filter_videogioco_term_link( $url, $term, $taxonomy ) {
	if ( get_option( 'ntn_journal_enable_videogame_links', 'yes' ) !== 'yes' ) {
		return $url;
	}

	if ( 'post_tag' === $taxonomy ) {
		$term_obj = ( $term instanceof WP_Term ) ? $term : ( is_numeric( $term ) ? get_term( intval( $term ), $taxonomy ) : null );
		if ( $term_obj instanceof WP_Term ) {
			$game_title = get_term_meta( $term_obj->term_id, '_ntn_game_title', true );
			if ( ! empty( $game_title ) ) {
				$cpt_link = ntn_get_cpt_videogioco_link_by_slug( $term_obj->slug );
				if ( ! empty( $cpt_link ) ) {
					return $cpt_link;
				}

				return home_url( user_trailingslashit( '/videogioco/' . $term_obj->slug ) );
			}
		}
	}
	return $url;
}
add_filter( 'term_link', 'ntn_filter_videogioco_term_link', 10, 3 );

function ntn_invalidate_tag_to_cpt_map( $post_id, $post, $update ) {
	if ( ! $post || 'videogioco' !== $post->post_type ) {
		return;
	}
	delete_transient( 'ntn_vg_link_' . md5( $post->post_name ) );
	delete_transient( 'ntn_vg_link_map' );
}
add_action( 'save_post_videogioco', 'ntn_invalidate_tag_to_cpt_map', 10, 3 );

/**
 * Rigenera le regole di riscrittura URL.
 */
function ntn_flush_custom_rewrite_rules() {
	if ( get_option( 'ntn_rewrite_rules_flushed_v3' ) !== '1' ) {
		ntn_register_game_engine_taxonomy();
		ntn_register_additional_game_taxonomies();
		ntn_add_videogioco_rewrite_rule();
		flush_rewrite_rules();
		update_option( 'ntn_rewrite_rules_flushed_v3', '1' );
	}
}
add_action( 'init', 'ntn_flush_custom_rewrite_rules', 99 );

/**
 * Modifica la query principale per l'archivio motori grafici (game_engine).
 * Trova sia i post con il termine "game_engine" assegnato nativamente,
 * sia i post collegati tramite tag di gioco (Hub Page) aventi il motore grafico corrispondente.
 */
function ntn_game_engine_archive_query( $query ) {
	if ( ! is_admin() && $query->is_main_query() && is_tax( 'game_engine' ) ) {
		$engine_term = get_queried_object();
		if ( $engine_term ) {
			$engine_name = $engine_term->name;

			$cache_key = 'ntn_engine_tags_' . $engine_term->term_id;
			$tag_ids = get_transient( $cache_key );

			if ( false === $tag_ids ) {
				$tag_ids = array();
				
				// Cerca tutti i tag che hanno questo motore grafico nel term meta
				$tags = get_terms( array(
					'taxonomy'   => 'post_tag',
					'hide_empty' => false,
					'meta_query' => array(
						array(
							'key'     => '_ntn_game_engine',
							'value'   => $engine_name,
							'compare' => 'LIKE',
						),
					),
				) );

				if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
					foreach ( $tags as $t ) {
						$tag_ids[] = $t->term_id;
					}
				}

				// Cerca anche i CPT videogioco che hanno questo motore grafico
				$cpts = get_posts( array(
					'post_type'              => 'videogioco',
					'post_status'            => 'publish',
					'posts_per_page'         => -1,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'tax_query'              => array(
						array(
							'taxonomy' => 'game_engine',
							'field'    => 'term_id',
							'terms'    => $engine_term->term_id,
						),
					),
				) );

				if ( ! empty( $cpts ) ) {
					foreach ( $cpts as $cpt_p ) {
						$tag_match = get_term_by( 'slug', $cpt_p->post_name, 'post_tag' );
						if ( ! $tag_match || is_wp_error( $tag_match ) ) {
							$tag_match = get_term_by( 'name', $cpt_p->post_title, 'post_tag' );
						}
						if ( $tag_match && ! is_wp_error( $tag_match ) && ! in_array( $tag_match->term_id, $tag_ids ) ) {
							$tag_ids[] = $tag_match->term_id;
						}
					}
				}
				
				set_transient( $cache_key, $tag_ids, 12 * HOUR_IN_SECONDS );
			}

			$query->set( 'game_engine', '' );
			$tax_query = array( 'relation' => 'OR' );
			$tax_query[] = array(
				'taxonomy' => 'game_engine',
				'field'    => 'term_id',
				'terms'    => $engine_term->term_id,
			);
			if ( ! empty( $tag_ids ) ) {
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $tag_ids,
				);
			}
			$query->set( 'tax_query', $tax_query );
		}
	}
}
add_action( 'pre_get_posts', 'ntn_game_engine_archive_query' );
