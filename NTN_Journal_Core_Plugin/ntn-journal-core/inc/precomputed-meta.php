<?php
/**
 * Precomputed Meta Module
 * Implements "Compute on Save, Read on Render" pattern.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ntn_journal_precompute_meta_on_save( $post_id, $post, $update ) {
	// Bail if autosave or revision
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( wp_is_post_autosave( $post_id ) ) {
		return;
	}

	// Only specific post types
	$allowed_types = array( 'post', 'videogioco', 'comunicato_stampa', 'page' );
	if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
		return;
	}

	// Verify permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// _ntn_reading_time
	$content      = $post->post_content;
	$word_count   = str_word_count( wp_strip_all_tags( $content ) );
	$reading_time = max( 1, (int) ceil( $word_count / 200 ) );
	update_post_meta( $post_id, '_ntn_reading_time', $reading_time );

	// _ntn_og_description
	$excerpt = get_the_excerpt( $post_id );
	if ( empty( $excerpt ) ) {
		$excerpt = wp_trim_words( wp_strip_all_tags( $content ), 30, '...' );
	}
	update_post_meta( $post_id, '_ntn_og_description', sanitize_text_field( $excerpt ) );

	// _ntn_toc_data
	if ( preg_match_all( '/<(h[23])[^>]*>(.*?)<\/\1>/si', $content, $matches, PREG_SET_ORDER ) ) {
		$toc_items = array();
		foreach ( $matches as $m ) {
			$text        = wp_strip_all_tags( $m[2] );
			$toc_items[] = array(
				'tag'  => $m[1],
				'text' => $text,
				'id'   => sanitize_title( $text ),
			);
		}
		update_post_meta( $post_id, '_ntn_toc_data', $toc_items );
	} else {
		delete_post_meta( $post_id, '_ntn_toc_data' );
	}

	// _ntn_video_ids
	if ( preg_match_all( '/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $content, $vid_matches ) ) {
		update_post_meta( $post_id, '_ntn_video_ids', array_unique( $vid_matches[1] ) );
	} else {
		delete_post_meta( $post_id, '_ntn_video_ids' );
	}

	// _ntn_faq_data
	$faq_pattern = '/<h([3-4])[^>]*>(.*?)<\/h\1>(.*?)(?=<h[2-4]|$)/is';
	if ( preg_match_all( $faq_pattern, $content, $faq_matches, PREG_SET_ORDER ) ) {
		$faq_items = array();
		foreach ( $faq_matches as $match ) {
			$question = wp_strip_all_tags( $match[2] );
			$answer   = wp_strip_all_tags( $match[3] );
			if ( str_ends_with( trim( $question ), '?' ) && ! empty( trim( $answer ) ) ) {
				$faq_items[] = array(
					'question' => trim( $question ),
					'answer'   => trim( $answer ),
				);
			}
		}
		if ( ! empty( $faq_items ) ) {
			update_post_meta( $post_id, '_ntn_faq_data', $faq_items );
		} else {
			delete_post_meta( $post_id, '_ntn_faq_data' );
		}
	} else {
		delete_post_meta( $post_id, '_ntn_faq_data' );
	}

	// _ntn_social_image_url
	if ( function_exists( 'ntn_get_social_preview_image' ) ) {
		// Pulisce il valore precedente per forzare il ricalcolo altrimenti la cache interna restituirebbe sempre il vecchio valore
		delete_post_meta( $post_id, '_ntn_social_image_url' );
		$social_img = ntn_get_social_preview_image( $post_id );
		if ( ! empty( $social_img ) ) {
			update_post_meta( $post_id, '_ntn_social_image_url', $social_img );
		} else {
			delete_post_meta( $post_id, '_ntn_social_image_url' );
		}
	}

	// Async YouTube oEmbed Prefetch
	$video_ids = get_post_meta( $post_id, '_ntn_video_ids', true );
	if ( ! empty( $video_ids ) ) {
		wp_schedule_single_event( time() + 5, 'ntn_async_prefetch_oembed', array( $post_id ) );
	}
}
add_action( 'save_post', 'ntn_journal_precompute_meta_on_save', 20, 3 );

// Action for async prefetch (callback still in schema-markup.php)
add_action( 'ntn_async_prefetch_oembed', 'ntn_journal_prefetch_yt_oembed' );
