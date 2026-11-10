<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Dashboard (v3.18.0): lo stato di ogni funzione con un numero e una riga, e il link alla scheda.
 * Riferimento visivo: mockup, pagina "Amministrazione", scheda Dashboard.
 */
$ntn_tab = static function ( $tab ) {
	return add_query_arg(
		array(
			'page' => 'ntn-journal-functions',
			'tab'  => $tab,
		),
		admin_url( 'admin.php' )
	);
};

// Hub accese e problemi del controllo copertura (in memoria finché le Hub non cambiano)
$ntn_hubs   = function_exists( 'ntn_core_category_hubs' ) ? ntn_core_category_hubs() : array();
$ntn_issues = 0;
if ( $ntn_hubs && function_exists( 'ntn_core_category_hub_coverage' ) ) {
	$ntn_key    = 'ntn_hub_cov_' . md5( (string) get_option( 'ntn_cat_hub_version', '0' ) );
	$ntn_issues = get_transient( $ntn_key );
	if ( false === $ntn_issues ) {
		$ntn_issues = 0;
		foreach ( $ntn_hubs as $ntn_hub ) {
			$ntn_issues += count( ntn_core_category_hub_coverage( $ntn_hub ) );
		}
		set_transient( $ntn_key, $ntn_issues, 10 * MINUTE_IN_SECONDS );
	}
}

// Redazione
$ntn_team_page = function_exists( 'ntn_core_team_page' ) ? ntn_core_team_page() : null;
$ntn_team      = ( $ntn_team_page && function_exists( 'ntn_core_get_team_data' ) ) ? ntn_core_get_team_data( (int) $ntn_team_page->ID ) : array(
	'groups' => array(),
	'count'  => 0,
);

// Recensioni
$ntn_reviews = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'     => 'rating',
				'value'   => '',
				'compare' => '!=',
			),
		), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- conteggio in amministrazione.
	)
);

// Comunicati
$ntn_press_count = wp_count_posts( 'comunicato_stampa' );
$ntn_press_last  = get_posts(
	array(
		'post_type'      => 'comunicato_stampa',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	)
);

// Scanner IGDB: giochi collegati
$ntn_games = get_terms(
	array(
		'taxonomy'   => 'post_tag',
		'hide_empty' => false,
		'fields'     => 'count',
		'meta_key'   => '_ntn_igdb_game_id',
	)
); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- conteggio in amministrazione.

// Redirect
$ntn_redirects = get_option( 'ntn_redirects_map', array() );

// Servizi API
$ntn_api = array();
if ( function_exists( 'ntn_core_api_services' ) ) {
	foreach ( ntn_core_api_services() as $ntn_service => $ntn_info ) {
		$ntn_api[ $ntn_service ] = array(
			'label'  => $ntn_info['label'],
			'status' => ntn_core_api_status_get( $ntn_service ),
		);
	}
}
$ntn_api_active = count(
	array_filter(
		$ntn_api,
		static function ( $s ) {
			return 'active' === $s['status']['state'];
		}
	)
);
?>
<div class="nadm-panel">
	<div class="nadm-sec"><h2>// Lo stato del sito</h2><span class="nadm-aside">Aggiornato ora</span></div>

	<div class="nadm-cells">
		<div class="nadm-cell">
			<span class="ntn-adm-k">// Hub</span>
			<span class="nadm-v"><?php echo esc_html( number_format_i18n( count( $ntn_hubs ) ) ); ?></span>
			<span class="nadm-s"><?php echo esc_html( $ntn_hubs ? 'Accese: ' . implode( ', ', array_map( 'ntn_core_category_hub_label', $ntn_hubs ) ) : 'Nessuna Hub accesa' ); ?></span>
			<?php if ( $ntn_issues ) : ?>
				<span class="nadm-s nadm-red"><?php echo esc_html( sprintf( 1 === (int) $ntn_issues ? '%d punto da sistemare nel controllo copertura' : '%d punti da sistemare nel controllo copertura', (int) $ntn_issues ) ); ?></span>
			<?php endif; ?>
			<a class="nadm-go" href="<?php echo esc_url( $ntn_tab( 'hub' ) ); ?>">Apri →</a>
		</div>

		<div class="nadm-cell">
			<span class="ntn-adm-k">// Redazione</span>
			<span class="nadm-v"><?php echo esc_html( number_format_i18n( (int) $ntn_team['count'] ) ); ?></span>
			<span class="nadm-s">
				<?php
				if ( ! $ntn_team_page ) {
					echo esc_html( 'Manca la pagina con il modello "Redazione"' );
				} else {
					$ntn_names = wp_list_pluck( $ntn_team['groups'], 'name' );
					echo esc_html( $ntn_names ? sprintf( 'Firme in %d gruppi: %s', count( $ntn_names ), implode( ', ', $ntn_names ) ) : 'Nessuna firma nei gruppi' );
				}
				?>
			</span>
			<a class="nadm-go" href="<?php echo esc_url( $ntn_tab( 'redazione' ) ); ?>">Apri →</a>
		</div>

		<div class="nadm-cell">
			<span class="ntn-adm-k">// Recensioni</span>
			<span class="nadm-v"><?php echo esc_html( number_format_i18n( (int) $ntn_reviews->found_posts ) ); ?></span>
			<span class="nadm-s"><?php echo esc_html( $ntn_reviews->posts ? 'Ultima: ' . wp_trim_words( get_the_title( $ntn_reviews->posts[0] ), 6, '…' ) . ', ' . get_the_date( 'd.m.Y', $ntn_reviews->posts[0] ) : 'Nessuna recensione' ); ?></span>
			<a class="nadm-go" href="<?php echo esc_url( $ntn_tab( 'reviews' ) ); ?>">Apri →</a>
		</div>

		<div class="nadm-cell">
			<span class="ntn-adm-k">// Servizi API</span>
			<span class="nadm-v"><?php echo esc_html( $ntn_api_active . '/' . count( $ntn_api ) ); ?></span>
			<span class="nadm-s">
				<?php foreach ( $ntn_api as $ntn_item ) : ?>
					<?php $ntn_state = $ntn_item['status']['state']; ?>
					<span class="nadm-st<?php echo 'error' === $ntn_state ? ' is-error' : ( 'active' === $ntn_state ? '' : ' is-off' ); ?>"><?php echo esc_html( $ntn_item['label'] . ': ' . $ntn_item['status']['label'] ); ?></span><br>
				<?php endforeach; ?>
			</span>
			<a class="nadm-go" href="<?php echo esc_url( $ntn_tab( 'api-services' ) ); ?>">Apri →</a>
		</div>

		<div class="nadm-cell">
			<span class="ntn-adm-k">// Scanner IGDB</span>
			<span class="nadm-v"><?php echo esc_html( number_format_i18n( is_numeric( $ntn_games ) ? (int) $ntn_games : 0 ) ); ?></span>
			<span class="nadm-s"><?php echo esc_html( 'Giochi collegati · scanner ' . ( 'yes' === get_option( 'ntn_igdb_scanner_active', 'yes' ) ? 'acceso' : 'spento' ) ); ?></span>
			<a class="nadm-go" href="<?php echo esc_url( $ntn_tab( 'igdb' ) ); ?>">Apri →</a>
		</div>

		<div class="nadm-cell">
			<span class="ntn-adm-k">// Comunicati</span>
			<span class="nadm-v"><?php echo esc_html( number_format_i18n( isset( $ntn_press_count->publish ) ? (int) $ntn_press_count->publish : 0 ) ); ?></span>
			<span class="nadm-s"><?php echo esc_html( $ntn_press_last ? 'Ultimo: ' . get_the_date( 'd.m.Y', $ntn_press_last[0] ) : 'Nessun comunicato' ); ?></span>
			<a class="nadm-go" href="<?php echo esc_url( $ntn_tab( 'press' ) ); ?>">Apri →</a>
		</div>

		<div class="nadm-cell">
			<span class="ntn-adm-k">// Breaking News</span>
			<?php $ntn_bn = 'yes' === get_option( 'ntn_breaking_news_active', 'no' ); ?>
			<span class="nadm-v<?php echo $ntn_bn ? ' nadm-red' : ''; ?>"><?php echo $ntn_bn ? 'On' : 'Off'; ?></span>
			<span class="nadm-s"><?php echo esc_html( $ntn_bn ? wp_trim_words( (string) get_option( 'ntn_breaking_news_text', '' ), 10, '…' ) : 'Nessun ticker in onda' ); ?></span>
			<a class="nadm-go" href="<?php echo esc_url( $ntn_tab( 'breaking-news' ) ); ?>">Apri →</a>
		</div>

		<div class="nadm-cell">
			<span class="ntn-adm-k">// Redirect</span>
			<span class="nadm-v"><?php echo esc_html( number_format_i18n( is_array( $ntn_redirects ) ? count( $ntn_redirects ) : 0 ) ); ?></span>
			<span class="nadm-s">Reindirizzamenti attivi</span>
			<a class="nadm-go" href="<?php echo esc_url( $ntn_tab( 'redirects' ) ); ?>">Apri →</a>
		</div>

		<div class="nadm-cell">
			<span class="ntn-adm-k">// Immagini</span>
			<?php
			$ntn_img = function_exists( 'ntn_core_image_report_dashboard_summary' ) ? ntn_core_image_report_dashboard_summary() : array(
				'count' => '—',
				'lines' => array( 'Strumenti non disponibili' ),
			);
			?>
			<span class="nadm-v"><?php echo esc_html( $ntn_img['count'] ); ?></span>
			<span class="nadm-s">
				<?php foreach ( $ntn_img['lines'] as $ntn_img_line ) : ?>
					<?php echo esc_html( $ntn_img_line ); ?><br>
				<?php endforeach; ?>
			</span>
			<a class="nadm-go" href="<?php echo esc_url( $ntn_tab( 'immagini' ) ); ?>">Apri →</a>
		</div>

		<div class="nadm-cell">
			<span class="ntn-adm-k">// SEO</span>
			<?php $ntn_schema = function_exists( 'ntn_core_schema_enabled' ) ? ntn_core_schema_enabled() : true; ?>
			<span class="nadm-v<?php echo $ntn_schema ? '' : ' nadm-red'; ?>"><?php echo $ntn_schema ? 'On' : 'Off'; ?></span>
			<span class="nadm-s"><?php echo esc_html( $ntn_schema ? 'Dati per Google accesi' : 'Dati per Google spenti (freno d\'emergenza tirato)' ); ?></span>
			<?php
			if ( function_exists( 'ntn_core_seo_thin_summary' ) ) :
				$ntn_thin = ntn_core_seo_thin_summary();
				?>
				<span class="nadm-s"><?php echo esc_html( sprintf( 'Nascoste a Google per pochi articoli: %1$s tag, %2$s categorie, %3$s Hub dei giochi', number_format_i18n( $ntn_thin['tags'] ), number_format_i18n( $ntn_thin['categories'] ), number_format_i18n( $ntn_thin['games'] ) ) ); ?></span>
			<?php endif; ?>
			<a class="nadm-go" href="<?php echo esc_url( $ntn_tab( 'seo' ) ); ?>">Apri →</a>
		</div>
	</div>
</div>
