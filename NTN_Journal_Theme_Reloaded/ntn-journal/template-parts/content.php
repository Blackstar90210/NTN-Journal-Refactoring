<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Template part for displaying posts
 *
 * Fase 6 del restyling (v3.8.0): scheda a cella con linee condivise, come nel mockup.
 * Va dentro un contenitore .ntn-cells (archivi, Hub, ricerca, blog). Con
 * $args['is_featured'] diventa la scheda grande del contenuto in evidenza della Hub.
 * $args['hide_author'] toglie "by Autore" dalla riga dei dati (pagina autore, v3.13.0).
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package NTN_Journal
 */

$post_img = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
if ( ! $post_img ) {
	$post_img = ntn_journal_default_cover_url();
}

// Calculate reading time
$reading_time = (int) get_post_meta( get_the_ID(), '_ntn_reading_time', true );
if ( ! $reading_time ) { $reading_time = 1; }
$reading_time_str = $reading_time . ' MIN';

// Rating
$rating = get_post_meta( get_the_ID(), 'rating', true );
$is_featured = isset( $args['is_featured'] ) ? $args['is_featured'] : false;
$hide_author = ! empty( $args['hide_author'] );
$primary_cat = ntn_get_primary_category( get_the_ID() );
?>

<a href="<?php the_permalink(); ?>" id="post-<?php the_ID(); ?>" <?php post_class( $is_featured ? 'ntn-cell ntn-card is-featured group' : 'ntn-cell ntn-card group' ); ?>>

	<!-- Foto: nessun filtro, nessuno zoom -->
	<div class="ntn-frame ntn-card-img">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php echo wp_get_attachment_image( get_post_thumbnail_id(), $is_featured ? 'large' : 'medium_large', false, array( 'class' => 'absolute inset-0 w-full h-full object-cover', 'loading' => 'lazy', 'alt' => ntn_get_image_alt( get_the_ID() ) ) ); ?>
		<?php else : ?>
			<img
				src="<?php echo esc_url( $post_img ); ?>"
				alt="<?php echo esc_attr( ntn_get_image_alt( get_the_ID() ) ); ?>"
				width="400"
				height="225"
				class="absolute inset-0 w-full h-full object-cover"
				loading="lazy"
				decoding="async"
			/>
		<?php endif; ?>
		<span class="ntn-card-badge"><?php echo $primary_cat ? esc_html( $primary_cat->name ) : 'POST'; ?></span>
		<?php if ( ! empty( $rating ) ) : ?>
			<span class="ntn-card-badge is-red">Voto: <?php echo esc_html( function_exists( 'get_rating_initials' ) ? get_rating_initials( $rating ) : $rating ); ?></span>
		<?php endif; ?>
	</div>

	<div class="ntn-meta">
		<?php if ( ! $hide_author ) : ?>
			<span>by <b><?php the_author(); ?></b></span>
		<?php endif; ?>
		<span><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></span>
		<span><?php echo esc_html( $reading_time_str ); ?></span>
	</div>

	<h3 class="ntn-card-title"><?php the_title(); ?></h3>

	<?php if ( $is_featured ) : ?>
		<p class="ntn-card-excerpt"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
	<?php endif; ?>
</a>
