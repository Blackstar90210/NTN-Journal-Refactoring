<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Scheda "Redazione" (v3.18.0): gruppi e persone della pagina Redazione si governano solo da qui.
 *
 * Gruppi da riordinare (trascinandoli o con le frecce, anche da tastiera), rinominare ed eliminare;
 * persone da aggiungere e togliere; ordine dentro i gruppi. I movimenti li gestisce admin-screen.js;
 * il salvataggio legge i campi nascosti nell'ordine in cui stanno nella pagina.
 * Riferimento visivo: mockup, pagina "Amministrazione", scheda Redazione.
 */
if ( ! function_exists( 'ntn_core_team_page' ) ) {
	echo '<p>Modulo della Redazione non disponibile.</p>';
	return;
}

$ntn_page = ntn_core_team_page();
if ( ! $ntn_page ) :
	?>
	<div class="nadm-panel">
		<div class="nadm-sec"><h2>// Gruppi della redazione</h2></div>
		<div class="nadm-box">
			<div class="nadm-box-h"><span>// Prima crea la pagina</span></div>
			<div class="nadm-box-b">
				<p class="nadm-note">Non c'è ancora una pagina con il modello "Redazione". Crea una pagina (per esempio "La nostra redazione"), alla voce <b>Template</b> scegli <b>Redazione</b> e pubblicala: poi torna qui per creare i gruppi.</p>
				<div class="nadm-actions"><a class="nadm-btn is-solid" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>">Crea la pagina</a></div>
			</div>
		</div>
	</div>
	<?php
	return;
endif;

$ntn_groups = ntn_core_team_groups( (int) $ntn_page->ID );
$ntn_order  = ntn_core_team_order_mode();

// Persone di ogni gruppo, nell'ordine salvato (a mano) o alfabetico
$ntn_members = array_fill_keys( $ntn_groups, array() );
foreach ( get_users(
	array(
		'meta_key' => '_ntn_team_group',
		'fields'   => array( 'ID', 'display_name' ),
	)
) as $ntn_user ) { // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- poche persone.
	$ntn_group = (string) get_user_meta( $ntn_user->ID, '_ntn_team_group', true );
	if ( isset( $ntn_members[ $ntn_group ] ) ) {
		$ntn_members[ $ntn_group ][] = array(
			'id'    => (int) $ntn_user->ID,
			'name'  => (string) $ntn_user->display_name,
			'order' => (int) get_user_meta( $ntn_user->ID, '_ntn_team_order', true ),
			'posts' => (int) count_user_posts( $ntn_user->ID, 'post', true ),
		);
	}
}
foreach ( $ntn_members as $ntn_group => $ntn_people ) {
	usort(
		$ntn_people,
		static function ( $a, $b ) {
			return $a['order'] !== $b['order'] ? $a['order'] <=> $b['order'] : strnatcasecmp( $a['name'], $b['name'] );
		}
	);
	$ntn_members[ $ntn_group ] = $ntn_people;
}

// Chi può comparire: chi scrive sul sito
$ntn_writers = get_users(
	array(
		'capability' => 'edit_posts',
		'orderby'    => 'display_name',
		'fields'     => array( 'ID', 'display_name' ),
	)
);

/**
 * Una persona dentro un gruppo (anche come modello per admin-screen.js).
 */
$ntn_person_row = static function ( $id, $name, $posts ) {
	$avatar = function_exists( 'ntn_journal_author_avatar' ) ? ntn_journal_author_avatar( $id, $name, 72 ) : '';
	?>
	<div class="nadm-person" data-person="<?php echo esc_attr( (string) $id ); ?>">
		<span class="nadm-av" aria-hidden="true"><?php echo $avatar; // phpcs:ignore WordPress.Security.EscapeOutput -- markup già sanificato dalla funzione del tema. ?></span>
		<b><?php echo esc_html( $name ); ?></b>
		<span class="nadm-m"><?php echo esc_html( sprintf( 1 === $posts ? '%s articolo' : '%s articoli', number_format_i18n( $posts ) ) ); ?></span>
		<span class="nadm-tools">
			<button type="button" data-nadm="person-up" aria-label="<?php echo esc_attr( 'Sposta su ' . $name ); ?>">↑</button>
			<button type="button" data-nadm="person-down" aria-label="<?php echo esc_attr( 'Sposta giù ' . $name ); ?>">↓</button>
			<button type="button" class="is-x" data-nadm="person-remove" aria-label="<?php echo esc_attr( 'Togli ' . $name ); ?>">✕</button>
		</span>
		<input type="hidden" data-nadm-field="people" value="<?php echo esc_attr( (string) $id ); ?>">
	</div>
	<?php
};

/**
 * Un gruppo con le sue persone.
 */
$ntn_group_block = static function ( $key, $name, $people ) use ( $ntn_person_row, $ntn_writers ) {
	?>
	<div class="nadm-grp" data-group="<?php echo esc_attr( $key ); ?>" draggable="true">
		<div class="nadm-grp-h">
			<span class="nadm-grip" aria-hidden="true" title="Trascina per spostare">⋮⋮</span>
			<input type="hidden" name="ntn_team_key[]" value="<?php echo esc_attr( $key ); ?>">
			<input type="text" name="ntn_team_name[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $name ); ?>" aria-label="Nome del gruppo">
			<span class="nadm-tools">
				<button type="button" data-nadm="group-up" aria-label="Sposta su il gruppo">↑</button>
				<button type="button" data-nadm="group-down" aria-label="Sposta giù il gruppo">↓</button>
				<button type="button" class="is-x" data-nadm="group-remove" aria-label="Elimina il gruppo">✕</button>
			</span>
		</div>
		<div class="nadm-people">
			<?php foreach ( $people as $person ) : ?>
				<?php $ntn_person_row( $person['id'], $person['name'], $person['posts'] ); ?>
			<?php endforeach; ?>
		</div>
		<div class="nadm-add">
			<label class="screen-reader-text" for="ntn_add_<?php echo esc_attr( $key ); ?>">Aggiungi persona</label>
			<select id="ntn_add_<?php echo esc_attr( $key ); ?>" data-nadm-add>
				<option value="">Aggiungi persona…</option>
				<?php foreach ( $ntn_writers as $writer ) : ?>
					<option value="<?php echo esc_attr( (string) $writer->ID ); ?>" data-posts="<?php echo esc_attr( (string) count_user_posts( $writer->ID, 'post', true ) ); ?>"><?php echo esc_html( $writer->display_name ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="button" class="nadm-btn is-sm" data-nadm="person-add">Aggiungi</button>
		</div>
	</div>
	<?php
};
?>
<form method="post" action="" class="nadm-panel" id="nadm-team-form">
	<?php wp_nonce_field( 'ntn_team_save', 'ntn_team_save_nonce' ); ?>
	<input type="hidden" name="ntn_team_page" value="<?php echo esc_attr( (string) $ntn_page->ID ); ?>">

	<div class="nadm-sec"><h2>// Gruppi della redazione</h2><a class="nadm-aside" href="<?php echo esc_url( (string) get_permalink( $ntn_page ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( 'Pagina: ' . wp_parse_url( (string) get_permalink( $ntn_page ), PHP_URL_PATH ) ); ?> ↗</a></div>

	<div class="nadm-actions">
		<label class="nadm-fl" for="ntn_team_order" style="font-family:var(--nadm-mono);font-size:9px;letter-spacing:.1em;text-transform:uppercase;color:var(--nadm-faint)">Ordine dentro i gruppi</label>
		<select id="ntn_team_order" name="ntn_team_order">
			<option value="alpha" <?php selected( $ntn_order, 'alpha' ); ?>>Alfabetico</option>
			<option value="manual" <?php selected( $ntn_order, 'manual' ); ?>>A mano (con le frecce)</option>
			<option value="posts" <?php selected( $ntn_order, 'posts' ); ?>>Per numero di articoli</option>
		</select>
	</div>

	<div id="nadm-groups">
		<?php
		$ntn_i = 0;
		foreach ( $ntn_members as $ntn_group => $ntn_people ) {
			$ntn_group_block( 'g' . ( $ntn_i++ ), $ntn_group, $ntn_people );
		}
		?>
	</div>
	<?php if ( empty( $ntn_groups ) ) : ?>
		<p class="nadm-note" id="nadm-no-groups">Nessun gruppo: aggiungine uno qui sotto (per esempio Fondatori).</p>
	<?php endif; ?>

	<div class="nadm-box">
		<div class="nadm-box-h"><span>// Nuovo gruppo</span></div>
		<div class="nadm-add">
			<label class="screen-reader-text" for="nadm-new-group">Nome del nuovo gruppo</label>
			<input type="text" id="nadm-new-group" placeholder="Nome del gruppo, per esempio Collaboratori" style="min-width:260px">
			<button type="button" class="nadm-btn is-sm" data-nadm="group-add">Aggiungi il gruppo</button>
		</div>
	</div>

	<p class="nadm-note">Una persona sta in un solo gruppo: se la aggiungi a un altro, si sposta. Chi non è in nessun gruppo non compare nella pagina. Le modifiche valgono dopo "Salva la redazione".</p>
	<div class="nadm-actions"><button type="submit" class="nadm-btn is-solid">Salva la redazione</button></div>

	<template id="nadm-tpl-group"><?php $ntn_group_block( '__KEY__', '', array() ); ?></template>
	<template id="nadm-tpl-person"><?php $ntn_person_row( 0, '__NAME__', 0 ); ?></template>
</form>
