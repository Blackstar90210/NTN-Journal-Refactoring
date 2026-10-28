<?php
/**
 * NTN Journal - IGDB SEO Routing & Redirects
 * 
 * Gestisce l'automazione dei redirect dai tag nativi (es. /argomento/hack-zero/)
 * alle Hub Page CPT videogioco per prevenire la duplicazione dei contenuti,
 * e gestisce la rimozione automatica dalla sitemap.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirige automaticamente l'archivio tag verso la Hub Page (CPT videogioco)
 * se il tag è stato collegato a IGDB.
 */
function ntn_redirect_tag_to_videogioco(): void {
	// Intercetta solo se siamo in un archivio tag
	if ( is_tag() ) {
		$tag = get_queried_object();
		
		if ( $tag instanceof WP_Term ) {
			// Verifica se il tag è associato a IGDB
			$igdb_id = get_term_meta( $tag->term_id, '_ntn_igdb_game_id', true );
			
			if ( ! empty( $igdb_id ) ) {
				// Cerca il CPT 'videogioco' avente lo stesso slug
				$cpt_posts = get_posts( array(
					'name'        => $tag->slug,
					'post_type'   => 'videogioco',
					'post_status' => 'publish',
					'numberposts' => 1
				) );
				
				if ( ! empty( $cpt_posts ) ) {
					$cpt_permalink = get_permalink( $cpt_posts[0]->ID );
					
					// Gestione della paginazione
					$paged = (int) get_query_var( 'paged' );
					if ( $paged === 0 ) {
						$paged = (int) get_query_var( 'page' );
					}
					
					if ( $paged > 1 ) {
						global $wp_rewrite;
						$pagination_base = $wp_rewrite->pagination_base ?? 'page';
						$cpt_permalink   = user_trailingslashit( trailingslashit( (string) $cpt_permalink ) . $pagination_base . '/' . $paged );
					}
					
					wp_safe_redirect( esc_url_raw( $cpt_permalink ), 301 );
					exit;
				}
			}
		}
	}
}
add_action( 'template_redirect', 'ntn_redirect_tag_to_videogioco' );

/**
 * Esclude dalla Sitemap di The SEO Framework i tag nativi che sono associati a IGDB.
 *
 * @param array<int> $excluded_terms Array di ID dei term da escludere.
 * @return array<int> Array aggiornato di ID dei term da escludere.
 */
function ntn_exclude_igdb_tags_from_sitemap( array $excluded_terms ): array {
	// Recuperiamo tutti i tag che hanno il metadato IGDB
	$igdb_tags = get_terms( array(
		'taxonomy'   => 'post_tag',
		'hide_empty' => false,
		'fields'     => 'ids',
		'meta_query' => array(
			array(
				'key'     => '_ntn_igdb_game_id',
				'compare' => 'EXISTS',
			),
		),
	) );
	
	if ( ! empty( $igdb_tags ) && ! is_wp_error( $igdb_tags ) ) {
		// Fondiamo l'array originale con i nuovi ID (assicurando la tipizzazione)
		$excluded_terms = array_unique( array_merge( $excluded_terms, array_map( 'intval', $igdb_tags ) ) );
	}
	
	return $excluded_terms;
}
add_filter( 'the_seo_framework_sitemap_exclude_terms', 'ntn_exclude_igdb_tags_from_sitemap' );
