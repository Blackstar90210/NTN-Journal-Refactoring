<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Impostazioni SEO Tab -->
<div class="card" style="padding: 20px; box-sizing: border-box; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; max-width: 100%;">
	<h2>Impostazioni SEO & Ottimizzazioni</h2>
	<p>Centralina di controllo per tutte le funzionalità SEO native di NTN Journal. Qui puoi accendere o spegnere gli schemi avanzati per evitare duplicati se usi plugin esterni (come Yoast, Rank Math, The SEO Framework).</p>
	
	<form method="post" action="">
		<?php wp_nonce_field( 'ntn_save_seo_settings', 'ntn_seo_settings_nonce' ); ?>
		
		<table class="form-table" role="presentation">
			<tbody>
				<!-- META TAGS -->
				<tr style="border-bottom: 1px solid #eee;">
					<th scope="row">
						<strong>Meta Tag e Open Graph Base</strong>
						<p class="description">Gestisce tag base (og:title, og:image, description).</p>
					</th>
					<td style="padding-bottom: 20px;">
						<label style="display: block; margin-bottom: 10px;">
							<input type="checkbox" name="ntn_seo_enable_base" value="1" <?php checked( get_option('ntn_seo_enable_base', get_theme_mod('ntn_journal_enable_base_seo', true) ? 'yes' : 'no'), 'yes' ); ?>>
							<strong>Attiva Meta Tag di Base & Breadcrumb JSON-LD</strong>
						</label>
						<div class="notice notice-warning inline" style="margin: 0 0 15px 0; padding: 10px;">
							<p style="margin: 0;"><strong>Avviso Conflitti:</strong> Se hai installato Yoast, Rank Math o simili, questa opzione <strong>si disattiva automaticamente nel codice</strong>, anche se è spuntata qui, per evitare duplicati nei meta tag.</p>
						</div>
						
						<label style="display: block; margin-bottom: 10px;">
							<input type="checkbox" name="ntn_seo_cleanup_og_duplicates" value="1" <?php checked( get_option('ntn_seo_cleanup_og_duplicates', 'yes'), 'yes' ); ?>>
							<strong>Pulizia conflitti Open Graph (Terze Parti)</strong>
						</label>
						<p class="description" style="margin-top: 0;">Blocca plugin come Jetpack o estensioni social dal generare <code>og:image</code> duplicati quando i Meta Tag di Base di NTN Journal sono attivi.</p>
						<div class="notice notice-warning inline" style="margin: 10px 0 15px 0; padding: 10px;">
							<p style="margin: 0;"><strong>Avviso Conflitti:</strong> Se usi plugin SEO dedicati (es. The SEO Framework, Yoast, Rank Math), ti consigliamo di <strong>lasciare questa opzione disattivata</strong> (senza spunta) per evitare interferenze, in quanto questi plugin gestiscono già autonomamente l'Open Graph.</p>
						</div>
					</td>
				</tr>
				
				<!-- REVIEW SCHEMA -->
				<tr style="border-bottom: 1px solid #eee;">
					<th scope="row">
						<strong>Schema Markup: tutti i dati per Google</strong>
						<p class="description">Freno d'emergenza: spento, il plugin non stampa nessun dato strutturato (JSON-LD). Vale per articoli, recensioni, video, FAQ, percorso, Hub dei giochi, Hub di categoria e pagina Redazione. Da spegnere se adotti un plugin SEO più completo.</p>
					</th>
					<td style="padding-top: 20px; padding-bottom: 20px;">
						<label style="display: block; margin-bottom: 10px;">
							<input type="checkbox" name="ntn_seo_enable_review_schema" value="1" <?php checked( get_option('ntn_seo_enable_review_schema', 'yes'), 'yes' ); ?>>
							<strong>Attiva i dati per Google (tutto lo Schema Markup del plugin)</strong>
						</label>
						<div class="notice notice-info inline" style="margin: 0; padding: 10px;">
							<p style="margin: 0;"><strong>Nota sui Dati:</strong> Disattivare questa opzione <strong>NON cancella i dati</strong> (voti, pro, contro) dal database, ma impedisce solo di stampare il codice per Google. <br><br>
							<strong>Integrazione intelligente:</strong> A partire dalla v2.3.10, mantenendo <strong>attiva</strong> questa spunta, NTN Journal inietterà automaticamente Voti, Pro, Contro e l'entità Videogioco <em>direttamente</em> all'interno del motore di <strong>The SEO Framework</strong> senza generare duplicati. Lasciala accesa per sfruttare a pieno i Rich Snippet!</p>
						</div>
					</td>
				</tr>
				
				<!-- VIDEO SCHEMA -->
				<tr style="border-bottom: 1px solid #eee;">
					<th scope="row">
						<strong>Schema Markup: Video (VideoObject)</strong>
						<p class="description">Estrae dati dai video YouTube negli articoli.</p>
					</th>
					<td style="padding-top: 20px; padding-bottom: 20px;">
						<label>
							<input type="checkbox" name="ntn_seo_enable_video_schema" value="1" <?php checked( get_option('ntn_seo_enable_video_schema', get_theme_mod('ntn_journal_enable_video_schema', true) ? 'yes' : 'no'), 'yes' ); ?>>
							<strong>Attiva Schema Video automatico</strong>
						</label>
					</td>
				</tr>

				<!-- FAQ SCHEMA -->
				<tr style="border-bottom: 1px solid #eee;">
					<th scope="row">
						<strong>Schema Markup: FAQ (FAQPage)</strong>
						<p class="description">Genera schema FAQ per i sottotitoli H3/H4 con punto interrogativo.</p>
					</th>
					<td style="padding-top: 20px; padding-bottom: 20px;">
						<label>
							<input type="checkbox" name="ntn_seo_enable_faq_schema" value="1" <?php checked( get_option('ntn_seo_enable_faq_schema', get_theme_mod('ntn_journal_enable_faq_schema', true) ? 'yes' : 'no'), 'yes' ); ?>>
							<strong>Attiva Schema FAQ automatico</strong>
						</label>
					</td>
				</tr>

				<!-- TABLE OF CONTENTS -->
				<tr style="border-bottom: 1px solid #eee;">
					<th scope="row">
						<strong>Table of Contents (Indice)</strong>
						<p class="description">Migliora UX e SEO creando link àncora ai paragrafi.</p>
					</th>
					<td style="padding-top: 20px; padding-bottom: 20px;">
						<label>
							<input type="checkbox" name="ntn_seo_enable_auto_toc" value="1" <?php checked( get_option('ntn_seo_enable_auto_toc', 'yes'), 'yes' ); ?>>
							<strong>Attiva Indice Automatico (se ci sono almeno 2 sottotitoli H2/H3)</strong>
						</label>
						<p class="description">Se usi già un plugin per il TOC (come Easy Table of Contents), spegni questa opzione.</p>
					</td>
				</tr>

				<!-- PAGINE CON POCHI ARTICOLI (v3.20.0) -->
				<tr style="border-bottom: 1px solid #eee;">
					<th scope="row">
						<strong>Pagine con pochi articoli</strong>
						<p class="description">Sotto la soglia una pagina è nascosta a Google (noindex, ma i suoi collegamenti agli articoli restano validi) e resta fuori dalla sitemap. Quando raggiunge la soglia torna da sola. Con 0 la regola è spenta.</p>
					</th>
					<td style="padding-top: 20px; padding-bottom: 20px;">
						<?php $ntn_thin = function_exists( 'ntn_core_seo_thin_summary' ) ? ntn_core_seo_thin_summary() : null; ?>
						<label style="display: block; margin-bottom: 10px;">
							<input type="number" name="ntn_seo_min_posts_terms" min="0" max="100" step="1" style="width: 80px;" value="<?php echo esc_attr( (string) ( function_exists( 'ntn_core_seo_min_terms' ) ? ntn_core_seo_min_terms() : 10 ) ); ?>">
							<strong>Articoli minimi per tag e categorie</strong> <span class="description">(anche Hub di categoria e pagine dei marchi; consigliato 10)</span>
						</label>
						<label style="display: block; margin-bottom: 10px;">
							<input type="number" name="ntn_seo_min_posts_games" min="0" max="100" step="1" style="width: 80px;" value="<?php echo esc_attr( (string) ( function_exists( 'ntn_core_seo_min_games' ) ? ntn_core_seo_min_games() : 3 ) ); ?>">
							<strong>Articoli minimi per le Hub dei videogiochi</strong> <span class="description">(consigliato 3: la Hub ha già scheda, copertina e trailer)</span>
						</label>
						<?php if ( $ntn_thin ) : ?>
							<p class="description" style="margin: 0;">Oggi sotto la soglia: <strong><?php echo esc_html( number_format_i18n( $ntn_thin['tags'] ) ); ?></strong> tag, <strong><?php echo esc_html( number_format_i18n( $ntn_thin['categories'] ) ); ?></strong> categorie, <strong><?php echo esc_html( number_format_i18n( $ntn_thin['games'] ) ); ?></strong> Hub dei videogiochi.</p>
						<?php endif; ?>
					</td>
				</tr>

				<!-- GOOGLE NEWS SITEMAP -->
				<tr>
					<th scope="row">
						<strong>Google News Sitemap</strong>
						<p class="description">Sitemap XML dedicata alle news (/news-sitemap.xml).</p>
					</th>
					<td style="padding-top: 20px;">
						<label>
							<input type="checkbox" name="ntn_seo_enable_news_sitemap" value="1" <?php checked( get_option('ntn_seo_enable_news_sitemap', 'yes'), 'yes' ); ?>>
							<strong>Attiva Google News Sitemap nativa (/news-sitemap.xml)</strong>
						</label>
						<p class="description">Genera automaticamente la sitemap XML per Google News con articoli e comunicati pubblicati nelle ultime 48 ore. Se utilizzi un modulo News Sitemap in plugin SEO esterni (come Yoast News o Rank Math Pro), deseleziona questa opzione per evitare conflitti.</p>
					</td>
				</tr>
			</tbody>
		</table>
		
		<p class="submit">
			<input type="submit" class="button button-primary" value="Salva Impostazioni SEO">
		</p>
	</form>
</div>
