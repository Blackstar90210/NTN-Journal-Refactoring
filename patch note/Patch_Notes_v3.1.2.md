# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.1.2 (Release Ufficiale)

Questo documento traccia tutte le modifiche strutturali, di accessibilità (a11y) e grafiche implementate durante le sessioni di collaudo per la risoluzione dei controlli PageSpeed Insights e il ripristino delle didascalie.

---

### ♿ Accessibilità (A11Y), WCAG & Navigazione Agentica (WebMCP)
- **Hardening GLightbox Client-Side (`gallery-lightbox.php`)**: Lo script di inizializzazione dinamica del lightbox garantisce ora che ogni elemento `<a>` (sia creato a runtime che preesistente) sia dotato di un attributo `aria-label` descrittivo:
  - Recupera il testo alternativo (`alt`) dell'immagine: `Visualizza immagine ingrandita: [alt]`.
  - In assenza di alt, recupera la didascalia dell'immagine o della galleria (`figcaption`).
  - In assenza di entrambi, applica il fallback semantico `Visualizza immagine ingrandita`.
  - Risolto al 100% il fallimento dell'audit PageSpeed / axe-core: *«Links must have discernible text»*.
- **Filtro Difensivo SSR (`render_block`)**: Aggiunto il filtro PHP `ntn_filter_accessible_gallery_links()` su `core/gallery` e `core/image` che inietta preventivamente l'`aria-label` nell'HTML servito dal server.
- **CPT Videogioco Media**: Aggiunto `aria-label` esplicito ai link GLightbox della griglia screenshot IGDB in `single-videogioco.php`.

---

### 🎨 Frontend e Didascalie Gallerie (Child & Parent Theme)
- **Ripristino Didascalie Globali (`figcaption.blocks-gallery-caption`)**: 
  - Rimossa la regola globale troppo restrittiva `body .wp-block-gallery figcaption { display: none; }` che oscurava la didascalia sotto le gallerie WordPress.
  - Mantenuto il nascondimento mirato solo per le didascalie sovrapposte alle singole miniature (`figure.wp-block-image figcaption`) per mantenere la griglia a mosaico pulita.
  - Formattata la didascalia principale dell'intera galleria in stile brutalista *Nothing OS*:
    - Posizionamento centrato (`flex-basis: 100%`, `text-align: center`).
    - Font monospazio *JetBrains Mono*, dimensione `0.75rem`, `line-height: 1.5`.
    - Colori armonizzati per tema chiaro (`#71717a` - zinc-500) e tema scuro (`#a1a1aa` - zinc-400).

---

### ✅ Quality Assurance, Test Suite e Packaging
- **Certificazione PHP 8.2+ Strict**: Zero notice, warning o fatal error.
- **Suite di Collaudo Automatizzata**: Aggiunta la sezione di test 25 in `audit_test_suite.php`. Risultato: **182 test eseguiti, 182 superati, 0 falliti**.
- **Sincronizzazione Atomica 4 Header (SemVer 3.1.2)**: Allineati simultaneamente `ntn-journal/style.css`, `ntn-journal-reloaded/style.css`, `ntn-journal-core.php` (header) e `NTN_JOURNAL_CORE_VERSION`.
- **Archiviazione & Gestione Beta**:
  - Precedente release `v3.0.0` spostata dalla cartella `beta/` in `archive/v3.0.0/`.
  - Nuova release `v3.1.2` salvata in `beta/v3.1.2/` completa di sorgenti e pacchetti ZIP conformi.
