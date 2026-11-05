<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Template part per la Scheda Tecnica Rapida su Mobile (IGDB)
 *
 * Fase 5 del restyling (v3.7.0): copertina, nome del gioco, piattaforme e uscita,
 * più il giudizio con i cinque LED quando l'articolo è una recensione.
 *
 * Con due o più giochi c'è un pannello per gioco in ordine alfabetico, con le linguette per passare
 * dall'uno all'altro. Con un gioco solo resta la scheda classica con sotto la riga della recensione.
 * Senza giochi non cambia nulla.
 */

$post_id = get_the_ID();
$rating = get_post_rating( $post_id );

$games      = ntn_get_post_games( $post_id );
$game_count = count( $games );

if ( 1 === $game_count ) {
	// Un gioco solo: i dati scritti sull'articolo restano prioritari, come sempre
	$single_game = ntn_apply_post_meta_to_game( $games[0], $post_id );
} elseif ( 0 === $game_count ) {
	// Nessun gioco: vecchi articoli e recensioni con il solo voto
	$game_title = ntn_get_igdb_data( $post_id, '_ntn_game_title' );

	if ( empty( $game_title ) && empty( $rating ) ) {
		return;
	}

	$single_game = array(
		'title'             => $game_title,
		'cover'             => ntn_get_igdb_data( $post_id, '_ntn_game_cover' ),
		'platforms'         => ntn_get_igdb_data( $post_id, '_ntn_game_platforms' ),
		'release'           => ntn_get_igdb_data( $post_id, '_ntn_game_release' ),
		'release_precision' => ntn_get_igdb_data( $post_id, '_ntn_game_release_precision' ),
		'hub_url'           => ntn_get_game_hub_url( $post_id ),
	);
}

$final_rating = ! empty( $rating ) ? max( 1, min( 5, intval( $rating ) ) ) : 0;
$rating_label = ( $final_rating && function_exists( 'get_rating_label' ) ) ? get_rating_label( $final_rating ) : '';

// Copertina, nome, piattaforme, uscita e, se richiesto, il giudizio dell'articolo
$render_body = static function ( $game, $label, $show_verdict, $lazy = false ) use ( $final_rating, $rating_label ) {
	$display_title = $game['title'] ? $game['title'] : get_the_title();
	$platforms     = $game['platforms'];
	$release       = $game['release'];

	// Data di uscita: solo le parti note (v3.21.0)
	$release_text = $release ? ntn_journal_release_label( $release, $game['release_precision'] ) : '';

	if ( $game['cover'] ) :
		?>
		<div class="ntn-rmob-cover">
			<img src="<?php echo esc_url( $game['cover'] ); ?>" alt="<?php echo esc_attr( $game['title'] ); ?>" class="w-full h-full object-cover" width="600" height="800"<?php echo $lazy ? ' loading="lazy"' : ''; ?> />
		</div>
		<?php
	endif;
	?>

	<div class="ntn-rmob-in">
		<span class="ntn-rmob-lbl"><?php echo esc_html( $label ); ?></span>
		<h4 class="ntn-rmob-name">
			<?php if ( $game['hub_url'] ) : ?>
				<a href="<?php echo esc_url( $game['hub_url'] ); ?>"><?php echo esc_html( $display_title ); ?></a>
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

		<?php if ( $show_verdict && $final_rating ) : ?>
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
	<?php
};

if ( 0 === $game_count ) :
	?>

<div class="ntn-rmob">
	<?php $render_body( $single_game, '// Scheda gioco', true ); ?>
</div>

	<?php
elseif ( 1 === $game_count ) :
	?>

<div class="ntn-rmob-wrap">
	<div class="ntn-rmob">
		<?php $render_body( $single_game, '// Scheda gioco', true ); ?>
	</div>
	<?php
	// Se l'articolo ha un voto è già lui la recensione del gioco: la riga servirebbe solo a smentirlo
	if ( empty( $rating ) && empty( $single_game['is_current_review'] ) ) {
		ntn_journal_game_review_row( $single_game, 'ntn-rmob-rev' );
	}
	?>
</div>

	<?php
else :
	?>

<div class="ntn-rmob-wrap" data-ntn-rmob>

	<?php foreach ( $games as $panel_index => $game ) : ?>
		<div class="ntn-rmob" data-i="<?php echo esc_attr( (string) $panel_index ); ?>" id="<?php echo esc_attr( sprintf( 'ntn-rmob-%d-%d', $post_id, $panel_index ) ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( sprintf( 'ntn-rmob-tab-%d-%d', $post_id, $panel_index ) ); ?>"<?php echo $panel_index > 0 ? ' hidden' : ''; ?>>
			<?php $render_body( $game, sprintf( '// Scheda gioco · %02d / %02d', $panel_index + 1, $game_count ), ! empty( $game['is_current_review'] ), $panel_index > 0 ); ?>
		</div>
	<?php endforeach; ?>

	<div class="ntn-gnames" role="tablist" aria-label="Giochi dell'articolo">
		<?php foreach ( $games as $tab_index => $game ) : ?>
			<button type="button" role="tab" class="ntn-gname" id="<?php echo esc_attr( sprintf( 'ntn-rmob-tab-%d-%d', $post_id, $tab_index ) ); ?>" aria-selected="<?php echo 0 === $tab_index ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( sprintf( 'ntn-rmob-%d-%d', $post_id, $tab_index ) ); ?>" tabindex="<?php echo 0 === $tab_index ? '0' : '-1'; ?>">
				<?php echo esc_html( $game['title'] ); ?><span class="<?php echo $game['review'] ? 'ok' : 'no'; ?>" role="img" aria-label="<?php echo $game['review'] ? 'Recensito' : 'Non recensito'; ?>"></span>
			</button>
		<?php endforeach; ?>
	</div>

	<?php
	// Le righe stanno dopo le linguette e si accendono insieme al loro pannello. Il gioco recensito ha già il giudizio.
	foreach ( $games as $row_index => $game ) {
		if ( empty( $game['is_current_review'] ) ) {
			ntn_journal_game_review_row( $game, 'ntn-rmob-rev', $row_index );
		}
	}
	?>

</div>

	<?php
endif;
