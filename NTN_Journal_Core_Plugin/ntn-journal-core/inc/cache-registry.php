<?php
/**
 * NTN Journal Core - Registro delle memorie (cache) del sito (v4.19.0)
 *
 * Un solo elenco dice quali memorie esistono, cosa contengono, quanto durano e se "Svuota tutta la memoria"
 * le cancella. Il pulsante del pannello e il riquadro informativo leggono questo elenco, quindi non c'è un
 * secondo elenco da tenere allineato: chi crea una memoria nuova la aggiunge qui e basta.
 * Un test controlla che ogni nome di memoria usato nel codice sia coperto da una voce di questo registro.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Le memorie del sito, una voce per famiglia.
 *
 * Campi di ogni voce:
 * - label        nome in italiano;
 * - what         cosa contiene, in parole semplici;
 * - ttl          quanto dura, a parole;
 * - prefix       inizio del nome salvato nel database ('' se non è salvata lì);
 * - object_group gruppo della cache in memoria (opzionale);
 * - flush        true se "Svuota tutta la memoria" la cancella;
 * - kept_reason  se flush è false, perché resta (mostrato nel riquadro);
 * - hidden       true per le voci tecniche che il riquadro non mostra.
 *
 * @return array[]
 */
function ntn_core_cache_registry() {
	return array(
		'hub_featured'  => array(
			'label'  => 'Hub dei videogiochi: articoli in evidenza',
			'what'   => 'Gli articoli mostrati in alto nella pagina di ogni gioco.',
			'ttl'    => 'fino a 1 giorno',
			'prefix' => NTN_CORE_FEATURED_CACHE_PREFIX,
			'flush'  => true,
		),
		'hub_counts'    => array(
			'label'  => 'Hub dei videogiochi: numeri nell\'intestazione',
			'what'   => 'Quanti articoli, recensioni e notizie ha ogni gioco.',
			'ttl'    => '1 ora',
			'prefix' => 'ntn_hub_counts_',
			'flush'  => true,
		),
		'cat_hub'       => array(
			'label'  => 'Hub di categoria e pagina Statistiche',
			'what'   => 'Console, Sviluppatori italiani e le statistiche per anno.',
			'ttl'    => 'da 6 ore a 1 giorno',
			'prefix' => 'ntn_cat_hub_',
			'flush'  => true,
		),
		'studio'        => array(
			'label'  => 'Hub degli sviluppatori',
			'what'   => 'Elenco degli studi, i loro giochi e la pagina di ogni studio.',
			'ttl'    => 'da 1 ora a 1 giorno',
			'prefix' => 'ntn_studio_',
			'flush'  => true,
		),
		'hub_review'    => array(
			'label'  => 'Gioco con o senza recensione',
			'what'   => 'Serve per mostrare e descrivere la recensione di un gioco (anche nei dati per Google).',
			'ttl'    => '1 ora',
			'prefix' => 'ntn_hub_has_review_',
			'flush'  => true,
		),
		'team'          => array(
			'label'  => 'Pagina Redazione',
			'what'   => 'Gruppi e persone della redazione con i loro numeri.',
			'ttl'    => '6 ore',
			'prefix' => 'ntn_team_',
			'flush'  => true,
		),
		'front'         => array(
			'label'  => 'Home',
			'what'   => 'Le sezioni della prima pagina del sito.',
			'ttl'    => '10 minuti',
			'prefix' => 'ntn_front_data_',
			'flush'  => true,
		),
		'author_stats'  => array(
			'label'  => 'Numeri degli autori',
			'what'   => 'Articoli e altri totali mostrati nel profilo di ogni autore.',
			'ttl'    => '1 ora',
			'prefix' => 'ntn_author_stats_',
			'flush'  => true,
		),
		'author_read'   => array(
			'label'  => 'Tempo di lettura degli autori',
			'what'   => 'Somma dei minuti di lettura degli articoli di un autore.',
			'ttl'    => '12 ore',
			'prefix' => 'ntn_author_reading_',
			'flush'  => true,
		),
		'catalog'       => array(
			'label'        => 'Catalogo dei videogiochi',
			'what'         => 'Le pagine del catalogo caricate con lo scorrimento e con i filtri.',
			'ttl'          => '5 minuti',
			'prefix'       => 'ntn_games_',
			'object_group' => NTN_CORE_REST_CACHE_GROUP,
			'flush'        => true,
		),
		'live_search'   => array(
			'label'  => 'Ricerca veloce dei giochi',
			'what'   => 'I risultati dei testi cercati dai visitatori.',
			'ttl'    => '5 minuti',
			'prefix' => 'ntn_livesearch2_',
			'flush'  => true,
		),
		'game_links'    => array(
			'label'  => 'Collegamenti agli articoli e alle schede dei giochi',
			'what'   => 'Quale scheda videogioco corrisponde a un articolo e viceversa.',
			'ttl'    => '12 ore',
			'prefix' => 'ntn_vg_link_',
			'flush'  => true,
		),
		'engine_tags'   => array(
			'label'  => 'Tag dei giochi per motore grafico',
			'what'   => 'Quali tag appartengono a un motore grafico.',
			'ttl'    => '12 ore',
			'prefix' => 'ntn_engine_tags_',
			'flush'  => true,
		),
		'primary_cat'   => array(
			'label'        => 'Categoria principale dei giochi',
			'what'         => 'La categoria usata per colorare e raggruppare un gioco.',
			'ttl'          => '1 ora, solo con la cache del server',
			'prefix'       => 'ntn_primary_cat_',
			'object_group' => 'ntn_journal',
			'flush'        => true,
		),
		'thin_pages'    => array(
			'label'  => 'Pagine escluse da Google perché povere di contenuto',
			'what'   => 'L\'elenco dei tag e delle pagine marcati noindex.',
			'ttl'    => '10 minuti',
			'prefix' => 'ntn_seo_thin_',
			'flush'  => true,
		),
		'news_sitemap'  => array(
			'label'  => 'Mappa delle notizie per Google News',
			'what'   => 'Il file XML con le notizie più recenti.',
			'ttl'    => '15 minuti',
			'prefix' => 'ntn_news_sitemap_cache',
			'flush'  => true,
		),
		'panel_checks'  => array(
			'label'  => 'Controlli del pannello',
			'what'   => 'I problemi segnalati nella scheda Dashboard (copertura delle Hub).',
			'ttl'    => '10 minuti',
			'prefix' => 'ntn_hub_cov_',
			'flush'  => true,
		),
		'platform_menu' => array(
			'label'  => 'Menu a tendina delle piattaforme',
			'what'   => 'Il menu con le console e i marchi, nel tema.',
			'ttl'    => '1 ora',
			'prefix' => 'ntn_pdrop_',
			'flush'  => true,
		),
		'ai_summary'    => array(
			'label'       => 'Riassunti scritti dall\'intelligenza artificiale',
			'what'        => 'I riassunti già generati per gli articoli.',
			'ttl'         => '1 mese',
			'prefix'      => 'ntn_ai_summary_',
			'flush'       => false,
			'kept_reason' => 'Rifarli costa richieste al servizio di intelligenza artificiale, quindi restano.',
		),
		'yt_oembed'     => array(
			'label'       => 'Dati dei video YouTube',
			'what'        => 'Titolo e miniatura dei video incorporati.',
			'ttl'         => '4 settimane',
			'prefix'      => 'ntn_yt_oembed_',
			'flush'       => false,
			'kept_reason' => 'Vengono da YouTube e cambiano di rado: rifarli rallenterebbe le pagine.',
		),
		'yt_date'       => array(
			'label'       => 'Date di caricamento dei trailer YouTube',
			'what'        => 'La data vera in cui ogni trailer è stato caricato su YouTube, usata nei dati per Google.',
			'ttl'         => '1 anno',
			'prefix'      => 'ntn_yt_date_',
			'flush'       => false,
			'kept_reason' => 'Non cambiano mai e rifarle costa richieste a YouTube.',
		),
		'igdb_answers'  => array(
			'label'       => 'Risposte di IGDB',
			'what'        => 'Ricerche, schede dei giochi e controlli dei tag già chiesti a IGDB.',
			'ttl'         => '1 giorno',
			'prefix'      => 'ntn_igdb_',
			'flush'       => false,
			'kept_reason' => 'IGDB accetta poche richieste al secondo e il token di accesso va tenuto: si svuotano dalla scheda IGDB (Svuota Cache Dati) o da sole.',
		),
		'admin_notice'  => array(
			'label'  => 'Messaggi del pannello',
			'what'   => 'Il messaggio mostrato per un minuto dopo un salvataggio.',
			'ttl'    => '1 minuto',
			'prefix' => 'ntn_admin_notice_',
			'flush'  => false,
			'hidden' => true,
		),
		'panel_counts'  => array(
			'label'       => 'Totali delle schede IGDB e Backup',
			'what'        => 'Quanti articoli e tag hanno i dati IGDB.',
			'ttl'         => '12 ore',
			'prefix'      => 'ntn_core_',
			'flush'       => false,
			'kept_reason' => 'Sono solo contatori del pannello e si aggiornano da soli.',
		),
	);
}

/**
 * Le voci che "Svuota tutta la memoria" cancella.
 *
 * @return array[]
 */
function ntn_core_cache_registry_flushed() {
	return array_filter( ntn_core_cache_registry(), static function ( $entry ) {
		return ! empty( $entry['flush'] );
	} );
}

/**
 * Le voci che restano al loro posto.
 *
 * @return array[]
 */
function ntn_core_cache_registry_kept() {
	return array_filter( ntn_core_cache_registry(), static function ( $entry ) {
		return empty( $entry['flush'] ) && empty( $entry['hidden'] );
	} );
}

/**
 * Il nome salvato nel database è coperto da una voce del registro?
 *
 * @param string $name Nome della memoria (senza il prefisso _transient_).
 * @return bool
 */
function ntn_core_cache_registry_covers( $name ) {
	foreach ( ntn_core_cache_registry() as $entry ) {
		if ( '' !== $entry['prefix'] && 0 === strpos( (string) $name, $entry['prefix'] ) ) {
			return true;
		}
	}
	return false;
}
