<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Ticker Breaking News Tab -->
<div class="card" style="padding: 20px; box-sizing: border-box; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; max-width: 100%;">
	<h2>Ticker Breaking News / Copertura Live</h2>
	<p>Configura la barra superiore per mostrare notizie dell'ultima ora o dirette streaming.</p>
	
	<form method="post" action="">
		<?php wp_nonce_field( 'ntn_save_breaking_news', 'ntn_breaking_news_nonce' ); ?>
		
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">Stato Ticker</th>
					<td>
						<label>
							<input type="checkbox" name="ntn_breaking_news_active" value="1" <?php checked( get_option('ntn_breaking_news_active', 'no'), 'yes' ); ?>>
							<strong>Attiva il Ticker sul sito</strong>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ntn_breaking_news_label">Testo Badge (es. LIVE)</label></th>
					<td>
						<input type="text" id="ntn_breaking_news_label" name="ntn_breaking_news_label" value="<?php echo esc_attr( get_option('ntn_breaking_news_label', 'LIVE') ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ntn_breaking_news_text">Testo Notizia</label></th>
					<td>
						<input type="text" id="ntn_breaking_news_text" name="ntn_breaking_news_text" value="<?php echo esc_attr( get_option('ntn_breaking_news_text', '') ); ?>" class="large-text" placeholder="Es. Segui la diretta dello State of Play...">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ntn_breaking_news_url">URL Destinazione</label></th>
					<td>
						<input type="url" id="ntn_breaking_news_url" name="ntn_breaking_news_url" value="<?php echo esc_attr( get_option('ntn_breaking_news_url', '') ); ?>" class="regular-text" placeholder="https://...">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ntn_breaking_news_badge_color">Colore LED</label></th>
					<td>
						<select id="ntn_breaking_news_badge_color" name="ntn_breaking_news_badge_color">
							<option value="#e11d48" <?php selected( get_option('ntn_breaking_news_badge_color', '#e11d48'), '#e11d48' ); ?>>Rosso (Pulsante)</option>
							<option value="#eab308" <?php selected( get_option('ntn_breaking_news_badge_color'), '#eab308' ); ?>>Giallo</option>
							<option value="#22c55e" <?php selected( get_option('ntn_breaking_news_badge_color'), '#22c55e' ); ?>>Verde</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">Comportamento</th>
					<td>
						<label style="display: block; margin-bottom: 10px;">
							<input type="checkbox" name="ntn_breaking_news_sticky" value="1" <?php checked( get_option('ntn_breaking_news_sticky', 'no'), 'yes' ); ?>>
							<strong>Sticky (Fisso in alto)</strong> - Rimane visibile durante lo scorrimento
						</label>
						<label style="display: block;">
							<input type="checkbox" name="ntn_breaking_news_dismissable" value="1" <?php checked( get_option('ntn_breaking_news_dismissable', 'no'), 'yes' ); ?>>
							<strong>Chiudibile (Dismissable)</strong> - Consenti all'utente di chiuderlo e riaprirlo
						</label>
					</td>
				</tr>
			</tbody>
		</table>
		
		<p class="submit">
			<input type="submit" class="button button-primary" value="Salva Impostazioni Ticker">
		</p>
	</form>
</div>
