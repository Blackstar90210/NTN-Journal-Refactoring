<?php
/**
 * Custom Post Type: Comunicati Stampa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Registrazione Custom Post Type
function ntn_journal_register_cpt_comunicato() {
	$labels = array(
		'name'                  => _x( 'Comunicati Stampa', 'Post Type General Name', 'ntn-journal-core' ),
		'singular_name'         => _x( 'Comunicato Stampa', 'Post Type Singular Name', 'ntn-journal-core' ),
		'menu_name'             => __( 'Comunicati Stampa', 'ntn-journal-core' ),
		'name_admin_bar'        => __( 'Comunicato Stampa', 'ntn-journal-core' ),
		'archives'              => __( 'Archivio Comunicati', 'ntn-journal-core' ),
		'attributes'            => __( 'Attributi Comunicato', 'ntn-journal-core' ),
		'parent_item_colon'     => __( 'Comunicato Genitore:', 'ntn-journal-core' ),
		'all_items'             => __( 'Tutti i Comunicati', 'ntn-journal-core' ),
		'add_new_item'          => __( 'Aggiungi Nuovo Comunicato', 'ntn-journal-core' ),
		'add_new'               => __( 'Aggiungi Nuovo', 'ntn-journal-core' ),
		'new_item'              => __( 'Nuovo Comunicato', 'ntn-journal-core' ),
		'edit_item'             => __( 'Modifica Comunicato', 'ntn-journal-core' ),
		'update_item'           => __( 'Aggiorna Comunicato', 'ntn-journal-core' ),
		'view_item'             => __( 'Vedi Comunicato', 'ntn-journal-core' ),
		'view_items'            => __( 'Vedi Comunicati', 'ntn-journal-core' ),
		'search_items'          => __( 'Cerca Comunicato', 'ntn-journal-core' ),
		'not_found'             => __( 'Non Trovato', 'ntn-journal-core' ),
		'not_found_in_trash'    => __( 'Non Trovato nel Cestino', 'ntn-journal-core' ),
		'featured_image'        => __( 'Immagine in evidenza', 'ntn-journal-core' ),
		'set_featured_image'    => __( 'Imposta immagine in evidenza', 'ntn-journal-core' ),
		'remove_featured_image' => __( 'Rimuovi immagine in evidenza', 'ntn-journal-core' ),
		'use_featured_image'    => __( 'Usa come immagine in evidenza', 'ntn-journal-core' ),
		'insert_into_item'      => __( 'Inserisci nel comunicato', 'ntn-journal-core' ),
		'uploaded_to_this_item' => __( 'Caricato in questo comunicato', 'ntn-journal-core' ),
		'items_list'            => __( 'Lista Comunicati', 'ntn-journal-core' ),
		'items_list_navigation' => __( 'Navigazione lista comunicati', 'ntn-journal-core' ),
		'filter_items_list'     => __( 'Filtra lista comunicati', 'ntn-journal-core' ),
	);
	$args   = array(
		'label'               => __( 'Comunicato Stampa', 'ntn-journal-core' ),
		'description'         => __( 'Comunicati Stampa ufficiali da aziende e PR', 'ntn-journal-core' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'taxonomies'          => array( 'category', 'post_tag' ), // Usa stesse categorie per facilità
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 5,
		'menu_icon'           => 'dashicons-megaphone',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => 'comunicato-stampa', // Slug dell'archivio (uguale per coerenza)
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
		'show_in_rest'        => true, // Abilita Gutenberg
		'rewrite'             => array( 'slug' => 'comunicato-stampa' ), // Slug singolo post
	);
	register_post_type( 'comunicato_stampa', $args );
}
add_action( 'init', 'ntn_journal_register_cpt_comunicato', 0 );


// 2. Meta Boxes per Comunicati Stampa
function ntn_journal_add_comunicato_metaboxes() {
	add_meta_box(
		'ntn_comunicato_meta',
		__( 'Dati Comunicato Stampa', 'ntn-journal-core' ),
		'ntn_journal_comunicato_meta_callback',
		'comunicato_stampa',
		'normal',
		'high'
	);
}
if ( is_admin() ) {
	add_action( 'add_meta_boxes', 'ntn_journal_add_comunicato_metaboxes' );
}

function ntn_journal_comunicato_meta_callback( $post ) {
	wp_nonce_field( 'ntn_comunicato_save_meta', 'ntn_comunicato_nonce' );

	$publisher   = get_post_meta( $post->ID, 'ntn_press_publisher', true );
	$developer   = get_post_meta( $post->ID, 'ntn_press_developer', true );
	$agency      = get_post_meta( $post->ID, 'ntn_press_agency', true );
	$source_link = get_post_meta( $post->ID, 'ntn_press_source_link', true );
	$embargo     = get_post_meta( $post->ID, 'ntn_press_embargo', true );
	$assets      = get_post_meta( $post->ID, 'ntn_press_assets', true );
	$noindex     = get_post_meta( $post->ID, 'ntn_press_noindex', true );

	?>
	<div style="margin-bottom: 15px;">
		<label for="ntn_press_publisher" style="display:block;font-weight:bold;margin-bottom:5px;"><?php esc_html_e( 'Editore (Publisher)', 'ntn-journal-core' ); ?></label>
		<input type="text" id="ntn_press_publisher" name="ntn_press_publisher" value="<?php echo esc_attr( $publisher ); ?>" style="width:100%;" placeholder="Es. Sony Interactive Entertainment" />
	</div>

	<div style="margin-bottom: 15px;">
		<label for="ntn_press_developer" style="display:block;font-weight:bold;margin-bottom:5px;"><?php esc_html_e( 'Studio di Sviluppo (Developer)', 'ntn-journal-core' ); ?></label>
		<input type="text" id="ntn_press_developer" name="ntn_press_developer" value="<?php echo esc_attr( $developer ); ?>" style="width:100%;" placeholder="Es. Naughty Dog" />
	</div>

	<div style="margin-bottom: 15px;">
		<label for="ntn_press_agency" style="display:block;font-weight:bold;margin-bottom:5px;"><?php esc_html_e( 'Agenzia PR', 'ntn-journal-core' ); ?></label>
		<input type="text" id="ntn_press_agency" name="ntn_press_agency" value="<?php echo esc_attr( $agency ); ?>" style="width:100%;" placeholder="Es. Tiziana Pollio PR" />
	</div>

	
	<div style="margin-bottom: 15px;">
		<label for="ntn_press_source_link" style="display:block;font-weight:bold;margin-bottom:5px;"><?php esc_html_e( 'Link Fonte Ufficiale (URL)', 'ntn-journal-core' ); ?></label>
		<input type="url" id="ntn_press_source_link" name="ntn_press_source_link" value="<?php echo esc_attr( $source_link ); ?>" style="width:100%;" placeholder="Es. https://blog.playstation.com/..." />
	</div>

	<div style="margin-bottom: 15px;">
		<label for="ntn_press_embargo" style="display:block;font-weight:bold;margin-bottom:5px;"><?php esc_html_e( 'Data/Ora Embargo (Testo Libero)', 'ntn-journal-core' ); ?></label>
		<input type="text" id="ntn_press_embargo" name="ntn_press_embargo" value="<?php echo esc_attr( $embargo ); ?>" style="width:100%;" placeholder="Es. 15 Novembre 2024, ore 15:00" />
		<p class="description">Nota: questo è solo un pro-memoria testuale per la redazione.</p>
	</div>

	<div style="margin-bottom: 15px;">
		<label for="ntn_press_assets" style="display:block;font-weight:bold;margin-bottom:5px;"><?php esc_html_e( 'Materiale Stampa / Press Kit (URL)', 'ntn-journal-core' ); ?></label>
		<input type="url" id="ntn_press_assets" name="ntn_press_assets" value="<?php echo esc_attr( $assets ); ?>" style="width:100%;" placeholder="Es. Google Drive o Dropbox URL" />
	</div>

	<div style="margin-bottom: 15px;">
		<label style="font-weight:bold;">
			<input type="checkbox" name="ntn_press_noindex" value="1" <?php checked( $noindex, '1' ); ?> />
			<?php esc_html_e( 'Applica tag "noindex" a questo comunicato per evitare penalizzazioni SEO (da usare se è un copia-incolla esatto).', 'ntn-journal-core' ); ?>
		</label>
	</div>
	<?php
}

function ntn_journal_save_comunicato_meta( $post_id ) {
	if ( ! isset( $_POST['ntn_comunicato_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ntn_comunicato_nonce'] ) ), 'ntn_comunicato_save_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['ntn_press_publisher'] ) ) {
		update_post_meta( $post_id, 'ntn_press_publisher', sanitize_text_field( wp_unslash( $_POST['ntn_press_publisher'] ) ) );
	}

	if ( isset( $_POST['ntn_press_developer'] ) ) {
		update_post_meta( $post_id, 'ntn_press_developer', sanitize_text_field( wp_unslash( $_POST['ntn_press_developer'] ) ) );
	}

	if ( isset( $_POST['ntn_press_agency'] ) ) {
		update_post_meta( $post_id, 'ntn_press_agency', sanitize_text_field( wp_unslash( $_POST['ntn_press_agency'] ) ) );
	}

	if ( isset( $_POST['ntn_press_source_link'] ) ) {
		update_post_meta( $post_id, 'ntn_press_source_link', sanitize_url( wp_unslash( $_POST['ntn_press_source_link'] ) ) );
	}

	if ( isset( $_POST['ntn_press_embargo'] ) ) {
		update_post_meta( $post_id, 'ntn_press_embargo', sanitize_text_field( wp_unslash( $_POST['ntn_press_embargo'] ) ) );
	}

	if ( isset( $_POST['ntn_press_assets'] ) ) {
		update_post_meta( $post_id, 'ntn_press_assets', sanitize_url( wp_unslash( $_POST['ntn_press_assets'] ) ) );
	}

	if ( isset( $_POST['ntn_press_noindex'] ) ) {
		update_post_meta( $post_id, 'ntn_press_noindex', '1' );
	} else {
		delete_post_meta( $post_id, 'ntn_press_noindex' );
	}
}
add_action( 'save_post_comunicato_stampa', 'ntn_journal_save_comunicato_meta' );


// 3. Integrazione Feed
// Aggiunge i comunicati stampa al feed principale (RSS e Homepage)
function ntn_journal_add_cpt_to_feed( $query ) {
	if ( ! is_admin() && $query->is_main_query() ) {
		// Aggiungiamo i comunicati stampa se siamo nel feed RSS o nella homepage
		if ( $query->is_feed() || $query->is_home() ) {
			$post_types = $query->get( 'post_type' );
			if ( empty( $post_types ) ) {
				$post_types = array( 'post' );
			} else {
				$post_types = (array) $post_types;
			}
			if ( ! in_array( 'comunicato_stampa', $post_types, true ) ) {
				$post_types[] = 'comunicato_stampa';
				$query->set( 'post_type', $post_types );
			}
		}
	}
}
add_action( 'pre_get_posts', 'ntn_journal_add_cpt_to_feed' );

// Sostituisce l'autore nel Feed RSS con il Publisher se è un comunicato stampa
function ntn_journal_rss_publisher_author( $author_name ) {
	if ( ! is_feed() || get_post_type() !== 'comunicato_stampa' ) {
		return $author_name;
	}

	$publisher = get_post_meta( get_the_ID(), 'ntn_press_publisher', true );
	$agency    = get_post_meta( get_the_ID(), 'ntn_press_agency', true );
	$developer = get_post_meta( get_the_ID(), 'ntn_press_developer', true );

	if ( ! empty( $publisher ) ) {
		return sanitize_text_field( $publisher );
	} elseif ( ! empty( $developer ) ) {
		return sanitize_text_field( $developer );
	} elseif ( ! empty( $agency ) ) {
		return sanitize_text_field( $agency );
	}

	return $author_name;
}
add_filter( 'the_author', 'ntn_journal_rss_publisher_author' );
add_filter( 'get_the_author_display_name', 'ntn_journal_rss_publisher_author' );
