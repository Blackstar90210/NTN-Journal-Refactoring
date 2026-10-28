# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.1.4 (Release Ufficiale)

Questo aggiornamento risolve in modo definitivo la disparità di spaziatura verticale tra il blocco "Immagine" (`wp-block-image`) e il blocco "Galleria" (`wp-block-gallery`), garantendo un ritmo verticale perfettamente simmetrico e uniforme in stile brutalista *Nothing OS*.

---

### 📐 Uniformazione Spaziature Verticali (Immagine vs Galleria)

- **Simmetria Margini Esterni dei Blocchi (`figure.wp-block-image` vs `figure.wp-block-gallery`)**:
  - Sia i blocchi "Immagine" che le "Gallerie" condividono ora esattamente lo stesso margine verticale superiore e inferiore: `margin-top: 1.5rem !important; margin-bottom: 1.5rem !important;`.
  - Neutralizzati i margini asimmetrici imposti da Tailwind Typography (`.prose figure` a 2em) e da WordPress core (`figure` a 1em).

- **Eliminazione Spazi Parassiti Inline sulle Immagini**:
  - Impostato `display: block !important; width: 100% !important; margin: 0 !important;` sia su `img` sia sugli elementi contenitori `<a>` (GLightbox link).
  - Azzerato lo spazio di discesa font parassita (baseline) che allontanava l'immagine dalla didascalia nel blocco immagine singolo.

- **Uniformazione Distanza Didascalia (`figcaption`)**:
  - Impostato `margin-top: 0.75rem !important; margin-bottom: 0 !important;` su tutte le didascalie (sia per blocchi immagine che per miniature di galleria e gallerie globali).
  - Eliminato il `margin-bottom: 1em` nativo di Gutenberg che generava uno spazio inferiore sproporzionato sotto la didascalia dell'immagine rispetto alla galleria.

- **Supporto Galleria a Singola Immagine**:
  - Quando una galleria contiene una sola immagine (`:only-child` / `:first-child:last-child`), essa si espande ora al 100% della larghezza riga, allineandosi visivamente in modo identico a un blocco immagine.

---

### ✅ Quality Assurance, Test Suite e Packaging

- **Certificazione PHP 8.2+ Strict**: Zero warning, notice o fatal error.
- **Suite di Collaudo Automatizzata**: Eseguiti **189 test su 189 superati al 100%** (`audit_test_suite.php`), inclusi i controlli specifici per la spaziatura verticale e l'azzeramento del margine inferiore delle didascalie.
- **Sincronizzazione Atomica 4 Header (SemVer 3.1.4)**: Allineati simultaneamente:
  1. `NTN_Journal_Theme_Reloaded/ntn-journal/style.css` (`Version: 3.1.4`)
  2. `NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css` (`Version: 3.1.4`)
  3. `NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php` Header (`Version: 3.1.4`)
  4. `NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php` Costante (`NTN_JOURNAL_CORE_VERSION` = `'3.1.4'`)
- **Organizzazione Release & Archivio**:
  - La release `v3.1.3` è stata archiviata in `archive/v3.1.3/`.
  - La nuova release ufficiale `v3.1.4` è posizionata in `releases/v3.1.4/`.
