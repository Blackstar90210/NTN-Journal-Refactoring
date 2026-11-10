<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The template for displaying all single posts
 *
 * Fase 4 del restyling (v3.6.0): testata dell'articolo sulla fascia a matrice di punti
 * (chiara o nera secondo il tema), foto in evidenza a tutta larghezza senza filtri,
 * testo più largo e leggibile con sottotitoli numerati, scheda dell'autore in fondo.
 * Il box della recensione e la scheda del gioco restano quelli di oggi (fase 5).
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html (pagina Articolo).
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package NTN_Journal
 */

get_header();

?>

<main id="primary" class="site-main ntn-main flex-grow">
	<?php
	while ( have_posts() ) :
		the_post();
		$primary_category = ntn_get_primary_category( get_the_ID() );
		$cat_name         = $primary_category ? $primary_category->name : 'ARTICLE';
		$rating           = ntn_get_post_rating( get_the_ID() );

		$game_title  = ntn_get_igdb_data( get_the_ID(), '_ntn_game_title' );
		$game_cover  = ntn_get_igdb_data( get_the_ID(), '_ntn_game_cover' );
		$has_sidebar = ! empty( $rating ) || ! empty( $game_title ) || ! empty( ntn_get_post_games( get_the_ID() ) );
		$game_entity = function_exists( 'ntn_front_game_entity' ) ? ntn_front_game_entity( get_the_ID() ) : null;

		// Sottotitolo: priorità 1 = estratto (manuale o AI), 2 = campo custom_h2, 3 = primo <h2> del contenuto
		$custom_h2_text   = '';
		$has_extracted_h2 = false;
		$is_ai_generated  = false;

		$raw_excerpt = get_post_field( 'post_excerpt', get_the_ID() );

		if ( ! empty( $raw_excerpt ) ) {
			$custom_h2_text = wp_strip_all_tags( $raw_excerpt );
			if ( get_post_meta( get_the_ID(), '_is_ai_excerpt', true ) == '1' ) {
				$is_ai_generated = true;
			}
		} else {
			$custom_meta_h2 = get_post_meta( get_the_ID(), 'custom_h2', true );
			if ( ! empty( $custom_meta_h2 ) ) {
				$custom_h2_text = $custom_meta_h2;
			} else {
				$raw_content = get_post_field( 'post_content', get_the_ID() );
				if ( preg_match( '/<h2[^>]*>(.*?)<\/h2>/is', $raw_content, $matches ) ) {
					$custom_h2_text   = strip_tags( $matches[1] );
					$has_extracted_h2 = true;
				}
			}
		}

		// Data di aggiornamento: si mostra solo se l'articolo è stato modificato almeno un giorno dopo l'uscita
		$published_ts = (int) get_post_timestamp( get_the_ID() );
		$modified_ts  = (int) get_post_timestamp( get_the_ID(), 'modified' );
		$show_updated = $modified_ts && $published_ts && ( $modified_ts - $published_ts ) > DAY_IN_SECONDS;

		// L'autore non è nella riga dei dati: lo mostra la firma subito sotto
		$rating_label = ( $rating && function_exists( 'ntn_get_rating_label' ) ) ? ntn_get_rating_label( $rating ) : '';
		?>

	<article id="post-<?php the_ID(); ?>" <?php post_class( 'ntn-article' ); ?>>

		<!-- ==================== TESTATA DELL'ARTICOLO ==================== -->
		<header class="ntn-band ntn-art-band">
			<div class="ntn-wrap">

				<div class="ntn-art-crumbs">
					<?php
					if ( function_exists( 'ntn_journal_breadcrumb' ) ) {
						ntn_journal_breadcrumb();
					}
					?>
				</div>

				<div class="ntn-art-head">
					<div class="flex flex-wrap gap-2">
						<?php
						if ( ! empty( $primary_category ) && isset( $primary_category->term_id ) ) :
							$cat_link = get_category_link( $primary_category->term_id );
							$cat_url  = ( ! empty( $cat_link ) && ! is_wp_error( $cat_link ) && is_string( $cat_link ) ) ? $cat_link : '#';
							?>
							<a href="<?php echo esc_url( $cat_url ); ?>" class="ntn-chip ntn-chip-link"><?php echo esc_html( $cat_name ); ?></a>
						<?php else : ?>
							<span class="ntn-chip"><?php echo esc_html( $cat_name ); ?></span>
						<?php endif; ?>
						<?php if ( $rating_label ) : ?>
							<span class="ntn-chip ntn-chip--red"><?php echo esc_html( $rating_label ); ?></span>
						<?php endif; ?>
					</div>

					<h1 class="ntn-art-title"><?php the_title(); ?></h1>

					<?php if ( ! empty( $custom_h2_text ) ) : ?>
						<h2 class="ntn-art-sub">
							<?php if ( $is_ai_generated ) : ?>
								<span class="ntn-ai-note">// Estratto by AI</span>
							<?php endif; ?>
							<?php echo esc_html( $custom_h2_text ); ?>
						</h2>
					<?php endif; ?>

					<div class="ntn-meta ntn-art-meta">
						<?php if ( $game_entity ) : ?>
							<a class="ntn-entity" href="<?php echo esc_url( $game_entity['url'] ); ?>"><span class="ntn-dot" aria-hidden="true"></span>// <?php echo esc_html( $game_entity['name'] ); ?></a>
						<?php endif; ?>
						<span><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd.m.Y · H:i' ) ); ?></time></span>
						<span><?php echo esc_html( ntn_calculate_reading_time( get_the_ID() ) ); ?></span>
						<?php if ( $show_updated ) : ?>
							<span>Aggiornato <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date( 'd.m.Y' ) ); ?></time></span>
						<?php endif; ?>
					</div>

					<!-- Firma dell'autore: sempre in vista, nella testata (variante A) -->
					<?php get_template_part( 'template-parts/author-panel' ); ?>
				</div>

				<?php if ( has_post_thumbnail() ) : ?>
					<?php $ntn_caption = wp_get_attachment_caption( get_post_thumbnail_id() ); ?>
					<figure class="ntn-art-hero">
						<div class="ntn-art-hero-img">
							<?php
							the_post_thumbnail(
								'large',
								array(
									'class'         => 'w-full h-full object-cover',
									'fetchpriority' => 'high',
									'loading'       => 'eager',
								)
							);
							?>
							<?php if ( get_theme_mod( 'ntn_journal_enable_watermark', true ) ) : ?>
								<div class="ntn-art-watermark">
									<span><?php echo esc_html( '// ' . get_theme_mod( 'ntn_journal_main_logo_text', 'NOME-SITO' ) ); ?></span>
									<span class="ntn-art-watermark-sep" aria-hidden="true"></span>
									<span class="truncate max-w-[150px] sm:max-w-[300px]" title="<?php echo esc_attr( get_the_title( get_post_thumbnail_id() ) ); ?>"><?php echo esc_html( get_the_title( get_post_thumbnail_id() ) ); ?></span>
								</div>
							<?php endif; ?>
						</div>
						<?php if ( $ntn_caption ) : ?>
							<figcaption><?php echo esc_html( $ntn_caption ); ?></figcaption>
						<?php endif; ?>
					</figure>
				<?php endif; ?>

			</div>
		</header>

		<!-- ==================== CORPO E COLONNA LATERALE ==================== -->
		<!-- v3.16.0: la colonna laterale c'è su tutti gli articoli (indice, scheda del videogioco se c'è, condivisione, Google) -->
		<div class="ntn-wrap ntn-art-layout has-sidebar">

			<div class="ntn-art-main">

				<!-- Mobile Review Header (IGDB) -->
				<div class="block xl:hidden">
					<?php get_template_part( 'template-parts/review-header-mobile' ); ?>
				</div>

				<!-- ==================== REVIEW GRAPHIC BOX (PC Only) ==================== -->
				<div class="hidden xl:block">
					<?php get_template_part( 'template-parts/review-original-box', null, array( 'is_mobile' => false ) ); ?>
				</div>

				<!-- Dynamic Content Body -->
				<div class="dynamic-content-body ntn-prose prose dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 font-sans leading-relaxed space-y-6">
					<?php
					the_content();

					wp_link_pages(
						array(
							'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ntn-journal' ),
							'after'  => '</div>',
						)
					);
					?>
				</div>

				<!-- ==================== REVIEW GRAPHIC BOX (Mobile Only - Bottom) ==================== -->
				<div class="block xl:hidden mt-10 border-t border-zinc-200 dark:border-zinc-900 pt-8">
					<?php get_template_part( 'template-parts/review-original-box', null, array( 'is_mobile' => true ) ); ?>
				</div>

				<!-- Tag dell'articolo: il gioco collegato in rosso, gli altri come "#Nome" -->
				<?php get_template_part( 'template-parts/article-tags' ); ?>

				<!-- Da telefono e schermi stretti: condivisione, Google e "Seguici" sotto l'articolo (da computer stanno nella colonna) -->
				<div class="ntn-art-after xl:hidden">
					<?php
					if ( function_exists( 'ntn_journal_share_buttons' ) ) {
						ntn_journal_share_buttons();
					}
					if ( function_exists( 'ntn_journal_google_feed_box' ) ) {
						echo ntn_journal_google_feed_box(); // phpcs:ignore WordPress.Security.EscapeOutput -- markup già sanificato dalla funzione.
					}
					if ( function_exists( 'ntn_journal_social_cta_box' ) ) {
						echo ntn_journal_social_cta_box(); // phpcs:ignore WordPress.Security.EscapeOutput -- markup già sanificato dalla funzione.
					}
					?>
				</div>

				<!-- Bottom Related Posts -->
				<?php
				if ( function_exists( 'ntn_journal_bottom_related_posts' ) ) {
					ntn_journal_bottom_related_posts();
				}
				?>

				<?php
				// Controlla se i commenti sono attivati a livello di tema
				if ( get_option( 'ntn_journal_enable_comments', 'yes' ) === 'yes' ) {
					// Mostra i commenti (form + lista) se sono aperti o se ce ne sono già.
					if ( comments_open() || get_comments_number() ) :
						?>
						<section class="ntn-comments" aria-label="<?php esc_attr_e( 'Commenti', 'ntn-journal' ); ?>">
							<div class="ntn-sec-head mb-6">
								<h2 class="ntn-sec-title">// Commenti (<?php echo esc_html( number_format_i18n( get_comments_number() ) ); ?>)</h2>
							</div>
							<?php comments_template(); ?>
						</section>
						<?php
					endif;
				}
				?>

			</div>

			<!-- Colonna laterale (schermi larghi), ferma mentre si legge: indice, scheda del videogioco, condivisione, Google, Seguici -->
			<aside id="ntn-sticky-sidebar-container" class="ntn-art-aside hidden xl:block" aria-label="<?php esc_attr_e( 'Indice e informazioni sull\'articolo', 'ntn-journal' ); ?>">
				<div class="ntn-art-aside-in" id="ntn-sticky-sidebar-content">
					<div class="ntn-art-aside-sticky">
						<?php
						if ( function_exists( 'ntn_core_get_toc_items' ) && function_exists( 'ntn_core_toc_markup' ) ) {
							echo ntn_core_toc_markup( ntn_core_get_toc_items( get_the_ID() ), 'ntn-toc--aside' ); // phpcs:ignore WordPress.Security.EscapeOutput -- markup già sanificato dal plugin.
						}
						if ( $has_sidebar ) {
							get_template_part( 'template-parts/review-sidebar-desktop' );
						}
						if ( function_exists( 'ntn_journal_share_buttons' ) ) {
							ntn_journal_share_buttons();
						}
						if ( function_exists( 'ntn_journal_google_feed_box' ) ) {
							echo ntn_journal_google_feed_box(); // phpcs:ignore WordPress.Security.EscapeOutput -- markup già sanificato dalla funzione.
						}
						if ( function_exists( 'ntn_journal_social_cta_box' ) ) {
							echo ntn_journal_social_cta_box(); // phpcs:ignore WordPress.Security.EscapeOutput -- markup già sanificato dalla funzione.
						}
						?>
					</div>
				</div>
			</aside>

		</div>

	</article>

		<?php
	endwhile; // End of the loop.
	?>
</main>

<?php
get_footer();
