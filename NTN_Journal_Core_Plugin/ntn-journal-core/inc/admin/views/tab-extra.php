<?php
/**
 * Tab Impostazioni Extra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$enable_comments        = get_option( 'ntn_journal_enable_comments', 'yes' );
$enable_videogame_links = get_option( 'ntn_journal_enable_videogame_links', 'yes' );
?>

<div class="card" style="max-width: 100%; margin-top: 0;">
	<h2>Impostazioni Extra</h2>
	<p>Gestisci opzioni aggiuntive del tema NTN Journal.</p>
	
	<form method="post" action="">
		<?php wp_nonce_field( 'ntn_save_extra_settings', 'ntn_extra_settings_nonce' ); ?>
		
		<table class="form-table">
			<tr>
				<th scope="row">Slot Commenti</th>
				<td>
					<label for="ntn_journal_enable_comments">
						<input type="checkbox" name="ntn_journal_enable_comments" id="ntn_journal_enable_comments" value="1" <?php checked( $enable_comments, 'yes' ); ?>>
						Mostra l'area dei commenti in articoli e pagine (se permessa nelle opzioni globali di WP).
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">Link Videogioco (Hub Pages)</th>
				<td>
					<label for="ntn_journal_enable_videogame_links">
						<input type="checkbox" name="ntn_journal_enable_videogame_links" id="ntn_journal_enable_videogame_links" value="1" <?php checked( $enable_videogame_links, 'yes' ); ?>>
						I link dei tag dei giochi portano alla Hub del gioco (<code>/videogioco/nome-del-gioco/</code>).
					</label>
					<div class="notice notice-info inline" style="margin: 10px 0 0 0; padding: 10px;">
						<p style="margin: 0 0 6px;"><strong>Cosa fa:</strong> quando un tag è collegato a un gioco di IGDB, ovunque nel sito il suo link porta alla Hub del gioco invece che all'archivio del tag. Per i giochi che non hanno ancora una scheda "Videogioco", l'indirizzo <code>/videogioco/nome-del-gioco/</code> mostra comunque la loro pagina.</p>
						<p style="margin: 0;"><strong>Se la spegni:</strong> i link dei tag tornano all'archivio <code>/tag/nome-del-gioco/</code> e l'indirizzo <code>/videogioco/…</code> funziona solo per i giochi che hanno una scheda "Videogioco". Le Hub dei giochi esistenti restano online.</p>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row">Cache Hub Page</th>
				<td>
					<label for="ntn_journal_enable_hub_cache">
						<input type="checkbox" name="ntn_journal_enable_hub_cache" id="ntn_journal_enable_hub_cache" value="1" <?php checked( get_option( 'ntn_journal_enable_hub_cache', 'yes' ), 'yes' ); ?>>
						Abilita la Cache Transient per i risultati della Hub Page
					</label>
					<div class="notice notice-info inline" style="margin: 10px 0 0 0; padding: 10px;">
						<p style="margin: 0;"><strong>Scopo:</strong> Salva in memoria i risultati della ricerca articoli per le Hub Page per 24 ore. Riduce drasticamente il carico sul database in quanto le 4 query vengono fatte solo una volta al giorno per ogni gioco, ma richiede tempo per riflettere la pubblicazione di nuove recensioni sulla Hub Page.</p>
					</div>
				</td>
			</tr>
		</table>
		
		<?php submit_button( 'Salva Impostazioni Extra' ); ?>
	</form>

	<hr style="margin: 30px 0;">

	<h2>Azioni Manutenzione</h2>
	<form method="post" action="">
		<?php wp_nonce_field( 'ntn_clear_hub_cache', 'ntn_clear_hub_cache_nonce' ); ?>
		<p>Svuota la memoria del sito se una modifica non compare subito: Hub dei videogiochi, Hub di categoria (Console, Sviluppatori italiani), pagina Redazione, Home e numeri degli autori. Si ricalcolano da soli alla prima visita.</p>
		<?php submit_button( 'Svuota tutta la memoria', 'secondary' ); ?>
	</form>
</div>
