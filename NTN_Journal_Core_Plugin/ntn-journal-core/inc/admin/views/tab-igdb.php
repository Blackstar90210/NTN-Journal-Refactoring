<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Scanner IGDB Tab (v4.34.0: ordine interruttore, associazione, scansione, riepilogo) -->
<?php $ntn_catalog_url = get_post_type_archive_link( 'videogioco' ); ?>
<div class="card" style="padding: 20px; box-sizing: border-box; max-width: 100%;">
	<div style="display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 8px 16px;">
		<h2>Scanner Automazioni IGDB</h2>
		<?php if ( is_string( $ntn_catalog_url ) && '' !== $ntn_catalog_url ) : ?>
			<a class="nadm-btn is-sm" href="<?php echo esc_url( $ntn_catalog_url ); ?>" target="_blank" rel="noopener noreferrer">Apri il Catalogo ↗</a>
		<?php endif; ?>
	</div>
	<p>Associa manualmente tag ai database IGDB per importare copertine, sviluppatori, generi e date di uscita.</p>

	<?php
	$scanner_active = get_option( 'ntn_igdb_scanner_active', 'yes' );
	?>
	<div style="background: var(--nadm-surface); border: 1px solid var(--nadm-line-strong); padding: 15px; margin-bottom: 20px;">
		<form method="post" action="">
			<?php wp_nonce_field( 'ntn_save_igdb_scanner_settings', 'ntn_igdb_scanner_nonce' ); ?>
			<label style="font-weight: bold; display: flex; align-items: center; gap: 10px;">
				<input type="checkbox" name="ntn_igdb_scanner_active" value="yes" <?php checked( $scanner_active, 'yes' ); ?>>
				Attiva lo Scanner IGDB
			</label>
			<p class="description" style="margin-top: 5px; margin-bottom: 10px;">Utilizza questo interruttore per abilitare o disabilitare la visibilità e le funzionalità dello Scanner IGDB.</p>
			<input type="submit" class="button button-secondary" value="Salva Impostazione">
		</form>
	</div>

	<?php if ( $scanner_active !== 'yes' ) : ?>
		<div class="notice notice-warning" style="margin-left: 0;"><p>Lo Scanner IGDB è attualmente <strong>disattivato</strong>. Spunta la casella sopra e salva per riattivarlo.</p></div>
</div>
		<?php
		return;
endif;
	?>

	<?php
	$client_id     = get_option( 'ntn_igdb_client_id', '' );
	$client_secret = get_option( 'ntn_igdb_client_secret', '' );
	if ( empty( $client_id ) || empty( $client_secret ) ) :
		?>
		<div class="notice notice-error" style="margin-left:0; margin-bottom: 20px;"><p><strong>Attenzione:</strong> Devi configurare Client ID e Client Secret di IGDB nella tab "Servizi API" per poter usare questa funzione.</p></div>
	<?php endif; ?>


	<!-- 1. Associazione manuale: tag, gioco IGDB e articolo in evidenza -->
	<div style="display: flex; flex-wrap: wrap; gap: 30px;">
		
		<!-- STEP 1: Trova Tag -->
		<div style="flex: 1; min-width: 300px; padding: 20px; background: var(--nadm-surface); border: 1px solid var(--nadm-line-strong);">
			<h3 style="margin-top: 0;">1. Seleziona Tag WP</h3>
			<p>Cerca il tag a cui associare i dati IGDB (deve già esistere in WordPress).</p>
			<input type="text" id="ntn-tag-search-input" placeholder="Es: The Last of Us..." class="regular-text" style="width: 100%; margin-bottom: 10px;">
			<div id="ntn-tag-search-results" style="max-height: 200px; overflow-y: auto; background: var(--nadm-bg); border: 1px solid var(--nadm-line-strong); display: none;"></div>
			
			<div id="ntn-selected-tag-display" style="margin-top: 15px; padding: 10px; background: var(--nadm-bg); color: var(--nadm-fg); border: 1px solid var(--nadm-fg); box-shadow: inset 2px 0 0 var(--nadm-accent); display: none;">
				Nessun tag selezionato
			</div>
			<input type="hidden" id="ntn-selected-tag-id" value="">
		</div>

		<!-- STEP 2: Trova IGDB -->
		<div style="flex: 1; min-width: 300px; padding: 20px; background: var(--nadm-surface); border: 1px solid var(--nadm-line-strong);">
			<h3 style="margin-top: 0;">2. Cerca nel Database IGDB</h3>
			<p>Cerca il videogioco nel database ufficiale.</p>
			<div style="display: flex; gap: 10px; margin-bottom: 10px;">
				<input type="text" id="ntn-igdb-search-input" placeholder="Es: The Last of Us Part II" class="regular-text" style="flex: 1;">
				<button id="ntn-igdb-search-btn" class="button button-primary">Cerca</button>
			</div>
			<div id="ntn-igdb-search-results" style="max-height: 250px; overflow-y: auto; background: var(--nadm-bg); border: 1px solid var(--nadm-line-strong); padding: 10px; margin-bottom: 10px;">
				I risultati appariranno qui...
			</div>
			<div id="ntn-igdb-selected-preview" style="padding: 10px; background: var(--nadm-bg); color: var(--nadm-fg); border: 1px solid var(--nadm-fg); box-shadow: inset 2px 0 0 var(--nadm-accent); display: none;">
			</div>
		</div>

		<!-- STEP 3: Salva Associazione -->
		<div style="flex: 1; min-width: 300px; padding: 20px; background: var(--nadm-surface); border: 1px solid var(--nadm-line-strong);">
			<h3 style="margin-top: 0;">3. Articolo in evidenza (Opzionale)</h3>
			<p>Inserisci il link per un articolo che vuoi mettere in evidenza all'interno della Hub Page.</p>
			
			<div>
				<input type="url" id="ntn-review-url-input" placeholder="https://..." class="regular-text" style="width: 100%;">
			</div>
		</div>
	</div>

	<div style="margin-top: 20px;">
		<button id="ntn-save-tag-assoc-btn" class="button button-primary button-hero" disabled style="width: 100%; text-align: center;">Salva Associazione</button>
		<div id="ntn-tag-assoc-status" style="margin-top: 15px; font-weight: bold; text-align: center;"></div>
	</div>

	<hr style="margin: 30px 0; border: 0; border-top: 1px solid var(--nadm-line);">

	<!-- 2. Scansione dei tag senza gioco -->
	<?php require __DIR__ . '/igdb-tag-scanner.php'; // v4.15.0: lista dei tag da confermare ?>

	<!-- 3. Riepilogo del database e tabelle dei collegamenti -->
	<?php
	// Ottieni numero totale di articoli pubblicati
	$total_posts = wp_count_posts( 'post' )->publish;

	// Transients per query pesanti SQL
	global $wpdb;

	$posts_with_igdb = get_transient( 'ntn_igdb_post_count' );
	if ( false === $posts_with_igdb ) {
		// La query diretta è usata per ragioni di performance assolute, ma wrappata in un transitorio.
		$posts_with_igdb = $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_ntn_game_title' AND meta_value != ''" );
		if ( ! $posts_with_igdb ) {
			$posts_with_igdb = 0;
		}
		set_transient( 'ntn_igdb_post_count', $posts_with_igdb, 12 * HOUR_IN_SECONDS );
	}

	$tags_with_igdb = get_transient( 'ntn_igdb_tag_count' );
	if ( false === $tags_with_igdb ) {
		$tags_with_igdb = $wpdb->get_var( "SELECT COUNT(DISTINCT term_id) FROM {$wpdb->termmeta} WHERE meta_key = '_ntn_game_title' AND meta_value != ''" );
		if ( ! $tags_with_igdb ) {
			$tags_with_igdb = 0;
		}
		set_transient( 'ntn_igdb_tag_count', $tags_with_igdb, 12 * HOUR_IN_SECONDS );
	}
	?>
	<div style="background: var(--nadm-surface); border: 1px solid var(--nadm-line-strong); padding: 15px; margin-bottom: 20px;">
		<h3 style="margin-top:0;">Riepilogo Database</h3>
		<ul style="margin:0; padding-left: 20px;">
			<li><strong>Articoli Totali Pubblicati:</strong> <?php echo esc_html( $total_posts ); ?></li>
			<li><strong>Articoli Collegati a IGDB:</strong> <?php echo esc_html( $posts_with_igdb ); ?></li>
			<li><strong>Tag di Gioco Collegati a IGDB:</strong> <?php echo esc_html( $tags_with_igdb ); ?></li>
		</ul>
		<div style="margin-top: 15px;">
			<button id="ntn-refresh-cache-btn" class="button button-secondary">Ricalcola Cache Dati</button>
			<button id="ntn-clear-cache-btn" class="button button-secondary">Svuota Cache Dati</button>
			<button id="ntn-repair-reconnect-btn" class="button button-primary" style="margin-left: 10px;">Ripristina & Riconnetti Metadati Orfani</button>
			<span id="ntn-cache-status" style="margin-left: 10px; font-weight: bold;"></span>
		</div>
		<div style="margin-top: 15px;">
			<button id="ntn-load-connections" class="button button-secondary" style="margin-right: 10px;">Visualizza Articoli Collegati</button>
			<button id="ntn-load-tag-connections" class="button button-secondary" style="margin-right: 10px;">Visualizza Tag Collegati</button>
			<button id="ntn-reset-scanner" class="button nadm-btn is-danger">Scollega tutti i dati IGDB (Reset)</button>
		</div>
		<div style="margin-top: 15px;">
			<button id="ntn-core-update-changed-tags" class="button button-primary">Aggiorna solo i collegamenti con modifiche</button>
			<span id="ntn-core-update-changed-status" role="status" style="margin-left: 10px; font-weight: bold;"></span>
			<p class="description" style="margin: 6px 0 0;">Chiede a IGDB quali giochi collegati sono cambiati dall'ultima sincronizzazione e risincronizza solo quei tag.</p>
		</div>
	</div>

	<!-- Container per gli articoli collegati -->
	<div id="ntn-connections-container" style="display:none; margin-bottom: 30px;">
		<h3 style="margin-top: 0; display:flex; align-items:center; justify-content:space-between;">
			<span>Articoli con Scheda Gioco</span>
			<button onclick="document.getElementById('ntn-connections-container').style.display='none'; document.getElementById('ntn-load-connections').disabled=false;" class="button">Chiudi Tabella</button>
		</h3>
		<div id="ntn-connections-loading" style="padding: 10px; background: var(--nadm-surface); text-align:center;">Caricamento in corso...</div>
		<div id="ntn-connections-table-wrapper"></div>
	</div>
	
	<!-- Container per i tag collegati -->
	<div id="ntn-tag-connections-container" style="display:none; margin-bottom: 30px;">
		<h3 style="margin-top: 0; display:flex; align-items:center; justify-content:space-between;">
			<span>Tag collegati a IGDB</span>
			<div>
				<button id="ntn-update-all-tags" class="button button-primary" style="display:none; margin-right:5px;">Aggiorna tutti</button>
				<button id="ntn-unlink-all-tags" class="button nadm-btn is-danger" style="display:none; margin-right:10px;">Scollega tutti</button>
				<button onclick="document.getElementById('ntn-tag-connections-container').style.display='none'; document.getElementById('ntn-load-tag-connections').disabled=false;" class="button">Chiudi Tabella</button>
			</div>
		</h3>
		<div id="ntn-update-progress-container" style="display:none; margin-top: 10px; width: 100%;">
			<div style="background: var(--nadm-line); height: 20px; width: 100%; overflow: hidden;">
				<div id="ntn-update-progress-bar" style="background: var(--nadm-fg); height: 100%; width: 0%; transition: width 0.3s;"></div>
			</div>
			<div id="ntn-update-progress-text" style="text-align: center; font-size: 12px; margin-top: 5px; font-weight: bold;">0%</div>
		</div>
		<div id="ntn-tag-connections-loading" style="padding: 10px; background: var(--nadm-surface); text-align:center;">Caricamento in corso...</div>
		<div id="ntn-tag-connections-table-wrapper"></div>
	</div>
</div>

<?php
// Il codice della scheda sta in assets/js/admin-igdb-scanner.js (dalla 4.27.0); qui solo i due codici di sicurezza.
wp_enqueue_script( 'ntn-core-igdb-scanner', NTN_JOURNAL_CORE_URL . 'assets/js/admin-igdb-scanner.js', array(), NTN_JOURNAL_CORE_VERSION, true );
wp_localize_script(
	'ntn-core-igdb-scanner',
	'ntnIgdbScanner',
	array(
		'nonce'      => wp_create_nonce( 'ntn_igdb_nonce' ),
		'batchNonce' => wp_create_nonce( 'ntn_batch_scan_igdb_nonce' ),
	)
);
?>
