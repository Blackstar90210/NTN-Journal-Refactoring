<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Recensioni (v3.18.0): ricerca, filtri per giudizio e pagine da 20, invece di caricare
 * fino a 500 recensioni insieme. Le righe si aprono anche da tastiera (admin-screen.js).
 * Riferimento visivo: mockup, pagina "Amministrazione", scheda Recensioni.
 */
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- ricerca, filtro e pagina sono solo letture.
$ntn_q      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$ntn_filter = isset( $_GET['giudizio'] ) ? sanitize_key( wp_unslash( $_GET['giudizio'] ) ) : '';
$ntn_paged  = isset( $_GET['pg'] ) ? max( 1, absint( $_GET['pg'] ) ) : 1;
// phpcs:enable

$ntn_filters = array(
	''            => array( 'Tutte', array() ),
	'must-play'   => array( 'Must Play', array( '5' ) ),
	'consigliato' => array( 'Consigliato', array( '4' ) ),
	'riserva'     => array( 'Con riserva', array( '3' ) ),
	'bocciato'    => array( 'Bocciato', array( '1', '2' ) ),
);
if ( ! isset( $ntn_filters[ $ntn_filter ] ) ) {
	$ntn_filter = '';
}

$ntn_meta = array( array( 'key' => 'rating', 'value' => '', 'compare' => '!=' ) );
if ( $ntn_filters[ $ntn_filter ][1] ) {
	$ntn_meta = array( array( 'key' => 'rating', 'value' => $ntn_filters[ $ntn_filter ][1], 'compare' => 'IN' ) );
}
$ntn_reviews = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
		'posts_per_page' => 20,
		'paged'          => $ntn_paged,
		's'              => $ntn_q,
		'meta_query'     => $ntn_meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- elenco in amministrazione, 20 per pagina.
	)
);

$ntn_base = add_query_arg( array( 'page' => 'ntn-journal-functions', 'tab' => 'reviews' ), admin_url( 'admin.php' ) );
$ntn_url  = static function ( $args ) use ( $ntn_base, $ntn_q, $ntn_filter ) {
	return add_query_arg( array_filter( array_merge( array( 'q' => $ntn_q, 'giudizio' => $ntn_filter ), $args ) ), $ntn_base );
};
?>
<?php // v4.16.0: voti alle vecchie recensioni
if ( function_exists( 'ntn_core_reviews_without_rating_query' ) ) {
	require __DIR__ . '/reviews-missing-rating.php';
}
?>
<div class="nadm-panel">
	<div class="nadm-sec"><h2>// Recensioni</h2><span class="nadm-aside"><?php echo esc_html( number_format_i18n( (int) $ntn_reviews->found_posts ) . ' trovate · 20 per pagina' ); ?></span></div>

	<div class="nadm-tools-row">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="nadm-actions" role="search">
			<input type="hidden" name="page" value="ntn-journal-functions">
			<input type="hidden" name="tab" value="reviews">
			<?php if ( $ntn_filter ) : ?><input type="hidden" name="giudizio" value="<?php echo esc_attr( $ntn_filter ); ?>"><?php endif; ?>
			<label class="screen-reader-text" for="nadm-rev-q">Cerca una recensione</label>
			<input type="search" id="nadm-rev-q" name="q" value="<?php echo esc_attr( $ntn_q ); ?>" placeholder="Cerca una recensione…">
			<button type="submit" class="nadm-btn is-sm">Cerca</button>
		</form>
		<nav class="nadm-filter" aria-label="Filtra per giudizio">
			<?php foreach ( $ntn_filters as $ntn_key => $ntn_item ) : ?>
				<a href="<?php echo esc_url( $ntn_url( array( 'giudizio' => $ntn_key, 'pg' => '' ) ) ); ?>"<?php echo $ntn_key === $ntn_filter ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $ntn_item[0] ); ?></a>
			<?php endforeach; ?>
		</nav>
	</div>

	<?php if ( ! $ntn_reviews->have_posts() ) : ?>
		<p class="nadm-note">Nessuna recensione trovata.</p>
	<?php else : ?>
		<div class="nadm-scroll">
			<table class="nadm-table">
				<thead><tr><th>Voto</th><th>Gioco</th><th>Giudizio</th><th>Data</th><th></th></tr></thead>
				<tbody>
					<?php
					while ( $ntn_reviews->have_posts() ) :
						$ntn_reviews->the_post();
						$ntn_id      = get_the_ID();
						$ntn_rating  = max( 0, min( 5, (int) get_post_meta( $ntn_id, 'rating', true ) ) );
						$ntn_item    = (string) get_post_meta( $ntn_id, 'reviewed_item', true );
						$ntn_pros    = (string) get_post_meta( $ntn_id, 'pros', true );
						$ntn_cons    = (string) get_post_meta( $ntn_id, 'cons', true );
						$ntn_verdict = (string) get_post_meta( $ntn_id, 'verdict', true );
						$ntn_label   = function_exists( 'get_rating_label' ) ? get_rating_label( $ntn_rating ) : '';
						$ntn_row     = 'nadm-rev-' . $ntn_id;
						$ntn_status  = get_post_status_object( (string) get_post_status() );
						?>
						<tr>
							<td>
								<span class="nadm-leds" role="img" aria-label="<?php echo esc_attr( sprintf( 'Voto %d su 5', $ntn_rating ) ); ?>">
									<?php for ( $ntn_i = 1; $ntn_i <= 5; $ntn_i++ ) : ?>
										<i class="<?php echo esc_attr( trim( ( $ntn_i <= $ntn_rating ? 'is-on' : '' ) . ( $ntn_i === $ntn_rating ? ' is-last' : '' ) ) ); ?>"></i>
									<?php endfor; ?>
								</span>
							</td>
							<td class="is-t">
								<?php echo esc_html( '' !== $ntn_item ? $ntn_item : get_the_title() ); ?>
								<?php if ( 'publish' !== get_post_status() && $ntn_status ) : ?>
									<span class="nadm-m">· <?php echo esc_html( $ntn_status->label ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( ucfirst( strtolower( $ntn_label ) ) ); ?></td>
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
							<td colspan="5">
								<div class="nadm-pc">
									<div><h4>// Pro</h4><ul class="is-plus"><?php foreach ( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $ntn_pros ) ) ) as $ntn_line ) : ?><li><?php echo esc_html( $ntn_line ); ?></li><?php endforeach; ?></ul></div>
									<div><h4>// Contro</h4><ul class="is-minus"><?php foreach ( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $ntn_cons ) ) ) as $ntn_line ) : ?><li><?php echo esc_html( $ntn_line ); ?></li><?php endforeach; ?></ul></div>
								</div>
								<?php if ( '' !== trim( $ntn_verdict ) ) : ?>
									<p class="nadm-verdict"><?php echo esc_html( wp_trim_words( $ntn_verdict, 60, '…' ) ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
		<?php wp_reset_postdata(); ?>

		<?php if ( $ntn_reviews->max_num_pages > 1 ) : ?>
			<nav class="nadm-pager" aria-label="Pagine delle recensioni">
				<?php for ( $ntn_p = 1; $ntn_p <= (int) $ntn_reviews->max_num_pages; $ntn_p++ ) : ?>
					<?php if ( $ntn_p === $ntn_paged ) : ?>
						<span class="is-cur" aria-current="page"><?php echo esc_html( (string) $ntn_p ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( $ntn_url( array( 'pg' => $ntn_p ) ) ); ?>"><?php echo esc_html( (string) $ntn_p ); ?></a>
					<?php endif; ?>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</div>
