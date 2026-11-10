<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Elenco degli articoli delle Hub di categoria (v4.5.0): righe, paginazione e stato vuoto.
 * Legge la query principale della pagina, quindi la paginazione è quella della categoria.
 *
 * Argomenti:
 * - row_tag: funzione che riceve l'ID dell'articolo e restituisce l'etichetta rossa della riga ('' = nessuna);
 * - empty:   testo da mostrare se non ci sono articoli.
 *
 * @package NTN_Journal
 */

$feed_row_tag = isset( $args['row_tag'] ) && is_callable( $args['row_tag'] ) ? $args['row_tag'] : null;
$feed_empty   = isset( $args['empty'] ) ? (string) $args['empty'] : '';
?>
			<?php if ( have_posts() ) : ?>
				<div class="ntn-chub-feed">
					<?php
					while ( have_posts() ) :
						the_post();
						$row_tag  = $feed_row_tag ? (string) $feed_row_tag( get_the_ID() ) : '';
						$row_mins = (int) get_post_meta( get_the_ID(), '_ntn_reading_time', true );
						?>
						<a class="ntn-chub-row" href="<?php the_permalink(); ?>">
							<span class="ntn-chub-row-date"><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></span>
							<span class="min-w-0">
								<?php if ( '' !== $row_tag ) : ?>
									<span class="ntn-chub-row-tag">// <?php echo esc_html( $row_tag ); ?></span>
								<?php endif; ?>
								<span class="ntn-chub-row-title"><?php the_title(); ?></span>
							</span>
							<span class="ntn-chub-row-min"><?php echo esc_html( max( 1, $row_mins ) . ' min' ); ?></span>
						</a>
					<?php endwhile; ?>
				</div>

				<div class="ntn-pager-wrap">
					<?php
					the_posts_pagination(
						array(
							'mid_size'  => 2,
							'prev_text' => '&larr; Precedente',
							'next_text' => 'Successivo &rarr;',
						)
					);
					?>
				</div>
			<?php else : ?>
				<div class="ntn-empty">
					<span>Nessun articolo</span>
					<p><?php echo esc_html( $feed_empty ); ?></p>
				</div>
			<?php endif; ?>
