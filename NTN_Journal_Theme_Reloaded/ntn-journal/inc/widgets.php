<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Registra le aree widget (sidebar) del tema.
 *
 * In precedenza sidebar.php richiamava dynamic_sidebar( 'sidebar-1' ) ma
 * l'area non era mai stata registrata: risultato, la sidebar non compariva
 * mai in Aspetto > Widget e non veniva mai mostrata in output.
 */
function ntn_journal_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar Principale', 'ntn-journal' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Area widget mostrata a fianco dell\'elenco articoli.', 'ntn-journal' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s mb-8">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="ntn-sec-head mb-3"><span class="ntn-sec-title">',
			'after_title'   => '</span></h2>',
		)
	);
	register_sidebar(
		array(
			'name'          => esc_html__( 'Twitch Live (Homepage)', 'ntn-journal' ),
			'id'            => 'sidebar-home-twitch',
			'description'   => esc_html__( 'Inserisci qui un blocco "HTML Personalizzato" con il codice iframe di Twitch. Se vuoto, l\'intero blocco Twitch scompare dalla Homepage.', 'ntn-journal' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s ntn-video-widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<span class="hidden">',
			'after_title'   => '</span>',
		)
	);
	register_sidebar(
		array(
			'name'          => esc_html__( 'YouTube Hub (Homepage)', 'ntn-journal' ),
			'id'            => 'sidebar-home-youtube',
			'description'   => esc_html__( 'Inserisci qui un blocco "HTML Personalizzato" con il codice iframe del player di YouTube. Se vuoto, l\'intero blocco YouTube scompare dalla Homepage.', 'ntn-journal' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s ntn-video-widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<span class="hidden">',
			'after_title'   => '</span>',
		)
	);
}
add_action( 'widgets_init', 'ntn_journal_widgets_init' );
