<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Scheda "Hub" (v3.18.0): le Hub di categoria si governano solo da qui.
 *
 * Elenco delle Hub accese, modificabili in linea; trasformazione di una categoria in Hub;
 * controllo copertura; memoria delle Hub. I dati restano nei meta della categoria (_ntn_hub_*).
 * Riferimento visivo: mockup, pagina "Amministrazione", scheda Hub.
 */
if ( ! function_exists( 'ntn_core_category_hubs' ) ) {
	echo '<p>Modulo delle Hub non disponibile.</p>';
	return;
}

$ntn_hubs    = ntn_core_category_hubs();
$ntn_choices = ntn_core_category_hub_choices();
$ntn_edit    = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo apertura del modulo.
$ntn_base    = add_query_arg( array( 'page' => 'ntn-journal-functions', 'tab' => 'hub' ), admin_url( 'admin.php' ) );
$ntn_enabled = wp_list_pluck( $ntn_hubs, 'term_id' );
?>
<div class="nadm-panel">

	<div class="nadm-sec"><h2>// Hub accese</h2><span class="nadm-aside"><?php echo esc_html( sprintf( 1 === count( $ntn_hubs ) ? '%d Hub' : '%d Hub', count( $ntn_hubs ) ) ); ?> · si governano solo da qui</span></div>

	<?php if ( empty( $ntn_hubs ) ) : ?>
		<p class="nadm-note">Nessuna Hub accesa. Trasforma una categoria in Hub qui sotto.</p>
	<?php else : ?>
		<div class="nadm-scroll">
			<table class="nadm-table">
				<thead><tr><th>Categoria</th><th>Tipo</th><th>Illustrazione</th><th>In evidenza</th><th>Articoli</th><th>Stato</th><th></th></tr></thead>
				<tbody>
					<?php foreach ( $ntn_hubs as $ntn_hub ) : ?>
						<?php
						$ntn_id       = (int) $ntn_hub->term_id;
						$ntn_type     = ntn_core_category_hub_meta( $ntn_id, 'type' );
						$ntn_featured = ntn_core_category_hub_meta( $ntn_id, 'featured' );
						$ntn_open     = $ntn_edit === $ntn_id;
						?>
						<tr<?php echo $ntn_open ? ' class="is-open"' : ''; ?>>
							<td class="is-t"><a href="<?php echo esc_url( (string) get_term_link( $ntn_hub ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( ntn_core_category_hub_label( $ntn_hub ) ); ?></a></td>
							<td><?php echo esc_html( 'studi' === $ntn_type ? 'Studi' : ( 'statistiche' === $ntn_type ? 'Statistiche' : 'Console' ) ); ?></td>
							<td><?php echo esc_html( $ntn_choices['illustration'][ ntn_core_category_hub_meta( $ntn_id, 'illustration' ) ] ); ?></td>
							<td><?php echo esc_html( $ntn_featured ? get_the_title( $ntn_featured ) . ' (fisso)' : 'Automatico (ultima recensione)' ); ?></td>
							<td class="is-n"><?php echo esc_html( number_format_i18n( ntn_core_category_hub_count( ntn_core_category_hub_tree( $ntn_id ) ) ) ); ?></td>
							<td><span class="nadm-st">Accesa</span></td>
							<td><a class="nadm-btn is-sm" href="<?php echo esc_url( $ntn_open ? $ntn_base : add_query_arg( 'edit', $ntn_id, $ntn_base ) ); ?>"><?php echo $ntn_open ? 'Chiudi' : 'Modifica'; ?></a></td>
						</tr>
						<?php if ( $ntn_open ) : ?>
							<?php
							$ntn_recent = ntn_core_category_hub_post_ids( ntn_core_category_hub_tree( $ntn_id ), 30 );
							if ( $ntn_featured && ! in_array( $ntn_featured, $ntn_recent, true ) ) {
								array_unshift( $ntn_recent, $ntn_featured );
							}
							?>
							<tr class="is-open"><td colspan="7">
								<form method="post" action="" class="nadm-form">
									<?php wp_nonce_field( 'ntn_hub_save', 'ntn_hub_save_nonce' ); ?>
									<input type="hidden" name="ntn_hub_term" value="<?php echo esc_attr( (string) $ntn_id ); ?>">
									<div class="nadm-field"><label for="ntn_hub_type">Tipo</label>
										<select id="ntn_hub_type" name="ntn_hub_type">
											<?php foreach ( $ntn_choices['type'] as $ntn_value => $ntn_label ) : ?>
												<option value="<?php echo esc_attr( $ntn_value ); ?>" <?php selected( $ntn_type, $ntn_value ); ?>><?php echo esc_html( $ntn_label ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="nadm-field"><label for="ntn_hub_title">Titolo mostrato</label>
										<input type="text" id="ntn_hub_title" name="ntn_hub_title" value="<?php echo esc_attr( ntn_core_category_hub_meta( $ntn_id, 'title' ) ); ?>" placeholder="<?php echo esc_attr( $ntn_hub->name ); ?>">
									</div>
									<div class="nadm-field is-wide"><label for="ntn_hub_intro">Testo di presentazione</label>
										<textarea id="ntn_hub_intro" name="ntn_hub_intro" rows="3"><?php echo esc_textarea( ntn_core_category_hub_meta( $ntn_id, 'intro' ) ); ?></textarea>
										<span class="nadm-help">Se è vuoto si usa la descrizione della categoria.</span>
									</div>
									<div class="nadm-field"><label for="ntn_hub_illustration">Illustrazione a punti</label>
										<select id="ntn_hub_illustration" name="ntn_hub_illustration">
											<?php foreach ( $ntn_choices['illustration'] as $ntn_value => $ntn_label ) : ?>
												<option value="<?php echo esc_attr( $ntn_value ); ?>" <?php selected( ntn_core_category_hub_meta( $ntn_id, 'illustration' ), $ntn_value ); ?>><?php echo esc_html( $ntn_label ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="nadm-field"><label for="ntn_hub_featured">Articolo in evidenza</label>
										<select id="ntn_hub_featured" name="ntn_hub_featured">
											<option value="0">Automatico: l'ultima recensione</option>
											<?php foreach ( $ntn_recent as $ntn_post_id ) : ?>
												<option value="<?php echo esc_attr( (string) $ntn_post_id ); ?>" <?php selected( $ntn_featured, $ntn_post_id ); ?>><?php echo esc_html( wp_trim_words( get_the_title( $ntn_post_id ), 12, '…' ) ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<?php if ( 'studi' === $ntn_type ) : ?>
										<div class="nadm-field is-wide"><label for="ntn_hub_excluded">Studi da escludere dall'indice</label>
											<input type="text" id="ntn_hub_excluded" name="ntn_hub_excluded" value="<?php echo esc_attr( ntn_core_category_hub_meta( $ntn_id, 'excluded' ) ); ?>">
											<span class="nadm-help">Nomi separati da virgola, per esempio studi stranieri indicati da IGDB come co-sviluppatori.</span>
										</div>
									<?php elseif ( 'console' === $ntn_type ) : ?>
										<div class="nadm-field is-wide"><span class="nadm-fl">Marchi (sottocategorie) e nome mostrato</span>
											<div class="nadm-form">
												<?php foreach ( ntn_core_category_hub_children( $ntn_id ) as $ntn_brand ) : ?>
													<div class="nadm-field"><label for="ntn_brand_<?php echo esc_attr( (string) $ntn_brand->term_id ); ?>"><?php echo esc_html( $ntn_brand->name ); ?></label>
														<input type="text" id="ntn_brand_<?php echo esc_attr( (string) $ntn_brand->term_id ); ?>" name="ntn_hub_brand_title[<?php echo esc_attr( (string) $ntn_brand->term_id ); ?>]" value="<?php echo esc_attr( ntn_core_category_hub_meta( (int) $ntn_brand->term_id, 'title' ) ); ?>" placeholder="<?php echo esc_attr( $ntn_brand->name ); ?>">
													</div>
												<?php endforeach; ?>
											</div>
											<span class="nadm-help">Per esempio "PlayStation" per Sony o "Xbox" per Microsoft. L'indirizzo non cambia.</span>
										</div>
									<?php endif; ?>
									<div class="nadm-actions is-wide">
										<button type="submit" name="ntn_hub_action" value="save" class="nadm-btn is-solid">Salva</button>
										<button type="submit" name="ntn_hub_action" value="off" class="nadm-btn is-danger">Spegni la Hub</button>
										<a class="nadm-btn" href="<?php echo esc_url( (string) get_term_link( $ntn_hub ) ); ?>" target="_blank" rel="noopener noreferrer">Vedi la Hub ↗</a>
									</div>
								</form>
							</td></tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<div class="nadm-box">
		<div class="nadm-box-h"><span>// Trasforma una categoria in Hub</span></div>
		<form method="post" action="" class="nadm-box-b">
			<?php wp_nonce_field( 'ntn_hub_new', 'ntn_hub_new_nonce' ); ?>
			<p class="nadm-note">Scegli una categoria esistente e il tipo di pagina che vuoi. L'indirizzo della categoria non cambia: cambia solo come appare.</p>
			<div class="nadm-form">
				<div class="nadm-field"><label for="ntn_hub_new_term">1 · Quale categoria</label>
					<?php
					wp_dropdown_categories(
						array(
							'taxonomy'         => 'category',
							'name'             => 'ntn_hub_term',
							'id'               => 'ntn_hub_new_term',
							'hide_empty'       => false,
							'hierarchical'     => true,
							'orderby'          => 'name',
							'exclude'          => $ntn_enabled,
							'show_option_none' => 'Scegli una categoria',
							'option_none_value' => '0',
						)
					);
					?>
				</div>
				<div class="nadm-field"><label for="ntn_hub_new_type">2 · Che tipo di Hub</label>
					<select id="ntn_hub_new_type" name="ntn_hub_type">
						<?php foreach ( $ntn_choices['type'] as $ntn_value => $ntn_label ) : ?>
							<option value="<?php echo esc_attr( $ntn_value ); ?>"><?php echo esc_html( $ntn_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="nadm-field is-wide"><span class="nadm-fl">I tipi possibili</span>
					<span class="nadm-help"><b>Console</b>: un selettore con i marchi (le sottocategorie) e una scheda per ogni piattaforma, come la Hub Console.</span>
					<span class="nadm-help"><b>Studi</b>: l'indice A–Z degli sviluppatori, ricavato dai giochi del catalogo IGDB, come la Hub Sviluppatori italiani.</span>
					<span class="nadm-help"><b>Statistiche</b>: il nome della sezione e quattro numeri: articoli, autore più prolifico dell'anno, ultimo articolo e videogiochi. Sottocategorie comprese, senza elenco degli articoli.</span>
				</div>
			</div>
			<div class="nadm-actions"><button type="submit" class="nadm-btn is-solid">Accendi la Hub</button><span class="nadm-help">Poi la trovi qui sopra, tra le Hub accese, per completarla.</span></div>
		</form>
	</div>

	<?php if ( ! empty( $ntn_hubs ) ) : ?>
		<?php
		$ntn_issues = array();
		foreach ( $ntn_hubs as $ntn_hub ) {
			foreach ( ntn_core_category_hub_coverage( $ntn_hub ) as $ntn_issue ) {
				$ntn_issue['hub'] = ntn_core_category_hub_label( $ntn_hub );
				$ntn_issues[]     = $ntn_issue;
			}
		}
		?>
		<div class="nadm-box">
			<div class="nadm-box-h"><span>// Controllo copertura</span><span class="<?php echo $ntn_issues ? 'nadm-red' : ''; ?>"><?php echo esc_html( $ntn_issues ? sprintf( 1 === count( $ntn_issues ) ? '%d da sistemare' : '%d da sistemare', count( $ntn_issues ) ) : 'Tutto a posto' ); ?></span></div>
			<?php if ( empty( $ntn_issues ) ) : ?>
				<p class="nadm-empty">Nessun problema: ogni marchio ha le sue piattaforme e ogni gioco ha il suo sviluppatore.</p>
			<?php else : ?>
				<?php foreach ( $ntn_issues as $ntn_issue ) : ?>
					<div class="nadm-issue">
						<div><b><?php echo esc_html( $ntn_issue['title'] ); ?> <span class="nadm-m">· <?php echo esc_html( $ntn_issue['hub'] ); ?></span></b><span><?php echo esc_html( $ntn_issue['text'] ); ?></span></div>
						<?php if ( $ntn_issue['url'] ) : ?>
							<a class="nadm-btn is-sm" href="<?php echo esc_url( $ntn_issue['url'] ); ?>"><?php echo esc_html( $ntn_issue['action'] ); ?></a>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<form method="post" action="" class="nadm-actions">
		<?php wp_nonce_field( 'ntn_hub_flush', 'ntn_hub_flush_nonce' ); ?>
		<button type="submit" class="nadm-btn">Svuota la memoria delle Hub</button>
		<span class="nadm-help">Le Hub si aggiornano già da sole a ogni pubblicazione: serve solo se una modifica non compare.</span>
	</form>
</div>
