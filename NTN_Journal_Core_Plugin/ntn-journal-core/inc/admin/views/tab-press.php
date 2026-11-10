<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Comunicati (v3.18.0): impostazioni e archivio con ricerca e pagine da 20, invece di
 * caricare fino a 500 comunicati insieme. Le righe si aprono anche da tastiera (admin-screen.js).
 */
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- ricerca e pagina sono solo letture.
$ntn_q     = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$ntn_paged = isset( $_GET['pg'] ) ? max( 1, absint( $_GET['pg'] ) ) : 1;
// phpcs:enable

$ntn_press = new WP_Query(
	array(
		'post_type'      => 'comunicato_stampa',
		'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
		'posts_per_page' => 20,
		'paged'          => $ntn_paged,
		's'              => $ntn_q,
	)
);
$ntn_base  = add_query_arg(
	array(
		'page' => 'ntn-journal-functions',
		'tab'  => 'press',
	),
	admin_url( 'admin.php' )
);
?>
<div class="nadm-panel">

	<div class="nadm-box">
		<div class="nadm-box-h"><span>// Impostazioni dei comunicati</span></div>
		<form method="post" action="" class="nadm-box-b">
			<?php wp_nonce_field( 'ntn_save_press_settings', 'ntn_press_settings_nonce' ); ?>
			<div class="nadm-field"><label for="ntn_press_disclaimer">Nota in fondo a ogni comunicato</label>
				<textarea id="ntn_press_disclaimer" name="ntn_press_disclaimer" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'ntn_press_disclaimer', 'Nota: Questo è un comunicato stampa ufficiale. Il testo non è stato redatto dalla redazione di NTN Journal.' ) ); ?></textarea>
				<span class="nadm-help">Compare in fondo a tutti i comunicati stampa. Accetta l'HTML di base (link, grassetto).</span>
			</div>
			<div class="nadm-actions"><button type="submit" class="nadm-btn is-solid">Salva le impostazioni</button></div>
		</form>
	</div>

	<div class="nadm-sec"><h2>// Archivio dei comunicati</h2><span class="nadm-aside"><?php echo esc_html( number_format_i18n( (int) $ntn_press->found_posts ) . ' trovati · 20 per pagina' ); ?></span></div>

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="nadm-actions" role="search">
		<input type="hidden" name="page" value="ntn-journal-functions">
		<input type="hidden" name="tab" value="press">
		<label class="screen-reader-text" for="nadm-press-q">Cerca un comunicato</label>
		<input type="search" id="nadm-press-q" name="q" value="<?php echo esc_attr( $ntn_q ); ?>" placeholder="Cerca un comunicato…">
		<button type="submit" class="nadm-btn is-sm">Cerca</button>
	</form>

	<?php if ( ! $ntn_press->have_posts() ) : ?>
		<p class="nadm-note">Nessun comunicato trovato.</p>
	<?php else : ?>
		<div class="nadm-scroll">
			<table class="nadm-table">
				<thead><tr><th>Comunicato</th><th>Da</th><th>Data</th><th></th></tr></thead>
				<tbody>
					<?php
					while ( $ntn_press->have_posts() ) :
						$ntn_press->the_post();
						$ntn_id       = get_the_ID();
						$ntn_entities = array_filter(
							array(
								(string) get_post_meta( $ntn_id, 'ntn_press_publisher', true ),
								(string) get_post_meta( $ntn_id, 'ntn_press_developer', true ),
								(string) get_post_meta( $ntn_id, 'ntn_press_agency', true ),
							)
						);
						$ntn_source   = (string) get_post_meta( $ntn_id, 'ntn_press_source_link', true );
						$ntn_assets   = (string) get_post_meta( $ntn_id, 'ntn_press_assets', true );
						$ntn_embargo  = (string) get_post_meta( $ntn_id, 'ntn_press_embargo', true );
						$ntn_noindex  = '1' === (string) get_post_meta( $ntn_id, 'ntn_press_noindex', true );
						$ntn_row      = 'nadm-press-' . $ntn_id;
						$ntn_status   = get_post_status_object( (string) get_post_status() );
						?>
						<tr>
							<td class="is-t">
								<?php the_title(); ?>
								<?php if ( 'publish' !== get_post_status() && $ntn_status ) : ?>
									<span class="nadm-m">· <?php echo esc_html( $ntn_status->label ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $ntn_entities ? implode( ' / ', $ntn_entities ) : '—' ); ?></td>
							<td class="is-n"><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></td>
							<td>
								<div class="nadm-actions">
									<button type="button" class="nadm-btn is-sm" data-nadm-toggle="<?php echo esc_attr( $ntn_row ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $ntn_row ); ?>">Apri</button>
									<a class="nadm-btn is-sm" href="<?php echo esc_url( (string) get_edit_post_link() ); ?>">Modifica</a>
									<a class="nadm-btn is-sm" href="<?php echo esc_url( (string) get_permalink() ); ?>" target="_blank" rel="noopener noreferrer">Vedi ↗</a>
								</div>
							</td>
						</tr>
						<tr id="<?php echo esc_attr( $ntn_row ); ?>" hidden>
							<td colspan="4">
								<div class="nadm-pc">
									<div><h4>// Fonte ufficiale</h4><?php echo $ntn_source ? '<a href="' . esc_url( $ntn_source ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $ntn_source ) . '</a>' : '—'; ?></div>
									<div><h4>// Materiale stampa</h4><?php echo $ntn_assets ? '<a href="' . esc_url( $ntn_assets ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $ntn_assets ) . '</a>' : '—'; ?></div>
								</div>
								<?php if ( '' !== $ntn_embargo ) : ?>
									<p class="nadm-verdict"><b>Embargo:</b> <?php echo esc_html( $ntn_embargo ); ?></p>
								<?php endif; ?>
								<?php if ( $ntn_noindex ) : ?>
									<p class="nadm-verdict nadm-red"><b>Non indicizzato:</b> questo comunicato non compare su Google.</p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
		<?php wp_reset_postdata(); ?>

		<?php if ( $ntn_press->max_num_pages > 1 ) : ?>
			<nav class="nadm-pager" aria-label="Pagine dei comunicati">
				<?php for ( $ntn_p = 1; $ntn_p <= (int) $ntn_press->max_num_pages; $ntn_p++ ) : ?>
					<?php if ( $ntn_p === $ntn_paged ) : ?>
						<span class="is-cur" aria-current="page"><?php echo esc_html( (string) $ntn_p ); ?></span>
					<?php else : ?>
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array_filter(
									array(
										'q'  => $ntn_q,
										'pg' => $ntn_p,
									)
								),
								$ntn_base
							)
						);
						?>
									"><?php echo esc_html( (string) $ntn_p ); ?></a>
					<?php endif; ?>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</div>
