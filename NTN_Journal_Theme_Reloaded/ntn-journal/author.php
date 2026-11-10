<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The template for displaying author archive pages
 *
 * Fase 9 del restyling (v3.13.0): fascia a matrice di punti come gli archivi, con foto o
 * iniziali in cornice, nome, biografia, numeri e link; articoli con le schede a celle.
 *
 * @package NTN_Journal
 */

get_header();

// Get the queried author object
$author      = get_queried_object();
$author_id   = ( $author && isset( $author->ID ) ) ? intval( $author->ID ) : get_queried_object_id();
$author_name = ( $author && isset( $author->display_name ) ) ? $author->display_name : 'Autore';
$author_bio  = ( $author && isset( $author->description ) ) ? $author->description : '';
$author_url  = ( $author && isset( $author->user_url ) ) ? $author->user_url : '';
$post_count  = $author_id ? count_user_posts( $author_id ) : 0;

// Helper to calculate total reading time of author's posts (cached for 12 hours)
$transient_key      = 'ntn_author_reading_time_' . $author_id;
$total_reading_time = get_transient( $transient_key );

if ( false === $total_reading_time ) {
	$total_reading_time = 0;
	$author_posts       = new WP_Query(
		array(
			'author'         => $author_id,
			'posts_per_page' => 100, // Limit to recent 100 posts for performance, instead of -1
			'fields'         => 'ids',
		)
	);
	if ( $author_posts->have_posts() ) {
		update_meta_cache( 'post', $author_posts->posts );
		foreach ( $author_posts->posts as $p_id ) {
			$reading_time = (int) get_post_meta( $p_id, '_ntn_reading_time', true );
			if ( ! $reading_time ) {
				$reading_time = 1; }
			$total_reading_time += $reading_time;
		}
		wp_reset_postdata();
	}
	set_transient( $transient_key, $total_reading_time, 12 * HOUR_IN_SECONDS );
}

// Fallback bio if empty
if ( empty( $author_bio ) ) {
	$author_bio = 'Redattore per ' . get_bloginfo( 'name' ) . '. Esplora le altre pubblicazioni di questo autore negli articoli qui sotto.';
}

// Recensioni dell'autore e anno di arrivo (in cache per un'ora, come nella firma degli articoli)
$author_stats = get_transient( 'ntn_author_stats_' . $author_id );
if ( ! is_array( $author_stats ) ) {
	$author_reviews    = 0;
	$author_review_cat = get_category_by_slug( 'recensioni' );
	if ( $author_id && $author_review_cat ) {
		$author_review_query = new WP_Query(
			array(
				'author'         => $author_id,
				'cat'            => (int) $author_review_cat->term_id,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		$author_reviews      = (int) $author_review_query->found_posts;
	}
	$author_registered = (string) get_the_author_meta( 'user_registered', $author_id );
	$author_stats      = array(
		'posts'   => $author_id ? (int) count_user_posts( $author_id, 'post', true ) : 0,
		'reviews' => $author_reviews,
		'since'   => $author_registered ? (int) substr( $author_registered, 0, 4 ) : 0,
	);
	set_transient( 'ntn_author_stats_' . $author_id, $author_stats, HOUR_IN_SECONDS );
}

// Iniziali per il riquadro a matrice di punti quando manca la foto
$author_initials = '';
foreach ( array_slice( (array) preg_split( '/\s+/', trim( $author_name ) ), 0, 2 ) as $author_word ) {
	$author_initials .= function_exists( 'mb_substr' ) ? mb_substr( $author_word, 0, 1 ) : substr( $author_word, 0, 1 );
}
$author_avatar = $author_id ? get_avatar( $author_id, 240, '', $author_name, array( 'class' => 'w-full h-full object-cover' ) ) : '';

$social_keys = array(
	'twitter'   => 'X',
	'instagram' => 'Instagram',
	'linkedin'  => 'LinkedIn',
	'github'    => 'GitHub',
	'youtube'   => 'YouTube',
	'facebook'  => 'Facebook',
);
?>

<main id="primary" class="site-main ntn-main">

	<!-- ==================== TESTATA DELL'AUTORE ==================== -->
	<header class="ntn-band ntn-arch-band" itemscope itemtype="https://schema.org/Person">
		<div class="ntn-wrap">

			<?php if ( function_exists( 'ntn_journal_breadcrumb' ) ) : ?>
				<div class="ntn-art-crumbs"><?php ntn_journal_breadcrumb(); ?></div>
			<?php endif; ?>

			<div class="ntn-arch-head ntn-authorpage">
				<div class="ntn-authorpage-photo ntn-author-avatar" aria-hidden="true">
					<?php
					// v3.17.1: iniziali a punti sotto, foto sopra solo se esiste (niente sagoma grigia)
					if ( function_exists( 'ntn_journal_author_avatar' ) ) {
						echo ntn_journal_author_avatar( $author_id, $author_name, 240 ); // phpcs:ignore WordPress.Security.EscapeOutput -- markup già sanificato dalla funzione.
					} elseif ( $author_avatar ) {
						echo $author_avatar; // phpcs:ignore WordPress.Security.EscapeOutput -- markup generato da WordPress.
					} else {
						echo '<span>' . esc_html( strtoupper( $author_initials ) ) . '</span>';
					}
					?>
				</div>

				<div class="ntn-authorpage-main">
					<div class="flex flex-wrap gap-2">
						<span class="ntn-chip">Autore</span>
					</div>
					<h1 class="ntn-arch-title" itemprop="name"><?php echo esc_html( $author_name ); ?></h1>
					<p class="ntn-arch-desc" itemprop="description"><?php echo esc_html( $author_bio ); ?></p>

					<div class="ntn-gcounts">
						<span><b><?php echo esc_html( number_format_i18n( $author_stats['posts'] ) ); ?></b><?php echo 1 === $author_stats['posts'] ? 'Articolo' : 'Articoli'; ?></span>
						<?php if ( $author_stats['reviews'] ) : ?>
							<span><b><?php echo esc_html( number_format_i18n( $author_stats['reviews'] ) ); ?></b><?php echo 1 === $author_stats['reviews'] ? 'Recensione' : 'Recensioni'; ?></span>
						<?php endif; ?>
						<span><b><?php echo esc_html( number_format_i18n( absint( $total_reading_time ) ) ); ?></b>Minuti di lettura</span>
						<?php if ( $author_stats['since'] ) : ?>
							<span><b><?php echo esc_html( $author_stats['since'] ); ?></b>Anno di ingresso</span>
						<?php endif; ?>
					</div>

					<?php
					$author_links = array();
					foreach ( $social_keys as $key => $label ) {
						$url = get_the_author_meta( $key, $author_id );
						if ( ! empty( $url ) && is_string( $url ) ) {
							$author_links[] = '<a class="ntn-chip ntn-chip-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" itemprop="sameAs">' . esc_html( $label ) . ' &nearr;</a>';
						}
					}
					if ( ! empty( $author_url ) ) {
						$author_links[] = '<a class="ntn-chip ntn-chip-link" href="' . esc_url( $author_url ) . '" target="_blank" rel="noopener noreferrer" itemprop="url">Sito web &nearr;</a>';
					}
					if ( $author_links ) {
						echo '<div class="ntn-authorpage-links">' . implode( '', $author_links ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- ogni link è già protetto.
					}
					?>
				</div>
			</div>

		</div>
	</header>

	<!-- ==================== ARTICOLI DELL'AUTORE ==================== -->
	<div class="ntn-wrap ntn-arch-body">
		<section aria-labelledby="ntn-h-author-posts">
			<div class="ntn-sec-head mb-6">
				<h2 id="ntn-h-author-posts" class="ntn-sec-title">// Tutti gli articoli (<?php echo esc_html( number_format_i18n( absint( $post_count ) ) ); ?>)</h2>
			</div>

			<?php if ( have_posts() ) : ?>
				<div class="ntn-cells ntn-cells--3">
					<?php
					while ( have_posts() ) :
						the_post();
						// Scheda a cella comune, senza "by Autore": qui l'autore è già nella testata
						get_template_part( 'template-parts/content', null, array( 'hide_author' => true ) );
					endwhile;
					?>
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
					<p>Questo autore non ha ancora pubblicato articoli. Riprova più tardi.</p>
				</div>
			<?php endif; ?>
		</section>
	</div>

</main>

<?php
get_footer();
