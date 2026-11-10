<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Tag dell'articolo in fondo al testo (v3.16.0).
 *
 * Il gioco collegato (tag con i dati IGDB) è il primo, in rosso con il pallino;
 * gli altri sono etichette "#Nome". Riferimento: mockup, pagina Articolo.
 *
 * @package NTN_Journal
 */

$ntn_tags = get_the_tags();
if ( empty( $ntn_tags ) || is_wp_error( $ntn_tags ) ) {
	return;
}

$ntn_game_tag = null;
foreach ( $ntn_tags as $ntn_tag ) {
	if ( get_term_meta( $ntn_tag->term_id, '_ntn_igdb_game_id', true ) || get_term_meta( $ntn_tag->term_id, '_ntn_game_title', true ) ) {
		$ntn_game_tag = $ntn_tag;
		break;
	}
}
?>
<div class="ntn-tags" aria-label="<?php esc_attr_e( 'Tag dell\'articolo', 'ntn-journal' ); ?>">
	<?php if ( $ntn_game_tag ) : ?>
		<?php $ntn_link = get_tag_link( $ntn_game_tag->term_id ); ?>
		<a class="ntn-tag is-game" href="<?php echo esc_url( is_string( $ntn_link ) ? $ntn_link : '#' ); ?>" rel="tag"><span class="ntn-dot" aria-hidden="true"></span><?php echo esc_html( $ntn_game_tag->name ); ?></a>
	<?php endif; ?>
	<?php foreach ( $ntn_tags as $ntn_tag ) : ?>
		<?php
		if ( $ntn_game_tag && (int) $ntn_tag->term_id === (int) $ntn_game_tag->term_id ) {
			continue;
		}
		$ntn_link = get_tag_link( $ntn_tag->term_id );
		?>
		<a class="ntn-tag" href="<?php echo esc_url( is_string( $ntn_link ) ? $ntn_link : '#' ); ?>" rel="tag">#<?php echo esc_html( $ntn_tag->name ); ?></a>
	<?php endforeach; ?>
</div>
