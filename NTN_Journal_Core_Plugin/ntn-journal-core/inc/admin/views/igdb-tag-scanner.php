<?php
/**
 * Scanner dei tag IGDB nel pannello (v4.15.0): lista da confermare riga per riga o in massa.
 * Nomi di tag e giochi vengono da fuori: si scrivono sempre come testo (textContent), mai come codice.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="ntn-scan" style="background: var(--nadm-surface); border: 1px solid var(--nadm-line-strong); padding: 15px; margin-bottom: 25px;">
	<h3 style="margin-top: 0;">Scansiona i tag senza gioco</h3>
	<p>Cerca su IGDB i tag degli articoli che non hanno un gioco collegato. La scansione non modifica nulla: i dati e la pagina Hub vengono creati solo quando confermi.</p>
	<button type="button" id="ntn-scan-start" class="button button-primary">Scansiona</button>
	<button type="button" id="ntn-scan-confirm-all" class="button button-primary" style="display: none;">Conferma selezionati</button>
	<button type="button" id="ntn-scan-reset" class="button button-secondary">Rimetti in lista i tag esclusi</button>
	<span id="ntn-scan-status" role="status" style="margin-left: 10px; font-weight: bold;"></span>
	<div id="ntn-scan-lists" style="margin-top: 15px;"></div>
</div>

<?php
// Il codice del riquadro sta in assets/js/admin-igdb-tag-scanner.js (dalla 4.28.0); qui solo i due codici di sicurezza.
wp_enqueue_script( 'ntn-core-igdb-tag-scanner', NTN_JOURNAL_CORE_URL . 'assets/js/admin-igdb-tag-scanner.js', array(), NTN_JOURNAL_CORE_VERSION, true );
wp_localize_script(
	'ntn-core-igdb-tag-scanner',
	'ntnIgdbTagScanner',
	array(
		'nonce'       => wp_create_nonce( 'ntn_igdb_scan' ),
		'searchNonce' => wp_create_nonce( 'ntn_igdb_nonce' ),
	)
);
?>
