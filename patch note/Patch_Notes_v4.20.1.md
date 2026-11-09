# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.20.1 (Le miniature dei PNG trasparenti restano PNG)

Difetto emerso durante la prova della 4.20.0, presente anche prima (era nel tema). Cambia solo `ntn-journal-core/inc/site-optimizations.php`.

### 🐛 Il problema

Quando si caricava un PNG trasparente (logo, badge, icona), l'originale restava PNG ma le sue **miniature** (thumbnail, medium, medium_large e quelle del tema) diventavano JPEG, con lo sfondo pieno al posto della trasparenza.

Il motivo: per le miniature WordPress chiama il filtro `image_editor_output_format` **senza il nome del file**, quindi il controllo della trasparenza non poteva partire e la miniatura veniva convertita.

### 🛠️ La correzione

- Prima di creare le miniature il plugin si ricorda il file originale dell'allegato (filtri `intermediate_image_sizes_advanced` e `wp_get_missing_image_subsizes`).
- Quando WordPress chiede il formato di una miniatura PNG senza nome del file, il controllo della trasparenza si fa sull'originale: se è trasparente la miniatura resta PNG.
- Finite tutte le miniature (filtro `wp_generate_attachment_metadata`) il file viene dimenticato, così non vale per l'immagine successiva. Non si usa `wp_update_attachment_metadata`, che WordPress chiama dopo ogni singola miniatura.
- Funziona al caricamento, quando si rigenerano le miniature (anche con un plugin) e quando WordPress crea solo le misure mancanti.
- I PNG pieni e le loro miniature diventano JPEG come prima. Le miniature create prima dell'aggiornamento non cambiano: per sistemare un logo già caricato basta rigenerarne le miniature o ricaricarlo.

### ✅ Test
Nuovi test 91.1-91.2 (controllo sul codice e prova con file finti). Provato sul Local: logo trasparente 1000x1000 con tutte le miniature PNG e angolo trasparente, anche dopo la rigenerazione.

### Ordine di installazione
Plugin, poi tema parent, poi child (il cambiamento vero è nel plugin).
