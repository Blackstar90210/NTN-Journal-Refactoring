<?php
/**
 * Elenco delle recensioni senza voto, con il voto proposto dal loro giudizio (v4.16.0).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- la pagina è solo una lettura.
$ntn_mp_paged = isset( $_GET['pgs'] ) ? max( 1, absint( $_GET['pgs'] ) ) : 1;
// phpcs:enable
$ntn_mp_query = ntn_core_reviews_without_rating_query( $ntn_mp_paged );
$ntn_mp_total = (int) $ntn_mp_query->found_posts;
$ntn_mp_all   = ntn_core_reviews_without_rating_query( 0 );
$ntn_mp_prop  = 0;
foreach ( (array) $ntn_mp_all->posts as $ntn_mp_pid ) {
	if ( ntn_core_post_rating_proposal( (int) $ntn_mp_pid ) ) {
		++$ntn_mp_prop;
	}
}
$ntn_mp_base = add_query_arg(
	array(
		'page' => 'ntn-journal-functions',
		'tab'  => 'reviews',
	),
	admin_url( 'admin.php' )
);
?>
<div class="nadm-panel">
	<div class="nadm-sec"><h2>// Recensioni senza voto</h2><span class="nadm-aside"><?php echo esc_html( number_format_i18n( $ntn_mp_total ) . ' senza voto · ' . number_format_i18n( $ntn_mp_prop ) . ' con un voto proposto' ); ?></span></div>
	<p class="nadm-note">Google mostra una recensione solo se ha un voto, e il voto compare anche nella pagina. Il voto proposto viene dal giudizio già scelto nell'articolo con la scala del sito: Must Play = 5, Consigliato = 4, Consigliato con riserva = 3, Bocciato = 2. Non sostituisce mai un voto già scritto. Puoi cambiarlo prima di confermare.</p>

	<?php if ( ! $ntn_mp_query->have_posts() ) : ?>
		<p class="nadm-note">Tutte le recensioni hanno un voto.</p>
	<?php else : ?>
		<form method="post" action="<?php echo esc_url( add_query_arg( 'pgs', $ntn_mp_paged, $ntn_mp_base ) ); ?>">
			<?php wp_nonce_field( 'ntn_rating_backfill', 'ntn_rating_backfill_nonce' ); ?>
			<div class="nadm-actions">
				<button type="submit" name="ntn_rating_backfill_mode" value="selected" class="nadm-btn">Conferma selezionati</button>
				<?php if ( $ntn_mp_prop > 0 ) : ?>
					<button type="submit" name="ntn_rating_backfill_mode" value="all" class="nadm-btn" onclick="return confirm('Applico il voto proposto a tutte le <?php echo esc_js( (string) $ntn_mp_prop ); ?> recensioni che hanno un giudizio. Procedo?');">Conferma tutte le <?php echo esc_html( (string) $ntn_mp_prop ); ?> proposte</button>
				<?php endif; ?>
			</div>
			<div class="nadm-scroll">
				<table class="nadm-table">
					<thead><tr><th>Conferma</th><th>Recensione</th><th>Giudizio trovato</th><th>Voto</th><th>Data</th></tr></thead>
					<tbody>
						<?php
						while ( $ntn_mp_query->have_posts() ) :
							$ntn_mp_query->the_post();
							$ntn_mp_id  = get_the_ID();
							$ntn_mp_pro = ntn_core_post_rating_proposal( $ntn_mp_id );
							$ntn_mp_val = $ntn_mp_pro ? (int) $ntn_mp_pro['rating'] : 0;
							?>
							<tr>
								<td><input type="checkbox" name="ntn_rating_pick[<?php echo esc_attr( (string) $ntn_mp_id ); ?>]" value="1" <?php checked( (bool) $ntn_mp_pro ); ?> aria-label="<?php echo esc_attr( 'Conferma il voto per ' . get_the_title() ); ?>"></td>
								<td class="is-t"><a href="<?php echo esc_url( (string) get_edit_post_link() ); ?>"><?php echo esc_html( get_the_title() ); ?></a> <a href="<?php echo esc_url( (string) get_permalink() ); ?>" target="_blank" rel="noopener noreferrer">↗</a></td>
								<td><?php echo $ntn_mp_pro ? esc_html( $ntn_mp_pro['label'] ) : '<span class="nadm-m">nessun giudizio</span>'; ?></td>
								<td>
									<label class="screen-reader-text" for="nadm-mp-<?php echo esc_attr( (string) $ntn_mp_id ); ?>">Voto</label>
									<select id="nadm-mp-<?php echo esc_attr( (string) $ntn_mp_id ); ?>" name="ntn_rating_value[<?php echo esc_attr( (string) $ntn_mp_id ); ?>]">
										<option value="">—</option>
										<?php for ( $ntn_mp_i = 5; $ntn_mp_i >= 1; $ntn_mp_i-- ) : ?>
											<option value="<?php echo esc_attr( (string) $ntn_mp_i ); ?>" <?php selected( $ntn_mp_val, $ntn_mp_i ); ?>><?php echo esc_html( $ntn_mp_i . ' · ' . ucfirst( strtolower( (string) ntn_get_rating_label( $ntn_mp_i ) ) ) ); ?></option>
										<?php endfor; ?>
									</select>
								</td>
								<td class="is-n"><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>
			</div>
		</form>
		<?php wp_reset_postdata(); ?>
		<?php if ( $ntn_mp_query->max_num_pages > 1 ) : ?>
			<nav class="nadm-pager" aria-label="Pagine delle recensioni senza voto">
				<?php for ( $ntn_mp_p = 1; $ntn_mp_p <= (int) $ntn_mp_query->max_num_pages; $ntn_mp_p++ ) : ?>
					<?php if ( $ntn_mp_p === $ntn_mp_paged ) : ?>
						<span class="is-cur" aria-current="page"><?php echo esc_html( (string) $ntn_mp_p ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( add_query_arg( 'pgs', $ntn_mp_p, $ntn_mp_base ) ); ?>"><?php echo esc_html( (string) $ntn_mp_p ); ?></a>
					<?php endif; ?>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</div>
