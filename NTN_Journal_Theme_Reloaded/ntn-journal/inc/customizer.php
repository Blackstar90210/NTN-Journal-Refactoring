<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Registrazione opzioni Customizer per il tema
 */
function ntn_journal_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'ntn_journal_settings', array(
		'title'    => __( 'Impostazioni Tema', 'ntn-journal' ),
		'priority' => 30,
	) );

	// Testo Banner Superiore
	$wp_customize->add_setting( 'ntn_journal_top_banner_text', array(
		'default'           => '// NOME SITO V1.00',
		'sanitize_callback' => 'sanitize_text_field',
	) );

	$wp_customize->add_control( 'ntn_journal_top_banner_text', array(
		'label'       => __( 'Testo Barra Superiore', 'ntn-journal' ),
		'description' => __( 'Il testo in stile codice nella barra nera in alto (es. // NOME SITO V1.00).', 'ntn-journal' ),
		'section'     => 'ntn_journal_settings',
		'type'        => 'text',
	) );

	// Immagine Logo Principale
	$wp_customize->add_setting( 'ntn_journal_main_logo_image', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );

	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'ntn_journal_main_logo_image', array(
		'label'       => __( 'Immagine Logo Principale', 'ntn-journal' ),
		'description' => __( 'Carica un\'immagine per sostituire il testo del logo principale.', 'ntn-journal' ),
		'section'     => 'ntn_journal_settings',
	) ) );

	// Nome Sito
	$wp_customize->add_setting( 'ntn_journal_main_logo_text', array(
		'default'           => 'NOME-SITO',
		'sanitize_callback' => 'sanitize_text_field',
	) );

	$wp_customize->add_control( 'ntn_journal_main_logo_text', array(
		'label'       => __( 'Nome Sito', 'ntn-journal' ),
		'description' => __( 'Il testo del logo grande. La prima lettera verrà automaticamente colorata di rosso (es. NOME-SITO).', 'ntn-journal' ),
		'section'     => 'ntn_journal_settings',
		'type'        => 'text',
	) );

	// ==========================================
	// SEZIONE: Identità del Sito (Spunte indipendenti Titolo e Motto)
	// ==========================================
	// Rimozione del controllo cumulativo predefinito di WordPress "display_header_text"
	if ( $wp_customize->get_control( 'display_header_text' ) ) {
		$wp_customize->remove_control( 'display_header_text' );
	}

	// Spunta per mostrare/nascondere il Titolo del Sito
	$wp_customize->add_setting( 'ntn_journal_show_site_title', array(
		'default'           => true,
		'sanitize_callback' => 'ntn_journal_sanitize_checkbox',
	) );

	$wp_customize->add_control( 'ntn_journal_show_site_title', array(
		'label'       => __( 'Mostra il titolo del sito', 'ntn-journal' ),
		'description' => __( 'Abilita o disabilita la visualizzazione del titolo o logo del sito nell\'intestazione.', 'ntn-journal' ),
		'section'     => 'title_tagline',
		'type'        => 'checkbox',
		'priority'    => 15,
	) );

	// Spunta per mostrare/nascondere il Motto (Tagline)
	$wp_customize->add_setting( 'ntn_journal_show_site_tagline', array(
		'default'           => true,
		'sanitize_callback' => 'ntn_journal_sanitize_checkbox',
	) );

	$wp_customize->add_control( 'ntn_journal_show_site_tagline', array(
		'label'       => __( 'Mostra il motto', 'ntn-journal' ),
		'description' => __( 'Abilita o disabilita la visualizzazione del motto del sito nell\'intestazione.', 'ntn-journal' ),
		'section'     => 'title_tagline',
		'type'        => 'checkbox',
		'priority'    => 25,
	) );

	$wp_customize->add_setting( 'ntn_journal_enable_related_posts', array(
		'default'           => true,
		'sanitize_callback' => 'ntn_journal_sanitize_checkbox',
	) );

	$wp_customize->add_control( 'ntn_journal_enable_related_posts', array(
		'label'       => __( 'Abilita Articoli Consigliati nel testo', 'ntn-journal' ),
		'description' => __( 'Mostra dinamicamente un riquadro con 2 articoli suggeriti (basati sui tag) all\'interno degli articoli.', 'ntn-journal' ),
		'section'     => 'ntn_journal_settings',
		'type'        => 'checkbox',
	) );

	$wp_customize->add_setting( 'ntn_journal_enable_bottom_related_posts', array(
		'default'           => true,
		'sanitize_callback' => 'ntn_journal_sanitize_checkbox',
	) );

	$wp_customize->add_control( 'ntn_journal_enable_bottom_related_posts', array(
		'label'       => __( 'Abilita Articoli Correlati (Fine Articolo)', 'ntn-journal' ),
		'description' => __( 'Mostra 3 articoli correlati con immagine sotto al contenuto dell\'articolo.', 'ntn-journal' ),
		'section'     => 'ntn_journal_settings',
		'type'        => 'checkbox',
	) );

	$wp_customize->add_setting( 'ntn_journal_enable_watermark', array(
		'default'           => true,
		'sanitize_callback' => 'ntn_journal_sanitize_checkbox',
	) );

	$wp_customize->add_control( 'ntn_journal_enable_watermark', array(
		'label'       => __( 'Mostra Filigrana sulle Immagini in Evidenza', 'ntn-journal' ),
		'description' => __( 'Sovrappone un riquadro nero tecnico con il nome dell\'immagine sulle copertine degli articoli.', 'ntn-journal' ),
		'section'     => 'ntn_journal_settings',
		'type'        => 'checkbox',
	) );

	// Home: fascia dell'Hub di sezione
	$ntn_hub_choices = array( '' => __( '— Nessuna fascia —', 'ntn-journal' ) );
	foreach ( get_categories( array( 'hide_empty' => false ) ) as $ntn_cat ) {
		$ntn_hub_choices[ $ntn_cat->slug ] = $ntn_cat->name;
	}

	$wp_customize->add_setting( 'ntn_journal_front_hub_category', array(
		'default'           => 'sviluppatori-italiani-di-videogiochi',
		'sanitize_callback' => 'sanitize_title',
	) );

	$wp_customize->add_control( 'ntn_journal_front_hub_category', array(
		'label'       => __( 'Home: categoria della fascia Hub', 'ntn-journal' ),
		'description' => __( 'La sezione da mettere in evidenza nella fascia a matrice di punti della Home.', 'ntn-journal' ),
		'section'     => 'ntn_journal_settings',
		'type'        => 'select',
		'choices'     => $ntn_hub_choices,
	) );

	$wp_customize->add_setting( 'ntn_journal_front_hub_map', array(
		'default'           => true,
		'sanitize_callback' => 'ntn_journal_sanitize_checkbox',
	) );

	$wp_customize->add_control( 'ntn_journal_front_hub_map', array(
		'label'       => __( 'Home: cartina dell\'Italia nella fascia Hub', 'ntn-journal' ),
		'description' => __( 'Mostra l\'Italia a puntini e l\'etichetta "Made in Italy".', 'ntn-journal' ),
		'section'     => 'ntn_journal_settings',
		'type'        => 'checkbox',
	) );


	// ==========================================
	// SEZIONE: Canali Social
	// ==========================================
	$wp_customize->add_section( 'ntn_journal_social_section', array(
		'title'    => __( 'Opzioni Social', 'ntn-journal' ),
		'priority' => 32,
	) );

	$wp_customize->add_setting( 'ntn_journal_enable_share_buttons', array(
		'default'           => true,
		'sanitize_callback' => 'ntn_journal_sanitize_checkbox',
	) );

	$wp_customize->add_control( 'ntn_journal_enable_share_buttons', array(
		'label'       => __( 'Mostra Pulsanti Condivisione a fine articolo', 'ntn-journal' ),
		'description' => __( 'Mostra i pulsanti nativi per condividere l\'articolo sui social (senza tracker JS).', 'ntn-journal' ),
		'section'     => 'ntn_journal_social_section',
		'type'        => 'checkbox',
		'priority'    => 1,
	) );

	// Google Feed Box
	$wp_customize->add_setting( 'ntn_journal_enable_google_feed', array(
		'default'           => true,
		'sanitize_callback' => 'ntn_journal_sanitize_checkbox',
	) );

	$wp_customize->add_control( 'ntn_journal_enable_google_feed', array(
		'label'       => __( 'Mostra Blocco Google Feed', 'ntn-journal' ),
		'description' => __( 'Mostra il banner dedicato // GOOGLE FEED a fine articolo.', 'ntn-journal' ),
		'section'     => 'ntn_journal_social_section',
		'type'        => 'checkbox',
		'priority'    => 2,
	) );

	$wp_customize->add_setting( 'ntn_google_feed_url', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );

	$wp_customize->add_control( 'ntn_google_feed_url', array(
		'label'       => __( 'URL Google Feed / Publisher', 'ntn-journal' ),
		'description' => __( 'Inserisci l\'URL della pubblicazione su Google News / Feed per il pulsante AGGIUNGI.', 'ntn-journal' ),
		'section'     => 'ntn_journal_social_section',
		'type'        => 'url',
		'priority'    => 3,
	) );

	$social_networks = array(
		'telegram'  => 'Telegram',
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'x'         => 'X (Twitter)',
		'bluesky'   => 'BlueSky',
		'mastodon'  => 'Mastodon'
	);

	foreach ( $social_networks as $id => $label ) {
		$wp_customize->add_setting( 'ntn_social_' . $id, array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		) );
		$wp_customize->add_control( 'ntn_social_' . $id, array(
			'label'       => sprintf( __( 'URL %s', 'ntn-journal' ), $label ),
			'section'     => 'ntn_journal_social_section',
			'type'        => 'url',
		) );
	}

}
add_action( 'customize_register', 'ntn_journal_customize_register' );

function ntn_journal_sanitize_checkbox( $checked ) {
	return ( ( isset( $checked ) && true == $checked ) ? true : false );
}
