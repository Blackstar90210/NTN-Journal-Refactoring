<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Firma dell'autore nella testata dell'articolo (v3.6.1, variante A scelta dal CEO).
 *
 * Sta dentro la fascia di apertura, subito sotto la riga dei dati: l'autore è la
 * prima cosa che si vede, da computer e da telefono. Nella 3.6.0 era una scheda
 * fissa nella colonna laterale (variante B), poi scartata perché affollava la colonna
 * e scorrendo si sovrapponeva alla scheda del gioco.
 *
 * @package NTN_Journal
 */

$ntn_ap_id   = (int) get_the_author_meta( 'ID' );
$ntn_ap_name = (string) get_the_author();
$ntn_ap_bio  = (string) get_the_author_meta( 'description', $ntn_ap_id );
$ntn_ap_url  = get_author_posts_url( $ntn_ap_id );
$ntn_ap_url  = ( ! empty( $ntn_ap_url ) && ! is_wp_error( $ntn_ap_url ) && is_string( $ntn_ap_url ) ) ? $ntn_ap_url : '#';

// Iniziali per l'avatar a matrice di punti quando manca la foto
$ntn_ap_words    = preg_split( '/\s+/', trim( $ntn_ap_name ) );
$ntn_ap_initials = '';
foreach ( array_slice( (array) $ntn_ap_words, 0, 2 ) as $ntn_ap_word ) {
	$ntn_ap_initials .= function_exists( 'mb_substr' ) ? mb_substr( $ntn_ap_word, 0, 1 ) : substr( $ntn_ap_word, 0, 1 );
}

// Numeri dell'autore, in cache per un'ora
$ntn_ap_stats = get_transient( 'ntn_author_stats_' . $ntn_ap_id );
if ( ! is_array( $ntn_ap_stats ) ) {
	$ntn_ap_reviews    = 0;
	$ntn_ap_review_cat = get_category_by_slug( 'recensioni' );
	if ( $ntn_ap_id && $ntn_ap_review_cat ) {
		$ntn_ap_review_query = new WP_Query(
			array(
				'author'         => $ntn_ap_id,
				'cat'            => (int) $ntn_ap_review_cat->term_id,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		$ntn_ap_reviews = (int) $ntn_ap_review_query->found_posts;
	}
	$ntn_ap_registered = (string) get_the_author_meta( 'user_registered', $ntn_ap_id );
	$ntn_ap_stats      = array(
		'posts'   => $ntn_ap_id ? (int) count_user_posts( $ntn_ap_id, 'post', true ) : 0,
		'reviews' => $ntn_ap_reviews,
		'since'   => $ntn_ap_registered ? (int) substr( $ntn_ap_registered, 0, 4 ) : 0,
	);
	set_transient( 'ntn_author_stats_' . $ntn_ap_id, $ntn_ap_stats, HOUR_IN_SECONDS );
}

$ntn_ap_socials = array(
	'twitter'   => 'X',
	'instagram' => 'IG',
	'linkedin'  => 'IN',
	'github'    => 'GIT',
	'youtube'   => 'YT',
	'facebook'  => 'FB',
	'user_url'  => 'WEB',
);
$ntn_ap_avatar = get_avatar( $ntn_ap_id, 112, '', $ntn_ap_name, array( 'class' => 'w-full h-full object-cover' ) );
$ntn_ap_text   = '' !== trim( $ntn_ap_bio ) ? wp_trim_words( $ntn_ap_bio, 28, '…' ) : sprintf( 'Redattore per %s.', get_bloginfo( 'name' ) );
?>
<section class="ntn-byline" aria-label="<?php esc_attr_e( 'Autore dell\'articolo', 'ntn-journal' ); ?>">
	<a class="ntn-author-avatar" href="<?php echo esc_url( $ntn_ap_url ); ?>" rel="author" tabindex="-1" aria-hidden="true">
		<?php
		// v3.17.1: iniziali a punti sotto, foto sopra solo se esiste (niente sagoma grigia)
		if ( function_exists( 'ntn_journal_author_avatar' ) ) {
			echo ntn_journal_author_avatar( $ntn_ap_id, $ntn_ap_name, 112 ); // phpcs:ignore WordPress.Security.EscapeOutput -- markup già sanificato dalla funzione.
		} elseif ( $ntn_ap_avatar ) {
			echo $ntn_ap_avatar; // phpcs:ignore WordPress.Security.EscapeOutput -- markup generato da WordPress.
		} else {
			echo '<span>' . esc_html( strtoupper( $ntn_ap_initials ) ) . '</span>';
		}
		?>
	</a>

	<div class="ntn-byline-main">
		<div class="ntn-byline-who">
			<a class="ntn-author-name" href="<?php echo esc_url( $ntn_ap_url ); ?>" rel="author"><?php echo esc_html( $ntn_ap_name ); ?></a>
			<span class="ntn-author-role">
				<?php echo esc_html( $ntn_ap_stats['since'] ? sprintf( 'Firma di %s · dal %d', get_bloginfo( 'name' ), $ntn_ap_stats['since'] ) : sprintf( 'Firma di %s', get_bloginfo( 'name' ) ) ); ?>
			</span>
		</div>
		<p class="ntn-author-bio"><?php echo esc_html( $ntn_ap_text ); ?></p>
	</div>

	<div class="ntn-byline-links">
		<span><b><?php echo esc_html( number_format_i18n( $ntn_ap_stats['posts'] ) ); ?></b> articoli</span>
		<?php if ( $ntn_ap_stats['reviews'] ) : ?>
			<span><b><?php echo esc_html( number_format_i18n( $ntn_ap_stats['reviews'] ) ); ?></b> recensioni</span>
		<?php endif; ?>
		<?php
		foreach ( $ntn_ap_socials as $ntn_ap_key => $ntn_ap_label ) {
			$ntn_ap_link = get_the_author_meta( $ntn_ap_key, $ntn_ap_id );
			if ( ! empty( $ntn_ap_link ) && is_string( $ntn_ap_link ) ) {
				echo '<a class="ntn-byline-social" href="' . esc_url( $ntn_ap_link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $ntn_ap_label ) . '</a>';
			}
		}
		?>
		<a class="ntn-byline-more" href="<?php echo esc_url( $ntn_ap_url ); ?>">Tutti i suoi articoli →</a>
	</div>
</section>
