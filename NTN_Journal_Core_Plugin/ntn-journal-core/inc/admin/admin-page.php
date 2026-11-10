<?php
/**
 * NTN Journal Funzioni - Admin Menu & Controller
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Schede della pagina, nell'ordine del menu, con il nome mostrato (v3.18.0: nuove "Hub" e "Redazione")
function ntn_journal_core_admin_tabs() {
	return array(
		'dashboard'     => 'Dashboard',
		'hub'           => 'Hub',
		'redazione'     => 'Redazione',
		'reviews'       => 'Recensioni',
		'press'         => 'Comunicati',
		'igdb'          => 'Scanner IGDB',
		'breaking-news' => 'Breaking News',
		'redirects'     => 'Redirect',
		'api-services'  => 'Servizi API',
		'seo'           => 'SEO',
		'extra'         => 'Extra',
		'immagini'      => 'Immagini', // v4.24.0: miniature dei PNG trasparenti e rapporto sulle immagini (prima in Extra)
		'backup'        => 'Backup',
	);
}

// Registrazione del Menu Admin: una voce per scheda
function ntn_journal_core_register_admin_menu() {
	add_menu_page(
		'NTN Journal Funzioni',
		'NTN Journal',
		'manage_options',
		'ntn-journal-functions',
		'ntn_journal_core_admin_page_html',
		'dashicons-superhero',
		30
	);
	foreach ( ntn_journal_core_admin_tabs() as $tab => $label ) {
		add_submenu_page(
			'ntn-journal-functions',
			$label,
			$label,
			'manage_options',
			'dashboard' === $tab ? 'ntn-journal-functions' : 'ntn-journal-functions&tab=' . $tab,
			'ntn_journal_core_admin_page_html'
		);
	}
}
add_action( 'admin_menu', 'ntn_journal_core_register_admin_menu' );

// Stile Nothing OS e caratteri del plugin, solo nella schermata NTN Journal Funzioni (v3.18.0)
function ntn_journal_core_admin_enqueue( $hook ) {
	if ( 'toplevel_page_ntn-journal-functions' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'ntn-core-admin', NTN_JOURNAL_CORE_URL . 'assets/css/admin.css', array(), NTN_JOURNAL_CORE_VERSION );
	wp_enqueue_script( 'ntn-core-admin', NTN_JOURNAL_CORE_URL . 'assets/js/admin-screen.js', array(), NTN_JOURNAL_CORE_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'ntn_journal_core_admin_enqueue' );

// Scheda aperta (dashboard se manca o non è valida)
function ntn_journal_core_admin_current_tab() {
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo lettura della scheda.
	return array_key_exists( $tab, ntn_journal_core_admin_tabs() ) ? $tab : 'dashboard';
}

// Siamo sulla pagina NTN Journal Funzioni?
function ntn_journal_core_is_admin_page() {
	return is_admin() && isset( $_GET['page'] ) && 'ntn-journal-functions' === sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo lettura della pagina.
}

// Menu a sinistra: evidenzia la voce della scheda aperta, non sempre "Dashboard" (v3.17.1)
function ntn_journal_core_admin_submenu_file( $submenu_file ) {
	if ( ntn_journal_core_is_admin_page() ) {
		$tab = ntn_journal_core_admin_current_tab();
		return 'dashboard' === $tab ? 'ntn-journal-functions' : 'ntn-journal-functions&tab=' . $tab;
	}
	return $submenu_file;
}
add_filter( 'submenu_file', 'ntn_journal_core_admin_submenu_file' );

// Titolo della finestra con il nome della scheda aperta (v3.17.1)
function ntn_journal_core_admin_title( $admin_title, $title ) {
	if ( ntn_journal_core_is_admin_page() && '' !== (string) $title ) {
		$tabs  = ntn_journal_core_admin_tabs();
		$label = $tabs[ ntn_journal_core_admin_current_tab() ] . ' · NTN Journal';
		$pos   = strpos( $admin_title, (string) $title );
		if ( false !== $pos ) {
			return substr_replace( $admin_title, $label, $pos, strlen( (string) $title ) );
		}
	}
	return $admin_title;
}
add_filter( 'admin_title', 'ntn_journal_core_admin_title', 10, 2 );

/**
 * Svuota tutte le memorie del sito gestite da NTN Journal (v3.17.1).
 *
 * Hub dei videogiochi, numeri delle Hub, Hub di categoria, pagina Redazione,
 * Home e numeri degli autori. I dati si ricalcolano alla prima visita.
 */
function ntn_core_flush_all_caches() {
	global $wpdb;
	// L'elenco di cosa si cancella sta in inc/cache-registry.php (v4.19.0): qui non ce n'è un secondo.
	foreach ( ntn_core_cache_registry_flushed() as $entry ) {
		if ( '' !== $entry['prefix'] ) {
			$like  = $wpdb->esc_like( '_transient_' . $entry['prefix'] ) . '%';
			$names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- pulizia manuale richiesta dall'amministratore.
			foreach ( (array) $names as $name ) {
				delete_transient( substr( $name, strlen( '_transient_' ) ) );
			}
		}
		// Risposte salvate nella cache in memoria del server, quando c'è (v4.16.2)
		if ( ! empty( $entry['object_group'] ) && function_exists( 'wp_cache_flush_group' ) && function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( $entry['object_group'] );
		}
	}
	// Le memorie a "versione" si invalidano anche con una cache persistente (Redis, Memcached)
	if ( function_exists( 'ntn_core_category_hub_flush' ) ) {
		ntn_core_category_hub_flush();
	}
	if ( function_exists( 'ntn_core_team_flush' ) ) {
		ntn_core_team_flush();
	}
}

// Valore di un campo inviato, già senza le barre aggiunte da WordPress
function ntn_journal_core_admin_field( $key ) {
	return isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verificato e valore pulito da chi lo usa.
}

// Il nonce di un modulo è valido?
function ntn_journal_core_admin_nonce_ok( $field, $action ) {
	return isset( $_POST[ $field ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $action ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}

/**
 * Gestione salvataggi (v3.17.1): si salva prima di disegnare la pagina, poi la pagina si
 * ricarica da sola (così il browser non rimanda il modulo se la si aggiorna).
 */
function ntn_journal_core_admin_handle_post() {
	if ( ! ntn_journal_core_is_admin_page() || 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$message = '';
	$type    = 'success';

	// Breaking News
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_breaking_news_nonce', 'ntn_save_breaking_news' ) ) {
		update_option( 'ntn_breaking_news_active', isset( $_POST['ntn_breaking_news_active'] ) ? 'yes' : 'no' );
		update_option( 'ntn_breaking_news_label', sanitize_text_field( ntn_journal_core_admin_field( 'ntn_breaking_news_label' ) ) );
		update_option( 'ntn_breaking_news_text', sanitize_text_field( ntn_journal_core_admin_field( 'ntn_breaking_news_text' ) ) );
		update_option( 'ntn_breaking_news_url', esc_url_raw( ntn_journal_core_admin_field( 'ntn_breaking_news_url' ) ) );
		update_option( 'ntn_breaking_news_badge_color', (string) sanitize_hex_color( ntn_journal_core_admin_field( 'ntn_breaking_news_badge_color' ) ) );
		update_option( 'ntn_breaking_news_sticky', isset( $_POST['ntn_breaking_news_sticky'] ) ? 'yes' : 'no' );
		update_option( 'ntn_breaking_news_dismissable', isset( $_POST['ntn_breaking_news_dismissable'] ) ? 'yes' : 'no' );
		$message = 'Impostazioni Ticker Breaking News salvate.';
	}

	// Servizi API: le chiavi non tornano mai nella pagina. Un campo vuoto lascia la chiave salvata;
	// "Cancella" la toglie. Una chiave nuova torna "Salvata, non verificata".
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_api_services_nonce', 'ntn_save_api_services' ) && function_exists( 'ntn_core_api_services' ) ) {
		foreach ( ntn_core_api_services() as $service => $info ) {
			$changed = false;
			foreach ( array_keys( $info['options'] ) as $option ) {
				$new = trim( sanitize_text_field( ntn_journal_core_admin_field( $option ) ) );
				if ( isset( $_POST[ $option . '_clear' ] ) ) {
					delete_option( $option );
					$changed = true;
				} elseif ( '' !== $new && $new !== (string) get_option( $option, '' ) ) {
					update_option( $option, $new );
					$changed = true;
				}
			}
			if ( $changed ) {
				ntn_core_api_status_reset( $service );
				if ( 'igdb' === $service ) {
					delete_transient( 'ntn_igdb_access_token' );
				}
			}
		}
		$message = 'Impostazioni Servizi API salvate.';
	}

	// Servizi API: pulsante "Verifica" (richiesta di prova che non consuma crediti)
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_api_verify_nonce', 'ntn_verify_api' ) && function_exists( 'ntn_core_api_verify' ) ) {
		$service  = sanitize_key( ntn_journal_core_admin_field( 'ntn_api_service' ) );
		$services = ntn_core_api_services();
		if ( isset( $services[ $service ] ) ) {
			$status  = ntn_core_api_verify( $service );
			$message = $services[ $service ]['label'] . ': ' . $status['label'] . ( '' !== $status['message'] ? ' — ' . $status['message'] : '' );
			$type    = 'active' === $status['state'] ? 'success' : 'error';
		}
	}

	// Scanner IGDB
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_igdb_scanner_nonce', 'ntn_save_igdb_scanner_settings' ) ) {
		update_option( 'ntn_igdb_scanner_active', isset( $_POST['ntn_igdb_scanner_active'] ) ? 'yes' : 'no' );
		$message = 'Stato dello Scanner IGDB aggiornato con successo.';
	}

	// Impostazioni SEO
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_seo_settings_nonce', 'ntn_save_seo_settings' ) ) {
		update_option( 'ntn_seo_enable_base', isset( $_POST['ntn_seo_enable_base'] ) ? 'yes' : 'no' );
		update_option( 'ntn_seo_enable_review_schema', isset( $_POST['ntn_seo_enable_review_schema'] ) ? 'yes' : 'no' );
		update_option( 'ntn_seo_enable_video_schema', isset( $_POST['ntn_seo_enable_video_schema'] ) ? 'yes' : 'no' );
		update_option( 'ntn_seo_enable_faq_schema', isset( $_POST['ntn_seo_enable_faq_schema'] ) ? 'yes' : 'no' );
		update_option( 'ntn_seo_enable_auto_toc', isset( $_POST['ntn_seo_enable_auto_toc'] ) ? 'yes' : 'no' );
		update_option( 'ntn_seo_enable_news_sitemap', isset( $_POST['ntn_seo_enable_news_sitemap'] ) ? 'yes' : 'no' );
		update_option( 'ntn_seo_cleanup_og_duplicates', isset( $_POST['ntn_seo_cleanup_og_duplicates'] ) ? 'yes' : 'no' );
		// v3.20.0: soglie delle pagine con pochi articoli (0-100)
		update_option( 'ntn_seo_min_posts_terms', min( 100, absint( ntn_journal_core_admin_field( 'ntn_seo_min_posts_terms' ) ) ) );
		update_option( 'ntn_seo_min_posts_games', min( 100, absint( ntn_journal_core_admin_field( 'ntn_seo_min_posts_games' ) ) ) );
		$message = 'Impostazioni SEO salvate con successo.';
	}

	// Comunicati
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_press_settings_nonce', 'ntn_save_press_settings' ) ) {
		update_option( 'ntn_press_disclaimer', wp_kses_post( ntn_journal_core_admin_field( 'ntn_press_disclaimer' ) ) );
		$message = 'Impostazioni Comunicati salvate con successo.';
	}

	// Extra (v3.17.1: tolta l'impostazione dei tag, che non aveva una casella)
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_extra_settings_nonce', 'ntn_save_extra_settings' ) ) {
		update_option( 'ntn_journal_enable_comments', isset( $_POST['ntn_journal_enable_comments'] ) ? 'yes' : 'no' );

		$old_videogame_links = get_option( 'ntn_journal_enable_videogame_links', 'yes' );
		$new_videogame_links = isset( $_POST['ntn_journal_enable_videogame_links'] ) ? 'yes' : 'no';
		update_option( 'ntn_journal_enable_videogame_links', $new_videogame_links );
		update_option( 'ntn_journal_enable_hub_cache', isset( $_POST['ntn_journal_enable_hub_cache'] ) ? 'yes' : 'no' );
		if ( $old_videogame_links !== $new_videogame_links ) {
			ntn_core_request_rewrite_flush(); // v4.25.1: prima cancellava il vecchio segnale _v2, che nessuno leggeva più
		}
		$message = 'Impostazioni Extra salvate con successo.';
	}

	// Hub: salva o spegni una Hub (v3.18.0)
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_hub_save_nonce', 'ntn_hub_save' ) && function_exists( 'ntn_core_category_hub_save_settings' ) ) {
		$term = get_term( absint( ntn_journal_core_admin_field( 'ntn_hub_term' ) ), 'category' );
		if ( $term instanceof WP_Term ) {
			if ( 'off' === ntn_journal_core_admin_field( 'ntn_hub_action' ) ) {
				ntn_core_category_hub_save_settings( (int) $term->term_id, array( 'enabled' => false ) );
				$message = 'Hub "' . $term->name . '" spenta: la categoria torna un archivio normale.';
			} else {
				$data = array(
					'type'         => ntn_journal_core_admin_field( 'ntn_hub_type' ),
					'title'        => ntn_journal_core_admin_field( 'ntn_hub_title' ),
					'intro'        => ntn_journal_core_admin_field( 'ntn_hub_intro' ),
					'illustration' => ntn_journal_core_admin_field( 'ntn_hub_illustration' ),
					'featured'     => ntn_journal_core_admin_field( 'ntn_hub_featured' ),
				);
				if ( isset( $_POST['ntn_hub_excluded'] ) ) {
					$data['excluded'] = ntn_journal_core_admin_field( 'ntn_hub_excluded' );
				}
				ntn_core_category_hub_save_settings( (int) $term->term_id, $data );
				// Nome mostrato dei marchi (sottocategorie dirette della Hub)
				$brands = isset( $_POST['ntn_hub_brand_title'] ) && is_array( $_POST['ntn_hub_brand_title'] ) ? wp_unslash( $_POST['ntn_hub_brand_title'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- pulito sotto.
				foreach ( $brands as $brand_id => $brand_title ) {
					$brand = get_term( absint( $brand_id ), 'category' );
					if ( $brand instanceof WP_Term && (int) $brand->parent === (int) $term->term_id ) {
						ntn_core_category_hub_save_settings( (int) $brand->term_id, array( 'title' => (string) $brand_title ) );
					}
				}
				$message = 'Hub "' . $term->name . '" salvata.';
			}
		}
	}

	// Hub: trasforma una categoria in Hub (v3.18.0)
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_hub_new_nonce', 'ntn_hub_new' ) && function_exists( 'ntn_core_category_hub_save_settings' ) ) {
		$term = get_term( absint( ntn_journal_core_admin_field( 'ntn_hub_term' ) ), 'category' );
		if ( $term instanceof WP_Term ) {
			ntn_core_category_hub_save_settings(
				(int) $term->term_id,
				array(
					'enabled' => true,
					'type'    => ntn_journal_core_admin_field( 'ntn_hub_type' ),
				)
			);
			$message = 'Hub "' . $term->name . '" accesa: completala qui sotto.';
			set_transient(
				'ntn_admin_notice_' . get_current_user_id(),
				array(
					'type' => 'success',
					'text' => $message,
				),
				MINUTE_IN_SECONDS
			);
			wp_safe_redirect(
				add_query_arg(
					array(
						'page' => 'ntn-journal-functions',
						'tab'  => 'hub',
						'edit' => (int) $term->term_id,
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
		$message = 'Scegli una categoria da trasformare in Hub.';
		$type    = 'error';
	}

	// Hub: svuota solo la memoria delle Hub (v3.18.0)
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_hub_flush_nonce', 'ntn_hub_flush' ) && function_exists( 'ntn_core_category_hub_flush' ) ) {
		ntn_core_category_hub_flush();
		$message = 'Memoria delle Hub svuotata: si ricalcolano alla prima visita.';
	}

	// Redazione: gruppi, persone e ordine (v3.18.0)
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_team_save_nonce', 'ntn_team_save' ) && function_exists( 'ntn_core_team_save' ) ) {
		$page_id = absint( ntn_journal_core_admin_field( 'ntn_team_page' ) );
		$keys    = isset( $_POST['ntn_team_key'] ) && is_array( $_POST['ntn_team_key'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['ntn_team_key'] ) ) : array();
		$names   = isset( $_POST['ntn_team_name'] ) && is_array( $_POST['ntn_team_name'] ) ? wp_unslash( $_POST['ntn_team_name'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- pulito in ntn_core_team_save().
		$people  = isset( $_POST['ntn_team_people'] ) && is_array( $_POST['ntn_team_people'] ) ? wp_unslash( $_POST['ntn_team_people'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- solo numeri, puliti in ntn_core_team_save().
		$groups  = array();
		foreach ( $keys as $key ) {
			$groups[] = array(
				'name'   => isset( $names[ $key ] ) ? (string) $names[ $key ] : '',
				'people' => isset( $people[ $key ] ) ? (array) $people[ $key ] : array(),
			);
		}
		if ( '1' === ntn_journal_core_admin_field( 'ntn_team_js' ) && $page_id && 'page' === get_post_type( $page_id ) ) {
			ntn_core_team_save( $page_id, $groups, sanitize_key( ntn_journal_core_admin_field( 'ntn_team_order' ) ) );
			$message = 'Redazione salvata.';
		} else {
			$message = 'Per salvare la redazione serve JavaScript attivo nel browser: nessuna modifica fatta.';
			$type    = 'error';
		}
	}

	// Voti alle vecchie recensioni (v4.16.0): solo alla conferma, mai sopra un voto già scritto
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_rating_backfill_nonce', 'ntn_rating_backfill' ) && function_exists( 'ntn_core_apply_review_ratings' ) ) {
		if ( 'all' === sanitize_key( ntn_journal_core_admin_field( 'ntn_rating_backfill_mode' ) ) ) {
			$written = ntn_core_apply_all_proposed_ratings();
		} else {
			$picks  = isset( $_POST['ntn_rating_pick'] ) && is_array( $_POST['ntn_rating_pick'] ) ? array_map( 'absint', array_keys( wp_unslash( $_POST['ntn_rating_pick'] ) ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- solo ID numerici.
			$values = isset( $_POST['ntn_rating_value'] ) && is_array( $_POST['ntn_rating_value'] ) ? wp_unslash( $_POST['ntn_rating_value'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- numeri, puliti sotto.
			$chosen = array();
			foreach ( $picks as $pick_id ) {
				$chosen[ $pick_id ] = isset( $values[ $pick_id ] ) ? absint( $values[ $pick_id ] ) : 0;
			}
			$written = ntn_core_apply_review_ratings( $chosen );
		}
		ntn_core_flush_all_caches();
		$message = $written > 0 ? sprintf( 'Voto scritto su %d recensioni: ora compare nella pagina e nei dati per Google.', $written ) : 'Nessun voto scritto: scegli un voto per le recensioni selezionate.';
		$type    = $written > 0 ? 'success' : 'error';
		set_transient(
			'ntn_admin_notice_' . get_current_user_id(),
			array(
				'type' => $type,
				'text' => $message,
			),
			MINUTE_IN_SECONDS
		);
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'ntn-journal-functions',
					'tab'  => 'reviews',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// Svuota tutte le memorie (v3.17.1: non solo le Hub dei videogiochi)
	if ( ntn_journal_core_admin_nonce_ok( 'ntn_clear_hub_cache_nonce', 'ntn_clear_hub_cache' ) ) {
		ntn_core_flush_all_caches();
		$message = 'Memoria svuotata: Hub, catalogo, Home, pagina Redazione e numeri degli autori si aggiorneranno alla prima visita. Le voci che restano sono elencate qui sotto.';
	}

	if ( '' === $message ) {
		return; // Moduli di altre schede (Redirect, Backup…) si gestiscono da soli
	}
	set_transient(
		'ntn_admin_notice_' . get_current_user_id(),
		array(
			'type' => $type,
			'text' => $message,
		),
		MINUTE_IN_SECONDS
	);
	wp_safe_redirect(
		add_query_arg(
			array(
				'page' => 'ntn-journal-functions',
				'tab'  => ntn_journal_core_admin_current_tab(),
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'ntn_journal_core_admin_handle_post' );

// Messaggio dell'ultimo salvataggio, mostrato una volta sola
function ntn_journal_core_admin_notice() {
	$key    = 'ntn_admin_notice_' . get_current_user_id();
	$notice = get_transient( $key );
	if ( ! is_array( $notice ) || empty( $notice['text'] ) ) {
		return;
	}
	delete_transient( $key );
	$class = 'error' === $notice['type'] ? 'notice-error' : 'notice-success';
	echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $notice['text'] ) . '</p></div>';
}

// Cancellazione cache quando si salvano meta IGDB (Hook per le performance)
function ntn_core_clear_igdb_count_cache( $meta_id, $object_id, $meta_key, $_meta_value ) {
	if ( $meta_key === '_ntn_game_title' || $meta_key === '_ntn_igdb_game_id' ) {
		delete_transient( 'ntn_igdb_post_count' );
		delete_transient( 'ntn_igdb_tag_count' );
	}
	if ( $meta_key === '_ntn_featured_review_id' ) {
		delete_transient( ntn_core_featured_cache_key( 'post', $object_id ) );
		delete_transient( ntn_core_featured_cache_key( 'term', $object_id ) );
	}
}
add_action( 'added_post_meta', 'ntn_core_clear_igdb_count_cache', 10, 4 );
add_action( 'updated_post_meta', 'ntn_core_clear_igdb_count_cache', 10, 4 );
add_action( 'deleted_post_meta', 'ntn_core_clear_igdb_count_cache', 10, 4 );
add_action( 'added_term_meta', 'ntn_core_clear_igdb_count_cache', 10, 4 );
add_action( 'updated_term_meta', 'ntn_core_clear_igdb_count_cache', 10, 4 );
add_action( 'deleted_term_meta', 'ntn_core_clear_igdb_count_cache', 10, 4 );

// Cancellazione cache featured hub page al salvataggio articoli
function ntn_core_clear_hub_cache_on_save( $post_id ) {
	delete_transient( ntn_core_featured_cache_key( 'post', $post_id ) );
	$tags = get_the_tags( $post_id );
	if ( $tags ) {
		foreach ( $tags as $tag ) {
			delete_transient( ntn_core_featured_cache_key( 'term', $tag->term_id ) );

			// Invalida anche il CPT videogioco corrispondente al tag usando i transienti o cache_key basata sullo slug
			$cache_key = 'ntn_vg_link_' . md5( $tag->slug );
			$cpt_id    = get_transient( $cache_key );
			if ( $cpt_id ) {
				delete_transient( ntn_core_featured_cache_key( 'post', $cpt_id ) );
			}
		}
	}
}
add_action( 'save_post', 'ntn_core_clear_hub_cache_on_save' );
add_action( 'clean_post_cache', 'ntn_core_clear_hub_cache_on_save' );

// Callback per visualizzare il contenuto della pagina
function ntn_journal_core_admin_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// I salvataggi si fanno prima, in admin_init (ntn_journal_core_admin_handle_post)
	$active_tab = ntn_journal_core_admin_current_tab();
	?>
	<div class="wrap ntn-adm">
		<header class="ntn-adm-head">
			<h1>NTN Journal Funzioni</h1>
			<span class="ntn-adm-k">NTN Core · v<?php echo esc_html( NTN_JOURNAL_CORE_VERSION ); ?></span>
		</header>
		<?php ntn_journal_core_admin_notice(); ?>

		<nav class="ntn-adm-tabs" aria-label="<?php esc_attr_e( 'Schede di NTN Journal Funzioni', 'ntn-journal-core' ); ?>">
			<?php foreach ( ntn_journal_core_admin_tabs() as $tab_key => $tab_label ) : ?>
				<a href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'page' => 'ntn-journal-functions',
							'tab'  => $tab_key,
						),
						admin_url( 'admin.php' )
					)
				);
				?>
							"<?php echo $active_tab === $tab_key ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $tab_label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="ntn-adm-body">
		<?php
		$view_path = plugin_dir_path( __FILE__ ) . 'views/tab-' . $active_tab . '.php';
		if ( file_exists( $view_path ) ) {
			include $view_path;
		} else {
			echo '<p>Modulo non trovato.</p>';
		}
		?>
		</div>
	</div>
	<?php
}

// Includi handlers AJAX per IGDB
require_once plugin_dir_path( __FILE__ ) . 'igdb-ajax-handlers.php';
