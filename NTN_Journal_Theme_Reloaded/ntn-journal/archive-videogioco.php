<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * L'Hub Centrale / Catalogo di tutti i Videogiochi.
 *
 * Fase 7 del restyling (v3.9.0): intestazione comune sulla fascia, filtri squadrati e
 * griglia a linee condivise. ID e parametri usati da catalog-filters.js sono invariati.
 *
 * @package NTN_Journal
 */

get_header();

// 1. SSR - Leggiamo i parametri dall'URL e li sanitizziamo in modo sicuro e tipizzato
$allowed_sorts = array( 'year_desc', 'year_asc', 'title_asc', 'title_desc' );
$sort_raw      = ( isset( $_GET['sort'] ) && is_string( $_GET['sort'] ) ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'year_desc';
$sort          = in_array( $sort_raw, $allowed_sorts, true ) ? $sort_raw : 'year_desc';

$franchise = ( isset( $_GET['franchise'] ) && is_string( $_GET['franchise'] ) ) ? sanitize_key( wp_unslash( $_GET['franchise'] ) ) : '';
$developer = ( isset( $_GET['developer'] ) && is_string( $_GET['developer'] ) ) ? sanitize_key( wp_unslash( $_GET['developer'] ) ) : '';
$publisher = ( isset( $_GET['publisher'] ) && is_string( $_GET['publisher'] ) ) ? sanitize_key( wp_unslash( $_GET['publisher'] ) ) : '';
$engine    = ( isset( $_GET['engine'] ) && is_string( $_GET['engine'] ) ) ? sanitize_key( wp_unslash( $_GET['engine'] ) ) : '';

$year_val  = ( isset( $_GET['year'] ) && is_numeric( $_GET['year'] ) ) ? absint( wp_unslash( $_GET['year'] ) ) : 0;
$year      = ( $year_val >= 1950 && $year_val <= 2100 ) ? $year_val : '';

$search    = ( isset( $_GET['search'] ) && is_string( $_GET['search'] ) ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
$page      = ( isset( $_GET['page'] ) && is_numeric( $_GET['page'] ) ) ? max( 1, absint( wp_unslash( $_GET['page'] ) ) ) : 1;

$params = array(
	'page'      => $page,
	'per_page'  => 20, // SSR carica i primi 20
	'franchise' => $franchise,
	'developer' => $developer,
	'publisher' => $publisher,
	'engine'    => $engine,
	'year'      => $year,
	'search'    => $search,
	'sort'      => $sort,
);

$has_filters = ! empty( $params['franchise'] ) || ! empty( $params['developer'] ) || ! empty( $params['publisher'] ) || ! empty( $params['engine'] ) || ! empty( $params['year'] ) || ! empty( $params['search'] ) || $params['sort'] !== 'year_desc';

// Rendering SSR dei primi risultati
$query = function_exists( 'ntn_get_catalog_query_args' ) ? new WP_Query( ntn_get_catalog_query_args( $params ) ) : null;

get_template_part(
	'template-parts/archive-head',
	null,
	array(
		'label'       => 'Catalogo',
		'title'       => 'Videogiochi',
		'description' => 'Esplora il nostro hub completo dei videogiochi trattati. Seleziona un gioco per accedere a tutte le recensioni, gli articoli e i contenuti ad esso dedicati.',
		// Totale del catalogo, non dei risultati filtrati: i filtri cambiano la griglia senza ricaricare la pagina.
		'count'       => function_exists( 'wp_count_posts' ) ? (int) wp_count_posts( 'videogioco' )->publish : 0,
		'unit'        => array( 'Gioco in catalogo', 'Giochi in catalogo' ),
	)
);
?>

<main id="primary" class="site-main ntn-main">
<div class="ntn-wrap ntn-arch-body ntn-catalog">

	<style>
		#ntn-catalog-grid {
			view-transition-name: catalog-grid;
		}
	</style>

	<!-- MOBILE FILTER TOGGLE (ACCORDION) -->
	<div class="ntn-filter-bar md:hidden">
		<button type="button" id="ntn-mobile-filter-toggle" class="ntn-filter-toggle" aria-controls="ntn-filters-container">
			<span>[ Filtri ]</span>
			<svg class="w-3.5 h-3.5 text-red-600 transition-all duration-300" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
				<!-- horizontal -->
				<circle cx="4" cy="12" r="1.5" />
				<circle cx="8" cy="12" r="1.5" />
				<circle cx="12" cy="12" r="1.5" />
				<circle cx="16" cy="12" r="1.5" />
				<circle cx="20" cy="12" r="1.5" />
				<!-- vertical group -->
				<g class="ntn-filter-icon-v transition-all duration-300 origin-center">
					<circle cx="12" cy="4" r="1.5" />
					<circle cx="12" cy="8" r="1.5" />
					<circle cx="12" cy="16" r="1.5" />
					<circle cx="12" cy="20" r="1.5" />
				</g>
			</svg>
			<?php if ( $has_filters ) : ?>
				<div class="ntn-active-filter-indicator w-1.5 h-1.5 rounded-full bg-red-600 ml-1"></div>
			<?php endif; ?>
		</button>
		<input type="text" id="mobile_filter_search" placeholder="Cerca..." value="<?php echo esc_attr( $params['search'] ); ?>" aria-label="<?php echo esc_attr__( 'Cerca nel catalogo videogiochi', 'ntn-journal' ); ?>" class="ntn-field-input ntn-filter-bar-search" />
	</div>

	<!-- FILTER UI -->
	<div id="ntn-filters-container" class="ntn-filters hidden md:flex">
		<div class="ntn-field hidden md:block">
			<label for="filter_search" class="ntn-field-label">Cerca</label>
			<input type="text" id="filter_search" name="filter_search" value="<?php echo esc_attr( $params['search'] ); ?>" placeholder="Es. Halo..." class="ntn-field-input" />
		</div>
		<div class="ntn-field">
			<label for="filter_franchise" class="ntn-field-label">Franchise</label>
			<?php wp_dropdown_categories( array(
				'show_option_all' => 'Tutti',
				'taxonomy'        => 'game_franchise',
				'name'            => 'filter_franchise',
				'id'              => 'filter_franchise',
				'class'           => 'ntn-field-input',
				'value_field'     => 'slug',
				'orderby'         => 'name',
				'order'           => 'ASC',
				'selected'        => $params['franchise'],
			) ); ?>
		</div>
		<div class="ntn-field">
			<label for="filter_developer" class="ntn-field-label">Sviluppatore</label>
			<?php wp_dropdown_categories( array(
				'show_option_all' => 'Tutti',
				'taxonomy'        => 'game_developer',
				'name'            => 'filter_developer',
				'id'              => 'filter_developer',
				'class'           => 'ntn-field-input',
				'value_field'     => 'slug',
				'orderby'         => 'name',
				'order'           => 'ASC',
				'selected'        => $params['developer'],
			) ); ?>
		</div>
		<div class="ntn-field">
			<label for="filter_publisher" class="ntn-field-label">Editore</label>
			<?php wp_dropdown_categories( array(
				'show_option_all' => 'Tutti',
				'taxonomy'        => 'game_publisher',
				'name'            => 'filter_publisher',
				'id'              => 'filter_publisher',
				'class'           => 'ntn-field-input',
				'value_field'     => 'slug',
				'orderby'         => 'name',
				'order'           => 'ASC',
				'selected'        => $params['publisher'],
			) ); ?>
		</div>
		<div class="ntn-field">
			<label for="filter_engine" class="ntn-field-label">Motore Grafico</label>
			<?php wp_dropdown_categories( array(
				'show_option_all' => 'Tutti',
				'taxonomy'        => 'game_engine',
				'name'            => 'filter_engine',
				'id'              => 'filter_engine',
				'class'           => 'ntn-field-input',
				'value_field'     => 'slug',
				'orderby'         => 'name',
				'order'           => 'ASC',
				'selected'        => $params['engine'],
			) ); ?>
		</div>
		<div class="ntn-field ntn-field--short">
			<label for="filter_year" class="ntn-field-label">Anno</label>
			<input type="number" id="filter_year" name="filter_year" value="<?php echo esc_attr( $params['year'] ?: '' ); ?>" placeholder="Es. 2024" class="ntn-field-input" />
		</div>
		<div class="ntn-field">
			<label for="filter_sort" class="ntn-field-label">Ordina</label>
			<select id="filter_sort" name="filter_sort" class="ntn-field-input">
				<option value="title_asc" <?php selected( $params['sort'], 'title_asc' ); ?>>A → Z</option>
				<option value="title_desc" <?php selected( $params['sort'], 'title_desc' ); ?>>Z → A</option>
				<option value="year_desc" <?php selected( $params['sort'], 'year_desc' ); ?>>Anno ↓ (più recente)</option>
				<option value="year_asc" <?php selected( $params['sort'], 'year_asc' ); ?>>Anno ↑ (più vecchio)</option>
			</select>
		</div>
		<div class="ntn-field ntn-field--reset <?php echo $has_filters ? '' : 'hidden'; ?>" id="ntn-reset-filters-container">
			<button type="button" id="ntn-reset-filters" class="ntn-reset">// Reset</button>
		</div>
	</div>

	<div id="ntn-catalog-grid" class="ntn-games">
		<?php
		if ( $query ) {
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					get_template_part( 'template-parts/content', 'game-card' );
				}
				// Passa max_pages al JS
				echo '<script>window.ntn_ssr_max_pages = ' . intval( $query->max_num_pages ) . ';</script>';
			} else {
				echo '<div class="ntn-empty col-span-full"><span>' . esc_html__( 'VUOTO', 'ntn-journal' ) . '</span><p>' . esc_html__( 'Nessun videogioco corrisponde ai criteri di ricerca.', 'ntn-journal' ) . '</p></div>';
				echo '<script>window.ntn_ssr_max_pages = 1;</script>';
			}
			wp_reset_postdata();
		} else {
			echo '<p>' . esc_html__( 'Errore: funzione di rendering non trovata.', 'ntn-journal' ) . '</p>';
		}
		?>
	</div>

	<!-- Infinite Scroll Sentinel -->
	<div id="ntn-catalog-sentinel" class="ntn-sentinel">
		<div class="ntn-loader hidden w-3 h-3 bg-red-600 animate-pulse"></div>
	</div>

</div>
</main>

<?php get_footer(); ?>
