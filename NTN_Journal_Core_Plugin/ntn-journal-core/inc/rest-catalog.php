<?php
/**
 * REST API Endpoints per il Catalogo.
 *
 * @package NTN_Journal_Core
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NTN_Catalog_REST_Controller {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route( 'ntn-journal/v1', '/games', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_games' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'page'      => array( 'type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint' ),
				'per_page'  => array( 'type' => 'integer', 'default' => 20, 'sanitize_callback' => 'absint' ),
				'franchise' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_title' ),
				'developer' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_title' ),
				'publisher' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_title' ),
				'engine'    => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_title' ),
				'year'      => array( 'type' => 'integer', 'sanitize_callback' => 'absint' ),
				'search'    => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
				'sort'      => array( 
					'type'    => 'string', 
					'enum'    => array( 'title_asc', 'title_desc', 'year_desc', 'year_asc' ), 
					'default' => 'year_desc' 
				),
			),
		) );

		register_rest_route( 'ntn-journal/v1', '/live-search', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_live_search' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'query' => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
			),
		) );
	}

	public function get_games( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$params = $request->get_params();

		// Hard-cap per_page a 20 (Anti-DoS come prescritto da Marco)
		if ( isset( $params['per_page'] ) && $params['per_page'] > 20 ) {
			$params['per_page'] = 20;
		}

		// v4.16.2: la chiave usa solo i parametri noti; le richieste con ricerca libera o filtri non vanno nel database
		$cache_key      = ntn_core_rest_games_cache_key( $params );
		$cache_in_db    = ntn_core_rest_games_uses_database( $params );
		$cached_payload = ntn_core_rest_cache_get( $cache_key, $cache_in_db );
		if ( false !== $cached_payload ) {
			$response = new WP_REST_Response( $cached_payload, 200 );
			$response->header( 'Cache-Control', 'public, max-age=300, stale-while-revalidate=60' );
			return $response;
		}

		if ( function_exists( 'ntn_get_catalog_query_args' ) ) {
			$args = ntn_get_catalog_query_args( $params );
		} else {
			return new WP_Error( 'ntn_error', 'Helper not found', array( 'status' => 500 ) );
		}

		// Disattivare SQL_CALC_FOUND_ROWS se non richiesto, ma nel catalogo lo usiamo per max_pages
		$query = new WP_Query( $args );

		$html = '';
		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				get_template_part( 'template-parts/content', 'game-card' );
			}
		} else {
			if ( $params['page'] === 1 ) {
				echo '<div class="ntn-empty col-span-full"><span>VUOTO</span><p>Nessun videogioco corrisponde ai criteri di ricerca.</p></div>';
			}
		}
		$html = ob_get_clean();

		wp_reset_postdata();

		$payload = array(
			'success'   => true,
			'data'      => array(
				'html'        => $html,
				'max_pages'   => $query->max_num_pages,
				'found_posts' => $query->found_posts,
			)
		);
		ntn_core_rest_cache_set( $cache_key, $payload, 300, $cache_in_db );

		$response = new WP_REST_Response( $payload, 200 );
		$response->header( 'Cache-Control', 'public, max-age=300, stale-while-revalidate=60' );
		
		return $response;
	}

	/**
	 * Suggerimenti della ricerca (v3.19.0): fino a 4 articoli e 3 giochi del catalogo, con il totale di
	 * ciascun gruppo. "results" resta il nome dei giochi, per compatibilità con le versioni precedenti del tema.
	 */
	public function get_live_search( WP_REST_Request $request ): WP_REST_Response {
		$search_term = trim( (string) $request->get_param( 'query' ) );
		$search_term = function_exists( 'mb_substr' ) ? mb_substr( $search_term, 0, 80 ) : substr( $search_term, 0, 80 );

		if ( strlen( $search_term ) < 2 ) {
			return new WP_REST_Response( array( 'success' => true, 'data' => array( 'results' => array(), 'articles' => array(), 'total_articles' => 0, 'total_games' => 0 ) ), 200 );
		}

		$cache_key = 'ntn_livesearch2_' . md5( strtolower( $search_term ) );
		// v4.16.2: il testo cercato è libero, quindi si salva solo nella cache in memoria (mai nel database)
		$cached = ntn_core_rest_cache_get( $cache_key, false );
		if ( is_array( $cached ) ) {
			$response = new WP_REST_Response( $cached, 200 );
			$response->header( 'Cache-Control', 'public, max-age=60' );
			return $response;
		}

		$articles = array();
		$posts_q  = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 4,
				's'                   => $search_term,
				'has_password'        => false,
				'ignore_sticky_posts' => true,
			)
		);
		foreach ( $posts_q->posts as $article ) {
			$kind = '';
			if ( '' !== (string) get_post_meta( $article->ID, 'rating', true ) ) {
				$kind = 'Recensione';
			} else {
				// La categoria principale, la stessa del percorso dell'articolo; altrimenti la prima
				$primary = ntn_get_primary_category( $article->ID );
				$cats    = $primary instanceof WP_Term ? array( $primary ) : get_the_category( $article->ID );
				if ( ! empty( $cats ) && $cats[0] instanceof WP_Term ) {
					$kind = $cats[0]->name;
				}
			}
			$thumb      = get_the_post_thumbnail_url( $article->ID, 'thumbnail' );
			$articles[] = array(
				'title' => html_entity_decode( get_the_title( $article ), ENT_QUOTES, 'UTF-8' ),
				'url'   => get_permalink( $article ),
				'thumb' => $thumb ? esc_url_raw( $thumb ) : '',
				'kind'  => html_entity_decode( $kind, ENT_QUOTES, 'UTF-8' ),
				'date'  => get_the_date( 'd.m.Y', $article ),
			);
		}
		$total_articles = (int) $posts_q->found_posts;

		$args = array(
			'post_type'              => 'videogioco',
			'post_status'            => 'publish',
			'posts_per_page'         => 3,
			's'                      => $search_term,
			'update_post_term_cache' => false,
		);

		$query = new WP_Query( $args );
		$total_games = (int) $query->found_posts;
		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();
				
				$cover = get_post_meta( $post_id, '_ntn_game_cover', true );
				if ( empty( $cover ) ) {
					$igdb_info = ntn_get_hub_igdb_info( $post_id );
					if ( ! empty( $igdb_info['cover'] ) ) {
						$cover = $igdb_info['cover'];
					}
				}
				if ( empty( $cover ) && has_post_thumbnail( $post_id ) ) {
					$cover = get_the_post_thumbnail_url( $post_id, 'medium_large' );
				}
				if ( ! empty( $cover ) && function_exists( 'ntn_normalize_game_cover_url' ) ) {
					$cover = ntn_normalize_game_cover_url( $cover );
				}

				// Studio e numero di articoli: dal tag del gioco con lo stesso slug (lo stesso legame delle Hub)
				$tag       = get_term_by( 'slug', get_post_field( 'post_name', $post_id ), 'post_tag' );
				$developer = (string) get_post_meta( $post_id, '_ntn_game_developer', true );
				if ( '' === $developer && $tag instanceof WP_Term ) {
					$developer = (string) get_term_meta( $tag->term_id, '_ntn_game_developer', true );
				}

				$results[] = array(
					'title'     => html_entity_decode( get_the_title(), ENT_QUOTES, 'UTF-8' ),
					'url'       => get_permalink(),
					'cover'     => $cover ? esc_url_raw( $cover ) : '',
					'developer' => html_entity_decode( $developer, ENT_QUOTES, 'UTF-8' ),
					'articles'  => $tag instanceof WP_Term ? (int) $tag->count : 0,
				);
			}
		}

		wp_reset_postdata();

		$payload = array(
			'success' => true,
			'data'    => array(
				'results'        => $results,
				'articles'       => $articles,
				'total_articles' => $total_articles,
				'total_games'    => $total_games,
			),
		);
		ntn_core_rest_cache_set( $cache_key, $payload, 5 * MINUTE_IN_SECONDS, false );

		$response = new WP_REST_Response( $payload, 200 );
		$response->header( 'Cache-Control', 'public, max-age=60' );

		return $response;
	}
}

new NTN_Catalog_REST_Controller();

