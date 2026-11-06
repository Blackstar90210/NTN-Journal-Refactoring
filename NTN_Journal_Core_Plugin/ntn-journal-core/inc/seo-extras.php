<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * ==============================================================================
 * SEO OTTIMIZZAZIONI: BREADCRUMB E TABLE OF CONTENTS
 * ==============================================================================
 */

if ( ! function_exists( 'ntn_core_schema_enabled' ) ) {
	/**
	 * Freno d'emergenza dei dati per Google (v3.17.1, scelta del CEO).
	 *
	 * L'interruttore "Schema Markup" della scheda SEO spegne TUTTI i dati strutturati (JSON-LD)
	 * stampati dal plugin: articoli, recensioni, video, FAQ, percorso, sito, Hub dei giochi,
	 * Hub di categoria e pagina Redazione. Serve se si passa a un plugin SEO più completo.
	 *
	 * @return bool
	 */
	function ntn_core_schema_enabled() {
		return 'yes' === get_option( 'ntn_seo_enable_review_schema', 'yes' );
	}
}

/**
 * Genera il Breadcrumb con JSON-LD
 */
function ntn_journal_breadcrumb() {
	if ( is_front_page() || is_home() ) return;

	$home_url = home_url( '/' );

	// Visual HTML
	echo '<nav class="flex flex-wrap items-center gap-2 text-[10px] font-mono text-zinc-500 uppercase tracking-widest mb-8" aria-label="Breadcrumb">';
	echo '<a href="' . esc_url( $home_url ) . '" class="hover:text-zinc-950 dark:hover:text-white transition-colors group flex items-center gap-1.5"><svg class="w-3.5 h-3.5 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"></path></svg> HOME</a>';
	echo '<span class="text-zinc-300 dark:text-zinc-700">//</span>';

	$schema = array(
		'@context' => 'https://schema.org',
		'@type' => 'BreadcrumbList',
		'name' => 'Breadcrumb',
		'itemListElement' => array(
			array(
				'@type' => 'ListItem',
				'position' => 1,
				'name' => 'Home',
				'item' => $home_url
			)
		)
	);
	$pos = 2;

	if ( is_single() ) {
		global $post;
		$queried = function_exists( 'get_queried_object' ) ? get_queried_object() : null;
		$post_id = ( $post instanceof WP_Post ) ? $post->ID : ( ( $queried instanceof WP_Post ) ? $queried->ID : get_the_ID() );
		$primary_category = ntn_get_primary_category( $post_id );
		$cat_name = $primary_category ? $primary_category->name : 'Article';
		$cat_url = $primary_category ? get_category_link( $primary_category->term_id ) : '#';
		if ( is_wp_error( $cat_url ) || ! is_string( $cat_url ) ) {
			$cat_url = '#';
		}
		$post_title = get_the_title( $post_id );
		$post_url = get_permalink( $post_id );

		// Comunicati, videogiochi e altri tipi con archivio: il passaggio intermedio è la loro sezione, non una categoria (v3.10.0)
		$post_type = get_post_type( $post_id );
		if ( $post_type && 'post' !== $post_type ) {
			$type_object  = get_post_type_object( $post_type );
			$type_archive = get_post_type_archive_link( $post_type );
			$primary_category = null;
			if ( $type_object && is_string( $type_archive ) && '' !== $type_archive ) {
				$primary_category = true;
				$cat_name = $type_object->labels->name;
				$cat_url  = $type_archive;
			}
		}

		if ( $primary_category ) {
			echo '<a href="' . esc_url( $cat_url ) . '" class="hover:text-zinc-950 dark:hover:text-white transition-colors">' . esc_html( $cat_name ) . '</a>';
			echo '<span class="text-zinc-300 dark:text-zinc-700">//</span>';
			
			$schema['itemListElement'][] = array(
				'@type' => 'ListItem',
				'position' => $pos,
				'name' => $cat_name,
				'item' => $cat_url
			);
			$pos++;
		}
		
		echo '<span class="text-zinc-900 dark:text-zinc-300 font-bold truncate max-w-[200px] sm:max-w-xs md:max-w-md">' . esc_html( $post_title ) . '</span>';
		
		$schema['itemListElement'][] = array(
			'@type' => 'ListItem',
			'position' => $pos,
			'name' => wp_strip_all_tags( $post_title ),
			'item' => $post_url
		);

	} elseif ( is_page() ) {
		$post_title = get_the_title();
		$post_url = get_permalink();
		echo '<span class="text-zinc-900 dark:text-zinc-300 font-bold truncate max-w-[200px] sm:max-w-xs md:max-w-md">' . esc_html( $post_title ) . '</span>';
		
		$schema['itemListElement'][] = array(
			'@type' => 'ListItem',
			'position' => $pos,
			'name' => wp_strip_all_tags( $post_title ),
			'item' => $post_url
		);
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		$term_name = ( $term && isset( $term->name ) ) ? $term->name : 'Archivio';
		$term_url = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : home_url( '/' );
		if ( is_wp_error( $term_url ) || ! is_string( $term_url ) ) {
			$term_url = home_url( '/' );
		}

		// Categorie figlie (v3.14.0): prima i genitori, es. Home // Console // PlayStation.
		// Con le Hub di categoria si usa il "Titolo mostrato" (es. PlayStation per la categoria Sony).
		$crumb_label = static function ( $t ) {
			return ( $t instanceof WP_Term && function_exists( 'ntn_core_category_hub_label' ) ) ? ntn_core_category_hub_label( $t ) : ( isset( $t->name ) ? $t->name : '' );
		};
		if ( $term instanceof WP_Term && ! empty( $term->parent ) && function_exists( 'get_ancestors' ) && is_taxonomy_hierarchical( $term->taxonomy ) ) {
			foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, $term->taxonomy );
				$anc_url  = $ancestor instanceof WP_Term ? get_term_link( $ancestor ) : '';
				if ( ! $ancestor instanceof WP_Term || is_wp_error( $anc_url ) || ! is_string( $anc_url ) ) {
					continue;
				}
				echo '<a href="' . esc_url( $anc_url ) . '" class="hover:text-zinc-950 dark:hover:text-white transition-colors">' . esc_html( $crumb_label( $ancestor ) ) . '</a>';
				echo '<span class="text-zinc-300 dark:text-zinc-700">//</span>';
				$schema['itemListElement'][] = array(
					'@type'    => 'ListItem',
					'position' => $pos,
					'name'     => wp_strip_all_tags( $crumb_label( $ancestor ) ),
					'item'     => $anc_url,
				);
				$pos++;
			}
		}
		// Hub dello studio (v3.21.0): prima la Hub "Sviluppatori italiani" che lo elenca, es. Home // Sviluppatori italiani // 3DClouds
		if ( $term instanceof WP_Term && 'post_tag' === $term->taxonomy && function_exists( 'ntn_core_get_studio_hub_data' ) ) {
			$studio = ntn_core_get_studio_hub_data( $term );
			if ( $studio && ! empty( $studio['parent'] ) ) {
				echo '<a href="' . esc_url( $studio['parent']['url'] ) . '" class="hover:text-zinc-950 dark:hover:text-white transition-colors">' . esc_html( $studio['parent']['label'] ) . '</a>';
				echo '<span class="text-zinc-300 dark:text-zinc-700">//</span>';
				$schema['itemListElement'][] = array(
					'@type'    => 'ListItem',
					'position' => $pos,
					'name'     => wp_strip_all_tags( $studio['parent']['label'] ),
					'item'     => $studio['parent']['url'],
				);
				$pos++;
			}
		}
		if ( $term instanceof WP_Term ) {
			$term_name = $crumb_label( $term );
		}
		echo '<span class="text-zinc-900 dark:text-zinc-300 font-bold truncate max-w-[200px] sm:max-w-xs md:max-w-md">' . esc_html( $term_name ) . '</span>';
		
		$schema['itemListElement'][] = array(
			'@type' => 'ListItem',
			'position' => $pos,
			'name' => wp_strip_all_tags( $term_name ),
			'item' => $term_url
		);
	} elseif ( is_post_type_archive() ) {
		$qv_pt = get_query_var( 'post_type' );
		$pt_slug = is_array( $qv_pt ) ? reset( $qv_pt ) : $qv_pt;
		$post_type = get_post_type_object( $pt_slug );
		$pt_name = ( $post_type && isset( $post_type->labels ) && isset( $post_type->labels->name ) ) ? $post_type->labels->name : 'Archivio';
		$pt_url = get_post_type_archive_link( $pt_slug );
		if ( is_wp_error( $pt_url ) || ! is_string( $pt_url ) ) {
			$pt_url = home_url( '/' );
		}
		echo '<span class="text-zinc-900 dark:text-zinc-300 font-bold truncate max-w-[200px] sm:max-w-xs md:max-w-md">' . esc_html( $pt_name ) . '</span>';
		
		$schema['itemListElement'][] = array(
			'@type' => 'ListItem',
			'position' => $pos,
			'name' => wp_strip_all_tags( $pt_name ),
			'item' => $pt_url
		);
	} elseif ( is_author() ) {
		$author = get_queried_object();
		$author_name = $author->display_name;
		$author_url = get_author_posts_url( $author->ID );
		echo '<span class="text-zinc-900 dark:text-zinc-300 font-bold truncate max-w-[200px] sm:max-w-xs md:max-w-md">' . esc_html( $author_name ) . '</span>';
		
		$schema['itemListElement'][] = array(
			'@type' => 'ListItem',
			'position' => $pos,
			'name' => wp_strip_all_tags( $author_name ),
			'item' => $author_url
		);
	} elseif ( is_search() ) {
		// v3.19.0: nella pagina dei risultati il percorso dice "Ricerca", non "Archivio" (nessun dato per Google)
		echo '<span class="text-zinc-900 dark:text-zinc-300 font-bold truncate max-w-[200px] sm:max-w-xs md:max-w-md">Ricerca</span>';
	} else {
		echo '<span class="text-zinc-900 dark:text-zinc-300 font-bold truncate max-w-[200px] sm:max-w-xs md:max-w-md">Archivio</span>';
	}

	echo '</nav>';

	$enable_base = get_option( 'ntn_seo_enable_base', get_theme_mod( 'ntn_journal_enable_base_seo', true ) ? 'yes' : 'no' );
	if ( $enable_base === 'yes' && ntn_core_schema_enabled() && ! defined( 'WPSEO_VERSION' ) && ! class_exists( 'RankMath' ) && ! defined( 'THE_SEO_FRAMEWORK_PRESENT' ) && ! defined( 'THE_SEO_FRAMEWORK_VERSION' ) && ! ( function_exists( 'the_seo_framework' ) && the_seo_framework() ) ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}

/**
 * Indice automatico dei contenuti (the_content).
 *
 * v3.16.0: l'indice resta in cima al testo (da telefono e schermi stretti) e lo stesso
 * elenco è disponibile per la colonna laterale del tema (ntn_core_get_toc_items).
 * Gli ID dei titoli e la gestione dei titoli doppi non cambiano.
 */
function ntn_journal_auto_toc( $content = '' ) {
	if ( ! is_string( $content ) || empty( $content ) ) {
		return $content;
	}

	if ( get_option( 'ntn_seo_enable_auto_toc', 'yes' ) !== 'yes' ) {
		return $content;
	}

	if ( ! is_single() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	// v3.21.1: gli estratti dei riquadri ("Leggi anche", Consigliati) passano dallo stesso filtro con il testo
	// di un altro articolo, mentre get_the_ID() è ancora quello principale: l'indice non va calcolato lì,
	// altrimenti quello dell'articolo veniva sostituito dai titoli dell'ultimo articolo collegato.
	if ( doing_filter( 'get_the_excerpt' ) || doing_filter( 'wp_trim_excerpt' ) ) {
		return $content;
	}

	$precomputed_toc = get_post_meta( get_the_ID(), '_ntn_toc_data', true );
	$has_precomputed = ! empty( $precomputed_toc ) && is_array( $precomputed_toc );
	$items           = array();
	$used_slugs      = array();

	// Slug unico per ogni titolo: i doppioni diventano "titolo-2", "titolo-3"
	$unique_slug = static function ( $base_slug ) use ( &$used_slugs ) {
		if ( empty( $base_slug ) ) {
			$base_slug = 'sezione-' . wp_generate_password( 4, false );
		}
		if ( ! isset( $used_slugs[ $base_slug ] ) ) {
			$used_slugs[ $base_slug ] = 1;
			return $base_slug;
		}
		$used_slugs[ $base_slug ]++;
		return $base_slug . '-' . $used_slugs[ $base_slug ];
	};

	if ( $has_precomputed ) {
		if ( count( $precomputed_toc ) < 2 ) {
			return $content;
		}
	} elseif ( ! preg_match_all( '/<h([2-3])(.*?)>(.*?)<\/h[2-3]>/is', $content, $found, PREG_SET_ORDER ) || count( $found ) < 2 ) {
		// Se ci sono meno di 2 titoli, non stampiamo l'indice
		return $content;
	}

	// Aggiunge l'ID (e la classe scroll-mt-32) a ogni titolo e raccoglie le voci dell'indice
	$toc_index = 0;
	$content   = preg_replace_callback(
		'/<h([2-3])(.*?)>(.*?)<\/h[2-3]>/is',
		static function ( $match ) use ( &$toc_index, &$items, $has_precomputed, $precomputed_toc, $unique_slug ) {
			if ( $has_precomputed && ! isset( $precomputed_toc[ $toc_index ] ) ) {
				return $match[0];
			}
			$level      = (int) $match[1];
			$attributes = $match[2];
			if ( $has_precomputed ) {
				$item  = $precomputed_toc[ $toc_index ];
				$slug  = $unique_slug( isset( $item['id'] ) ? (string) $item['id'] : '' );
				$text  = isset( $item['text'] ) ? (string) $item['text'] : wp_strip_all_tags( $match[3] );
				$level = isset( $item['tag'] ) ? (int) substr( (string) $item['tag'], 1 ) : $level;
			} else {
				$text = wp_strip_all_tags( $match[3] );
				$slug = $unique_slug( sanitize_title( $text ) );
			}
			$toc_index++;
			$items[] = array(
				'level' => 3 === $level ? 3 : 2,
				'id'    => $slug,
				'text'  => $text,
			);

			// Preveniamo la duplicazione dell'attributo class (BUG-13)
			$class_pos = stripos( $attributes, 'class="' );
			if ( false === $class_pos ) {
				$class_pos = stripos( $attributes, "class='" );
			}
			if ( false !== $class_pos ) {
				$attributes = substr_replace( $attributes, 'scroll-mt-32 ', $class_pos + 7, 0 );
				return '<h' . $match[1] . $attributes . ' id="' . $slug . '">' . $match[3] . '</h' . $match[1] . '>';
			}
			return '<h' . $match[1] . $attributes . ' id="' . $slug . '" class="scroll-mt-32">' . $match[3] . '</h' . $match[1] . '>';
		},
		$content
	);

	if ( count( $items ) < 2 ) {
		return $content;
	}

	// La prima elaborazione è quella del testo dell'articolo: un passaggio successivo non la sostituisce
	if ( ! isset( $GLOBALS['ntn_core_toc_items'][ (int) get_the_ID() ] ) ) {
		$GLOBALS['ntn_core_toc_items'][ (int) get_the_ID() ] = $items;
	}

	// Indice in cima al testo: il tema lo nasconde quando c'è la colonna laterale
	return ntn_core_toc_markup( $items, 'ntn-toc--inline' ) . $content;
}
add_filter( 'the_content', 'ntn_journal_auto_toc', 15 );

/**
 * Voci dell'indice di un articolo, raccolte mentre si stampa il testo.
 *
 * @param int $post_id ID dell'articolo (0 = articolo corrente).
 * @return array<int,array{level:int,id:string,text:string}>
 */
function ntn_core_get_toc_items( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	return isset( $GLOBALS['ntn_core_toc_items'][ $post_id ] ) && is_array( $GLOBALS['ntn_core_toc_items'][ $post_id ] ) ? $GLOBALS['ntn_core_toc_items'][ $post_id ] : array();
}

/**
 * Markup dell'indice: pannello "// Indice" con le sezioni numerate come i sottotitoli (01, 02…).
 *
 * @param array  $items    Voci dell'indice.
 * @param string $modifier Classe aggiuntiva (es. ntn-toc--inline, ntn-toc--aside).
 * @return string
 */
function ntn_core_toc_markup( $items, $modifier = '' ) {
	if ( ! is_array( $items ) || count( $items ) < 2 ) {
		return '';
	}
	$sections = 0;
	foreach ( $items as $item ) {
		if ( isset( $item['level'] ) && 2 === (int) $item['level'] ) {
			$sections++;
		}
	}
	if ( 0 === $sections ) {
		$sections = count( $items );
	}

	$out  = '<nav class="ntn-toc ntn-panel not-prose' . ( $modifier ? ' ' . esc_attr( $modifier ) : '' ) . '" aria-label="' . esc_attr__( 'Indice dei contenuti', 'ntn-journal-core' ) . '">';
	$out .= '<div class="ntn-panel-h"><span>// Indice</span><span>' . esc_html( sprintf( 1 === $sections ? '%d sezione' : '%d sezioni', $sections ) ) . '</span></div>';
	$out .= '<ol class="ntn-toc-list">';
	foreach ( $items as $item ) {
		$level = isset( $item['level'] ) && 3 === (int) $item['level'] ? 3 : 2;
		$out  .= '<li class="ntn-toc-item is-h' . $level . '"><a href="#' . esc_attr( (string) $item['id'] ) . '">' . esc_html( (string) $item['text'] ) . '</a></li>';
	}
	$out .= '</ol></nav>';
	return $out;
}

if ( ! function_exists( 'ntn_normalize_game_cover_url' ) ) {
	/**
	 * Normalizza e ottimizza l'URL di una copertina di gioco (IGDB o CDN esterna).
	 * Garantisce protocollo HTTPS, espansione di hash ID raw a 1080p, e preserva
	 * inalterate le URL di CDN terze o caricate localmente.
	 *
	 * @param mixed $cover URL o ID raw dell'immagine.
	 * @return string URL normalizzato.
	 */
	function ntn_normalize_game_cover_url( $cover ) {
		if ( empty( $cover ) || is_array( $cover ) || ( is_object( $cover ) && ! method_exists( $cover, '__toString' ) ) ) {
			return '';
		}

		$cover = trim( (string) $cover );
		if ( '' === $cover ) {
			return '';
		}

		// 1. Risolvi URL relativi al protocollo
		if ( strpos( $cover, '//' ) === 0 ) {
			$cover = 'https:' . $cover;
		}
		// 2. Forza HTTPS per immagini IGDB
		elseif ( strpos( $cover, 'http://' ) === 0 && strpos( $cover, 'images.igdb.com' ) !== false ) {
			$cover = 'https://' . substr( $cover, 7 );
		}
		// 3. Espandi hash ID raw di IGDB (es. "co1r7f" o "co1r7f.jpg")
		elseif ( strpos( $cover, 'http' ) !== 0 && preg_match( '/^[a-zA-Z0-9_-]+(\.(jpg|png|jpeg|webp))?$/i', $cover ) ) {
			$clean_id = preg_replace( '/\.(jpg|png|jpeg|webp)$/i', '', $cover );
			$cover    = 'https://images.igdb.com/igdb/image/upload/t_1080p/' . $clean_id . '.jpg';
		}

		// 4. Per domini IGDB, garantisci la risoluzione Full HD 1080p senza corrompere CDN terze
		if ( strpos( $cover, 'images.igdb.com' ) !== false ) {
			if ( preg_match( '/\/t_[a-z0-9_]+\//', $cover ) ) {
				$cover = preg_replace( '/\/t_[a-z0-9_]+\//', '/t_1080p/', $cover );
			} elseif ( strpos( $cover, '/igdb/image/upload/' ) !== false && strpos( $cover, '/igdb/image/upload/t_' ) === false ) {
				$cover = str_replace( '/igdb/image/upload/', '/igdb/image/upload/t_1080p/', $cover );
			}
		}

		return $cover;
	}
}

if ( ! function_exists( 'ntn_get_social_preview_image' ) ) {
	/**
	 * Helper per recuperare l'immagine social (Open Graph / Twitter) ottimale
	 * con priorità: Featured Image WP > Cover IGDB (Hub Page) > Fallback Tema.
	 */
	function ntn_get_social_preview_image( $post_or_term = null ) {
		$image_url = '';

		if ( is_null( $post_or_term ) || empty( $post_or_term ) ) {
			if ( function_exists( 'get_queried_object' ) && get_queried_object() ) {
				$post_or_term = get_queried_object();
			} elseif ( is_singular() ) {
				$post_or_term = get_the_ID();
			} elseif ( is_tag() || is_category() || is_tax() ) {
				$post_or_term = get_queried_object();
			}
		}

		$target_id = 0;
		if ( is_numeric( $post_or_term ) ) {
			$target_id = intval( $post_or_term );
		} elseif ( $post_or_term instanceof WP_Post ) {
			$target_id = $post_or_term->ID;
		}
		// v3.22.2: con l'immagine in evidenza si usa sempre l'originale (Discover vuole almeno 1200 px),
		// così anche il valore precalcolato degli articoli già pubblicati (versione da 1024 px) viene superato
		if ( $target_id > 0 && has_post_thumbnail( $target_id ) ) {
			$full_img = get_the_post_thumbnail_url( $target_id, 'full' );
			if ( ! empty( $full_img ) ) {
				return esc_url_raw( $full_img );
			}
		}
		if ( $target_id > 0 ) {
			$precomputed_img = get_post_meta( $target_id, '_ntn_social_image_url', true );
			if ( ! empty( $precomputed_img ) ) {
				return esc_url_raw( $precomputed_img );
			}
		}

		static $cache = array();
		$cache_key = '';
		if ( is_numeric( $post_or_term ) ) {
			$cache_key = 'id_' . $post_or_term;
		} elseif ( $post_or_term instanceof WP_Post ) {
			$cache_key = 'post_' . $post_or_term->ID;
		} elseif ( $post_or_term instanceof WP_Term ) {
			$cache_key = 'term_' . $post_or_term->term_id;
		} elseif ( is_array( $post_or_term ) ) {
			$cache_key = 'arr_' . md5( wp_json_encode( $post_or_term ) );
		}
		if ( $cache_key && isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}
		// 1. Caso Array arg (es. array('id' => 201, 'taxonomy' => 'post_tag') o array('term_id' => 201))
		if ( is_array( $post_or_term ) ) {
			$hub_info = ntn_get_hub_igdb_info( $post_or_term );
			if ( ! empty( $hub_info['cover'] ) ) {
				$image_url = $hub_info['cover'];
			}
			if ( empty( $image_url ) ) {
				$id = isset( $post_or_term['id'] ) ? $post_or_term['id'] : ( isset( $post_or_term['term_id'] ) ? $post_or_term['term_id'] : 0 );
				if ( is_numeric( $id ) && ! empty( $id ) ) {
					$post_or_term = intval( $id );
				}
			}
		}

		// 2. Caso Post / CPT Singolare
		if ( empty( $image_url ) && ( ( is_numeric( $post_or_term ) && get_post( $post_or_term ) ) || $post_or_term instanceof WP_Post ) ) {
			$post_id = is_numeric( $post_or_term ) ? intval( $post_or_term ) : $post_or_term->ID;

			// A. Immagine in evidenza nativa di WordPress
			if ( has_post_thumbnail( $post_id ) ) {
				$image_url = get_the_post_thumbnail_url( $post_id, 'full' );
			}

			// B. Cover IGDB per Hub Page o post con metadati di gioco
			if ( empty( $image_url ) ) {
				$hub_info = ntn_get_hub_igdb_info( $post_id );
				if ( ! empty( $hub_info['cover'] ) ) {
					$image_url = $hub_info['cover'];
				}
				if ( empty( $image_url ) ) {
					$game_cover = get_post_meta( $post_id, '_ntn_game_cover', true );
					if ( ! empty( $game_cover ) ) {
						$image_url = $game_cover;
					}
				}
				if ( empty( $image_url ) ) {
					$game_cover = ntn_get_igdb_data( $post_id, '_ntn_game_cover' );
					if ( ! empty( $game_cover ) ) {
						$image_url = $game_cover;
					}
				}
			}
		}
		// 3. Caso Termine Tassonomia / Tag Archivio
		elseif ( empty( $image_url ) && ( $post_or_term instanceof WP_Term || ( is_numeric( $post_or_term ) && get_term( $post_or_term ) ) ) ) {
			$term = $post_or_term instanceof WP_Term ? $post_or_term : get_term( $post_or_term );
			if ( $term && ! is_wp_error( $term ) ) {
				$hub_info = ntn_get_hub_igdb_info( $term );
				if ( ! empty( $hub_info['cover'] ) ) {
					$image_url = $hub_info['cover'];
				}
				if ( empty( $image_url ) ) {
					$term_cover = get_term_meta( $term->term_id, '_ntn_game_cover', true );
					if ( ! empty( $term_cover ) ) {
						$image_url = $term_cover;
					}
				}
			}
		}

		// 4. Formattazione e protocollo HTTPS
		if ( ! empty( $image_url ) ) {
			if ( function_exists( 'ntn_normalize_game_cover_url' ) ) {
				$image_url = ntn_normalize_game_cover_url( $image_url );
			} else {
				if ( strpos( $image_url, '//' ) === 0 ) {
					$image_url = 'https:' . $image_url;
				}
				$image_url = preg_replace( '/\/t_[a-z0-9_]+\//', '/t_1080p/', $image_url );
			}
			$result = esc_url_raw( $image_url );
			if ( $cache_key ) $cache[ $cache_key ] = $result;
			return $result;
		}

		// 5. Fallback Globale Immagine Predefinita
		if ( function_exists( 'ntn_journal_default_cover_url' ) ) {
			$result = ntn_journal_default_cover_url();
			if ( $cache_key ) $cache[ $cache_key ] = $result;
			return $result;
		}

		$result = get_template_directory_uri() . '/assets/images/default-cover.png';
		if ( $cache_key ) $cache[ $cache_key ] = $result;
		return $result;
	}
}

/**
 * Filtro compatibilità per Yoast SEO, Rank Math, SEOPress e AIOSEO
 * Inietta la copertina IGDB dell'Hub se il plugin non ha un'immagine impostata.
 */
function ntn_seo_plugin_social_image_fallback( $img_url = '', $post_or_term = null ) {
	if ( empty( $img_url ) ) {
		$social_img = ntn_get_social_preview_image( $post_or_term );
		if ( ! empty( $social_img ) && strpos( $social_img, 'default-cover.png' ) === false ) {
			return $social_img;
		}
	}
	return $img_url;
}
function ntn_init_seo_plugin_hooks() {
	if ( is_admin() ) {
		return;
	}
	add_filter( 'wpseo_opengraph_image', 'ntn_seo_plugin_social_image_fallback', 10, 2 );
	add_filter( 'wpseo_twitter_image', 'ntn_seo_plugin_social_image_fallback', 10, 2 );
	add_filter( 'rank_math/opengraph/facebook/image', 'ntn_seo_plugin_social_image_fallback', 10, 2 );
	add_filter( 'rank_math/opengraph/twitter/image', 'ntn_seo_plugin_social_image_fallback', 10, 2 );
	add_filter( 'seopress_social_og_thumb', 'ntn_seo_plugin_social_image_fallback', 10, 2 );
	add_filter( 'seopress_social_twitter_card_thumb', 'ntn_seo_plugin_social_image_fallback', 10, 2 );
	add_filter( 'aioseo_facebook_tags', function( $tags ) {
		if ( empty( $tags['og:image'] ) ) {
			$social_img = ntn_get_social_preview_image();
			if ( ! empty( $social_img ) && strpos( $social_img, 'default-cover.png' ) === false ) {
				$tags['og:image'] = $social_img;
			}
		}
		return $tags;
	} );
	add_filter( 'aioseo_twitter_tags', function( $tags ) {
		if ( empty( $tags['twitter:image'] ) ) {
			$social_img = ntn_get_social_preview_image();
			if ( ! empty( $social_img ) && strpos( $social_img, 'default-cover.png' ) === false ) {
				$tags['twitter:image'] = $social_img;
			}
		}
		return $tags;
	} );
}
add_action( 'init', 'ntn_init_seo_plugin_hooks', 20 );

/**
 * Filtri di compatibilità per The SEO Framework (TSF v3.x, v4.x, v5.x)
 * 1. the_seo_framework_image_generation_params: Generatore prioritario di immagini social per TSF moderno (v4.0+)
 * 2. the_seo_framework_custom_image_url / the_seo_framework_social_image_url: Fallback custom URL
 * 3. the_seo_framework_image_details: Intercetta e sostituisce il fallback logo/icona del sito con la cover IGDB HD
 * 4. the_seo_framework_ogimage_output / the_seo_framework_twitterimage_output: Override diretto URL per tag OG/Twitter
 */

/**
 * Misure reali dell'immagine social (v3.22.2): prima erano sempre dichiarate 1920x1080.
 * Per le immagini esterne (cover IGDB) le misure non si conoscono: 0 = non dichiarate.
 */
function ntn_get_social_image_dimensions( $url ) {
	static $cache = array();
	$url = (string) $url;
	if ( isset( $cache[ $url ] ) ) {
		return $cache[ $url ];
	}
	$dims          = array( 'width' => 0, 'height' => 0 );
	$attachment_id = '' !== $url ? attachment_url_to_postid( $url ) : 0;
	if ( $attachment_id ) {
		$src = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( is_array( $src ) ) {
			$dims = array( 'width' => (int) $src[1], 'height' => (int) $src[2] );
		}
	}
	$cache[ $url ] = $dims;
	return $dims;
}

/**
 * Generatore PHP (yield) per The SEO Framework moderno
 */
function ntn_tsf_image_generator( $args = null, $size = 'full' ) {
	$target = $args;
	if ( is_null( $target ) || empty( $target ) ) {
		if ( function_exists( 'get_queried_object' ) && get_queried_object() ) {
			$target = get_queried_object();
		} elseif ( function_exists( 'get_the_ID' ) && get_the_ID() ) {
			$target = get_the_ID();
		}
	}
	$social_img = ntn_get_social_preview_image( $target );
	if ( ! empty( $social_img ) && strpos( $social_img, 'default-cover.png' ) === false && strpos( $social_img, 'qgin_Logo' ) === false && strpos( $social_img, 'cropped-' ) === false ) {
		$dims = ntn_get_social_image_dimensions( $social_img );
		yield array(
			'url'    => $social_img,
			'id'     => 0,
			'width'  => $dims['width'],
			'height' => $dims['height'],
		);
	}
}

function ntn_tsf_image_generation_params( $params, $args = null, $context = 'social' ) {
	if ( ! is_array( $params ) ) {
		return $params;
	}
	if ( ! isset( $params['cbs'] ) || ! is_array( $params['cbs'] ) ) {
		$params['cbs'] = array();
	}
	// Inserisce il generatore IGDB in prima posizione prima dei fallback di TSF
	$params['cbs'] = array( 'ntn_social_image' => 'ntn_tsf_image_generator' ) + $params['cbs'];
	return $params;
}
add_filter( 'the_seo_framework_image_generation_params', 'ntn_tsf_image_generation_params', 10, 3 );

/**
 * Filtro compatibilità specifico per The SEO Framework (TSF)
 * Inietta la copertina IGDB sia per le chiamate dirette sia per il rendering OpenGraph / Twitter.
 */
function ntn_tsf_social_image_fallback( $image_url = '', $args = array() ) {
	$social_img = ntn_get_social_preview_image( $args );
	if ( ! empty( $social_img ) && strpos( $social_img, 'default-cover.png' ) === false ) {
		return $social_img;
	}

	return $image_url;
}
add_filter( 'the_seo_framework_custom_image_url', 'ntn_tsf_social_image_fallback', 10, 2 );
add_filter( 'the_seo_framework_social_image_url', 'ntn_tsf_social_image_fallback', 10, 2 );

function ntn_tsf_image_details_fallback( $details = array(), $args = array() ) {
	$is_site_logo_or_empty = empty( $details ) || ! is_array( $details ) || empty( $details['url'] );

	if ( ! $is_site_logo_or_empty && ! empty( $details['url'] ) ) {
		$url_str = is_string( $details['url'] ) ? $details['url'] : '';
		if ( empty( $url_str ) || strpos( $url_str, 'cropped-' ) !== false || strpos( $url_str, 'qgin_Logo' ) !== false || strpos( $url_str, 'default-cover' ) !== false || strpos( $url_str, 'Logo-su-sfondo-bianco' ) !== false || strpos( $url_str, 'site-logo' ) !== false || strpos( $url_str, 'default-logo' ) !== false ) {
			$is_site_logo_or_empty = true;
		}
	}

	if ( $is_site_logo_or_empty ) {
		$social_img = ntn_get_social_preview_image( $args );
		if ( ! empty( $social_img ) && strpos( $social_img, 'default-cover.png' ) === false && strpos( $social_img, 'qgin_Logo' ) === false && strpos( $social_img, 'cropped-' ) === false ) {
			$target_title = '';
			if ( is_singular() ) {
				$target_title = get_the_title();
			} elseif ( is_numeric( $args ) ) {
				$target_title = get_the_title( intval( $args ) );
			} elseif ( is_array( $args ) && ! empty( $args['id'] ) && is_numeric( $args['id'] ) ) {
				$target_title = get_the_title( intval( $args['id'] ) );
			}
			$dims = ntn_get_social_image_dimensions( $social_img );
			return array(
				'url'    => $social_img,
				'id'     => 0,
				'width'  => $dims['width'],
				'height' => $dims['height'],
				'alt'    => $target_title,
			);
		}
	}

	return $details;
}
add_filter( 'the_seo_framework_image_details', 'ntn_tsf_image_details_fallback', 10, 2 );

function ntn_tsf_override_og_image_output( $image_url = '', $args = array() ) {
	$url_str = '';
	if ( is_string( $image_url ) ) {
		$url_str = $image_url;
	} elseif ( is_array( $image_url ) ) {
		if ( isset( $image_url['url'] ) && is_string( $image_url['url'] ) ) {
			$url_str = $image_url['url'];
		} elseif ( isset( $image_url['src'] ) && is_string( $image_url['src'] ) ) {
			$url_str = $image_url['src'];
		}
	}

	$is_site_logo_or_empty = empty( $url_str ) || strpos( $url_str, 'cropped-' ) !== false || strpos( $url_str, 'qgin_Logo' ) !== false || strpos( $url_str, 'default-cover' ) !== false || strpos( $url_str, 'site-logo' ) !== false || strpos( $url_str, 'default-logo' ) !== false;
	if ( $is_site_logo_or_empty ) {
		$social_img = ntn_get_social_preview_image( $args );
		if ( ! empty( $social_img ) && strpos( $social_img, 'default-cover.png' ) === false && strpos( $social_img, 'qgin_Logo' ) === false && strpos( $social_img, 'cropped-' ) === false ) {
			return $social_img;
		}
	}
	return is_string( $image_url ) ? $image_url : ( ! empty( $url_str ) ? $url_str : '' );
}
add_filter( 'the_seo_framework_ogimage_output', 'ntn_tsf_override_og_image_output', 10, 2 );
add_filter( 'the_seo_framework_twitterimage_output', 'ntn_tsf_override_og_image_output', 10, 2 );
add_filter( 'the_seo_framework_og_image_after_url', 'ntn_tsf_override_og_image_output', 10, 2 );
add_filter( 'the_seo_framework_twitter_image_after_url', 'ntn_tsf_override_og_image_output', 10, 2 );

/**
 * Stampa i meta tag Open Graph e Twitter Cards nella sezione <head>
 */
function ntn_journal_open_graph_tags() {
	// Immagine di anteprima per Open Graph / Twitter:
	// Priorità: Featured Image -> IGDB Cover (Hub Page) -> default-cover.png
	$ntn_og_image = ntn_get_social_preview_image();

	// SEO Fallback Logic
	if ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$pt_slug = is_array( $post_type ) ? reset( $post_type ) : $post_type;
		$ntn_og_url = get_post_type_archive_link( $pt_slug );
		$ntn_og_type = 'website';
		if ( $pt_slug === 'videogioco' ) {
			$ntn_og_description = 'Catalogo e hub centrale di tutti i videogiochi trattati su NTN Journal.';
		} else {
			$ntn_og_description = get_bloginfo( 'description' );
		}
	} elseif ( is_singular() ) {
		$ntn_og_url = get_permalink();
		$ntn_og_type = ( is_single() && ! is_singular( 'videogioco' ) ) ? 'article' : 'website';

		$precomputed_og = get_post_meta( get_the_ID(), '_ntn_og_description', true );
		if ( ! empty( $precomputed_og ) ) {
			$ntn_og_description = $precomputed_og;
		} elseif ( has_excerpt() ) {
			$ntn_og_description = wp_strip_all_tags( get_the_excerpt() );
		} else {
			$content = get_post_field( 'post_content', get_the_ID() );
			if ( ! empty( trim( strip_tags( $content ) ) ) ) {
				$ntn_og_description = wp_trim_words( wp_strip_all_tags( $content ), 25, '...' );
			} elseif ( get_post_type() === 'videogioco' && function_exists( 'ntn_get_hub_auto_summary' ) ) {
				// Testo di presentazione automatico della Hub (v3.11.0), lo stesso mostrato nella pagina
				$ntn_og_description = ntn_get_hub_auto_summary( (int) get_the_ID() );
			} else {
				$ntn_og_description = get_bloginfo( 'description' );
			}
		}
	} elseif ( is_tag() || is_category() || is_tax() ) {
		$term = get_queried_object();
		$ntn_og_url = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : home_url( '/' );
		$ntn_og_type = 'website';
		if ( ! empty( $term->description ) ) {
			$ntn_og_description = wp_strip_all_tags( $term->description );
		} else {
			$hub_info = ntn_get_hub_igdb_info( $term );
			if ( ! empty( $hub_info['title'] ) ) {
				$ntn_og_description = "Tutti gli articoli, recensioni, anteprime e notizie su {$hub_info['title']}.";
			} else {
				$ntn_og_description = get_bloginfo( 'description' );
			}
		}
	} else {
		$ntn_og_url = home_url( '/' );
		$ntn_og_type = 'website';
		$ntn_og_description = get_bloginfo( 'description' );
	}

	if ( is_wp_error( $ntn_og_url ) || ! is_string( $ntn_og_url ) ) {
		$ntn_og_url = home_url( '/' );
	}
	if ( is_wp_error( $ntn_og_image ) || ! is_string( $ntn_og_image ) ) {
		$ntn_og_image = '';
	}
	
	// Gestione dinamica Robots (noindex per comunicati stampa)
	$robots_meta = 'index, follow';
	if ( is_singular( 'comunicato_stampa' ) ) {
		$noindex = get_post_meta( get_the_ID(), 'ntn_press_noindex', true );
		if ( $noindex == '1' ) {
			$robots_meta = 'noindex, follow';
		}
	}
	?>

	<!-- SEO: meta description di base. Se attivi un plugin SEO (Yoast,
	Rank Math, The SEO Framework, ecc.) questi tag vengono bypassati per evitare duplicati. -->
	<?php 
	$enable_base = get_option( 'ntn_seo_enable_base', get_theme_mod( 'ntn_journal_enable_base_seo', true ) ? 'yes' : 'no' );
	if ( $enable_base === 'yes' && ! defined( 'WPSEO_VERSION' ) && ! class_exists( 'RankMath' ) && ! defined( 'THE_SEO_FRAMEWORK_PRESENT' ) && ! defined( 'THE_SEO_FRAMEWORK_VERSION' ) && ! ( function_exists( 'the_seo_framework' ) && the_seo_framework() ) ) : ?>
		<meta name="description" content="<?php echo esc_attr( $ntn_og_description ); ?>">
		<meta name="robots" content="<?php echo esc_attr( $robots_meta ); ?>">

		<!-- Open Graph / Facebook -->
		<meta property="og:type" content="<?php echo esc_attr( $ntn_og_type ); ?>">
		<meta property="og:url" content="<?php echo esc_url( $ntn_og_url ); ?>">
		<meta property="og:title" content="<?php echo esc_attr( wp_get_document_title() ); ?>">
		<meta property="og:description" content="<?php echo esc_attr( $ntn_og_description ); ?>">
		<meta property="og:image" content="<?php echo esc_url( $ntn_og_image ); ?>">
		<meta property="og:locale" content="<?php echo esc_attr( str_replace( '-', '_', get_locale() ) ); ?>">
		<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">

		<!-- Twitter -->
		<meta name="twitter:card" content="summary_large_image">
		<meta name="twitter:url" content="<?php echo esc_url( $ntn_og_url ); ?>">
		<meta name="twitter:title" content="<?php echo esc_attr( wp_get_document_title() ); ?>">
		<meta name="twitter:description" content="<?php echo esc_attr( $ntn_og_description ); ?>">
		<meta name="twitter:image" content="<?php echo esc_url( $ntn_og_image ); ?>">

		<?php
		// Schema.org JSON-LD per Navigazione Agentica (Spostato da setup.php)
		if ( ( is_front_page() || is_home() ) && ntn_core_schema_enabled() ) {
			$schema = array(
				'@context'    => 'https://schema.org',
				'@type'       => 'WebSite',
				'name'        => get_bloginfo( 'name' ),
				'url'         => home_url( '/' ),
				'description' => get_bloginfo( 'description' )
			);
			echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
		}
		?>
	<?php endif;
}
add_action( 'wp_head', 'ntn_journal_open_graph_tags', 5 );

/**
 * Pulisce i meta tag Open Graph generati da plugin di terze parti (come Jetpack)
 * se la funzionalità SEO base di NTN Journal è attiva e se l'opzione di pulizia è abilitata.
 */
function ntn_journal_cleanup_third_party_og() {
	// 1. Controllo base: se la nostra SEO è disattivata (o sovrascritta da Yoast/RankMath), 
	// non tocchiamo nulla dei plugin esterni, lasciamo che se la vedano tra loro.
	$enable_base = get_option( 'ntn_seo_enable_base', get_theme_mod( 'ntn_journal_enable_base_seo', true ) ? 'yes' : 'no' );
	if ( $enable_base !== 'yes' || defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'THE_SEO_FRAMEWORK_PRESENT' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) || ( function_exists( 'the_seo_framework' ) && the_seo_framework() ) ) {
		return;
	}

	// 2. Controlliamo se l'utente ha attivato la pulizia dei conflitti (PROP-14)
	$cleanup_enabled = get_option( 'ntn_seo_cleanup_og_duplicates', 'yes' );
	if ( $cleanup_enabled === 'yes' ) {
		// A. Disattiva Open Graph di Jetpack per evitare duplicati
		add_filter( 'jetpack_enable_open_graph', '__return_false' );
		
		// B. Rimuove altre action comuni (es. vecchi plugin Jetpack o social sharing generici)
		// che si agganciano a wp_head per stampare l'og:image
		remove_action( 'wp_head', 'jetpack_og_tags' );
		
		// C. Supporto per altri plugin popolari se presenti
		// Web Stories (Google) spesso inietta og:image, lo blocchiamo per evitare duplicati nei post normali
		add_filter( 'web_stories_enable_open_graph', '__return_false' );
	}
}
// Eseguiamo la pulizia solo nel frontend per evitare interferenze nell'admin
add_action( 'template_redirect', 'ntn_journal_cleanup_third_party_og' );

/**
 * Identificatore (@id) dell'entità VideoGame nel grafo, uguale per lo stesso nome di gioco (v4.15.1).
 *
 * @param string $game_title Nome del gioco.
 * @return string
 */
function ntn_core_schema_game_id( $game_title ) {
	return home_url( '/#videogame-' . sanitize_title( $game_title ) );
}

/**
 * Normalizza un titolo per confrontarlo con un altro: minuscolo, senza accenti né simboli (v4.15.1).
 *
 * @param mixed $text Titolo.
 * @return string
 */
function ntn_core_schema_norm( $text ) {
	$text = remove_accents( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' ) );
	$text = strtolower( preg_replace( '/[^a-z0-9]+/i', ' ', $text ) );
	return trim( $text );
}

/**
 * Sceglie i giochi di cui parla un articolo, per i dati strutturati (v4.15.1).
 *
 * Con un solo gioco collegato, o nessuno, restituisce l'elenco com'è. Con più giochi:
 * 1. se l'articolo ha il campo "Gioco recensito", vale solo il gioco che corrisponde;
 * 2. altrimenti si cercano i giochi il cui nome (intero, o la parte prima dei due punti) compare nel titolo.
 *    Se un nome è contenuto in uno più lungo ("Death Stranding" in "Death Stranding 2") o è solo l'inizio di un
 *    altro nome presente nel titolo ("Assassin's Creed" davanti a "Black Flag Resynced"), vince quello più lungo;
 * 3. con una recensione, con un titolo che comincia con il nome del gioco o dove un nome corto è stato scartato,
 *    vale solo il gioco rimasto;
 * 4. in tutti gli altri casi (raccolte, notizie su più giochi) valgono tutti i giochi collegati.
 * Mai un gioco scelto a caso.
 *
 * @param int $post_id ID dell'articolo.
 * @return array[] Giochi nel formato di ntn_get_post_games(), al massimo 12.
 */
function ntn_core_schema_pick_games( $post_id ) {
	if ( ! function_exists( 'ntn_get_post_games' ) ) {
		return array();
	}

	$post_id = (int) $post_id;
	$games   = array_values( array_filter(
		ntn_get_post_games( $post_id ),
		static function ( $game ) {
			return ! empty( $game['title'] ) && is_string( $game['title'] );
		}
	) );

	if ( count( $games ) > 1 ) {
		$contains = static function ( $haystack, $needle ) {
			return '' !== $needle && false !== strpos( ' ' . $haystack . ' ', ' ' . $needle . ' ' );
		};
		// Nome intero e parte prima dei due punti o del trattino ("Death Stranding 2: On the Beach" -> "Death Stranding 2").
		$keys = static function ( $title ) {
			$full = ntn_core_schema_norm( $title );
			$list = array( $full );
			$base = ntn_core_schema_norm( preg_split( '/\s*(?::|\s[-–—]\s)\s*/u', html_entity_decode( (string) $title, ENT_QUOTES, 'UTF-8' ), 2 )[0] );
			if ( '' !== $base && $base !== $full && strlen( $base ) >= 3 ) {
				$list[] = $base;
			}
			return $list;
		};

		$reviewed = ntn_core_schema_norm( get_post_meta( $post_id, 'reviewed_item', true ) );
		$matched  = array();
		if ( '' !== $reviewed ) {
			foreach ( $games as $game ) {
				$name = ntn_core_schema_norm( $game['title'] );
				if ( $name === $reviewed || $contains( $reviewed, $name ) || $contains( $name, $reviewed ) ) {
					$matched[] = $game;
				}
			}
		}

		$decided = ! empty( $matched );
		if ( ! $decided ) {
			$article_title = ntn_core_schema_norm( get_the_title( $post_id ) );
			$in_title      = array();
			foreach ( $games as $game ) {
				foreach ( $keys( $game['title'] ) as $key ) {
					if ( $contains( $article_title, $key ) ) {
						$in_title[] = $game;
						break;
					}
				}
			}

			// Vince il nome più lungo: via i nomi contenuti in un altro o seguiti nel titolo da un altro gioco.
			foreach ( $in_title as $game ) {
				$name    = ntn_core_schema_norm( $game['title'] );
				$dropped = false;
				foreach ( $in_title as $other ) {
					if ( $other === $game ) {
						continue;
					}
					foreach ( $keys( $other['title'] ) as $other_key ) {
						if ( strlen( $other_key ) > strlen( $name ) && $contains( $other_key, $name ) ) {
							$dropped = true;
						}
					}
					if ( $contains( $article_title, $name . ' ' . ntn_core_schema_norm( $other['title'] ) ) ) {
						$dropped = true;
					}
				}
				if ( ! $dropped ) {
					$matched[] = $game;
				}
			}

			if ( ! empty( $matched ) ) {
				$is_review = function_exists( 'has_category' ) && has_category( array( 'recensioni', 'recensione' ), $post_id );
				$starts    = false;
				foreach ( $matched as $game ) {
					foreach ( $keys( $game['title'] ) as $key ) {
						if ( $article_title === $key || 0 === strpos( $article_title, $key . ' ' ) ) {
							$starts = true;
						}
					}
				}
				// Se un nome corto è stato scartato perché fa parte di un titolo più lungo, l'articolo parla di quello lungo.
				$decided = $is_review || $starts || count( $matched ) < count( $in_title );
			}
		}

		if ( $decided ) {
			$games = $matched;
		}
	}

	return array_slice( $games, 0, 12 );
}

/**
 * Costruisce l'entità VideoGame di un gioco di un articolo con più giochi (v4.15.1).
 * Ogni gioco usa solo i propri dati: niente copertina o sviluppatore di un altro gioco.
 *
 * @param array $game    Gioco nel formato di ntn_get_post_games().
 * @param int   $post_id ID dell'articolo.
 * @return array
 */
function ntn_core_schema_multi_game_entity( array $game, $post_id ) {
	$title  = wp_strip_all_tags( wp_specialchars_decode( (string) $game['title'], ENT_QUOTES ) );
	$entity = array(
		'@type'               => 'VideoGame',
		'@id'                 => ntn_core_schema_game_id( $game['title'] ),
		'name'                => $title,
		'applicationCategory' => 'Game',
	);

	$url = ! empty( $game['igdb_url'] ) ? $game['igdb_url'] : ( ! empty( $game['hub_url'] ) ? $game['hub_url'] : '' );
	if ( ! empty( $url ) ) {
		$entity['url'] = esc_url( $url );
	}
	if ( ! empty( $game['cover'] ) ) {
		$cover = function_exists( 'ntn_normalize_game_cover_url' ) ? ntn_normalize_game_cover_url( $game['cover'] ) : $game['cover'];
		$entity['image'] = esc_url( $cover );
	}

	$term       = ( isset( $game['term'] ) && $game['term'] instanceof WP_Term ) ? $game['term'] : null;
	$view_model = ( $term && function_exists( 'ntn_get_game_view_model' ) ) ? ntn_get_game_view_model( $term ) : null;

	if ( ! empty( $game['developer'] ) ) {
		$developer = is_array( $game['developer'] ) ? implode( ', ', $game['developer'] ) : $game['developer'];
		$entity['author'] = array(
			'@type' => 'Organization',
			'name'  => wp_strip_all_tags( wp_specialchars_decode( $developer, ENT_QUOTES ) ),
		);
	}

	$publisher = $view_model && ! empty( $view_model['publisher'] ) ? $view_model['publisher'][0] : '';
	if ( '' === $publisher && ! empty( $game['publisher'] ) ) {
		$publisher = is_array( $game['publisher'] ) ? reset( $game['publisher'] ) : trim( explode( ',', (string) $game['publisher'] )[0] );
	}
	if ( $publisher ) {
		$entity['publisher'] = array(
			'@type' => 'Organization',
			'name'  => wp_strip_all_tags( wp_specialchars_decode( (string) $publisher, ENT_QUOTES ) ),
		);
	}

	$genres = $view_model && ! empty( $view_model['genres'] ) ? $view_model['genres'] : array();
	if ( empty( $genres ) && ! empty( $game['genres'] ) ) {
		$genres = is_array( $game['genres'] ) ? $game['genres'] : array_filter( array_map( 'trim', explode( ',', (string) $game['genres'] ) ) );
	}
	if ( ! empty( $genres ) ) {
		$entity['genre'] = array_values( array_map( 'wp_strip_all_tags', $genres ) );
	}

	$raw_release   = $view_model && ! empty( $view_model['release_raw'] ) ? $view_model['release_raw'] : $game['release'];
	$raw_precision = $view_model && ! empty( $view_model['release_raw'] ) ? $view_model['release_precision'] : $game['release_precision'];
	if ( $raw_release ) {
		$entity['datePublished'] = function_exists( 'ntn_release_schema_date' ) ? ntn_release_schema_date( $raw_release, (string) $raw_precision ) : $raw_release;
	}

	// Piattaforme: le categorie del sito valgono per l'articolo, quelle di IGDB sono del singolo gioco.
	$igdb_platforms = $view_model ? (array) $view_model['platforms'] : array();
	if ( empty( $igdb_platforms ) && ! empty( $game['platforms'] ) ) {
		$igdb_platforms = ntn_core_schema_platform_names( is_array( $game['platforms'] ) ? $game['platforms'] : array_map( 'trim', explode( ',', (string) $game['platforms'] ) ) );
	}
	$platforms = ntn_core_post_platform_names( $post_id, $igdb_platforms );
	if ( ! empty( $platforms ) ) {
		$entity['gamePlatform'] = $platforms;
	}

	return $entity;
}

/**
 * Entità VideoGame di un articolo con un solo gioco (o nessuno), come prima della 4.15.1.
 *
 * @param int $post_id ID dell'articolo.
 * @return array|null L'entità, o null se l'articolo non ha un gioco.
 */
function ntn_core_schema_single_game_entity( $post_id ) {
	// 1. Troviamo le informazioni sul gioco (Hub IGDB o meta)
	$game_title = '';
	$game_url = '';
	$game_image = '';
	$game_developer = '';

	$hub_info = ntn_get_hub_igdb_info( $post_id );
	if ( is_array( $hub_info ) && ! empty( $hub_info['title'] ) ) {
		$game_title = $hub_info['title'];
		$game_url = ! empty( $hub_info['url'] ) ? $hub_info['url'] : '';
		$game_image = ! empty( $hub_info['cover'] ) ? $hub_info['cover'] : '';
		if ( ! empty( $hub_info['developer'] ) ) {
			$game_developer = is_array( $hub_info['developer'] ) ? implode( ', ', $hub_info['developer'] ) : $hub_info['developer'];
		}
	}

	// Se non l'abbiamo trovato tramite hub_info, proviamo i tag
	$found_tag = null;
	if ( empty( $game_title ) ) {
		$tags = get_the_tags( $post_id );
		if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
			foreach ( $tags as $tag ) {
				$hub_title = get_term_meta( $tag->term_id, '_ntn_game_title', true );
				if ( ! empty( $hub_title ) ) {
					$game_title = $hub_title;
					$game_url = get_term_link( $tag );
					$game_image = get_term_meta( $tag->term_id, '_ntn_game_cover', true );
					$found_tag = $tag;
					break;
				}
			}
		}
	}

	if ( empty( $game_title ) ) {
		return null; // Nessun gioco associato
	}

	$game_schema_id = ntn_core_schema_game_id( $game_title );

	// 2. Costruiamo l'entità VideoGame tramite API Helper (se disponibile)
	$target_object = $found_tag ? $found_tag : $post_id;
	$view_model = function_exists( 'ntn_get_game_view_model' ) ? ntn_get_game_view_model( $target_object ) : null;
	
	$videogame_entity = array(
		'@type'               => 'VideoGame',
		'@id'                 => $game_schema_id,
		'name'                => wp_strip_all_tags( wp_specialchars_decode( $game_title ?? '', ENT_QUOTES ) ),
		'applicationCategory' => 'Game',
	);

	if ( ! empty( $game_url ) && ! is_wp_error( $game_url ) ) {
		$videogame_entity['url'] = esc_url( $game_url );
	}
	
	if ( ! empty( $game_image ) ) {
		if ( function_exists( 'ntn_normalize_game_cover_url' ) ) {
			$game_image = ntn_normalize_game_cover_url( $game_image );
		}
		$videogame_entity['image'] = esc_url( $game_image );
	}

	if ( ! empty( $game_developer ) ) {
		$videogame_entity['author'] = array(
			'@type' => 'Organization',
			'name'  => wp_strip_all_tags( wp_specialchars_decode( $game_developer ?? '', ENT_QUOTES ) )
		);
	}

	if ( $view_model ) {
		// Publisher
		if ( ! empty( $view_model['publisher'] ) ) {
			$videogame_entity['publisher'] = array(
				'@type' => 'Organization',
				'name'  => wp_strip_all_tags( wp_specialchars_decode( $view_model['publisher'][0] ?? '', ENT_QUOTES ) )
			);
		}

		// Genres
		if ( ! empty( $view_model['genres'] ) ) {
			$videogame_entity['genre'] = array_map( 'wp_strip_all_tags', $view_model['genres'] );
		}

		// Release Date
		if ( ! empty( $view_model['release'] ) ) {
			// Try to find raw release date from meta if the view model returned localized string
			$raw_release   = $view_model['release_raw'] ? $view_model['release_raw'] : get_post_meta( $post_id, '_ntn_game_release', true ); // v4.14.0
			$raw_precision = $view_model['release_raw'] ? $view_model['release_precision'] : get_post_meta( $post_id, '_ntn_game_release_precision', true );
			if ( $raw_release ) {
				$videogame_entity['datePublished'] = function_exists( 'ntn_release_schema_date' ) ? ntn_release_schema_date( $raw_release, (string) $raw_precision ) : $raw_release; // v3.19.2: solo le parti note
			}
		}

		// Platforms (v4.14.3): vincono le categorie piattaforma dell'articolo, IGDB aggiunge quelle che mancano.
		// Niente riserva "PC, Console": senza piattaforme il campo non c'è.
		$platforms = ntn_core_post_platform_names( $post_id, (array) $view_model['platforms'] );
		if ( ! empty( $platforms ) ) {
			$videogame_entity['gamePlatform'] = $platforms;
		}

		// Trailer
		if ( ! empty( $view_model['video_url'] ) ) {
			$video_id = str_replace( 'https://www.youtube.com/watch?v=', '', $view_model['video_url'] );
			$videogame_entity['trailer'] = array(
				'@type'        => 'VideoObject',
				'name'         => 'Trailer di ' . wp_strip_all_tags( wp_specialchars_decode( $game_title ?? '', ENT_QUOTES ) ),
				'description'  => 'Guarda il trailer ufficiale di ' . wp_strip_all_tags( wp_specialchars_decode( $game_title ?? '', ENT_QUOTES ) ),
				'thumbnailUrl' => 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg',
				'embedUrl'     => 'https://www.youtube.com/embed/' . $video_id,
				'uploadDate'   => get_the_date( 'c', $post_id )
			);
		}

		// Screenshots
		if ( ! empty( $view_model['screenshots'] ) ) {
			$videogame_entity['screenshot'] = array();
			foreach ( $view_model['screenshots'] as $screenshot ) {
				$videogame_entity['screenshot'][] = array(
					'@type' => 'ImageObject',
					'url'   => esc_url_raw( $screenshot )
				);
			}
		}
	} else {
		// Senza view model: stesse piattaforme, solo dalle categorie (v4.14.3)
		$platforms = ntn_core_post_platform_names( $post_id );
		if ( ! empty( $platforms ) ) {
			$videogame_entity['gamePlatform'] = $platforms;
		}
	}

	return $videogame_entity;
}

/**
 * Inietta l'entità VideoGame e la collega tramite 'about' nel Graph JSON-LD di The SEO Framework.
 */
function ntn_tsf_add_videogame_schema( $data, $context = array() ) {
	if ( ! is_single() || ! ntn_core_schema_enabled() ) {
		return $data;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return $data;
	}

	// 1. Giochi dell'articolo (v4.15.1): con più giochi collegati si sceglie quello giusto, mai il primo a caso
	$videogame_entities = array();
	$picked_games       = is_singular( 'videogioco' ) ? array() : ntn_core_schema_pick_games( $post_id );
	// Anche un solo gioco scelto tra più collegati passa di qui: il percorso classico prenderebbe il primo tag.
	$linked_games_count = function_exists( 'ntn_get_post_games' ) && ! empty( $picked_games ) ? count( ntn_get_post_games( $post_id ) ) : 0;
	if ( count( $picked_games ) > 1 || ( ! empty( $picked_games ) && $linked_games_count > 1 ) ) {
		foreach ( $picked_games as $picked_game ) {
			$videogame_entities[] = ntn_core_schema_multi_game_entity( $picked_game, $post_id );
		}
	} else {
		$single_entity = ntn_core_schema_single_game_entity( $post_id );
		if ( null === $single_entity ) {
			return $data; // Nessun gioco associato
		}
		$videogame_entities[] = $single_entity;
	}
	$game_schema_ids = array_values( array_unique( array_column( $videogame_entities, '@id' ) ) );

	// 2b. Hub del videogioco (v3.11.0): non è un articolo ma una pagina di raccolta che parla del gioco
	if ( is_singular( 'videogioco' ) && function_exists( 'ntn_get_hub_videogame_entity' ) && is_array( $data ) ) {
		$hub_game = ntn_get_hub_videogame_entity( (int) $post_id );
		foreach ( $data as $key => $entity ) {
			if ( is_array( $entity ) && isset( $entity['@type'] ) && in_array( 'WebPage', (array) $entity['@type'], true ) ) {
				$data[ $key ]['@type']      = 'CollectionPage';
				$data[ $key ]['about']      = array( '@id' => $hub_game['@id'] );
				$data[ $key ]['mainEntity'] = array( '@id' => $hub_game['@id'] );
				if ( empty( $data[ $key ]['description'] ) && ! empty( $hub_game['description'] ) ) {
					$data[ $key ]['description'] = $hub_game['description'];
				}
				$data[] = $hub_game;
				break;
			}
		}
		return $data;
	}

	// 3. Collega l'entità VideoGame all'Article
	$article_found = false;
	if ( is_array( $data ) ) {
		foreach ( $data as $key => $entity ) {
			if ( is_array( $entity ) && isset( $entity['@type'] ) ) {
				$types = (array) $entity['@type'];
				if ( in_array( 'Article', $types ) || in_array( 'NewsArticle', $types ) || in_array( 'BlogPosting', $types ) || in_array( 'WebPage', $types ) ) {
					
					// Upgrade WebPage to NewsArticle if it's missing the Article nature
					if ( in_array( 'WebPage', $types ) && count( array_intersect( array( 'Article', 'NewsArticle', 'BlogPosting' ), $types ) ) === 0 ) {
						$data[$key]['@type'] = array( 'WebPage', 'NewsArticle' );
					}

					// Fix for Google Rich Results requirements on Article/NewsArticle/Review
					$current_types = (array) $data[$key]['@type'];
					if ( count( array_intersect( array( 'Article', 'NewsArticle', 'BlogPosting', 'Review' ), $current_types ) ) > 0 ) {
						if ( empty( $data[$key]['headline'] ) ) {
							$data[$key]['headline'] = wp_strip_all_tags( wp_specialchars_decode( get_the_title( $post_id ), ENT_QUOTES ) );
						}
						
						if ( empty( $data[$key]['image'] ) ) {
							$thumbnail_id = get_post_thumbnail_id( $post_id );
							if ( $thumbnail_id ) {
								$image_url = wp_get_attachment_image_url( $thumbnail_id, 'full' );
								if ( $image_url ) {
									$data[$key]['image'] = array(
										'@type' => 'ImageObject',
										'url'   => esc_url( $image_url )
									);
								}
							}
						}
						
						if ( empty( $data[$key]['publisher'] ) ) {
							$data[$key]['publisher'] = array(
								'@id' => home_url( '/#/schema/Organization' )
							);
						}

						// Fix date timezones (Google Rich Results recommends ISO 8601 with timezone)
						$data[$key]['datePublished'] = get_the_time( 'c', $post_id );
						$data[$key]['dateModified']  = get_the_modified_time( 'c', $post_id );

						// Fix missing author URL
						if ( isset( $data[$key]['author'] ) && is_array( $data[$key]['author'] ) ) {
							if ( empty( $data[$key]['author']['url'] ) ) {
								$author_id = get_post_field( 'post_author', $post_id );
								$data[$key]['author']['url'] = esc_url( get_author_posts_url( $author_id ) );
							}
						}
					}
					
					$rating = get_post_meta( $post_id, 'rating', true );
					// v4.15.1: una Review dice quale gioco recensisce; con più giochi e nessuno scelto resta un articolo normale (about), senza voto
					if ( ! empty( $rating ) && 1 === count( $game_schema_ids ) && get_option( 'ntn_seo_enable_review_schema', 'yes' ) === 'yes' ) {
						// Upgrade Article to Review
						$data[$key]['@type'] = array( 'WebPage', 'Review' );
						$data[$key]['reviewRating'] = array(
							'@type'       => 'Rating',
							'ratingValue' => intval( $rating ),
							'bestRating'  => '5',
							'worstRating' => '1'
						);
						
						$verdict = get_post_meta( $post_id, 'verdict', true );
						if ( ! empty( $verdict ) ) {
							$data[$key]['reviewBody'] = wp_strip_all_tags( wp_trim_words( $verdict, 150 ) );
						}
						
						$pros = get_post_meta( $post_id, 'pros', true );
						if ( ! empty( $pros ) ) {
							$pros_arr = is_array( $pros ) ? $pros : array_filter( array_map( 'trim', explode( "\n", $pros ) ) );
							if ( ! empty( $pros_arr ) ) {
								$data[$key]['positiveNotes'] = array( '@type' => 'ItemList', 'itemListElement' => array() );
								$i = 1;
								foreach ( $pros_arr as $pro ) {
									$data[$key]['positiveNotes']['itemListElement'][] = array( '@type' => 'ListItem', 'position' => $i++, 'name' => wp_strip_all_tags( $pro ) );
								}
							}
						}
						
						$cons = get_post_meta( $post_id, 'cons', true );
						if ( ! empty( $cons ) ) {
							$cons_arr = is_array( $cons ) ? $cons : array_filter( array_map( 'trim', explode( "\n", $cons ) ) );
							if ( ! empty( $cons_arr ) ) {
								$data[$key]['negativeNotes'] = array( '@type' => 'ItemList', 'itemListElement' => array() );
								$j = 1;
								foreach ( $cons_arr as $con ) {
									$data[$key]['negativeNotes']['itemListElement'][] = array( '@type' => 'ListItem', 'position' => $j++, 'name' => wp_strip_all_tags( $con ) );
								}
							}
						}
						
						// Link to VideoGame via itemReviewed
						$data[$key]['itemReviewed'] = array( '@id' => $game_schema_ids[0] );
					} else {
						// Normal Article -> about -> VideoGame
						if ( ! isset( $data[$key]['about'] ) ) {
							$data[$key]['about'] = array();
						}
						
						$is_numeric = true;
						if ( is_array( $data[$key]['about'] ) ) {
							foreach ( array_keys( $data[$key]['about'] ) as $k ) {
								if ( ! is_int( $k ) ) {
									$is_numeric = false;
									break;
								}
							}
						} else {
							$is_numeric = false;
						}

						if ( empty( $data[$key]['about'] ) || $is_numeric ) {
							if ( ! is_array( $data[$key]['about'] ) ) {
								$data[$key]['about'] = array( $data[$key]['about'] );
							}
							if ( count( $data[$key]['about'] ) === 1 && empty( $data[$key]['about'][0] ) ) {
								$data[$key]['about'] = array();
							}
							foreach ( $game_schema_ids as $game_schema_id ) {
								$data[$key]['about'][] = array( '@id' => $game_schema_id );
							}
						} else {
							$existing_about = $data[$key]['about'];
							$data[$key]['about'] = array( $existing_about );
							foreach ( $game_schema_ids as $game_schema_id ) {
								$data[$key]['about'][] = array( '@id' => $game_schema_id );
							}
						}
					}
					
					$article_found = true;
					break;
				}
			}
		}
	}

	// 4. Se abbiamo trovato l'articolo, iniettiamo il VideoGame nel grafo
	if ( $article_found ) {
		// IMPORTANT: Usa l'append array [] (numerico) e non una chiave stringa come 'ntn_videogame'
		// per evitare che l'intero @graph diventi un Oggetto invece che un Array in JSON.
		foreach ( $videogame_entities as $videogame_entity ) {
			$data[] = $videogame_entity;
		}
	}

	return $data;
}
add_filter( 'the_seo_framework_schema_graph_data', 'ntn_tsf_add_videogame_schema', 10, 2 );

