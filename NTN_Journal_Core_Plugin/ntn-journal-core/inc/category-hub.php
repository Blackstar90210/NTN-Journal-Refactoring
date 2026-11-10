<?php
/**
 * NTN Journal - Hub di categoria (v3.14.0)
 *
 * Trasforma una categoria in una "Hub di sezione" con un'impaginazione dedicata. Due tipi:
 * - 'console': la categoria (es. Console) raccoglie i marchi (Nintendo, Sony, Microsoft), che a loro
 *   volta raccolgono le piattaforme (Switch, PS5, Xbox Series X|S...). I marchi aprono la stessa Hub
 *   con il marchio già scelto, ognuno sul suo indirizzo di sempre.
 * - 'studi':   la categoria (es. Sviluppatori italiani) mostra l'indice degli studi, ricavato dallo
 *   sviluppatore dei giochi collegati a IGDB.
 * - 'statistiche' (v4.2.0): la categoria mostra i numeri della sezione. Le sottocategorie restano archivi normali.
 *
 * Qui stanno impostazioni, dati, cache e dati strutturati. Il tema legge solo tramite
 * ntn_core_category_hub_context() e ntn_core_get_category_hub_data(), sempre dietro function_exists().
 * L'indirizzo delle categorie non cambia mai: se l'opzione è spenta, la categoria resta un archivio normale.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
==========================================================================
	1. Impostazioni (v3.18.0: si governano dalla scheda "Hub" di NTN Journal Funzioni)
	I dati restano dove erano (meta della categoria _ntn_hub_*): le Hub già accese
	si ritrovano nella scheda così come sono.
	========================================================================== */

/**
 * Valori ammessi per le impostazioni della Hub.
 *
 * @return array<string, array<string, string>>
 */
function ntn_core_category_hub_choices(): array {
	return array(
		'type'         => array(
			'console'     => 'Piattaforme: console, mobile, PC (marchi e dispositivi)',
			'studi'       => 'Studi (indice degli sviluppatori)',
			'statistiche' => 'Statistiche (i numeri della sezione)',
		),
		'illustration' => array(
			'auto'      => 'Automatica',
			'joycon'    => 'Joy-Con (Nintendo)',
			'dualsense' => 'DualSense (PlayStation)',
			'xbox'      => 'Controller Xbox',
			'pc'        => 'Postazione PC (v3.22.0)',
			'android'   => 'Telefono Android (v3.22.0)',
			'ios'       => 'iPhone (v3.22.0)',
			'italia'    => 'Italia a punti',
			'none'      => 'Nessuna',
		),
	);
}

/**
 * Legge un'impostazione della Hub di una categoria.
 *
 * @param int    $term_id ID della categoria.
 * @param string $key     enabled | type | intro | title | illustration | featured | excluded.
 * @return mixed
 */
function ntn_core_category_hub_meta( int $term_id, string $key ) {
	$value = get_term_meta( $term_id, '_ntn_hub_' . $key, true );
	switch ( $key ) {
		case 'enabled':
			return '1' === (string) $value;
		case 'featured':
			return (int) $value;
		case 'type':
			return array_key_exists( (string) $value, ntn_core_category_hub_choices()['type'] ) ? (string) $value : 'console';
		case 'illustration':
			return array_key_exists( (string) $value, ntn_core_category_hub_choices()['illustration'] ) ? (string) $value : 'auto';
		default:
			return is_string( $value ) ? $value : '';
	}
}

/**
 * Salva le impostazioni della Hub di una categoria (chiamata dalla scheda "Hub", che controlla nonce e permessi).
 *
 * @param int   $term_id ID della categoria.
 * @param array $data    Valori grezzi (già senza barre): enabled, type, title, intro, illustration, featured, excluded.
 */
function ntn_core_category_hub_save_settings( int $term_id, array $data ): void {
	$choices = ntn_core_category_hub_choices();
	if ( array_key_exists( 'enabled', $data ) ) {
		update_term_meta( $term_id, '_ntn_hub_enabled', ! empty( $data['enabled'] ) ? '1' : '0' );
	}
	if ( array_key_exists( 'type', $data ) ) {
		$type = sanitize_key( (string) $data['type'] );
		update_term_meta( $term_id, '_ntn_hub_type', array_key_exists( $type, $choices['type'] ) ? $type : 'console' );
	}
	if ( array_key_exists( 'illustration', $data ) ) {
		$illustration = sanitize_key( (string) $data['illustration'] );
		update_term_meta( $term_id, '_ntn_hub_illustration', array_key_exists( $illustration, $choices['illustration'] ) ? $illustration : 'auto' );
	}
	if ( array_key_exists( 'title', $data ) ) {
		update_term_meta( $term_id, '_ntn_hub_title', sanitize_text_field( (string) $data['title'] ) );
	}
	if ( array_key_exists( 'intro', $data ) ) {
		update_term_meta( $term_id, '_ntn_hub_intro', sanitize_textarea_field( (string) $data['intro'] ) );
	}
	if ( array_key_exists( 'featured', $data ) ) {
		update_term_meta( $term_id, '_ntn_hub_featured', absint( $data['featured'] ) );
	}
	if ( array_key_exists( 'excluded', $data ) ) {
		update_term_meta( $term_id, '_ntn_hub_excluded', sanitize_text_field( (string) $data['excluded'] ) );
	}
	ntn_core_category_hub_flush();
}

/**
 * Categorie con la Hub accesa, in ordine alfabetico.
 *
 * @return WP_Term[]
 */
function ntn_core_category_hubs(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'meta_key'   => '_ntn_hub_enabled', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- poche categorie, solo in amministrazione.
			'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( ! is_array( $terms ) ) {
		return array();
	}
	usort(
		$terms,
		static function ( $a, $b ) {
			return strnatcasecmp( $a->name, $b->name );
		}
	);
	return $terms;
}

/**
 * Nella pagina "Modifica categoria" resta solo un rimando alla scheda "Hub" (v3.18.0).
 *
 * @param WP_Term $term Categoria.
 */
function ntn_core_category_hub_fields( $term ): void {
	if ( ! $term instanceof WP_Term || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$url   = add_query_arg(
		array(
			'page' => 'ntn-journal-functions',
			'tab'  => 'hub',
			'edit' => (int) $term->term_id,
		),
		admin_url( 'admin.php' )
	);
	$state = ntn_core_category_hub_meta( (int) $term->term_id, 'enabled' ) ? 'Questa categoria è una Hub accesa.' : 'Questa categoria non è una Hub.';
	?>
	<tr class="form-field">
		<th scope="row">Hub di sezione (NTN Core)</th>
		<td>
			<p style="margin-top:0;"><?php echo esc_html( $state ); ?> Le Hub si accendono e si modificano in <a href="<?php echo esc_url( $url ); ?>">NTN Journal → Hub</a>.</p>
		</td>
	</tr>
	<?php
}
add_action( 'category_edit_form_fields', 'ntn_core_category_hub_fields' );

/*
==========================================================================
	2. Cache: una "versione" che cambia a ogni pubblicazione o modifica
	========================================================================== */

/**
 * Invalida tutte le cache delle Hub di categoria.
 */
function ntn_core_category_hub_flush(): void {
	update_option( 'ntn_cat_hub_version', (string) microtime( true ), false );
}
add_action( 'save_post_post', 'ntn_core_category_hub_flush' );
add_action( 'deleted_post', 'ntn_core_category_hub_flush' );
add_action( 'created_category', 'ntn_core_category_hub_flush' );
add_action( 'delete_category', 'ntn_core_category_hub_flush' );
add_action( 'edited_post_tag', 'ntn_core_category_hub_flush' );

/*
==========================================================================
	3. Contesto: questa categoria è una Hub, o un marchio di una Hub Console?
	========================================================================== */

/**
 * Contesto della Hub per una categoria.
 *
 * @param WP_Term|int|null $term Categoria (null = categoria corrente).
 * @return array{root: WP_Term, selected: ?WP_Term, type: string}|null Null se la categoria non è una Hub.
 */
function ntn_core_category_hub_context( $term = null ): ?array {
	if ( null === $term ) {
		$term = is_category() ? get_queried_object() : null;
	} elseif ( is_numeric( $term ) ) {
		$term = get_term( (int) $term, 'category' );
	}
	if ( ! $term instanceof WP_Term || 'category' !== $term->taxonomy ) {
		return null;
	}
	if ( ntn_core_category_hub_meta( (int) $term->term_id, 'enabled' ) ) {
		return array(
			'root'     => $term,
			'selected' => null,
			'type'     => ntn_core_category_hub_meta( (int) $term->term_id, 'type' ),
		);
	}
	// Un marchio (figlio diretto) di una Hub Console apre la stessa Hub con il marchio scelto
	if ( $term->parent ) {
		$parent = get_term( (int) $term->parent, 'category' );
		if ( $parent instanceof WP_Term && ntn_core_category_hub_meta( (int) $parent->term_id, 'enabled' ) && 'console' === ntn_core_category_hub_meta( (int) $parent->term_id, 'type' ) ) {
			return array(
				'root'     => $parent,
				'selected' => $term,
				'type'     => 'console',
			);
		}
	}
	return null;
}

/**
 * Nome mostrato di una categoria nella Hub ("Titolo mostrato" oppure il nome).
 *
 * @param WP_Term $term Categoria.
 * @return string
 */
function ntn_core_category_hub_label( WP_Term $term ): string {
	$title = trim( ntn_core_category_hub_meta( (int) $term->term_id, 'title' ) );
	return '' !== $title ? $title : (string) $term->name;
}

/**
 * Illustrazione di una categoria: controller per i marchi, Italia per gli studi.
 *
 * @param WP_Term $term Categoria.
 * @param string  $type Tipo di Hub.
 * @return string joycon | dualsense | xbox | italia | none
 */
function ntn_core_category_hub_illustration( WP_Term $term, string $type ): string {
	$set = ntn_core_category_hub_meta( (int) $term->term_id, 'illustration' );
	if ( 'auto' !== $set ) {
		return $set;
	}
	if ( 'studi' === $type ) {
		return 'italia';
	}
	$hay = strtolower( $term->slug . ' ' . $term->name . ' ' . ntn_core_category_hub_label( $term ) );
	if ( false !== strpos( $hay, 'nintendo' ) || false !== strpos( $hay, 'switch' ) ) {
		return 'joycon';
	}
	if ( false !== strpos( $hay, 'sony' ) || false !== strpos( $hay, 'playstation' ) ) {
		return 'dualsense';
	}
	if ( false !== strpos( $hay, 'microsoft' ) || false !== strpos( $hay, 'xbox' ) ) {
		return 'xbox';
	}
	// v3.22.0: PC e telefoni
	if ( preg_match( '/\b(ios|iphone|ipad|apple)\b/', $hay ) ) {
		return 'ios';
	}
	if ( false !== strpos( $hay, 'android' ) ) {
		return 'android';
	}
	if ( preg_match( '/\b(pc|computer|steam|windows)\b/', $hay ) || false !== strpos( $hay, 'pc-gaming' ) ) {
		return 'pc';
	}
	if ( preg_match( '/\b(mobile|smartphone|telefoni)\b/', $hay ) ) {
		return 'android,ios';
	}
	return 'none';
}

/*
==========================================================================
	4. Dati della Hub (in cache)
	========================================================================== */

/**
 * Figli diretti di una categoria, in ordine alfabetico per nome mostrato (scelta del CEO).
 *
 * @param int $parent_id ID del genitore.
 * @return WP_Term[]
 */
function ntn_core_category_hub_children( int $parent_id ): array {
	$children = get_terms(
		array(
			'taxonomy'   => 'category',
			'parent'     => $parent_id,
			'hide_empty' => false,
		)
	);
	if ( ! is_array( $children ) ) {
		return array();
	}
	usort(
		$children,
		static function ( $a, $b ) {
			return strnatcasecmp( ntn_core_category_hub_label( $a ), ntn_core_category_hub_label( $b ) );
		}
	);
	return $children;
}

/**
 * Il tag è un gioco del catalogo (collegato a IGDB)?
 *
 * @param WP_Term $tag Tag.
 * @return bool
 */
function ntn_core_category_hub_is_game_tag( WP_Term $tag ): bool {
	return (bool) ( get_term_meta( $tag->term_id, '_ntn_igdb_game_id', true ) || get_term_meta( $tag->term_id, '_ntn_game_title', true ) );
}

/**
 * Nome del gioco di un articolo, per l'etichetta nell'elenco delle Hub.
 *
 * @param int $post_id Articolo.
 * @return string Vuota se l'articolo non ha un tag collegato a un gioco.
 */
function ntn_core_category_hub_post_game_name( int $post_id ): string {
	$tags = get_the_terms( $post_id, 'post_tag' );
	if ( is_array( $tags ) ) {
		foreach ( $tags as $tag ) {
			if ( ntn_core_category_hub_is_game_tag( $tag ) ) {
				return (string) $tag->name;
			}
		}
	}
	return '';
}

/**
 * Ultimi articoli di una o più categorie (solo ID).
 *
 * @param int[] $cat_ids  Categorie.
 * @param int   $limit    Quanti.
 * @param int[] $exclude  Da escludere.
 * @param bool  $reviews  Solo recensioni.
 * @return int[]
 */
function ntn_core_category_hub_post_ids( array $cat_ids, int $limit, array $exclude = array(), bool $reviews = false ): array {
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'fields'              => 'ids',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'category__in'        => array_map( 'intval', $cat_ids ),
		'post__not_in'        => array_map( 'intval', $exclude ),
	);
	if ( $reviews ) {
		$review_cat = get_category_by_slug( 'recensioni' );
		if ( ! $review_cat ) {
			return array();
		}
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => (int) $review_cat->term_id,
			),
		); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}
	$query = new WP_Query( $args );
	return array_map( 'intval', (array) $query->posts );
}

/**
 * Tutte le categorie discendenti (ID), compresa quella di partenza.
 *
 * @param int $term_id Categoria.
 * @return int[]
 */
function ntn_core_category_hub_tree( int $term_id ): array {
	$children = get_term_children( $term_id, 'category' );
	return array_merge( array( $term_id ), is_array( $children ) ? array_map( 'intval', $children ) : array() );
}

/**
 * Giochi (tag collegati a IGDB) di un insieme di articoli, dal più citato.
 *
 * @param int[] $post_ids Articoli.
 * @return array<int, array{term: WP_Term, count: int}>
 */
function ntn_core_category_hub_games( array $post_ids ): array {
	if ( empty( $post_ids ) ) {
		return array();
	}
	$games = array();
	foreach ( $post_ids as $post_id ) {
		$tags = get_the_terms( $post_id, 'post_tag' );
		if ( ! is_array( $tags ) ) {
			continue;
		}
		foreach ( $tags as $tag ) {
			if ( ! ntn_core_category_hub_is_game_tag( $tag ) ) {
				continue;
			}
			if ( ! isset( $games[ $tag->term_id ] ) ) {
				$games[ $tag->term_id ] = array(
					'term'  => $tag,
					'count' => 0,
				);
			}
			++$games[ $tag->term_id ]['count'];
		}
	}
	uasort(
		$games,
		static function ( $a, $b ) {
			return $b['count'] <=> $a['count'];
		}
	);
	return array_values( $games );
}

/**
 * Riassunto di un articolo per le schede della Hub.
 *
 * @param int $post_id Articolo.
 * @return array{id: int, title: string, url: string, date: string}
 */
function ntn_core_category_hub_post_card( int $post_id ): array {
	return array(
		'id'    => $post_id,
		'title' => wp_strip_all_tags( get_the_title( $post_id ) ),
		'url'   => (string) get_permalink( $post_id ),
		'date'  => (string) get_the_date( 'd.m.Y', $post_id ),
	);
}

/**
 * Numero di articoli pubblicati in un albero di categorie.
 *
 * @param int[] $cat_ids Categorie.
 * @return int
 */
function ntn_core_category_hub_count( array $cat_ids ): int {
	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
			'category__in'        => array_map( 'intval', $cat_ids ),
		)
	);
	return (int) $query->found_posts;
}

/**
 * Tutti i dati della Hub, in cache fino alla prossima pubblicazione o modifica.
 *
 * @param WP_Term|int|null $term Categoria (null = corrente).
 * @return array|null
 */
function ntn_core_get_category_hub_data( $term = null ): ?array {
	$ctx = ntn_core_category_hub_context( $term );
	if ( ! $ctx ) {
		return null;
	}
	$root     = $ctx['root'];
	$selected = $ctx['selected'];
	$focus    = $selected ? $selected : $root;

	$cache_key = 'ntn_cat_hub_' . md5( $root->term_id . '|' . ( $selected ? $selected->term_id : 0 ) . '|' . get_option( 'ntn_cat_hub_version', '0' ) . '|' . ( defined( 'NTN_JOURNAL_CORE_VERSION' ) ? NTN_JOURNAL_CORE_VERSION : '' ) );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$tree   = ntn_core_category_hub_tree( (int) $focus->term_id );
	$recent = ntn_core_category_hub_post_ids( $tree, 200 );
	$intro  = trim( ntn_core_category_hub_meta( (int) $focus->term_id, 'intro' ) );
	if ( '' === $intro && $selected ) {
		$intro = trim( ntn_core_category_hub_meta( (int) $root->term_id, 'intro' ) );
	}
	if ( '' === $intro ) {
		$intro = wp_strip_all_tags( (string) $focus->description );
	}

	$data = array(
		'type'         => $ctx['type'],
		'root'         => array(
			'id'    => (int) $root->term_id,
			'label' => ntn_core_category_hub_label( $root ),
			'url'   => (string) get_term_link( $root ),
		),
		'selected'     => $selected ? array(
			'id'    => (int) $selected->term_id,
			'label' => ntn_core_category_hub_label( $selected ),
			'url'   => (string) get_term_link( $selected ),
		) : null,
		'title'        => ntn_core_category_hub_label( $focus ),
		'intro'        => $intro,
		'illustration' => array(),
		'brands'       => array(),
		'platforms'    => array(),
		'studios'      => array(),
		'stats'        => array(),
		'featured'     => null,
		'side'         => array(),
	);

	$posts_total = ntn_core_category_hub_count( $tree );
	$latest_date = ! empty( $recent ) ? (string) get_the_date( 'd.m.Y', $recent[0] ) : '';
	// v4.2.0: la voce "Giochi" conta tutti gli articoli, non solo gli ultimi 200 (scelta del CEO)
	$hub_stats = ntn_core_category_hub_stats_query( $tree, (int) wp_date( 'Y' ) );

	if ( 'console' === $ctx['type'] ) {
		// Marchi (figli della Hub) e piattaforme (figli dei marchi)
		foreach ( ntn_core_category_hub_children( (int) $root->term_id ) as $brand ) {
			$data['brands'][] = array(
				'id'           => (int) $brand->term_id,
				'label'        => ntn_core_category_hub_label( $brand ),
				'url'          => (string) get_term_link( $brand ),
				'illustration' => ntn_core_category_hub_illustration( $brand, 'console' ),
				'current'      => $selected && (int) $selected->term_id === (int) $brand->term_id,
			);
		}
		$brand_terms = $selected ? array( $selected ) : ntn_core_category_hub_children( (int) $root->term_id );
		foreach ( $brand_terms as $brand ) {
			$illu = ntn_core_category_hub_illustration( $brand, 'console' );
			if ( 'none' !== $illu ) {
				$data['illustration'][] = $illu;
			}
			// v3.22.0: un marchio senza sottocategorie (es. Android e iOS sotto Mobile) è lui stesso la piattaforma
			// v3.22.3: ma non sulla sua stessa pagina, dove la scheda rimanderebbe a sé stessa ("Android: Android")
			$brand_platforms = ntn_core_category_hub_children( (int) $brand->term_id );
			if ( empty( $brand_platforms ) ) {
				if ( $selected ) {
					continue;
				}
				$brand_platforms = array( $brand );
			}
			foreach ( $brand_platforms as $platform ) {
				$platform_tree       = ntn_core_category_hub_tree( (int) $platform->term_id );
				$platform_ids        = ntn_core_category_hub_post_ids( $platform_tree, 20 );
				$data['platforms'][] = array(
					'id'     => (int) $platform->term_id,
					'slug'   => $platform->slug,
					'label'  => ntn_core_category_hub_label( $platform ),
					'brand'  => ntn_core_category_hub_label( $brand ),
					'url'    => (string) get_term_link( $platform ),
					'path'   => (string) wp_parse_url( (string) get_term_link( $platform ), PHP_URL_PATH ),
					'count'  => ntn_core_category_hub_count( $platform_tree ),
					'games'  => array_map(
						static function ( $g ) {
							return $g['term']->name;
						},
						array_slice( ntn_core_category_hub_games( $platform_ids ), 0, 3 )
					),
					'latest' => ! empty( $platform_ids ) ? ntn_core_category_hub_post_card( $platform_ids[0] ) : null,
				);
			}
		}
		// v3.22.0: una Hub senza marchi (es. PC Gaming) usa il proprio disegno e non mostra il contatore delle piattaforme
		if ( empty( $data['illustration'] ) ) {
			$own = ntn_core_category_hub_illustration( $focus, 'console' );
			if ( 'none' !== $own ) {
				$data['illustration'][] = $own;
			}
		}
		$data['stats'] = array_values(
			array_filter(
				array(
					$data['platforms'] ? array( 'Piattaforme', (string) count( $data['platforms'] ) ) : null,
					array( 'Giochi', (string) $hub_stats['games'] ),
					array( 'Articoli', (string) $posts_total ),
					array( 'Aggiornato', $latest_date ),
				)
			)
		);
	} elseif ( 'statistiche' === $ctx['type'] ) {
		// v4.2.0: disegno e contatori essenziali. Il modello dedicato arriverà dopo.
		$illu = ntn_core_category_hub_illustration( $root, 'statistiche' );
		if ( 'none' !== $illu ) {
			$data['illustration'][] = $illu;
		}
		$data['stats'] = array(
			array( 'Giochi', (string) $hub_stats['games'] ),
			array( 'Articoli', (string) $posts_total ),
			array( 'Aggiornato', $latest_date ),
		);
	} else {
		// Studi: sviluppatore dei giochi del catalogo, nomi uniti senza spazi e maiuscole
		$excluded = array_filter(
			array_map(
				static function ( $n ) {
					return strtolower( preg_replace( '/[^a-z0-9]/i', '', $n ) );
				},
				explode( ',', ntn_core_category_hub_meta( (int) $root->term_id, 'excluded' ) )
			)
		);
		$studios  = array();
		foreach ( $recent as $post_id ) {
			$tags = get_the_terms( $post_id, 'post_tag' );
			if ( ! is_array( $tags ) ) {
				continue;
			}
			foreach ( $tags as $tag ) {
				if ( ! ntn_core_category_hub_is_game_tag( $tag ) ) {
					continue;
				}
				$devs = get_term_meta( $tag->term_id, '_ntn_game_developer', true );
				$devs = is_array( $devs ) ? $devs : explode( ',', (string) $devs );
				foreach ( $devs as $dev ) {
					$dev = trim( wp_strip_all_tags( (string) $dev ) );
					$key = strtolower( preg_replace( '/[^a-z0-9]/i', '', $dev ) );
					if ( '' === $key || in_array( $key, $excluded, true ) ) {
						continue;
					}
					if ( ! isset( $studios[ $key ] ) ) {
						$studio_tag = get_term_by( 'name', $dev, 'post_tag' );
						if ( ! $studio_tag instanceof WP_Term ) {
							$studio_tag = get_term_by( 'slug', sanitize_title( $dev ), 'post_tag' );
						}
						$studios[ $key ] = array(
							'name'  => $dev,
							'url'   => $studio_tag instanceof WP_Term ? (string) get_term_link( $studio_tag ) : '',
							'games' => array(),
							'posts' => array(),
						);
					}
					$studios[ $key ]['games'][ $tag->term_id ] = $tag->name;
					$studios[ $key ]['posts'][ $post_id ]      = true;
				}
			}
		}
		foreach ( $studios as $studio ) {
			$post_ids          = array_keys( $studio['posts'] );
			$data['studios'][] = array(
				'name'   => $studio['name'],
				'url'    => $studio['url'],
				'letter' => ctype_digit( substr( $studio['name'], 0, 1 ) ) ? '#' : strtoupper( substr( remove_accents( $studio['name'] ), 0, 1 ) ),
				'games'  => array_values( $studio['games'] ),
				'count'  => count( $post_ids ),
				'latest' => ntn_core_category_hub_post_card( (int) $post_ids[0] ),
			);
		}
		usort(
			$data['studios'],
			static function ( $a, $b ) {
				return strnatcasecmp( $a['name'], $b['name'] );
			}
		);
		$illu = ntn_core_category_hub_illustration( $root, 'studi' );
		if ( 'none' !== $illu ) {
			$data['illustration'][] = $illu;
		}
		$data['stats'] = array(
			array( 'Studi', (string) count( $data['studios'] ) ),
			array( 'Giochi', (string) $hub_stats['games'] ),
			array( 'Articoli', (string) $posts_total ),
			array( 'Aggiornato', $latest_date ),
		);
	}

	// In evidenza: scelta della redazione, poi l'ultima recensione, poi l'ultimo articolo
	$featured_id = ntn_core_category_hub_meta( (int) $focus->term_id, 'featured' );
	if ( ! $featured_id && $selected ) {
		$featured_id = ntn_core_category_hub_meta( (int) $root->term_id, 'featured' );
	}
	if ( $featured_id && ( 'publish' !== get_post_status( $featured_id ) || ! has_term( $tree, 'category', $featured_id ) ) ) {
		$featured_id = 0;
	}
	if ( ! $featured_id ) {
		$reviews     = ntn_core_category_hub_post_ids( $tree, 1, array(), true );
		$featured_id = ! empty( $reviews ) ? $reviews[0] : ( ! empty( $recent ) ? $recent[0] : 0 );
	}
	if ( $featured_id ) {
		$data['featured'] = $featured_id;
		$data['side']     = ntn_core_category_hub_post_ids( $tree, 3, array( $featured_id ) );
	}

	set_transient( $cache_key, $data, 6 * HOUR_IN_SECONDS );
	return $data;
}

/*
==========================================================================
	5. Filtro per console nella lista degli articoli (?console=slug)
	========================================================================== */

/**
 * Piattaforma scelta con il filtro, se appartiene alla Hub corrente.
 *
 * @param array|null $data Dati della Hub.
 * @return array|null
 */
function ntn_core_category_hub_platform_filter( $data ): ?array {
	if ( ! is_array( $data ) || empty( $data['platforms'] ) || empty( $_GET['console'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return null;
	}
	$slug = sanitize_title( wp_unslash( (string) $_GET['console'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	foreach ( $data['platforms'] as $platform ) {
		if ( $platform['slug'] === $slug ) {
			return $platform;
		}
	}
	return null;
}

/**
 * Applica il filtro per console alla lista principale degli articoli della Hub.
 *
 * @param WP_Query $query Query.
 */
function ntn_core_category_hub_pre_get_posts( $query ): void {
	if ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() || ! $query->is_category() || empty( $_GET['console'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$term = $query->get_queried_object();
	$data = ntn_core_get_category_hub_data( $term );
	$pick = ntn_core_category_hub_platform_filter( $data );
	if ( $pick ) {
		$query->set( 'cat', '' );
		$query->set( 'category_name', '' );
		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => ntn_core_category_hub_tree( (int) $pick['id'] ),
				),
			)
		); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}
}
add_action( 'pre_get_posts', 'ntn_core_category_hub_pre_get_posts', 20 );

/**
 * Le viste filtrate non vanno nei risultati di ricerca: la pagina da indicizzare è la Hub senza filtro.
 *
 * @param array $robots Direttive robots di WordPress.
 * @return array
 */
function ntn_core_category_hub_robots( $robots ) {
	if ( is_category() && ! empty( $_GET['console'] ) && ntn_core_category_hub_context() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'ntn_core_category_hub_robots' );

/*
==========================================================================
	6. Dati strutturati: elenco delle piattaforme o degli studi
	========================================================================== */

/**
 * ItemList delle piattaforme (Hub Console) o degli studi (Hub Studi).
 */
function ntn_core_category_hub_schema(): void {
	if ( ! is_category() || get_option( 'ntn_seo_enable_review_schema', 'yes' ) !== 'yes' ) {
		return;
	}
	$data = ntn_core_get_category_hub_data();
	if ( ! $data ) {
		return;
	}
	$items = array();
	if ( 'console' === $data['type'] ) {
		foreach ( $data['platforms'] as $i => $platform ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $platform['label'],
				'url'      => $platform['url'],
			);
		}
	} else {
		foreach ( $data['studios'] as $i => $studio ) {
			$item = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $studio['name'],
			);
			if ( '' !== $studio['url'] ) {
				$item['url'] = $studio['url'];
			}
			$items[] = $item;
		}
	}
	if ( empty( $items ) ) {
		return;
	}
	$list = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => $data['title'] . ( 'console' === $data['type'] ? ' – piattaforme' : ' – studi' ),
		'numberOfItems'   => count( $items ),
		'itemListElement' => $items,
	);
	echo '<!-- SEO: Schema Markup della Hub di categoria -->';
	echo '<script type="application/ld+json">' . wp_json_encode( $list, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_footer', 'ntn_core_category_hub_schema', 99 );

/*
==========================================================================
	7. Google e condivisioni (v3.20.0): descrizione, immagine e sitemap delle Hub
	========================================================================== */

/**
 * Categoria a cui si riferiscono gli argomenti di The SEO Framework (null = pagina corrente).
 *
 * @param array|null $args Argomenti di TSF ('id', 'tax', 'pta', 'uid').
 * @return WP_Term|null
 */
function ntn_core_category_hub_term_from_tsf_args( $args ): ?WP_Term {
	if ( null === $args ) {
		$term = is_category() ? get_queried_object() : null;
	} elseif ( is_array( $args ) && ! empty( $args['id'] ) && 'category' === ( $args['tax'] ?? ( $args['taxonomy'] ?? '' ) ) ) {
		$term = get_term( (int) $args['id'], 'category' );
	} else {
		$term = null;
	}
	return $term instanceof WP_Term ? $term : null;
}

/**
 * Descrizione per Google di una Hub di categoria: l'introduzione della Hub, se c'è; altrimenti un testo
 * costruito dai suoi dati. Esempi:
 * "Notizie, recensioni e anteprime su Console: PlayStation 5, Xbox Series X|S, Nintendo Switch 2 e altre 3 piattaforme, con tutti gli articoli di NTN Journal."
 * "Gli studi di Sviluppatori italiani di videogiochi e i loro giochi: 3DClouds. Annunci, anteprime e recensioni di NTN Journal."
 *
 * @param WP_Term $term Categoria.
 * @return string Vuota se la categoria non è una Hub.
 */
function ntn_core_category_hub_seo_description( WP_Term $term ): string {
	$data = ntn_core_get_category_hub_data( $term );
	if ( ! $data ) {
		return '';
	}
	if ( '' !== trim( (string) $data['intro'] ) ) {
		return trim( wp_strip_all_tags( (string) $data['intro'] ) );
	}
	$site  = trim( wp_strip_all_tags( (string) get_bloginfo( 'name' ) ) );
	$title = (string) $data['title'];
	if ( 'console' === $data['type'] ) {
		$names = wp_list_pluck( $data['platforms'], 'label' );
		$text  = 'Notizie, recensioni e anteprime su ' . $title;
		if ( $names ) {
			$shown = array_slice( $names, 0, 4 );
			$more  = count( $names ) - count( $shown );
			$text .= ': ' . implode( ', ', $shown ) . ( $more > 0 ? ' e altre ' . $more . ( 1 === $more ? ' piattaforma' : ' piattaforme' ) : '' );
		}
		return $text . ', con tutti gli articoli' . ( '' !== $site ? ' di ' . $site : '' ) . '.';
	}
	if ( 'statistiche' === $data['type'] ) {
		return 'I numeri di ' . $title . ': articoli, autori e giochi' . ( '' !== $site ? ' di ' . $site : '' ) . '.';
	}
	$names = wp_list_pluck( $data['studios'], 'name' );
	$text  = 'Gli studi di ' . $title . ' e i loro giochi';
	if ( $names ) {
		$shown = array_slice( $names, 0, 4 );
		$more  = count( $names ) - count( $shown );
		$text .= ': ' . implode( ', ', $shown ) . ( $more > 0 ? ' e altri ' . $more : '' );
	}
	return $text . '. Annunci, anteprime e recensioni' . ( '' !== $site ? ' di ' . $site : '' ) . '.';
}

/**
 * The SEO Framework: testo da cui nasce la descrizione automatica della Hub di categoria.
 * Una descrizione scritta a mano (nella categoria o nel riquadro di TSF) ha sempre la precedenza.
 *
 * @param string     $excerpt Testo usato da TSF (la descrizione della categoria, se c'è).
 * @param array|null $args    Argomenti di TSF.
 * @return string
 */
function ntn_core_category_hub_tsf_excerpt( $excerpt, $args = null ) {
	if ( '' !== trim( (string) $excerpt ) ) {
		return $excerpt;
	}
	$term = ntn_core_category_hub_term_from_tsf_args( $args );
	if ( $term ) {
		$desc = ntn_core_category_hub_seo_description( $term );
		if ( '' !== $desc ) {
			return $desc;
		}
	}
	return $excerpt;
}
add_filter( 'the_seo_framework_description_excerpt', 'ntn_core_category_hub_tsf_excerpt', 10, 2 );

/**
 * Immagine per le condivisioni della Hub di categoria: quella dell'articolo in evidenza.
 *
 * @param WP_Term $term Categoria.
 * @return string URL, vuoto se la Hub non ha un articolo in evidenza con immagine.
 */
function ntn_core_category_hub_social_image( WP_Term $term ): string {
	$data = ntn_core_get_category_hub_data( $term );
	if ( ! $data || empty( $data['featured'] ) ) {
		return '';
	}
	$url = (string) get_the_post_thumbnail_url( (int) $data['featured'], 'full' );
	if ( '' === $url ) {
		$url = (string) get_post_meta( (int) $data['featured'], '_ntn_social_image_url', true );
	}
	return $url;
}

/**
 * Generatore di immagini per The SEO Framework: la Hub di categoria usa l'immagine dell'articolo in evidenza.
 *
 * @param array|null $args Argomenti di TSF.
 * @param string     $size Dimensione richiesta.
 * @return Generator
 */
function ntn_core_category_hub_tsf_image_generator( $args = null, $size = 'full' ) {
	$term = ntn_core_category_hub_term_from_tsf_args( $args );
	$url  = $term ? ntn_core_category_hub_social_image( $term ) : '';
	if ( '' !== $url ) {
		yield array(
			'url' => $url,
			'id'  => (int) get_post_thumbnail_id( (int) ntn_core_get_category_hub_data( $term )['featured'] ),
		);
	}
}

/**
 * Mette il generatore della Hub di categoria davanti agli altri (dopo quello dei giochi in seo-extras.php,
 * che per le categorie non restituisce nulla).
 *
 * @param array      $params  Parametri di generazione di TSF.
 * @param array|null $args    Argomenti di TSF.
 * @param string     $context Contesto.
 * @return array
 */
function ntn_core_category_hub_tsf_image_params( $params, $args = null, $context = 'social' ) {
	if ( ! is_array( $params ) || ! ntn_core_category_hub_term_from_tsf_args( $args ) ) {
		return $params;
	}
	$cbs           = isset( $params['cbs'] ) && is_array( $params['cbs'] ) ? $params['cbs'] : array();
	$params['cbs'] = array( 'ntn_category_hub' => 'ntn_core_category_hub_tsf_image_generator' ) + $cbs;
	return $params;
}
add_filter( 'the_seo_framework_image_generation_params', 'ntn_core_category_hub_tsf_image_params', 11, 3 );

/**
 * Sitemap di The SEO Framework: TSF non elenca le categorie, quindi aggiungiamo le Hub di categoria e
 * le pagine dei marchi delle Hub Console (es. /console/microsoft/), con la data dell'ultimo articolo.
 *
 * @param array $custom_urls URL aggiuntivi, per indirizzo.
 * @return array
 */
function ntn_core_category_hub_sitemap_urls( $custom_urls ) {
	$custom_urls = is_array( $custom_urls ) ? $custom_urls : array();
	$terms       = array();
	foreach ( ntn_core_category_hubs() as $hub ) {
		$terms[] = $hub;
		if ( 'console' === ntn_core_category_hub_meta( (int) $hub->term_id, 'type' ) ) {
			foreach ( ntn_core_category_hub_children( (int) $hub->term_id ) as $brand ) {
				$terms[] = $brand;
			}
		}
	}
	foreach ( $terms as $term ) {
		$url = get_term_link( $term );
		if ( ! is_string( $url ) ) {
			continue;
		}
		$latest = ntn_core_category_hub_post_ids( ntn_core_category_hub_tree( (int) $term->term_id ), 1 );
		if ( empty( $latest ) ) {
			continue; // Una Hub senza articoli non serve a Google
		}
		if ( function_exists( 'ntn_core_seo_is_thin_term' ) && ntn_core_seo_is_thin_term( $term ) ) {
			continue; // Sotto la soglia di articoli (seo-thin-pages.php): resta fuori finché non la raggiunge
		}
		$lastmod             = (string) get_post_field( 'post_modified_gmt', $latest[0] );
		$custom_urls[ $url ] = array( 'lastmod' => $lastmod );
	}
	return $custom_urls;
}
add_filter( 'the_seo_framework_sitemap_additional_urls', 'ntn_core_category_hub_sitemap_urls' );

/*
==========================================================================
	Controllo copertura (v3.18.0, scheda "Hub" di NTN Journal Funzioni)
	========================================================================== */

/**
 * Problemi di una Hub, con un link per sistemarli.
 *
 * - Console: piattaforme senza articoli (un marchio senza sottocategorie conta come piattaforma).
 * - Studi: giochi del catalogo senza sviluppatore su IGDB (non entrano nell'indice degli studi).
 * - Statistiche: categoria senza articoli (i numeri resterebbero a zero).
 *
 * @param WP_Term $term Categoria con la Hub accesa.
 * @return array<int,array{title:string,text:string,url:string,action:string}>
 */
function ntn_core_category_hub_coverage( WP_Term $term ): array {
	$issues = array();
	$type   = ntn_core_category_hub_meta( (int) $term->term_id, 'type' );
	if ( 'statistiche' === $type ) {
		if ( 0 === ntn_core_category_hub_count( ntn_core_category_hub_tree( (int) $term->term_id ) ) ) {
			$issues[] = array(
				'title'  => ntn_core_category_hub_label( $term ),
				'text'   => 'Categoria senza articoli: le statistiche resterebbero a zero.',
				'url'    => (string) get_edit_term_link( $term->term_id, 'category' ),
				'action' => 'Apri la categoria',
			);
		}
	} elseif ( 'console' === $type ) {
		foreach ( ntn_core_category_hub_children( (int) $term->term_id ) as $brand ) {
			// v3.22.0: come nella pagina della Hub, un marchio senza sottocategorie è lui stesso la piattaforma
			$platforms = ntn_core_category_hub_children( (int) $brand->term_id );
			if ( empty( $platforms ) ) {
				$platforms = array( $brand );
			}
			foreach ( $platforms as $platform ) {
				if ( 0 === ntn_core_category_hub_count( ntn_core_category_hub_tree( (int) $platform->term_id ) ) ) {
					$issues[] = array(
						'title'  => ntn_core_category_hub_label( $platform ),
						'text'   => 'Piattaforma senza articoli: la sua scheda resterebbe vuota.',
						'url'    => (string) get_edit_term_link( $platform->term_id, 'category' ),
						'action' => 'Apri la categoria',
					);
				}
			}
		}
	} else {
		$seen = array();
		foreach ( ntn_core_category_hub_post_ids( ntn_core_category_hub_tree( (int) $term->term_id ), 200 ) as $post_id ) {
			$tags = get_the_terms( $post_id, 'post_tag' );
			if ( ! is_array( $tags ) ) {
				continue;
			}
			foreach ( $tags as $tag ) {
				if ( isset( $seen[ $tag->term_id ] ) || ! ntn_core_category_hub_is_game_tag( $tag ) ) {
					continue;
				}
				$seen[ $tag->term_id ] = true;
				$devs                  = get_term_meta( $tag->term_id, '_ntn_game_developer', true );
				$devs                  = is_array( $devs ) ? array_filter( $devs ) : trim( (string) $devs );
				if ( empty( $devs ) ) {
					$issues[] = array(
						'title'  => $tag->name,
						'text'   => 'Gioco senza sviluppatore su IGDB: non entra nell\'indice degli studi.',
						'url'    => (string) get_edit_term_link( $tag->term_id, 'post_tag' ),
						'action' => 'Apri il gioco',
					);
				}
			}
		}
	}
	return array_slice( $issues, 0, 20 );
}
