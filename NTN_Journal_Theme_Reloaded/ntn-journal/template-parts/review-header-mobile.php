<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Template part per la Scheda Tecnica Rapida su Mobile (IGDB)
 *
 * Fase 5 del restyling (v3.7.0): copertina, nome del gioco, piattaforme e uscita,
 * più il giudizio con i cinque LED quando l'articolo è una recensione.
 */

$post_id = get_the_ID();
$rating = get_post_rating( $post_id );

$game_title = ntn_get_igdb_data( $post_id, '_ntn_game_title' );
$cover      = ntn_get_igdb_data( $post_id, '_ntn_game_cover' );
$platforms  = ntn_get_igdb_data( $post_id, '_ntn_game_platforms' );
$release    = ntn_get_igdb_data( $post_id, '_ntn_game_release' );
$hub_url    = ntn_get_game_hub_url( $post_id );

if ( empty( $game_title ) && empty( $rating ) ) {
	return;
}

$display_title = $game_title ? $game_title : get_the_title();

// Data di uscita: solo le parti note (v3.21.0)
$release_text = $release ? ntn_journal_release_label( $release, ntn_get_igdb_data( $post_id, '_ntn_game_release_precision' ) ) : '';

$final_rating = ! empty( $rating ) ? max( 1, min( 5, intval( $rating ) ) ) : 0;
$rating_label = ( $final_rating && function_exists( 'get_rating_label' ) ) ? get_rating_label( $final_rating ) : '';
?>

<div class="ntn-rmob">

	<?php if ( $cover ) : ?>
		<div class="ntn-rmob-cover">
			<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $game_title ); ?>" class="w-full h-full object-cover" width="600" height="800" />
		</div>
	<?php endif; ?>

	<div class="ntn-rmob-in">
		<span class="ntn-rmob-lbl">// Scheda gioco</span>
		<h4 class="ntn-rmob-name">
			<?php if ( $hub_url ) : ?>
				<a href="<?php echo esc_url( $hub_url ); ?>"><?php echo esc_html( $display_title ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $display_title ); ?>
			<?php endif; ?>
		</h4>

		<?php if ( $platforms || $release_text ) : ?>
			<div class="ntn-rmob-row">
				<?php if ( $platforms ) : ?>
					<span><?php echo esc_html( is_array( $platforms ) ? implode( ', ', array_filter( array_map( 'trim', $platforms ) ) ) : (string) $platforms ); ?></span>
				<?php endif; ?>
				<?php if ( $release_text ) : ?>
					<span><?php echo esc_html( $release_text ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $final_rating ) : ?>
			<div class="ntn-rmob-row">
				<b><?php echo esc_html( $rating_label ); ?></b>
				<span class="ntn-leds" role="img" aria-label="<?php echo esc_attr( sprintf( 'Voto %d su 5', $final_rating ) ); ?>">
					<?php
					for ( $i = 1; $i <= 5; $i++ ) {
						$led_class = 'ntn-led';
						if ( $i <= $final_rating ) {
							$led_class .= ' is-on';
						}
						if ( $i === $final_rating ) {
							$led_class .= ' is-last';
						}
						echo '<i class="' . esc_attr( $led_class ) . '"></i>';
					}
					?>
				</span>
			</div>
		<?php endif; ?>
	</div>

</div>
