<?php
/**
 * Tab Backup / Ripristino Unificato NTN Journal Core
 *
 * @package NTN_Journal_Core
 * @version 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$backup_nonce = wp_create_nonce( 'ntn_backup_nonce' );

// Statistiche rapide sui dati Core
$posts_with_core = get_transient( 'ntn_core_posts_count' );
if ( false === $posts_with_core ) {
	global $wpdb;
	$posts_with_core = (int) $wpdb->get_var(
		"
		SELECT COUNT(DISTINCT p.ID) 
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
		WHERE p.post_type IN ('videogioco', 'comunicato_stampa')
		   OR pm.meta_key LIKE '_ntn_%'
		   OR pm.meta_key LIKE 'ntn_%'
		   OR pm.meta_key IN ('rating', 'pros', 'cons', 'verdict', 'reviewed_item', 'ntn_review_source')
	"
	);
	set_transient( 'ntn_core_posts_count', $posts_with_core, 12 * HOUR_IN_SECONDS );
}

$terms_with_core = get_transient( 'ntn_core_terms_count' );
if ( false === $terms_with_core ) {
	global $wpdb;
	$terms_with_core = (int) $wpdb->get_var(
		"
		SELECT COUNT(DISTINCT t.term_id)
		FROM {$wpdb->terms} t
		INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
		LEFT JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id
		WHERE tt.taxonomy IN ('game_engine', 'game_franchise', 'game_developer', 'game_publisher', 'game_year')
		   OR tm.meta_key LIKE '_ntn_%'
		   OR tm.meta_key LIKE 'ntn_%'
	"
	);
	set_transient( 'ntn_core_terms_count', $terms_with_core, 12 * HOUR_IN_SECONDS );
}
?>
<!-- Backup Tab -->
<div class="card" style="padding: 20px; box-sizing: border-box; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; max-width: 100%;">
	<h2>Backup / Ripristino Unificato NTN Journal Core</h2>
	<p>Strumento <strong>All-in-One</strong> per il salvataggio e il ripristino integrale dell'ecosistema <strong>NTN Journal Core</strong>: include tutte le impostazioni e opzioni (IGDB, Redirect 301/302, Breaking News, SEO Extras, AI Summaries), i Custom Post Types (<em>Videogiochi</em> e <em>Comunicati Stampa</em> con relative tassonomie), le schede recensione e i metadati delle Hub Page in formato JSONL in streaming ad alte prestazioni (con retrocompatibilità per vecchi file JSON).</p>
	
	<div class="notice notice-info inline" style="margin: 15px 0 20px 0; padding: 12px;">
		<p style="margin: 0;">
			<strong>Copertura Unificata Core:</strong> A differenza dell'esportatore XML standard di WordPress, questa procedura migra e ripristina la mappa di redirect, le API key, i metadati strutturati, le schede recensione e le relazioni tassonomiche del plugin senza dispersioni di dati.
		</p>
	</div>

	<!-- Indicatori Dati Presenti -->
	<div style="display: flex; gap: 20px; margin-bottom: 25px;">
		<div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; text-align: center;">
			<div style="font-size: 24px; font-weight: bold; color: #0f172a; font-family: monospace;"><?php echo esc_html( (string) $posts_with_core ); ?></div>
			<div style="font-size: 13px; color: #64748b; margin-top: 4px;">Post & CPT con Dati Core</div>
		</div>
		<div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; text-align: center;">
			<div style="font-size: 24px; font-weight: bold; color: #0f172a; font-family: monospace;"><?php echo esc_html( (string) $terms_with_core ); ?></div>
			<div style="font-size: 13px; color: #64748b; margin-top: 4px;">Termini, Tassonomie & Hub Page</div>
		</div>
	</div>

	<div style="display: flex; flex-wrap: wrap; gap: 30px;">
		
		<!-- Sezione Esportazione -->
		<div style="flex: 1; min-width: 320px; padding: 22px; background: #f0f6fc; border: 1px solid #c8d7e1; border-radius: 6px; display: flex; flex-direction: column;">
			<h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
				<span class="dashicons dashicons-download" style="color: #0073aa;"></span>
				Esporta Pacchetto Core Unificato
			</h3>
			<p style="color: #475569; font-size: 13px; line-height: 1.5;">
				Genera un archivio JSONL contenente tutte le Opzioni Core, i CPT (videogiochi e comunicati), i metadati recensioni (voti, pro, contro), i collegamenti IGDB e le tassonomie personalizzate.
			</p>
			
			<div id="ntn-export-progress-wrap" style="display: none; margin-top: 15px; margin-bottom: 15px;">
				<div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px; color: #334155;">
					<span id="ntn-export-status-text">Inizializzazione...</span>
					<span id="ntn-export-percentage">0%</span>
				</div>
				<div style="background: #e2e8f0; border-radius: 4px; height: 8px; overflow: hidden;">
					<div id="ntn-export-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
				</div>
			</div>

			<div id="ntn-export-result" style="display: none; margin-bottom: 15px;"></div>

			<div style="margin-top: auto; padding-top: 15px;">
				<button type="button" id="ntn-btn-export-core" class="button button-primary" style="display: inline-flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-database-export" style="font-size: 17px; line-height: 1.6;"></span>
					Esporta Dati Core (JSONL)
				</button>
			</div>
		</div>
		
		<!-- Sezione Importazione -->
		<div style="flex: 1; min-width: 320px; padding: 22px; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 6px; display: flex; flex-direction: column;">
			<h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px; color: #9b2c2c;">
				<span class="dashicons dashicons-upload" style="color: #c53030;"></span>
				Ripristina Pacchetto Core Unificato
			</h3>
			<p style="color: #475569; font-size: 13px; line-height: 1.5;">
				Carica un file JSONL (o legacy JSON) per ripristinare opzioni, redirect, CPT, recensioni e metadati. I CPT mancanti vengono creati automaticamente in modo sicuro.
			</p>

			<div style="margin-bottom: 15px;">
				<input type="file" id="ntn-import-file-input" accept=".jsonl,.json" style="display: block; width: 100%; font-size: 13px;">
			</div>

			<div class="notice notice-warning inline" style="margin: 0 0 15px 0; padding: 10px;">
				<p style="margin: 0; font-size: 12px; color: #742a2a;">
					<strong>Attenzione:</strong> Le opzioni del Core e i metadati corrispondenti verranno aggiornati con le informazioni contenute nel file.
				</p>
			</div>

			<div id="ntn-import-progress-wrap" style="display: none; margin-bottom: 15px;">
				<div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px; color: #334155;">
					<span id="ntn-import-status-text">Importazione in corso...</span>
					<span id="ntn-import-counter">0 elementi</span>
				</div>
				<div style="background: #fed7d7; border-radius: 4px; height: 8px; overflow: hidden;">
					<div id="ntn-import-bar" style="background: #e53e3e; height: 100%; width: 30%; transition: width 0.3s;"></div>
				</div>
			</div>

			<div id="ntn-import-result" style="display: none; margin-bottom: 15px;"></div>

			<div style="margin-top: auto; padding-top: 15px;">
				<button type="button" id="ntn-btn-import-core" class="button button-primary" style="background: #c53030; border-color: #9b2c2c; display: inline-flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-database-import" style="font-size: 17px; line-height: 1.6;"></span>
					Importa Dati Core
				</button>
			</div>
		</div>

	</div>
</div>

<?php
// Il codice della scheda sta in assets/js/admin-backup.js (dalla 4.29.0); qui solo l'indirizzo delle richieste e il codice di sicurezza.
wp_enqueue_script( 'ntn-core-backup', NTN_JOURNAL_CORE_URL . 'assets/js/admin-backup.js', array(), NTN_JOURNAL_CORE_VERSION, true );
wp_localize_script(
	'ntn-core-backup',
	'ntnBackup',
	array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => $backup_nonce,
	)
);
?>
