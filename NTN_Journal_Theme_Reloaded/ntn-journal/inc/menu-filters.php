<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Contesto del menu nella testata: 'desktop' (riga della testata) o 'mobile' (pannello da telefono).
 * v3.12.0: lo stesso menu viene stampato due volte, una per contesto.
 *
 * @param object $args Argomenti di wp_nav_menu.
 * @return string
 */
function ntn_journal_menu_context( $args ) {
	return ( isset( $args->ntn_context ) && 'mobile' === $args->ntn_context ) ? 'mobile' : 'desktop';
}

/**
 * Siamo nella tendina "Piattaforme" della testata (pannello a colonne, v3.13.1)?
 *
 * @param object $args Argomenti di wp_nav_menu.
 * @return bool
 */
function ntn_journal_in_platform_drop( $args ) {
	return isset( $args->theme_location ) && 'menu-piattaforme' === $args->theme_location && 'desktop' === ntn_journal_menu_context( $args );
}

/**
 * Add custom classes to nav menu list items and links for dynamic customizer menus
 */
function ntn_journal_menu_classes( $classes, $item, $args, $depth = 0 ) {
	if ( ! is_array( $classes ) ) {
		$classes = array();
	}
	if ( isset( $args->theme_location ) && in_array( $args->theme_location, array( 'menu-contenuti', 'menu-piattaforme', 'menu-1' ), true ) ) {
		$in_dropdown = ntn_journal_in_platform_drop( $args );
		if ( $in_dropdown ) {
			// Pannello a colonne: ogni voce principale è una colonna, le sue voci stanno sotto
			$classes[] = 0 === $depth ? 'ntn-drop-col' : 'ntn-drop-item';
		} elseif ( $depth === 0 ) {
			$classes[] = 'inline-block';
			if ( in_array( 'menu-item-has-children', $classes, true ) ) {
				$classes[] = 'relative group'; // Add group for hover state on parent
			}
		} else {
			$classes[] = 'block w-full';
		}
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'ntn_journal_menu_classes', 10, 4 );

function ntn_journal_menu_link_atts( $atts, $item, $args, $depth = 0 ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}
	if ( isset( $args->theme_location ) ) {
		if ( ntn_journal_in_platform_drop( $args ) ) {
			// v3.13.1: titolo di colonna (voce principale) e voci sotto, rientrate per livello
			$atts['class'] = 0 === $depth ? 'ntn-drop-head' : ( $depth > 1 ? 'ntn-drop-link is-deep' : 'ntn-drop-link' );
		} elseif ( $depth > 0 ) {
			// Indentation and styling based on depth
			// Dropdown link style (componenti .ntn-submenu-link in build/input.css)
			$atts['class'] = ( $depth > 1 ) ? 'ntn-submenu-link is-deep' : 'ntn-submenu-link';
		} else {
			// Top level link style
			$item_classes = ( isset( $item->classes ) && is_array( $item->classes ) ) ? $item->classes : array();
			if ( in_array( 'menu-item-has-children', $item_classes, true ) ) {
				$atts['aria-haspopup'] = 'true';
				$atts['aria-expanded'] = 'false';
			}
			if ( 'menu-contenuti' === $args->theme_location || 'menu-1' === $args->theme_location ) {
				$is_current = in_array( 'current-menu-item', $item_classes, true ) || in_array( 'current_page_item', $item_classes, true );
				$atts['class'] = $is_current ? 'ntn-nav-link is-current' : 'ntn-nav-link';
				if ( $is_current ) {
					$atts['aria-current'] = 'page';
				}
			} elseif ( 'menu-piattaforme' === $args->theme_location ) {
				// Nel pannello da telefono le piattaforme sono voci normali
				$atts['class'] = 'ntn-nav-link';
			}
		}
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'ntn_journal_menu_link_atts', 10, 4 );

/**
 * Filter to add Tailwind classes to the dropdown ul (.sub-menu)
 */
function ntn_journal_submenu_classes( $classes, $args, $depth = 0 ) {
	if ( ! is_array( $classes ) ) {
		$classes = array();
	}
	if ( 'mobile' === ntn_journal_menu_context( $args ) ) {
		// Nel pannello da telefono i sottomenu sono sempre aperti, rientrati sotto la voce
		$classes[] = 'ntn-submenu-mobile';
	} elseif ( ntn_journal_in_platform_drop( $args ) ) {
		// Nella tendina Piattaforme i sottomenu sono sempre aperti: niente tendine dentro la tendina
		$classes[] = 0 === $depth ? 'ntn-drop-sub' : 'ntn-drop-sub is-deep';
	} elseif ( $depth === 0 ) {
		// First level dropdown: absolute positioning, hidden by default, shown on group-hover or focus-within
		$classes[] = 'ntn-submenu absolute left-0 top-full min-w-[200px] z-50 hidden group-hover:block group-focus-within:block';
	} else {
		// Nested levels (depth >= 1): display inline as a flattened list
		$classes[] = 'ntn-submenu-nested block w-full';
	}
	return $classes;
}
add_filter( 'nav_menu_submenu_css_class', 'ntn_journal_submenu_classes', 10, 3 );

/**
 * Append a dotted arrow indicator to menu items with children (Nothing OS style)
 * Uses walker_nav_menu_start_el to ensure it applies robustly to the final HTML output.
 */
function ntn_journal_inject_arrow_start_el( $item_output, $item, $depth, $args ) {
	if ( ! is_string( $item_output ) ) {
		return $item_output;
	}
	// Aggiunge la freccetta SOLO se l'elemento ha effettivamente dei sottomenu (menu a tendina)
	if ( isset( $item->classes ) && is_array( $item->classes ) && in_array( 'menu-item-has-children', $item->classes, true ) && $depth === 0 && 'desktop' === ntn_journal_menu_context( $args ) && ! ntn_journal_in_platform_drop( $args ) ) {
		// Rimuove vecchi pallini rossi inseriti manualmente dall'utente per fare spazio alla freccetta
		$item_output = preg_replace( '/<span[^>]*bg-red-600[^>]*><\/span>/i', '', $item_output );
		$item_output = str_replace( '🔴', '', (string) $item_output ); // Rimuove eventuali emoji inserite a mano
		
		// L'SVG della freccetta dinamica (animazione pulse e stile Nothing OS)
		$arrow = '<svg class="w-3 h-3 ml-1 text-red-600 group-hover:-rotate-180 transition-transform duration-300 shrink-0" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="square" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg>';
		
		// Inserisce la freccetta appena prima della chiusura del link
		$item_output = str_replace( '</a>', $arrow . '</a>', (string) $item_output );
	}
	return $item_output;
}
add_filter( 'walker_nav_menu_start_el', 'ntn_journal_inject_arrow_start_el', 20, 4 );

/**
 * v3.12.0: le voci dei menu non hanno più il prefisso '//' (come nel mockup). Il '//' resta
 * solo nelle intestazioni di sezione. Il filtro sui titoli dei menu è stato rimosso.
 */

/**
 * Nel pannello da telefono il menu viene stampato una seconda volta: niente id duplicati sulle voci.
 *
 * @param string $menu_id ID della voce.
 * @param object $item    Voce del menu.
 * @param object $args    Argomenti di wp_nav_menu.
 * @return string
 */
function ntn_journal_mobile_menu_item_id( $menu_id, $item, $args ) {
	return 'mobile' === ntn_journal_menu_context( $args ) ? '' : $menu_id;
}
add_filter( 'nav_menu_item_id', 'ntn_journal_mobile_menu_item_id', 10, 3 );

/**
 * Stampa i menu della testata (v3.12.0).
 * - 'desktop': voci "Articoli" in riga, tendina "Piattaforme", voci del menu Primary;
 * - 'mobile':  gli stessi menu in colonna, con un'etichetta per gruppo.
 *
 * @param string $context 'desktop' o 'mobile'.
 */
function ntn_journal_header_menus( $context = 'desktop' ) {
	$context   = 'mobile' === $context ? 'mobile' : 'desktop';
	$is_mobile = 'mobile' === $context;
	$base_args = array(
		'container'   => false,
		'fallback_cb' => false,
		'items_wrap'  => '<ul class="%2$s">%3$s</ul>',
		'ntn_context' => $context,
	);

	// 1. Articoli (menu "Gestione Contenuti", con un elenco di ripiego se non è assegnato)
	echo '<div class="ntn-nav-group">';
	if ( $is_mobile ) {
		echo '<p class="ntn-mobile-nav-label">Articoli</p>';
	}
	if ( has_nav_menu( 'menu-contenuti' ) ) {
		wp_nav_menu( array_merge( $base_args, array( 'theme_location' => 'menu-contenuti', 'menu_class' => 'ntn-nav-list' ) ) );
	} else {
		$ntn_cat_link   = static function ( $slug ) {
			$cat  = get_category_by_slug( $slug );
			$link = $cat ? get_category_link( $cat->term_id ) : '';
			return ( $link && ! is_wp_error( $link ) && is_string( $link ) ) ? $link : home_url( '/' );
		};
		$ntn_posts_link = get_post_type_archive_link( 'post' );
		$ntn_fallback   = array(
			array( 'Tutti', home_url( '/' ), is_front_page() ),
			array( 'Notizie', $ntn_cat_link( 'notizie' ), is_category( 'notizie' ) ),
			array( 'Editoriali', $ntn_cat_link( 'editoriali' ), is_category( 'editoriali' ) ),
			array( 'Recensioni', $ntn_cat_link( 'recensioni' ), is_category( 'recensioni' ) ),
			array( 'Autori', ( $ntn_posts_link && ! is_wp_error( $ntn_posts_link ) && is_string( $ntn_posts_link ) ) ? $ntn_posts_link : home_url( '/' ), false ),
		);
		echo '<ul class="ntn-nav-list">';
		foreach ( $ntn_fallback as $ntn_item ) {
			echo '<li><a href="' . esc_url( $ntn_item[1] ) . '" class="ntn-nav-link' . ( $ntn_item[2] ? ' is-current" aria-current="page' : '' ) . '">' . esc_html( $ntn_item[0] ) . '</a></li>';
		}
		echo '</ul>';
	}
	echo '</div>';

	// 2. Piattaforme: gruppo nel pannello da telefono; da computer è un pulsante a destra, tra gli strumenti (v3.22.0)
	if ( has_nav_menu( 'menu-piattaforme' ) && $is_mobile ) {
		echo '<div class="ntn-nav-group"><p class="ntn-mobile-nav-label">Piattaforme</p>';
		wp_nav_menu( array_merge( $base_args, array( 'theme_location' => 'menu-piattaforme', 'menu_class' => 'ntn-nav-list' ) ) );
		echo '</div>';
	}

	// 3. Menu Primary (se assegnato): altre voci nella stessa riga
	if ( has_nav_menu( 'menu-1' ) ) {
		echo '<div class="ntn-nav-group">';
		if ( $is_mobile ) {
			echo '<p class="ntn-mobile-nav-label">Altro</p>';
		}
		wp_nav_menu( array_merge( $base_args, array( 'theme_location' => 'menu-1', 'menu_class' => 'ntn-nav-list' ) ) );
		echo '</div>';
	}
}

/**
 * Pulsante "Piattaforme" tra gli strumenti della testata (v3.22.0, variante C del mockup): squadrato, con l'icona
 * a punti del controller; apre il pannello a tutta larghezza sotto la testata. Una linea sottile lo separa da lente
 * e chiaro/scuro. Da telefono le piattaforme restano nel pannello del menu.
 */
function ntn_journal_header_platform_drop() {
	if ( ! has_nav_menu( 'menu-piattaforme' ) ) {
		return;
	}
	$dots = '';
	foreach ( str_split( implode( '', array( '0111110', '1010101', '1111111', '1100011', '1000001' ) ) ) as $on ) {
		$dots .= '1' === $on ? '<i class="is-on"></i>' : '<i></i>';
	}
	echo '<div class="ntn-nav-drop is-mega ntn-pdrop-wrap">';
	echo '<button type="button" class="ntn-nav-drop-btn ntn-pdrop-tool" aria-haspopup="true" aria-expanded="false" aria-controls="ntn-pdrop"><span class="ntn-pdrop-icon is-mini" aria-hidden="true">' . $dots . '</span><span>Piattaforme</span><svg class="ntn-nav-drop-arrow" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="square" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg></button>';
	ntn_journal_platform_drop_panel();
	echo '</div>';
	echo '<span class="ntn-tools-sep" aria-hidden="true"></span>';
}

/**
 * Articoli di una voce del menu Piattaforme: categoria con le sue sottocategorie (come nelle Hub), 0 se non è una categoria.
 *
 * @param WP_Post $item Voce del menu.
 * @return int
 */
function ntn_journal_platform_item_count( $item ) {
	if ( 'taxonomy' !== $item->type || 'category' !== $item->object ) {
		return 0;
	}
	if ( function_exists( 'ntn_core_category_hub_count' ) && function_exists( 'ntn_core_category_hub_tree' ) ) {
		return (int) ntn_core_category_hub_count( ntn_core_category_hub_tree( (int) $item->object_id ) );
	}
	$term = get_term( (int) $item->object_id, 'category' );
	return $term instanceof WP_Term ? (int) $term->count : 0;
}

/**
 * Icona a matrice di punti (7 × 5) di una sezione: postazione PC, telefono o controller.
 *
 * @param WP_Post $item Voce del menu.
 * @return string Markup dell'icona.
 */
function ntn_journal_platform_item_icon( $item ) {
	$kind = '';
	if ( 'taxonomy' === $item->type && 'category' === $item->object && function_exists( 'ntn_core_category_hub_illustration' ) ) {
		$term = get_term( (int) $item->object_id, 'category' );
		$kind = $term instanceof WP_Term ? ntn_core_category_hub_illustration( $term, 'console' ) : '';
	}
	$hay = strtolower( $kind . ' ' . $item->title );
	if ( false !== strpos( $hay, 'pc' ) ) {
		$rows = array( '1111111', '1000001', '1000001', '1111111', '0011100' );
	} elseif ( preg_match( '/android|ios|mobile|phone|telefon/', $hay ) ) {
		$rows = array( '0011100', '0010100', '0010100', '0010100', '0011100' );
	} else {
		$rows = array( '0111110', '1010101', '1111111', '1100011', '1000001' );
	}
	$dots = '';
	foreach ( str_split( implode( '', $rows ) ) as $on ) {
		$dots .= '1' === $on ? '<i class="is-on"></i>' : '<i></i>';
	}
	return '<span class="ntn-pdrop-icon" aria-hidden="true">' . $dots . '</span>';
}

/**
 * Tendina "Piattaforme" (v3.22.0): una colonna per voce principale del menu, con icona a punti, nome grande e
 * numero di articoli; sotto, le sottovoci (marchi) con il loro numero e le loro sottovoci (console) come etichette.
 * Una voce senza sottovoci mostra la descrizione della sua categoria. I contenuti restano quelli del menu di WordPress.
 */
function ntn_journal_platform_drop_panel() {
	$locations = get_nav_menu_locations();
	$menu_id   = ! empty( $locations['menu-piattaforme'] ) ? (int) $locations['menu-piattaforme'] : 0;
	if ( ! $menu_id ) {
		return;
	}
	// In memoria per un'ora: il pannello è uguale in tutte le pagine; si rinnova con il menu, le Hub o la versione del tema
	$menu_obj  = wp_get_nav_menu_object( $menu_id );
	$cache_key = 'ntn_pdrop_' . md5( $menu_id . '|' . ( $menu_obj ? (int) $menu_obj->count : 0 ) . '|' . get_option( 'ntn_cat_hub_version', '0' ) . '|' . ( function_exists( 'wp_get_theme' ) ? wp_get_theme( get_template() )->get( 'Version' ) : '' ) );
	$cached    = get_transient( $cache_key );
	if ( is_string( $cached ) && '' !== $cached ) {
		echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput -- markup già sanificato quando è stato costruito.
		return;
	}
	$items = wp_get_nav_menu_items( $menu_id );
	if ( ! is_array( $items ) || empty( $items ) ) {
		return;
	}
	$children = array();
	foreach ( $items as $item ) {
		$children[ (int) $item->menu_item_parent ][] = $item;
	}
	$tops  = isset( $children[0] ) ? $children[0] : array();
	$total = 0;
	$cols  = '';
	foreach ( $tops as $i => $top ) {
		$count  = ntn_journal_platform_item_count( $top );
		$total += $count;
		$meta   = $count ? number_format_i18n( $count ) . ( 1 === $count ? ' articolo' : ' articoli' ) : '';
		if ( 'taxonomy' === $top->type && function_exists( 'ntn_core_category_hub_context' ) && ntn_core_category_hub_context( (int) $top->object_id ) ) {
			$meta .= ( '' !== $meta ? ' · ' : '' ) . 'Hub';
		}
		$col  = '<div class="ntn-pdrop-col" style="--i:' . (int) $i . '">';
		$col .= '<a class="ntn-pdrop-head" href="' . esc_url( $top->url ) . '">' . ntn_journal_platform_item_icon( $top );
		$col .= '<span class="min-w-0"><span class="ntn-pdrop-t">' . esc_html( $top->title ) . '</span>';
		$col .= '' !== $meta ? '<span class="ntn-pdrop-m">' . esc_html( $meta ) . '</span>' : '';
		$col .= '</span><span class="ntn-pdrop-go" aria-hidden="true">→</span></a>';

		$brands = isset( $children[ (int) $top->ID ] ) ? $children[ (int) $top->ID ] : array();
		if ( $brands ) {
			$col .= '<ul class="ntn-pdrop-brands">';
			foreach ( $brands as $brand ) {
				$b_count = ntn_journal_platform_item_count( $brand );
				$col    .= '<li><a class="ntn-pdrop-b" href="' . esc_url( $brand->url ) . '">' . esc_html( $brand->title ) . ( $b_count ? '<span>' . esc_html( number_format_i18n( $b_count ) ) . '</span>' : '' ) . '</a>';
				$plats   = isset( $children[ (int) $brand->ID ] ) ? $children[ (int) $brand->ID ] : array();
				if ( $plats ) {
					$col .= '<span class="ntn-pdrop-plats">';
					foreach ( $plats as $plat ) {
						$col .= '<a href="' . esc_url( $plat->url ) . '">' . esc_html( $plat->title ) . '</a>';
					}
					$col .= '</span>';
				}
				$col .= '</li>';
			}
			$col .= '</ul>';
		} elseif ( 'taxonomy' === $top->type ) {
			$desc = wp_strip_all_tags( (string) term_description( (int) $top->object_id ) );
			if ( '' !== trim( $desc ) ) {
				$col .= '<p class="ntn-pdrop-note">' . esc_html( wp_trim_words( $desc, 18, '…' ) ) . '</p>';
			}
		}
		$cols .= $col . '</div>';
	}

	$html  = '<div class="ntn-submenu ntn-nav-drop-panel ntn-pdrop" id="ntn-pdrop">';
	$html .= '<div class="ntn-pdrop-top"><span>// Piattaforme</span><span><b>' . esc_html( (string) count( $tops ) ) . '</b> ' . ( 1 === count( $tops ) ? 'sezione' : 'sezioni' ) . ( $total ? ' · <b>' . esc_html( number_format_i18n( $total ) ) . '</b> articoli' : '' ) . '</span></div>';
	$html .= '<div class="ntn-pdrop-cols">' . $cols . '</div>';
	$html .= '</div>';
	set_transient( $cache_key, $html, HOUR_IN_SECONDS );
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- ogni parte è già sanificata sopra.
}

/**
 * Svuota la memoria del pannello "Piattaforme" quando si salva un menu (v4.25.1).
 * Prima, rinominare o spostare una voce senza cambiarne il numero lasciava il pannello vecchio fino a un'ora.
 */
function ntn_journal_platform_drop_flush() {
	global $wpdb;
	if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
		return;
	}
	$like  = $wpdb->esc_like( '_transient_ntn_pdrop_' ) . '%';
	$names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- solo al salvataggio di un menu.
	foreach ( (array) $names as $name ) {
		delete_transient( substr( (string) $name, strlen( '_transient_' ) ) );
	}
}
add_action( 'wp_update_nav_menu', 'ntn_journal_platform_drop_flush' );

/* Rimosso filtro ntn_journal_custom_menu_link_atts poiché ridondante con ntn_journal_menu_link_atts */


