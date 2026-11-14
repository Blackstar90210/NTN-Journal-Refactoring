<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Tag dell'articolo in fondo al testo (v3.16.0, multi-gioco v4.34.4).
 *
 * I giochi collegati (tag con i dati IGDB / Hub Page) sono i primi, in rosso con il pallino;
 * gli altri sono etichette "#Nome". Riferimento: mockup, pagina Articolo.
 *
 * @package NTN_Journal
 */

$ntn_tags = get_the_tags();
if ( empty( $ntn_tags ) || is_wp_error( $ntn_tags ) ) {
	return;
}

$ntn_game_tags    = array();
$ntn_game_tag_ids = array();
foreach ( $ntn_tags as $ntn_tag ) {
	$ntn_resolved = function_exists( 'ntn_resolve_game_term' ) ? ntn_resolve_game_term( $ntn_tag ) : null;
	if ( is_object( $ntn_resolved ) && ! empty( $ntn_resolved->term_id ) ) {
		$ntn_game_tag_ids[] = (int) $ntn_tag->term_id;
		$ntn_resolved_id    = (int) $ntn_resolved->term_id;
		if ( ! isset( $ntn_game_tags[ $ntn_resolved_id ] ) ) {
			$ntn_game_tags[ $ntn_resolved_id ] = $ntn_resolved;
		}
		continue;
	}
	if ( get_term_meta( $ntn_tag->term_id, '_ntn_igdb_game_id', true ) || get_term_meta( $ntn_tag->term_id, '_ntn_game_title', true ) ) {
		$ntn_tag_id                   = (int) $ntn_tag->term_id;
		$ntn_game_tag_ids[]           = $ntn_tag_id;
		$ntn_game_tags[ $ntn_tag_id ] = $ntn_tag;
	}
}
?>
<div class="ntn-tags" aria-label="<?php esc_attr_e( 'Tag dell\'articolo', 'ntn-journal' ); ?>">
	<?php foreach ( $ntn_game_tags as $ntn_game_tag ) : ?>
		<?php $ntn_link = get_tag_link( $ntn_game_tag->term_id ); ?>
		<a class="ntn-tag is-game" href="<?php echo esc_url( is_string( $ntn_link ) ? $ntn_link : '#' ); ?>" rel="tag"><span class="ntn-dot" aria-hidden="true"></span><?php echo esc_html( $ntn_game_tag->name ); ?></a>
	<?php endforeach; ?>
	<?php foreach ( $ntn_tags as $ntn_tag ) : ?>
		<?php
		if ( in_array( (int) $ntn_tag->term_id, $ntn_game_tag_ids, true ) ) {
			continue;
		}
		$ntn_link = get_tag_link( $ntn_tag->term_id );
		?>
		<a class="ntn-tag" href="<?php echo esc_url( is_string( $ntn_link ) ? $ntn_link : '#' ); ?>" rel="tag">#<?php echo esc_html( $ntn_tag->name ); ?></a>
	<?php endforeach; ?>
</div>
