<?php
/**
 * Template part for displaying a game card in the catalog.
 *
 * Fase 7 del restyling (v3.9.0): copertina intera in cornice e titolo con anno sotto
 * l'immagine, al posto della banda scura sopra la foto. La logica delle copertine è invariata.
 *
 * @package NTN_Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();

// Ottieni info IGDB (se disponibile)
$igdb_info = function_exists( 'ntn_get_hub_igdb_info' ) ? ntn_get_hub_igdb_info( $post_id ) : array();

// 1. Priorità Cover
$cover = ! empty( $igdb_info['cover'] ) ? $igdb_info['cover'] : '';
if ( empty( $cover ) && function_exists( 'ntn_get_igdb_data' ) ) {
	$cover = ntn_get_igdb_data( $post_id, '_ntn_game_cover' );
}
if ( empty( $cover ) ) {
	$cover = get_post_meta( $post_id, '_ntn_game_cover', true );
}
if ( ! empty( $cover ) && function_exists( 'ntn_normalize_game_cover_url' ) ) {
	$cover = ntn_normalize_game_cover_url( $cover );
}

// 2. Fallback Thumbnail
if ( empty( $cover ) && has_post_thumbnail( $post_id ) ) {
	$cover = get_the_post_thumbnail_url( $post_id, 'medium_large' );
}

// Normalizzazione fallback
if ( ! empty( $cover ) && ! function_exists( 'ntn_normalize_game_cover_url' ) ) {
	if ( ! is_array( $cover ) && ( ! is_object( $cover ) || method_exists( $cover, '__toString' ) ) ) {
		$cover = trim( (string) $cover );
		if ( strpos( $cover, '//' ) === 0 ) {
			$cover = 'https:' . $cover;
		} elseif ( strpos( $cover, 'http://' ) === 0 && strpos( $cover, 'images.igdb.com' ) !== false ) {
			$cover = 'https://' . substr( $cover, 7 );
		} elseif ( strpos( $cover, 'http' ) !== 0 && preg_match( '/^[a-zA-Z0-9_-]+(\.(jpg|png|jpeg|webp))?$/i', $cover ) ) {
			$clean_id = preg_replace( '/\.(jpg|png|jpeg|webp)$/i', '', $cover );
			$cover    = 'https://images.igdb.com/igdb/image/upload/t_1080p/' . $clean_id . '.jpg';
		}
		if ( strpos( $cover, 'images.igdb.com' ) !== false ) {
			if ( preg_match( '/\/t_[a-z0-9_]+\//', $cover ) ) {
				$cover = preg_replace( '/\/t_[a-z0-9_]+\//', '/t_1080p/', $cover );
			} elseif ( strpos( $cover, '/igdb/image/upload/' ) !== false && strpos( $cover, '/igdb/image/upload/t_' ) === false ) {
				$cover = str_replace( '/igdb/image/upload/', '/igdb/image/upload/t_1080p/', $cover );
			}
		}
	} else {
		$cover = '';
	}
}

// Data di uscita
$release = ! empty( $igdb_info['release'] ) ? $igdb_info['release'] : ( function_exists( 'ntn_get_igdb_data' ) ? ntn_get_igdb_data( $post_id, '_ntn_game_release' ) : get_post_meta( $post_id, '_ntn_game_release', true ) );

// v3.19.1: data completa solo se IGDB conosce il giorno, altrimenti l'anno (helpers.php del tema padre)
$rel_year = '';
if ( ! empty( $release ) && function_exists( 'ntn_journal_game_card_date' ) ) {
	$rel_year = ntn_journal_game_card_date( $post_id, $release );
} elseif ( ! empty( $release ) ) {
	$rel_clean = is_scalar( $release ) ? trim( (string) $release ) : ( is_array( $release ) ? trim( (string) reset( $release ) ) : '' );
	if ( is_numeric( $rel_clean ) ) {
		if ( strlen( $rel_clean ) === 4 ) {
			$rel_year = $rel_clean;
		} elseif ( strlen( $rel_clean ) === 13 ) {
			$rel_year = date( 'Y', intval( $rel_clean / 1000 ) );
		} else {
			$rel_year = date( 'Y', intval( $rel_clean ) );
		}
	} elseif ( ! empty( $rel_clean ) ) {
		$rel_time = strtotime( $rel_clean );
		$rel_year = $rel_time ? date( 'Y', $rel_time ) : $rel_clean;
	}
}
?>
<article id="post-<?php echo esc_attr( $post_id ); ?>" <?php post_class( 'ntn-game-card group' ); ?>>
	<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="absolute inset-0 z-10">
		<span class="sr-only"><?php echo esc_html( get_the_title( $post_id ) ); ?></span>
	</a>

	<div class="ntn-game-cover">
	<?php if ( $cover ) : 
		$loading_attr = 'loading="lazy" decoding="async"';
		$class_attr   = 'w-full h-full object-cover';

		// Dalla 4.16.4 la posizione arriva da chi disegna l'elenco (argomento 'position', con 'first_page'):
		// prima si leggeva $wp_query, che per questo elenco non è quello giusto e dava "alta priorità" a tutte le schede.
		// Priority hinting solo per le prime 4 schede della prima pagina (sopra la piega) per migliorare l'LCP.
		$card_args = ( isset( $args ) && is_array( $args ) ) ? $args : array();
		if ( ! empty( $card_args['first_page'] ) && isset( $card_args['position'] ) && (int) $card_args['position'] < 4 ) {
			$loading_attr = 'fetchpriority="high" loading="eager"';
			$class_attr  .= ' skip-lazy no-lazy';
		}
	?>
		<img src="<?php echo esc_url( $cover ); ?>" alt="<?php the_title_attribute( array( 'post' => $post_id ) ); ?>" width="400" height="533" <?php echo $loading_attr; ?> class="<?php echo esc_attr( $class_attr ); ?>">
	<?php else : ?>
		<div class="ntn-game-nocover">NO COVER</div>
	<?php endif; ?>
	</div>

	<div class="ntn-game-info">
		<h2 class="ntn-game-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h2>
		<?php if ( ! empty( $rel_year ) ) : ?>
			<span class="ntn-game-year"><?php echo esc_html( $rel_year ); ?></span>
		<?php endif; ?>
	</div>
</article>
