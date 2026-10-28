<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * ==============================================================================
 * GOOGLE NEWS SITEMAP GENERATOR
 * ==============================================================================
 */
function ntn_journal_news_sitemap() {
	if ( get_option( 'ntn_seo_enable_news_sitemap', 'yes' ) !== 'yes' ) {
		return;
	}
	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	if ( strpos( $request_uri, '/news-sitemap.xml' ) !== false ) {
		status_header( 200 );
		header( 'Content-Type: text/xml; charset=utf-8' );
		
		$cached_xml = get_transient( 'ntn_news_sitemap_cache' );
		if ( false === $cached_xml ) {
			ob_start();
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
			
			$args = array(
				'post_type'              => array( 'post', 'comunicato_stampa' ),
				'post_status'            => 'publish',
				'posts_per_page'         => 1000,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'fields'                 => 'ids',
				'date_query'             => array(
					array(
						'after' => '48 hours ago'
					)
				)
			);
		$query = new WP_Query( $args );
		
		$publisher_name = get_bloginfo( 'name' );
		$language = get_bloginfo( 'language' );
		
		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post_id ) {
				echo "\t<url>\n";
				echo "\t\t<loc>" . esc_url( get_permalink( $post_id ) ) . "</loc>\n";
				echo "\t\t<lastmod>" . get_the_modified_date( 'c', $post_id ) . "</lastmod>\n";
				echo "\t\t<news:news>\n";
				echo "\t\t\t<news:publication>\n";
				echo "\t\t\t\t<news:name>" . esc_html( $publisher_name ) . "</news:name>\n";
				echo "\t\t\t\t<news:language>" . esc_html( substr( $language, 0, 2 ) ) . "</news:language>\n";
				echo "\t\t\t</news:publication>\n";
				echo "\t\t\t<news:publication_date>" . get_the_date( 'c', $post_id ) . "</news:publication_date>\n";
				echo "\t\t\t<news:title>" . esc_html( get_the_title( $post_id ) ) . "</news:title>\n";
				echo "\t\t</news:news>\n";
				
				if ( has_post_thumbnail( $post_id ) ) {
					$thumb_id = get_post_thumbnail_id( $post_id );
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
		
		echo $cached_xml;
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
