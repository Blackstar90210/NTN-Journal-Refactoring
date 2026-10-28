<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Servizi API (v3.17.1).
 *
 * Le chiavi non vengono mai scritte nella pagina: per ogni servizio si vede lo stato
 * (Attiva, Salvata non verificata, Errore, Mancante). Un campo lasciato vuoto non cambia
 * la chiave salvata; "Cancella" la toglie. "Verifica" fa una richiesta di prova gratuita.
 * Tolta la casella "Abilita Integrazione IGDB", che nessuna parte del sito leggeva.
 */
$ntn_status_colors = array(
	'active'  => '#00a32a',
	'saved'   => '#996800',
	'error'   => '#d63638',
	'missing' => '#646970',
);
?>
<!-- Servizi API Tab -->
<div class="card" style="padding: 20px; box-sizing: border-box; background: #fff; border: 1px solid #ccd0d4; max-width: 100%;">
	<h2>Servizi API ed Integrazioni</h2>
	<p>Le chiavi dei servizi esterni usati dal sito: i riassunti con l'IA (Gemini, con Groq come riserva) e il database dei giochi IGDB. Per sicurezza le chiavi salvate non vengono mai mostrate: per cambiarne una scrivi quella nuova, per tenerla lascia il campo vuoto.</p>

	<?php foreach ( ntn_core_api_services() as $ntn_service => $ntn_info ) : ?>
		<?php $ntn_status = ntn_core_api_status_get( $ntn_service ); ?>
		<div style="border: 1px solid #ccd0d4; padding: 16px; margin-top: 16px;">
			<div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px;">
				<h3 style="margin: 0;"><?php echo esc_html( $ntn_info['label'] ); ?></h3>
				<div style="display: flex; align-items: center; gap: 12px;">
					<strong style="color: <?php echo esc_attr( $ntn_status_colors[ $ntn_status['state'] ] ); ?>;">● <?php echo esc_html( $ntn_status['label'] ); ?></strong>
					<?php if ( 'missing' !== $ntn_status['state'] ) : ?>
						<form method="post" action="" style="margin: 0;">
							<?php wp_nonce_field( 'ntn_verify_api', 'ntn_api_verify_nonce' ); ?>
							<input type="hidden" name="ntn_api_service" value="<?php echo esc_attr( $ntn_service ); ?>">
							<button type="submit" class="button button-secondary">Verifica</button>
						</form>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( '' !== $ntn_status['message'] ) : ?>
				<p style="margin: 8px 0 0; color: #d63638;"><?php echo esc_html( $ntn_status['message'] ); ?></p>
			<?php endif; ?>
			<?php if ( $ntn_status['time'] ) : ?>
				<p class="description" style="margin: 6px 0 0;">Ultimo controllo: <?php echo esc_html( wp_date( 'd.m.Y H:i', $ntn_status['time'] ) ); ?></p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>

	<form method="post" action="" style="margin-top: 24px;">
		<?php wp_nonce_field( 'ntn_save_api_services', 'ntn_api_services_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<?php foreach ( ntn_core_api_services() as $ntn_service => $ntn_info ) : ?>
					<?php foreach ( $ntn_info['options'] as $ntn_option => $ntn_field_label ) : ?>
						<?php $ntn_has_key = '' !== trim( (string) get_option( $ntn_option, '' ) ); ?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $ntn_option ); ?>"><?php echo esc_html( $ntn_info['label'] . ' · ' . $ntn_field_label ); ?></label></th>
							<td>
								<input type="password" id="<?php echo esc_attr( $ntn_option ); ?>" name="<?php echo esc_attr( $ntn_option ); ?>" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo esc_attr( $ntn_has_key ? 'Chiave salvata: scrivi qui per cambiarla' : 'Nessuna chiave' ); ?>">
								<?php if ( $ntn_has_key ) : ?>
									<label style="margin-left: 10px;"><input type="checkbox" name="<?php echo esc_attr( $ntn_option ); ?>_clear" value="1"> Cancella</label>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">Gemini: chiave da Google AI Studio. Groq: chiave dalla console di Groq. IGDB: Client ID e Client Secret dalla Twitch Developer Console.</p>

		<p class="submit">
			<input type="submit" class="button button-primary" value="Salva Servizi API">
		</p>
	</form>
</div>
