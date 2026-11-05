<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Hub di categoria (v3.14.0): "Console" (marchi e piattaforme) e "Studi" (sviluppatori italiani).
 * v3.22.0: lo stesso modello serve Mobile (Android e iOS come marchi) e PC Gaming (senza marchi).
 *
 * Riferimento visivo: docs/mockup/ntn-mockup-completo.html, pagine "Hub console" e "Hub studi".
 * I dati arrivano dal plugin; se mancano, si torna all'archivio normale.
 *
 * @package NTN_Journal
 */

$hub = function_exists( 'ntn_core_get_category_hub_data' ) ? ntn_core_get_category_hub_data() : null;
if ( ! $hub ) {
	locate_template( 'archive.php', true, false );
	return;
}

get_header();

$hub_is_console = 'console' === $hub['type'];
$hub_filter     = function_exists( 'ntn_core_category_hub_platform_filter' ) ? ntn_core_category_hub_platform_filter( $hub ) : null;
$hub_base_url   = $hub['selected'] ? $hub['selected']['url'] : $hub['root']['url'];
$hub_platform_ids = wp_list_pluck( $hub['platforms'], 'label', 'id' );
// v3.22.0: la stessa Hub serve anche Mobile (Android, iOS) e PC Gaming, quindi i testi non parlano solo di console
$hub_is_consoles = false !== strpos( (string) wp_parse_url( $hub['root']['url'], PHP_URL_PATH ), 'console' );
$hub_chip        = $hub['selected'] ? $hub['root']['label'] . ' ' . $hub['selected']['label'] : ( empty( $hub['brands'] ) ? $hub['root']['label'] : ( $hub_is_consoles ? 'Tutte le console' : 'Tutte le piattaforme' ) );

/**
 * Etichetta rossa della riga: la console dell'articolo (Hub Console) o il gioco (Hub Studi).
 */
$hub_row_tag = static function ( $post_id ) use ( $hub_is_console, $hub_platform_ids ) {
	if ( $hub_is_console ) {
		foreach ( (array) wp_get_post_categories( $post_id ) as $cat_id ) {
			if ( isset( $hub_platform_ids[ $cat_id ] ) ) {
				return $hub_platform_ids[ $cat_id ];
			}
		}
		return '';
	}
	return function_exists( 'ntn_core_category_hub_post_game_name' ) ? ntn_core_category_hub_post_game_name( (int) $post_id ) : '';
};
?>

<main id="primary" class="site-main ntn-main">

	<!-- ==================== FASCIA DI APERTURA ==================== -->
	<header class="ntn-band ntn-chub-band">
		<?php if ( ! empty( $hub['illustration'] ) ) : ?>
			<canvas class="ntn-chub-art" data-ntn-dots="<?php echo esc_attr( implode( ',', $hub['illustration'] ) ); ?>" aria-hidden="true"></canvas>
		<?php endif; ?>
		<div class="ntn-wrap">

			<?php if ( function_exists( 'ntn_journal_breadcrumb' ) ) : ?>
				<div class="ntn-art-crumbs"><?php ntn_journal_breadcrumb(); ?></div>
			<?php endif; ?>

			<div class="ntn-chub-head">
				<div class="flex flex-wrap gap-2">
					<span class="ntn-chip">Hub di sezione</span>
					<?php if ( $hub_is_console ) : ?>
						<span class="ntn-chip ntn-chip--red"><?php echo esc_html( $hub_chip ); ?></span>
					<?php elseif ( 'studi' === $hub['type'] ) : ?>
						<span class="ntn-chip ntn-chip--red">Made in Italy</span>
					<?php endif; ?>
				</div>

				<h1 class="ntn-arch-title"><?php echo esc_html( $hub['title'] ); ?></h1>

				<?php if ( '' !== $hub['intro'] ) : ?>
					<p class="ntn-arch-desc"><?php echo esc_html( $hub['intro'] ); ?></p>
				<?php endif; ?>

				<dl class="ntn-chub-stats">
					<?php foreach ( $hub['stats'] as $stat ) : ?>
						<?php if ( '' === (string) $stat[1] ) { continue; } ?>
						<div><dt class="ntn-stat-k"><?php echo esc_html( $stat[0] ); ?></dt><dd class="ntn-stat-v<?php echo 'Aggiornato' === $stat[0] ? ' is-small' : ''; ?>"><?php echo esc_html( is_numeric( $stat[1] ) ? number_format_i18n( (int) $stat[1] ) : $stat[1] ); ?></dd></div>
					<?php endforeach; ?>
				</dl>

				<?php if ( $hub_is_console && ! empty( $hub['brands'] ) ) : ?>
					<!-- Selettore dei marchi: ogni marchio resta sul suo indirizzo -->
					<nav class="ntn-chub-pick" aria-label="<?php esc_attr_e( 'Scegli la piattaforma', 'ntn-journal' ); ?>">
						<a class="ntn-pick-btn" href="<?php echo esc_url( $hub['root']['url'] ); ?>"<?php echo $hub['selected'] ? '' : ' aria-current="page"'; ?>>Tutte</a>
						<?php foreach ( $hub['brands'] as $brand ) : ?>
							<a class="ntn-pick-btn" href="<?php echo esc_url( $brand['url'] ); ?>"<?php echo $brand['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $brand['label'] ); ?></a>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>
			</div>

		</div>
	</header>

	<div class="ntn-wrap ntn-chub-body">

		<?php if ( $hub_is_console && ! empty( $hub['platforms'] ) ) : ?>
			<!-- ==================== PIATTAFORME ==================== -->
			<section class="ntn-home-block" aria-labelledby="ntn-h-plats">
				<div class="ntn-sec-head">
					<h2 id="ntn-h-plats" class="ntn-sec-title">// Piattaforme</h2>
					<span class="ntn-sec-aside"><?php echo esc_html( $hub['selected'] ? 'Le piattaforme ' . $hub['selected']['label'] : 'Tutte le piattaforme della sezione' ); ?></span>
				</div>
				<div class="ntn-chub-grid">
					<?php foreach ( $hub['platforms'] as $i => $platform ) : ?>
						<article class="ntn-chub-card">
							<div class="ntn-chub-card-top">
								<div class="min-w-0">
									<span class="ntn-chub-card-id"><?php echo esc_html( ( $hub['selected'] || $platform['brand'] === $platform['label'] ? '' : $platform['brand'] . ' · ' ) . 'Piattaforma ' . str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
									<h3 class="ntn-chub-card-title"><a href="<?php echo esc_url( $platform['url'] ); ?>">// <?php echo esc_html( $platform['label'] ); ?></a></h3>
									<span class="ntn-chub-card-path"><?php echo esc_html( $platform['path'] ); ?></span>
								</div>
								<div class="ntn-chub-count"><b><?php echo esc_html( number_format_i18n( $platform['count'] ) ); ?></b><span>Articoli</span></div>
							</div>
							<?php if ( ! empty( $platform['games'] ) ) : ?>
								<div class="ntn-chub-games">
									<?php foreach ( $platform['games'] as $game ) : ?>
										<span><?php echo esc_html( $game ); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<?php if ( $platform['latest'] ) : ?>
								<div class="ntn-chub-last">
									<span>Ultimo articolo</span>
									<a href="<?php echo esc_url( $platform['latest']['url'] ); ?>"><?php echo esc_html( $platform['latest']['title'] ); ?></a>
								</div>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $hub['featured'] ) : ?>
			<!-- ==================== IN EVIDENZA ==================== -->
			<section class="ntn-home-block" aria-labelledby="ntn-h-cfeat">
				<div class="ntn-sec-head">
					<h2 id="ntn-h-cfeat" class="ntn-sec-title"><span class="ntn-dot text-red-600" aria-hidden="true"></span>// In evidenza</h2>
					<span class="ntn-sec-aside">Scelto dalla redazione</span>
				</div>
				<?php
				// Stessa apertura della Home: un articolo grande e tre piccoli
				$hub_main = ntn_front_card_data( (int) $hub['featured'], 'large' );
				$hub_side = array_map( static function ( $side_id ) {
					return ntn_front_card_data( (int) $side_id, 'large' );
				}, $hub['side'] );
				?>
				<div class="ntn-lead<?php echo empty( $hub_side ) ? ' ntn-lead--solo' : ''; ?>">
					<a class="ntn-lead-main group" href="<?php echo esc_url( $hub_main['permalink'] ); ?>">
						<?php ntn_front_image( $hub_main, 'aspect-video' ); ?>
						<?php if ( $hub_main['cat_name'] ) : ?>
							<div class="flex flex-wrap gap-2"><span class="ntn-chip"><?php echo esc_html( $hub_main['cat_name'] ); ?></span></div>
						<?php endif; ?>
						<h3 class="ntn-lead-title"><?php echo esc_html( $hub_main['title'] ); ?></h3>
						<?php if ( $hub_main['excerpt'] ) : ?>
							<p class="ntn-lead-excerpt"><?php echo esc_html( $hub_main['excerpt'] ); ?></p>
						<?php endif; ?>
						<div class="ntn-meta">
							<?php ntn_front_entity( $hub_main ); ?>
							<?php if ( $hub_main['author'] ) : ?>
								<span>by <b><?php echo esc_html( $hub_main['author'] ); ?></b></span>
							<?php endif; ?>
							<span><?php echo esc_html( $hub_main['date'] ); ?></span>
							<span><?php echo esc_html( $hub_main['minutes'] ); ?> min</span>
						</div>
					</a>

					<?php if ( ! empty( $hub_side ) ) : ?>
					<div class="ntn-lead-side">
						<?php foreach ( $hub_side as $hub_card ) : ?>
							<a class="ntn-lead-item group" href="<?php echo esc_url( $hub_card['permalink'] ); ?>">
								<?php ntn_front_image( $hub_card, 'aspect-[4/3]' ); ?>
								<div class="min-w-0">
									<?php if ( $hub_card['cat_name'] ) : ?>
										<span class="ntn-kicker"><?php echo esc_html( $hub_card['cat_name'] ); ?></span>
									<?php endif; ?>
									<h3 class="ntn-lead-item-title"><?php echo esc_html( $hub_card['title'] ); ?></h3>
									<?php if ( $hub_card['excerpt'] ) : ?>
										<p class="ntn-lead-item-excerpt"><?php echo esc_html( wp_trim_words( $hub_card['excerpt'], 14, '…' ) ); ?></p>
									<?php endif; ?>
									<div class="ntn-meta mt-2"><?php ntn_front_entity( $hub_card ); ?></div>
								</div>
							</a>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- ==================== TUTTI GLI ARTICOLI ==================== -->
		<section class="ntn-home-block" aria-labelledby="ntn-h-cfeed">
			<div class="ntn-sec-head">
				<h2 id="ntn-h-cfeed" class="ntn-sec-title">// Tutti gli articoli<?php echo $hub_filter ? ' · ' . esc_html( $hub_filter['label'] ) : ''; ?></h2>
				<span class="ntn-sec-aside">Dal più recente</span>
			</div>

			<?php if ( $hub_is_console && count( $hub['platforms'] ) > 1 ) : ?>
				<nav class="ntn-chub-pick ntn-chub-filter" aria-label="<?php esc_attr_e( 'Filtra per piattaforma', 'ntn-journal' ); ?>">
					<a class="ntn-pick-btn" href="<?php echo esc_url( $hub_base_url ); ?>"<?php echo $hub_filter ? '' : ' aria-current="page"'; ?>>Tutte</a>
					<?php foreach ( $hub['platforms'] as $platform ) : ?>
						<a class="ntn-pick-btn" href="<?php echo esc_url( add_query_arg( 'console', $platform['slug'], $hub_base_url ) ); ?>"<?php echo ( $hub_filter && $hub_filter['id'] === $platform['id'] ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $platform['label'] ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<?php get_template_part( 'template-parts/hub-article-feed', null, array( 'row_tag' => $hub_row_tag, 'empty' => 'Non ci sono ancora articoli in questa sezione.' ) ); ?>
		</section>

		<?php if ( ! $hub_is_console && ! empty( $hub['studios'] ) ) : ?>
			<!-- ==================== INDICE DEGLI STUDI (v3.22.1: dopo gli articoli) ==================== -->
			<section class="ntn-home-block" aria-labelledby="ntn-h-studios">
				<div class="ntn-sec-head">
					<h2 id="ntn-h-studios" class="ntn-sec-title">// Indice degli studi</h2>
					<span class="ntn-sec-aside">Dallo sviluppatore indicato su IGDB</span>
				</div>
				<?php $hub_letters = array_unique( wp_list_pluck( $hub['studios'], 'letter' ) ); ?>
				<div class="ntn-chub-pick ntn-chub-az" data-ntn-az hidden>
					<button type="button" class="ntn-pick-btn" aria-pressed="true" data-letter="">Tutti</button>
					<?php foreach ( array_merge( array( '#' ), range( 'A', 'Z' ) ) as $letter ) : ?>
						<button type="button" class="ntn-pick-btn" aria-pressed="false" data-letter="<?php echo esc_attr( $letter ); ?>"<?php echo in_array( $letter, $hub_letters, true ) ? '' : ' disabled'; ?>><?php echo esc_html( $letter ); ?></button>
					<?php endforeach; ?>
				</div>
				<div class="ntn-chub-grid is-3">
					<?php foreach ( $hub['studios'] as $i => $studio ) : ?>
						<article class="ntn-chub-card" data-letter="<?php echo esc_attr( $studio['letter'] ); ?>">
							<div class="ntn-chub-card-top">
								<div class="min-w-0">
									<span class="ntn-chub-card-id">Studio <?php echo esc_html( str_pad( (string) ( $i + 1 ), 3, '0', STR_PAD_LEFT ) ); ?></span>
									<h3 class="ntn-chub-card-title">
										<?php if ( '' !== $studio['url'] ) : ?>
											<a href="<?php echo esc_url( $studio['url'] ); ?>">// <?php echo esc_html( $studio['name'] ); ?></a>
										<?php else : ?>
											// <?php echo esc_html( $studio['name'] ); ?>
										<?php endif; ?>
									</h3>
								</div>
								<div class="ntn-chub-count"><b><?php echo esc_html( number_format_i18n( $studio['count'] ) ); ?></b><span>Articoli</span></div>
							</div>
							<div class="ntn-chub-games">
								<?php foreach ( $studio['games'] as $game ) : ?>
									<span><?php echo esc_html( $game ); ?></span>
								<?php endforeach; ?>
							</div>
							<div class="ntn-chub-last">
								<span>Ultimo articolo</span>
								<a href="<?php echo esc_url( $studio['latest']['url'] ); ?>"><?php echo esc_html( $studio['latest']['title'] ); ?></a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
