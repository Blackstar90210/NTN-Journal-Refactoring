<?php
/**
 * Advanced Schema Markup (VideoObject and FAQPage)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pulisce i dati strutturati JSON-LD di Jetpack se lo schema di NTN Journal è attivo.
 */
function ntn_journal_cleanup_schema_duplicates() {
	if ( get_option( 'ntn_seo_enable_review_schema', 'yes' ) === 'yes' ) {
		// Disabilita lo Schema/JSON-LD di Jetpack per evitare duplicati in GSC
		add_filter( 'jetpack_seo_meta_tags_enabled', '__return_false' );
		add_filter( 'jetpack_structured_data', '__return_empty_array' );
		add_filter( 'jetpack_disable_seo_tools', '__return_true' );
	}
}
add_action( 'init', 'ntn_journal_cleanup_schema_duplicates' );

/**
 * Utility function to completely decode HTML entities for JSON-LD strings.
 */
function ntn_clean_json_ld_text( $text ) {
	$text = wp_strip_all_tags( $text );
	$text = wp_specialchars_decode( $text, ENT_QUOTES );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return $text;
}

/**
 * Extracts game platforms for Schema markup.
 */
function ntn_get_game_platforms_for_schema() {
	// v4.14.3: solo le categorie piattaforma dell'articolo (vuoto se non ce ne sono)
	return array_map( 'ntn_clean_json_ld_text', ntn_core_post_platform_names( get_the_ID() ) );
}

/**
 * Injects VideoObject and FAQPage schemas into the footer of single posts.
 */
function ntn_journal_advanced_schema_markup() {
	// Il freno d'emergenza dei dati per Google spegne anche video e FAQ (v3.17.1)
	if ( ! is_single() || ( function_exists( 'ntn_core_schema_enabled' ) && ! ntn_core_schema_enabled() ) ) {
		return;
	}

	global $post;
	$raw_content = $post->post_content;
	$schema_data = array();

	// 1. VideoObject Schema
	$enable_video = get_option( 'ntn_seo_enable_video_schema', get_theme_mod( 'ntn_journal_enable_video_schema', true ) ? 'yes' : 'no' );
	if ( $enable_video === 'yes' ) {
		$video_ids_found = array();

		// Cerca URL YouTube nel meta precomputato o nel contenuto
		$precomputed_vids = get_post_meta( $post->ID, '_ntn_video_ids', true );
		if ( ! empty( $precomputed_vids ) && is_array( $precomputed_vids ) ) {
			foreach ( $precomputed_vids as $vid ) {
				$video_ids_found[ $vid ] = 'content';
			}
		} else {
			$pattern = '#(?:https?://)?(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([^"&?/\s]{11})#i';
			if ( preg_match_all( $pattern, $raw_content, $matches ) ) {
				foreach ( $matches[1] as $vid ) {
					$video_ids_found[ $vid ] = 'content';
				}
			}
		}

		// Aggiungi video IGDB se presente e se IGDB è attivo
		if ( get_option( 'ntn_igdb_scanner_active', 'yes' ) === 'yes' ) {
			$igdb_video = get_post_meta( $post->ID, '_ntn_game_video', true );
			if ( ! empty( $igdb_video ) ) {
				$video_ids_found[ $igdb_video ] = 'igdb';
			}
		}

		foreach ( $video_ids_found as $video_id => $source ) {
			// Usa un transient per evitare chiamate API ad ogni caricamento
			$transient_key = 'ntn_yt_oembed_' . $video_id;
			$body          = get_transient( $transient_key );

			// Se il transient esiste (generato in save_post), crea lo schema
			if ( $body && isset( $body['title'] ) ) {
				$video_name        = $body['title'];
				$video_description = $body['title'];

				// Titolo SEO-Friendly per IGDB Trailer
				if ( $source === 'igdb' ) {
					$game_title = get_post_meta( $post->ID, '_ntn_game_title', true );
					if ( ! empty( $game_title ) ) {
						$video_name        = ntn_clean_json_ld_text( 'Trailer ufficiale di ' . $game_title );
						$video_description = ntn_clean_json_ld_text( 'Guarda il trailer ufficiale di ' . $game_title );
					}
				}

				$video_schema  = array(
					'@context'     => 'https://schema.org',
					'@type'        => 'VideoObject',
					'name'         => $video_name,
					'description'  => $video_description,
					'thumbnailUrl' => $body['thumbnail_url'],
					'uploadDate'   => ntn_core_youtube_upload_date( (string) $video_id, get_the_date( 'c', $post->ID ) ), // Data vera di YouTube, se c'è; altrimenti quella dell'articolo
					'embedUrl'     => 'https://www.youtube.com/embed/' . $video_id,
				);
				$schema_data[] = $video_schema;
			}
		}
	}

	// 2. FAQPage Schema
	$enable_faq = get_option( 'ntn_seo_enable_faq_schema', get_theme_mod( 'ntn_journal_enable_faq_schema', true ) ? 'yes' : 'no' );
	if ( $enable_faq === 'yes' ) {
		$faq_items = array();

		$precomputed_faq = get_post_meta( $post->ID, '_ntn_faq_data', true );
		if ( ! empty( $precomputed_faq ) && is_array( $precomputed_faq ) ) {
			foreach ( $precomputed_faq as $faq ) {
				$faq_items[] = array(
					'@type'          => 'Question',
					'name'           => $faq['question'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $faq['answer'],
					),
				);
			}
		} else {
			// Estrai Domanda (H3/H4 che finisce per ?) e Risposta (testo seguente fino al prossimo heading)
			$pattern = '/<h([3-4])[^>]*>(.*?)<\/h\1>(.*?)(?=<h[2-4]|$)/is';
			if ( preg_match_all( $pattern, $raw_content, $matches, PREG_SET_ORDER ) ) {
				foreach ( $matches as $match ) {
					$question = wp_strip_all_tags( $match[2] );
					$answer   = wp_strip_all_tags( $match[3] );

					// Se il titolo è una domanda, lo consideriamo una FAQ
					if ( str_ends_with( trim( $question ), '?' ) && ! empty( trim( $answer ) ) ) {
						$faq_items[] = array(
							'@type'          => 'Question',
							'name'           => trim( $question ),
							'acceptedAnswer' => array(
								'@type' => 'Answer',
								'text'  => trim( $answer ),
							),
						);
					}
				}
			}
		}

		if ( ! empty( $faq_items ) ) {
			$faq_schema    = array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'name'       => 'FAQ',
				'mainEntity' => $faq_items,
			);
			$schema_data[] = $faq_schema;
		}
	}

	// Output
	if ( ! empty( $schema_data ) ) {
		foreach ( $schema_data as $schema ) {
			echo '<!-- Advanced Schema Markup by NTN -->';
			echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		}
	}
}
add_action( 'wp_footer', 'ntn_journal_advanced_schema_markup', 99 );

/**
 * Injects JSON-LD Schema Markup for Articles and Reviews into the footer.
 */
function ntn_journal_review_article_schema() {
	// Le Hub dei videogiochi non sono articoli: i loro dati strutturati sono in hub-seo.php (v3.11.0)
	if ( ! is_single() || is_singular( 'videogioco' ) ) {
		return;
	}

	if ( get_option( 'ntn_seo_enable_review_schema', 'yes' ) !== 'yes' ) {
		return;
	}

	// Se The SEO Framework è attivo, la gestione Schema è delegata a seo-extras.php
	if ( defined( 'THE_SEO_FRAMEWORK_PRESENT' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) || ( function_exists( 'the_seo_framework' ) && the_seo_framework() ) ) {
		return;
	}

	$ntn_schema_rating = get_post_meta( get_the_ID(), 'rating', true );

	// Setup publisher schema (comune ad Article e Review)
	$publisher_schema = array(
		'@type' => 'Organization',
		'name'  => ntn_clean_json_ld_text( get_bloginfo( 'name' ) ),
	);
	$custom_logo_id   = get_theme_mod( 'custom_logo' );
	if ( $custom_logo_id ) {
		$publisher_schema['logo'] = array(
			'@type' => 'ImageObject',
			'url'   => esc_url_raw( wp_get_attachment_image_url( $custom_logo_id, 'full' ) ),
		);
	}

	if ( ! empty( $ntn_schema_rating ) ) {
		$ntn_schema_item = get_post_meta( get_the_ID(), 'reviewed_item', true );
		if ( empty( $ntn_schema_item ) || get_option( 'ntn_igdb_scanner_active', 'yes' ) !== 'yes' ) {
			$ntn_schema_item = get_the_title();
		}
		$ntn_schema_item = ntn_clean_json_ld_text( $ntn_schema_item );

		$ntn_schema_verdict = get_post_meta( get_the_ID(), 'verdict', true );

		$game_platform = ntn_get_game_platforms_for_schema();

		// Array base per lo Schema Review
		$ntn_schema_data = array(
			'@context'         => 'https://schema.org/',
			'@type'            => 'Review',
			'@id'              => get_permalink() . '#review',
			'url'              => get_permalink(),
			'mainEntityOfPage' => get_permalink(),
			'name'             => ntn_clean_json_ld_text( get_the_title() ),
			'itemReviewed'     => array(
				'@type'               => 'VideoGame',
				'name'                => $ntn_schema_item,
				'applicationCategory' => 'Game',
			),
		);
		if ( ! empty( $game_platform ) ) {
			$ntn_schema_data['itemReviewed']['gamePlatform'] = $game_platform; // v4.14.3: solo le categorie piattaforma
		}

		// Enhanced IGDB Meta via API Helper
		if ( function_exists( 'ntn_get_game_view_model' ) ) {
			$view_model = ntn_get_game_view_model( get_the_ID() );
			if ( $view_model && ! empty( $view_model['title'] ) ) {
				if ( ! empty( $view_model['publisher'] ) ) {
					$ntn_schema_data['itemReviewed']['publisher'] = array(
						'@type' => 'Organization',
						'name'  => ntn_clean_json_ld_text( reset( $view_model['publisher'] ) ),
					);
				}
				if ( ! empty( $view_model['genres'] ) ) {
					$ntn_schema_data['itemReviewed']['genre'] = array_map( 'ntn_clean_json_ld_text', $view_model['genres'] );
				}
				$raw_release = $view_model['release_raw']; // v4.14.0: dati dell'articolo o, se mancano, del gioco
				if ( $raw_release ) {
					$ntn_schema_data['itemReviewed']['datePublished'] = function_exists( 'ntn_release_schema_date' ) ? ntn_release_schema_date( $raw_release, (string) $view_model['release_precision'] ) : $raw_release; // v3.19.2: solo le parti note
				}
				if ( ! empty( $view_model['platforms'] ) ) { // v4.14.3: le categorie comandano, IGDB aggiunge quelle che mancano
					$combined_platforms = ntn_core_post_platform_names( get_the_ID(), (array) $view_model['platforms'] );
					if ( ! empty( $combined_platforms ) ) {
						$ntn_schema_data['itemReviewed']['gamePlatform'] = array_map( 'ntn_clean_json_ld_text', $combined_platforms );
					}
				}
				if ( ! empty( $view_model['video_url'] ) ) {
					$video_id                                   = str_replace( 'https://www.youtube.com/watch?v=', '', $view_model['video_url'] );
					$ntn_schema_data['itemReviewed']['trailer'] = array(
						'@type'        => 'VideoObject',
						'name'         => 'Trailer di ' . ntn_clean_json_ld_text( $ntn_schema_item ),
						'description'  => 'Guarda il trailer ufficiale di ' . ntn_clean_json_ld_text( $ntn_schema_item ),
						'thumbnailUrl' => 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg',
						'embedUrl'     => 'https://www.youtube.com/embed/' . $video_id,
						'uploadDate'   => ntn_core_youtube_upload_date( (string) $video_id, get_the_date( 'c', get_the_ID() ) ),
					);
				}
				if ( ! empty( $view_model['screenshots'] ) ) {
					$ntn_schema_data['itemReviewed']['screenshot'] = array();
					foreach ( $view_model['screenshots'] as $screenshot ) {
						$ntn_schema_data['itemReviewed']['screenshot'][] = array(
							'@type' => 'ImageObject',
							'url'   => esc_url_raw( $screenshot ),
						);
					}
				}
			}
		}

		$ntn_schema_data['reviewRating']  = array(
			'@type'       => 'Rating',
			'ratingValue' => intval( $ntn_schema_rating ),
			'bestRating'  => '5',
			'worstRating' => '1',
		);
		$ntn_schema_data['author']        = array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', get_post_field( 'post_author', get_the_ID() ) ),
			'url'   => esc_url_raw( get_author_posts_url( get_post_field( 'post_author', get_the_ID() ) ) ),
		);
		$ntn_schema_data['publisher']     = $publisher_schema;
		$ntn_schema_data['datePublished'] = get_the_date( 'c', get_the_ID() );
		$ntn_schema_data['dateModified']  = get_the_modified_date( 'c', get_the_ID() );

		if ( ! empty( $ntn_schema_verdict ) ) {
			$ntn_schema_data['reviewBody'] = ntn_clean_json_ld_text( wp_trim_words( $ntn_schema_verdict, 150 ) );
		}

		if ( has_post_thumbnail() ) {
			$ntn_schema_image                         = get_the_post_thumbnail_url( get_the_ID(), 'full' );
			$ntn_schema_data['image']                 = $ntn_schema_image;
			$ntn_schema_data['itemReviewed']['image'] = $ntn_schema_image;
		}

		// Aggiungiamo Pro e Contro se disponibili al markup SEO (tramite il formato stringa di pros/cons)
		$ntn_schema_pros = get_post_meta( get_the_ID(), 'pros', true );
		if ( ! empty( $ntn_schema_pros ) ) {
			$pros_array = is_array( $ntn_schema_pros ) ? $ntn_schema_pros : explode( "\n", $ntn_schema_pros );
			$pros_clean = array_map( 'trim', $pros_array );
			$pros_clean = array_filter( $pros_clean );

			if ( ! empty( $pros_clean ) ) {
				$ntn_schema_data['positiveNotes'] = array(
					'@type'           => 'ItemList',
					'itemListElement' => array(),
				);
				$i                                = 1;
				foreach ( $pros_clean as $pro ) {
					$ntn_schema_data['positiveNotes']['itemListElement'][] = array(
						'@type'    => 'ListItem',
						'position' => $i++,
						'name'     => ntn_clean_json_ld_text( $pro ),
					);
				}
			}
		}

		$ntn_schema_cons = get_post_meta( get_the_ID(), 'cons', true );
		if ( ! empty( $ntn_schema_cons ) ) {
			$cons_array = is_array( $ntn_schema_cons ) ? $ntn_schema_cons : explode( "\n", $ntn_schema_cons );
			$cons_clean = array_map( 'trim', $cons_array );
			$cons_clean = array_filter( $cons_clean );

			if ( ! empty( $cons_clean ) ) {
				$ntn_schema_data['negativeNotes'] = array(
					'@type'           => 'ItemList',
					'itemListElement' => array(),
				);
				$j                                = 1;
				foreach ( $cons_clean as $con ) {
					$ntn_schema_data['negativeNotes']['itemListElement'][] = array(
						'@type'    => 'ListItem',
						'position' => $j++,
						'name'     => ntn_clean_json_ld_text( $con ),
					);
				}
			}
		}

		echo '<!-- SEO: Schema Markup della Recensione -->';
		echo '<script type="application/ld+json">' . wp_json_encode( $ntn_schema_data, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	} else {
		// Se è un Comunicato Stampa
		if ( is_singular( 'comunicato_stampa' ) ) {
			$schema_type    = 'PressRelease';
			$publisher_meta = get_post_meta( get_the_ID(), 'ntn_press_publisher', true );

			// Se abbiamo un publisher specifico per il comunicato, sovrascriviamo l'Organization
			if ( ! empty( $publisher_meta ) ) {
				$publisher_schema = array(
					'@type' => 'Organization',
					'name'  => ntn_clean_json_ld_text( $publisher_meta ),
				);
			}

			$ntn_schema_article = array(
				'@context'      => 'https://schema.org',
				'@type'         => $schema_type,
				'name'          => ntn_clean_json_ld_text( get_the_title() ),
				'headline'      => ntn_clean_json_ld_text( get_the_title() ),
				'datePublished' => get_the_date( 'c', get_the_ID() ),
				'dateModified'  => get_the_modified_date( 'c', get_the_ID() ),
				'publisher'     => $publisher_schema,
				'author'        => $publisher_schema, // Anche l'autore è l'organizzazione per trasparenza
			);

			if ( has_post_thumbnail() ) {
				$ntn_schema_article['image'] = array(
					get_the_post_thumbnail_url( get_the_ID(), 'full' ),
				);
			}

			echo '<!-- SEO: Schema Markup Comunicato Stampa -->';
			echo '<script type="application/ld+json">' . wp_json_encode( $ntn_schema_article, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";

		} else {
			// Determina se è una Notizia o un Articolo standard
			$is_news     = has_category( array( 'notizie', 'news' ) );
			$schema_type = $is_news ? 'NewsArticle' : 'Article';

			global $post;
			$word_count  = str_word_count( wp_strip_all_tags( $post->post_content ) );
			$description = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( $post->post_content ), 25, '...' );

			// Fallback: Schema Markup per Articolo o Notizia
			$ntn_schema_article = array(
				'@context'         => 'https://schema.org',
				'@type'            => $schema_type,
				'@id'              => get_permalink() . '#article',
				'mainEntityOfPage' => get_permalink(),
				'name'             => ntn_clean_json_ld_text( get_the_title() ),
				'headline'         => ntn_clean_json_ld_text( get_the_title() ),
				'description'      => ntn_clean_json_ld_text( $description ),
				'wordCount'        => $word_count,
				'datePublished'    => get_the_date( 'c', get_the_ID() ),
				'dateModified'     => get_the_modified_date( 'c', get_the_ID() ),
				'author'           => array(
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', get_post_field( 'post_author', get_the_ID() ) ),
					'url'   => esc_url_raw( get_author_posts_url( get_post_field( 'post_author', get_the_ID() ) ) ),
				),
				'publisher'        => $publisher_schema,
			);
			if ( has_post_thumbnail() ) {
				$ntn_schema_article['image'] = array(
					get_the_post_thumbnail_url( get_the_ID(), 'full' ),
				);
			}

			// Integrazione VideoGame per l'Articolo nativo
			$hub_info = ntn_get_hub_igdb_info( get_the_ID() );
			if ( ! is_array( $hub_info ) ) {
				$hub_info = array();
			}
			$game_title = ! empty( $hub_info['title'] ) ? $hub_info['title'] : get_post_meta( get_the_ID(), '_ntn_game_title', true );
			$found_tag  = null;
			if ( empty( $game_title ) ) {
				$tags = get_the_tags();
				if ( $tags ) {
					foreach ( $tags as $tag ) {
						$tag_game_title = get_term_meta( $tag->term_id, '_ntn_game_title', true );
						if ( ! empty( $tag_game_title ) ) {
							$game_title = $tag_game_title;
							$found_tag  = $tag;
							if ( empty( $hub_info ) ) {
								$hub_info = ntn_get_hub_igdb_info( $tag );
								if ( ! is_array( $hub_info ) ) {
									$hub_info = array();
								}
							}
							break;
						}
					}
				}
			}

			if ( ! empty( $game_title ) ) {
				$game_entity = array(
					'@type'               => 'VideoGame',
					'name'                => ntn_clean_json_ld_text( $game_title ),
					'applicationCategory' => 'Game',
				);

				$game_cover = ! empty( $hub_info['cover'] ) ? $hub_info['cover'] : get_post_meta( get_the_ID(), '_ntn_game_cover', true );
				if ( ! empty( $game_cover ) ) {
					if ( function_exists( 'ntn_normalize_game_cover_url' ) ) {
						$game_cover = ntn_normalize_game_cover_url( $game_cover );
					}
					$game_entity['image'] = esc_url_raw( $game_cover );
				}

				if ( ! empty( $hub_info['developer'] ) ) {
					$devs                     = (array) $hub_info['developer'];
					$game_entity['developer'] = array(
						'@type' => 'Organization',
						'name'  => ntn_clean_json_ld_text( reset( $devs ) ),
					);
				}
				if ( ! empty( $hub_info['publisher'] ) ) {
					$pubs                     = (array) $hub_info['publisher'];
					$game_entity['publisher'] = array(
						'@type' => 'Organization',
						'name'  => ntn_clean_json_ld_text( reset( $pubs ) ),
					);
				}

				$platforms = ntn_get_game_platforms_for_schema();
				if ( ! empty( $platforms ) ) {
					$game_entity['gamePlatform'] = $platforms; // v4.14.3: solo le categorie piattaforma
				}

				// Enhanced IGDB Meta via API Helper
				if ( function_exists( 'ntn_get_game_view_model' ) ) {
					$target_object = $found_tag ? $found_tag : get_the_ID();
					$view_model    = ntn_get_game_view_model( $target_object );
					if ( $view_model && ! empty( $view_model['title'] ) ) {
						if ( ! empty( $view_model['publisher'] ) ) {
							$game_entity['publisher'] = array(
								'@type' => 'Organization',
								'name'  => ntn_clean_json_ld_text( reset( $view_model['publisher'] ) ),
							);
						}
						if ( ! empty( $view_model['genres'] ) ) {
							$game_entity['genre'] = array_map( 'ntn_clean_json_ld_text', $view_model['genres'] );
						}
						$raw_release   = $view_model['release_raw'] ? $view_model['release_raw'] : get_post_meta( get_the_ID(), '_ntn_game_release', true ); // v4.14.0
						$raw_precision = $view_model['release_raw'] ? $view_model['release_precision'] : get_post_meta( get_the_ID(), '_ntn_game_release_precision', true );
						if ( $raw_release ) {
							$game_entity['datePublished'] = function_exists( 'ntn_release_schema_date' ) ? ntn_release_schema_date( $raw_release, (string) $raw_precision ) : $raw_release; // v3.19.2: solo le parti note
						}
						if ( ! empty( $view_model['platforms'] ) ) { // v4.14.3
							$combined_platforms = ntn_core_post_platform_names( get_the_ID(), (array) $view_model['platforms'] );
							if ( ! empty( $combined_platforms ) ) {
								$game_entity['gamePlatform'] = array_map( 'ntn_clean_json_ld_text', $combined_platforms );
							}
						}
						if ( ! empty( $view_model['video_url'] ) ) {
							$video_id               = str_replace( 'https://www.youtube.com/watch?v=', '', $view_model['video_url'] );
							$game_entity['trailer'] = array(
								'@type'        => 'VideoObject',
								'name'         => 'Trailer di ' . ntn_clean_json_ld_text( $game_title ),
								'description'  => 'Guarda il trailer ufficiale di ' . ntn_clean_json_ld_text( $game_title ),
								'thumbnailUrl' => 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg',
								'embedUrl'     => 'https://www.youtube.com/embed/' . $video_id,
								'uploadDate'   => ntn_core_youtube_upload_date( (string) $video_id, get_the_date( 'c', get_the_ID() ) ),
							);
						}
						if ( ! empty( $view_model['screenshots'] ) ) {
							$game_entity['screenshot'] = array();
							foreach ( $view_model['screenshots'] as $screenshot ) {
								$game_entity['screenshot'][] = array(
									'@type' => 'ImageObject',
									'url'   => esc_url_raw( $screenshot ),
								);
							}
						}
					}
				}

				$ntn_schema_article['about'] = $game_entity;
			}

			echo '<!-- SEO: Schema Markup Articolo -->';
			echo '<script type="application/ld+json">' . wp_json_encode( $ntn_schema_article, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		}
	}
}
add_action( 'wp_footer', 'ntn_journal_review_article_schema', 98 );

/**
 * Pre-fetches YouTube oEmbed data on post save to avoid blocking frontend rendering.
 */
function ntn_journal_prefetch_yt_oembed( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return;
	}

	// Evita salvataggi automatici e revisioni
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Verifica permessi (potrebbe non esserci current_user in async, commento o ignoro)
	// if ( ! current_user_can( 'edit_post', $post_id ) ) {
	// return;
	// }

	// Solo post in stato 'publish' o 'future' (schedulati)
	if ( $post->post_status !== 'publish' && $post->post_status !== 'future' ) {
		return;
	}

	$enable_video = get_option( 'ntn_seo_enable_video_schema', get_theme_mod( 'ntn_journal_enable_video_schema', true ) ? 'yes' : 'no' );
	if ( $enable_video === 'yes' ) {
		$video_ids_to_fetch = array();

		// Cerca nel contenuto
		$raw_content = $post->post_content;
		$pattern     = '#(?:https?://)?(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([^"&?/\s]{11})#i';
		if ( preg_match_all( $pattern, $raw_content, $matches ) ) {
			foreach ( $matches[1] as $vid ) {
				$video_ids_to_fetch[] = $vid;
			}
		}

		// Cerca nel meta IGDB se attivo
		if ( get_option( 'ntn_igdb_scanner_active', 'yes' ) === 'yes' ) {
			$igdb_video = get_post_meta( $post_id, '_ntn_game_video', true );
			if ( ! empty( $igdb_video ) ) {
				$video_ids_to_fetch[] = $igdb_video;
			}
		}

		$video_ids_to_fetch = array_unique( $video_ids_to_fetch );

		foreach ( $video_ids_to_fetch as $video_id ) {
			$transient_key = 'ntn_yt_oembed_' . $video_id;
			$body          = get_transient( $transient_key );

			if ( false === $body ) {
				$oembed_url = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $video_id . '&format=json';
				// Timeout più alto in backend, non blocca l'utente frontend
				$response = wp_remote_get( $oembed_url, array( 'timeout' => 5 ) );

				if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
					$body = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( $body ) {
						set_transient( $transient_key, $body, WEEK_IN_SECONDS * 4 );
					}
				}
			}
		}
	}
}
