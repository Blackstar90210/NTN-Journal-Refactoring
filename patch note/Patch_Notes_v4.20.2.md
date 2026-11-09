# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.20.2 (Pulizie minori dell'audit)

Piccole pulizie di codice dell'audit di coerenza. Nessuna modifica visibile al sito.

### 🧹 Cosa cambia

- **Funzioni dei voti con il prefisso del progetto.** `get_post_rating`, `get_rating_label` e `get_rating_initials` diventano `ntn_get_post_rating`, `ntn_get_rating_label` e `ntn_get_rating_initials` in tutto il codice di plugin, tema e test. Prima potevano scontrarsi con un altro plugin. I nomi vecchi restano nel plugin come alias, definiti solo se nessun altro codice li ha già (`inc/rating-labels.php`, `inc/game-data.php`): eventuali snippet esterni continuano a funzionare e non può nascere un errore fatale.
- **Tolto `content-format.php` dal tema.** Conteneva solo `ntn_format_content_links()`, che restituiva il testo com'era e non era collegata a nessun filtro. Tolto anche il file dal `functions.php`.
- **Tolta `ntn_get_tag_to_cpt_map()`** dal plugin (`inc/taxonomies.php`): restituiva sempre un elenco vuoto e nessuno la chiamava. (`ntn_get_tag_featured_content()` era già stata tolta nella 4.12.)
- **ZIP del tema parent senza `build/`.** La cartella `build/` contiene i sorgenti di Tailwind per chi sviluppa e non serve sul sito: `package_release.ps1` ora la lascia fuori dallo ZIP del parent (resta nel repository).
- **Pannello dell'editor senza la funzione obsoleta.** `ai-summary-gutenberg.js` prende `PluginDocumentSettingPanel` da `wp.editor` (WordPress 6.6 e successivi) e solo se manca da `wp.editPost`; lo script dichiara anche `wp-editor` tra le dipendenze.

### Controllato e lasciato com'era
- **Text domain**: sono coerenti (`ntn-journal` nel tema, `ntn-journal-core` nel plugin, nessuna miscela tra i due), quindi nessuna modifica.

### ✅ Test
Nuova suite 92 (92.1-92.6). La suite 18 e il test 27.9, che provavano la funzione tolta, ora verificano che non esista più.

### Ordine di installazione
Plugin, poi tema parent, poi child. Con il tema nuovo e il plugin vecchio i voti non compaiono finché non si aggiorna il plugin.
