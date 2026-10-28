# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.1.3 (Release Ufficiale)

Questo aggiornamento introduce l'unificazione estetica e funzionale delle didascalie per i singoli blocchi "Immagine" e per le miniature/immagini annidate all'interno delle "Gallerie", portando l'intera visualizzazione al rigore brutalista *Nothing OS*.

---

### 🎨 Unificazione e Ripristino Didascalie Immagini & Gallerie

- **Blocchi "Immagine" Singoli (`wp-block-image`)**:
  - Eliminato il precedente allineamento a destra (`text-align: right`) e il maiuscolo forzato (`text-transform: uppercase`).
  - Allineata la formattazione a quella delle gallerie: testo centrato (`text-align: center`), font monospazio *JetBrains Mono*, dimensione `0.75rem`, `line-height: 1.5`, margini verticali proporzionati (`margin-top: 0.75rem`).
  - Pieno supporto bicolore reattivo: colore `#71717a` (zinc-500) per Light Mode e `#a1a1aa` (zinc-400) per Dark Mode.
  - Piena compatibilità con elementi inline come grassetti (`<strong>`), corsivi e link.

- **Didascalie su Immagini Annidate nelle Gallerie (`wp-block-gallery figure.wp-block-image figcaption`)**:
  - Risolto il problema per cui le didascalie impostate direttamente sull'immagine (o su gallerie composte da singola immagine come nell'articolo *Dawnwalker*) risultavano invisibili a causa del `display: none`.
  - Resettato e neutralizzato l'overlay scuro predefinito di WordPress Gutenberg (`background: linear-gradient(...)`, `position: absolute; bottom: 0`).
  - Le didascalie appaiono ora in modo pulito ed elegante subito sotto l'immagine (`position: relative`, `background: transparent`), con la stessa formattazione brutalista *Nothing OS* centrata.

- **Didascalie Globali di Galleria (`wp-block-gallery > figcaption`)**:
  - Mantenuto lo stile uniforme centrato *Nothing OS* a tutta larghezza (`flex-basis: 100%`).

---

### ✅ Quality Assurance, Test Suite e Packaging

- **Certificazione PHP 8.2+ Strict**: Nessun notice, warning o fatal error.
- **Suite di Collaudo Automatizzata**: Aggiunta la sezione di test 26 in `audit_test_suite.php`. Risultato: **186 test eseguiti, 186 superati (100%), 0 falliti**.
- **Sincronizzazione Atomica 4 Header (SemVer 3.1.3)**: Allineati simultaneamente:
  1. `NTN_Journal_Theme_Reloaded/ntn-journal/style.css` (`Version: 3.1.3`)
  2. `NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css` (`Version: 3.1.3`)
  3. `NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php` Header (`Version: 3.1.3`)
  4. `NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php` Costante (`NTN_JOURNAL_CORE_VERSION` = `'3.1.3'`)
- **Organizzazione Release & Archivio**:
  - Precedente release ufficiale `v3.1.2` spostata in `archive/v3.1.2/`.
  - Nuova release ufficiale `v3.1.3` posizionata in `releases/v3.1.3/` completa di pacchetti ZIP e sorgenti verificati.
