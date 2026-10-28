<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The template for displaying specific CPT Videogioco.
 * Funge da Hub Page per il singolo videogioco.
 *
 * Fase 6 del restyling (v3.8.0): intestazione sulla fascia che segue il tema, contenuto in
 * evidenza, trailer e screenshot in una scheda media,
 * tutti gli articoli a celle. Riferimento visivo: docs/mockup/ntn-mockup-completo.html (Hub gioco).
 *
 * @package NTN_Journal
 */

get_header();

$post_id = get_the_ID();

// Recupera tutti i metadati IGDB con fallback bidirezionale post_meta <-> term_meta
$igdb_info = function_exists( 'ntn_get_hub_igdb_info' ) ? ntn_get_hub_igdb_info( $post_id ) : array();

$game_title = ! empty( $igdb_info['title'] ) ? $igdb_info['title'] : get_the_title( $post_id );

// Testo scritto dalla redazione nell'editor della Hub (v3.11.0): compare come "// Panoramica" nel corpo.
// L'indice automatico non serve in un testo breve, quindi viene sospeso solo qui.
$hub_intro     = '';
$hub_intro_raw = (string) get_post_field( 'post_content', $post_id );
if ( '' !== trim( wp_strip_all_tags( $hub_intro_raw ) ) ) {
	$toc_priority = has_filter( 'the_content', 'ntn_journal_auto_toc' );
	if ( false !== $toc_priority ) {
		remove_filter( 'the_content', 'ntn_journal_auto_toc', $toc_priority );
	}
	$hub_intro = apply_filters( 'the_content', $hub_intro_raw );
	if ( false !== $toc_priority ) {
		add_filter( 'the_content', 'ntn_journal_auto_toc', $toc_priority );
	}
}

// Hero Header (Copertina + Titolo + Contatori + Scheda Tecnica)
get_template_part( 'template-parts/hub-header', null, array( 'id' => $post_id, 'info' => $igdb_info, 'has_intro' => '' !== $hub_intro ) );

// Ricerca articolo in evidenza con priorità tassativa: Manuale > Recensioni > Anteprime > Editoriali > Notizie
$featured_data = function_exists( 'ntn_get_game_featured_content' ) ? ntn_get_game_featured_content( $post_id ) : false;

// Calcolo titolo dinamico per il box in evidenza
$box_title = '// ARTICOLO IN EVIDENZA';

if ( $featured_data ) {
	switch ( $featured_data['type'] ) {
		case 'review':
			$box_title = '// RECENSIONE IN EVIDENZA';
			break;
		case 'preview':
			$box_title = '// ANTEPRIMA IN EVIDENZA';
			break;
		case 'editorial':
			$box_title = '// EDITORIALE IN EVIDENZA';
			break;
		case 'news':
			$box_title = '// NOTIZIA IN EVIDENZA';
			break;
		default:
			$box_title = '// ARTICOLO IN EVIDENZA';
			break;
	}
} else {
	$box_title = '// NESSUN ARTICOLO //';
}

// Trailer e screenshot
$raw_video = ( ! empty( $igdb_info['video'] ) && is_scalar( $igdb_info['video'] ) ) ? trim( (string) $igdb_info['video'] ) : ( ( ! empty( $igdb_info['video'] ) && is_array( $igdb_info['video'] ) ) ? trim( (string) reset( $igdb_info['video'] ) ) : '' );
$video_id  = '';
if ( ! empty( $raw_video ) ) {
	if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $raw_video, $v_matches ) ) {
		$video_id = $v_matches[1];
	} else {
		$video_id = sanitize_text_field( $raw_video );
	}
}
$has_video = ! empty( $video_id );

$scr_array = array();
if ( ! empty( $igdb_info['screenshots'] ) ) {
	$raw_scrs = is_array( $igdb_info['screenshots'] ) ? $igdb_info['screenshots'] : ( is_scalar( $igdb_info['screenshots'] ) ? explode( ',', (string) $igdb_info['screenshots'] ) : array() );
	foreach ( $raw_scrs as $scr_item ) {
		if ( ! is_scalar( $scr_item ) && ( ! is_object( $scr_item ) || ! method_exists( $scr_item, '__toString' ) ) ) {
			continue;
		}
		$scr_item = trim( (string) $scr_item );
		if ( empty( $scr_item ) ) continue;
		if ( strpos( $scr_item, 'http' ) === 0 || strpos( $scr_item, '//' ) === 0 ) {
			$thumb_url = preg_replace( '/\/t_[a-z0-9_]+\//', '/t_720p/', $scr_item );
			$full_url  = preg_replace( '/\/t_[a-z0-9_]+\//', '/t_1080p/', $scr_item );
			if ( strpos( $thumb_url, '//' ) === 0 ) {
				$thumb_url = 'https:' . $thumb_url;
			}
			if ( strpos( $full_url, '//' ) === 0 ) {
				$full_url = 'https:' . $full_url;
			}
		} else {
			$clean_id  = preg_replace( '/\.(jpg|png|jpeg|webp)$/i', '', $scr_item );
			$thumb_url = 'https://images.igdb.com/igdb/image/upload/t_720p/' . $clean_id . '.jpg';
			$full_url  = 'https://images.igdb.com/igdb/image/upload/t_1080p/' . $clean_id . '.jpg';
		}
		$scr_array[] = array(
			'thumb' => $thumb_url,
			'full'  => $full_url,
		);
	}
}
$has_screenshots = ! empty( $scr_array );
?>

<main id="primary" class="site-main ntn-main">
	<div class="ntn-wrap ntn-hub-body">

		<!-- SEZIONE SUPERIORE: contenuto in evidenza + scheda media -->
		<div class="ntn-hubgrid<?php echo esc_attr( ( $has_video || $has_screenshots ) ? '' : ' is-single' ); ?>">

			<!-- Panoramica della redazione + contenuto in evidenza -->
			<div class="min-w-0">
				<?php if ( '' !== $hub_intro ) : ?>
					<section class="ntn-hub-intro" aria-labelledby="ntn-h-intro">
						<div class="ntn-sec-head mb-6">
							<h2 id="ntn-h-intro" class="ntn-sec-title">// Panoramica</h2>
						</div>
						<div class="dynamic-content-body ntn-prose prose dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 font-sans leading-relaxed">
							<?php echo wp_kses_post( $hub_intro ); ?>
						</div>
					</section>
				<?php endif; ?>

				<div class="ntn-sec-head mb-6">
					<?php if ( $featured_data && ! empty( $featured_data['post'] ) ) : ?>
						<a href="<?php echo esc_url( get_permalink( $featured_data['post']->ID ) ); ?>" class="group">
							<h2 class="ntn-sec-title"><span class="ntn-dot text-red-600" aria-hidden="true"></span><?php echo esc_html( $box_title ); ?></h2>
						</a>
					<?php else : ?>
						<h2 class="ntn-sec-title"><?php echo esc_html( $box_title ); ?></h2>
					<?php endif; ?>
				</div>

				<?php if ( $featured_data && ! empty( $featured_data['post'] ) ) :
					global $post;
					$post = $featured_data['post'];
					setup_postdata( $post );

					$rating = function_exists( 'get_post_rating' ) ? get_post_rating( $post->ID ) : '';
					if ( $featured_data['type'] === 'review' && ! empty( $rating ) ) {
						get_template_part( 'template-parts/review-original-box', null, array( 'hide_verdict' => true ) );
						echo '<a class="ntn-readmore" href="' . esc_url( get_permalink( $post->ID ) ) . '"><span>Leggi la recensione completa</span><span aria-hidden="true">→</span></a>';
					} else {
						echo '<div class="ntn-cells ntn-cells--1">';
						get_template_part( 'template-parts/content', null, array( 'is_featured' => true ) );
						echo '</div>';
					}

					wp_reset_postdata();
				?>
				<?php else : ?>
					<div class="ntn-empty">
						<span>// Nessun articolo //</span>
						<p>Nessun contenuto in evidenza attualmente disponibile.</p>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $has_video || $has_screenshots ) : ?>
			<!-- Scheda media: trailer e screenshot -->
			<aside class="min-w-0" aria-labelledby="ntn-h-media">
				<div class="ntn-sec-head mb-6">
					<h2 id="ntn-h-media" class="ntn-sec-title">// Scheda media</h2>
				</div>
				<div class="ntn-media">
					<?php if ( $has_video ) : ?>
						<h3 class="ntn-media-h"><span>Trailer</span><span>YouTube</span></h3>
						<div class="ntn-lazy-video relative w-full overflow-hidden bg-black aspect-video cursor-pointer group" data-video-id="<?php echo esc_attr( $video_id ); ?>">
							<img src="https://img.youtube.com/vi/<?php echo esc_attr( $video_id ); ?>/hqdefault.jpg" alt="<?php echo esc_attr( sprintf( 'Trailer ufficiale di %s', get_the_title() ) ); ?>" width="640" height="360" loading="lazy" decoding="async" class="w-full h-full object-cover">
							<div class="absolute inset-0 flex items-center justify-center">
								<div class="ntn-play">
									<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white ml-1" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
										<path d="M8 5v14l11-7z" />
									</svg>
								</div>
							</div>
						</div>
						<p class="ntn-media-note">Il video si carica solo al clic</p>
					<?php endif; ?>

					<?php if ( $has_screenshots ) : ?>
						<h3 class="ntn-media-h"><span>Screenshot</span><span><?php echo esc_html( count( $scr_array ) ); ?></span></h3>
						<div class="ntn-shots">
							<?php foreach ( $scr_array as $scr_index => $scr_item ) : ?>
								<a href="<?php echo esc_url( $scr_item['full'] ); ?>" target="_blank" rel="noopener noreferrer" class="glightbox ntn-shot" data-gallery="igdb-media" aria-label="<?php echo esc_attr( sprintf( 'Visualizza screenshot ingrandito di %s', get_the_title() ) ); ?>">
									<img src="<?php echo esc_url( $scr_item['thumb'] ); ?>" alt="<?php echo esc_attr( sprintf( 'Screenshot di %s', get_the_title() ) ); ?>" width="640" height="360" loading="lazy" decoding="async" class="w-full h-full object-cover" />
									<span class="ntn-shot-n"><?php echo esc_html( sprintf( '%02d', $scr_index + 1 ) ); ?></span>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</aside>
			<?php endif; ?>
		</div>

		<!-- SEZIONE INFERIORE: TUTTI GLI ARTICOLI DEL GIOCO -->
		<section class="ntn-home-block" aria-labelledby="ntn-h-all">
			<div class="ntn-sec-head">
				<h2 id="ntn-h-all" class="ntn-sec-title">
					TUTTI GLI ARTICOLI // <?php echo esc_html( strtoupper( $game_title ) ); ?>
				</h2>
				<span class="ntn-sec-aside">Dal più recente</span>
			</div>

			<?php
			// Cerchiamo gli articoli con il tag avente lo stesso slug o nome del CPT
			$game_slug = get_post_field( 'post_name', $post_id );
			$matching_tag = get_term_by( 'slug', $game_slug, 'post_tag' );
			if ( ! $matching_tag || is_wp_error( $matching_tag ) ) {
				$matching_tag = get_term_by( 'name', get_the_title( $post_id ), 'post_tag' );
			}
			if ( ( ! $matching_tag || is_wp_error( $matching_tag ) ) && ! empty( $igdb_info['title'] ) && is_string( $igdb_info['title'] ) ) {
				$matching_tag = get_term_by( 'name', $igdb_info['title'], 'post_tag' );
				if ( ! $matching_tag || is_wp_error( $matching_tag ) ) {
					$matching_tag = get_term_by( 'slug', sanitize_title( $igdb_info['title'] ), 'post_tag' );
				}
			}
			$query_tag = ( $matching_tag && ! is_wp_error( $matching_tag ) && isset( $matching_tag->slug ) ) ? $matching_tag->slug : $game_slug;

			// I template singoli in WP gestiscono la paginazione su "page"
			$paged = ( get_query_var( 'page' ) ) ? intval( get_query_var( 'page' ) ) : ( get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1 );

			$articles_query = new WP_Query( array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'tag'            => $query_tag,
				'paged'          => $paged,
			) );

			if ( $articles_query->have_posts() ) : ?>
				<div class="ntn-cells ntn-cells--3">
					<?php while ( $articles_query->have_posts() ) : $articles_query->the_post();
						get_template_part( 'template-parts/content' );
					endwhile; ?>
				</div>

				<?php
				$big = 999999999;
				$pagination_links = paginate_links( array(
					'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format'    => '?page=%#%',
					'current'   => max( 1, $paged ),
					'total'     => $articles_query->max_num_pages,
					'mid_size'  => 2,
					'prev_text' => '&larr; Precedente',
					'next_text' => 'Successivo &rarr;',
					'type'      => 'plain',
				) );
				if ( $pagination_links ) :
				?>
				<div class="ntn-pager-wrap">
					<nav class="navigation pagination" aria-label="<?php esc_attr_e( 'Paginazione articoli correlati', 'ntn-journal' ); ?>">
						<h2 class="screen-reader-text"><?php esc_html_e( 'Paginazione degli articoli', 'ntn-journal' ); ?></h2>
						<div class="nav-links">
							<?php echo $pagination_links; // phpcs:ignore WordPress.Security.EscapeOutput -- markup generato da paginate_links(). ?>
						</div>
					</nav>
				</div>
				<?php endif; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<div class="ntn-empty">
					<span>Vuoto</span>
					<p>Nessun articolo aggiuntivo per questo gioco.</p>
				</div>
			<?php endif; ?>
		</section>

	</div>
</main>

<?php get_footer(); ?>
