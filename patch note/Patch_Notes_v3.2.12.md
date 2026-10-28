# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.2.12 (Release Ufficiale)

Questo aggiornamento esegue una bonifica completa e sistematica dell'intero ecosistema NTN Journal, eliminando rimasugli di codice commentato, stringhe hardcoded legacy, impostazioni orfane nel pannello di amministrazione e uniformando la paginazione e i colori di sistema all'estetica brutalista Nothing OS.

---

### 🧹 1. Pulizia ed Uniformazione della Paginazione Brutalista

- **Eliminazione Residui Commentati (`archive.php`)**:
  - Rimossi i frammenti di markup HTML commentati rimasti dalle iterazioni precedenti all'interno del blocco `the_posts_pagination()`.
- **Uniformazione Parametri `prev_text` e `next_text` su Tutti i Template di Archivio**:
  - In precedenza, alcuni template (`tag.php`, `author.php`, `archive-comunicato_stampa.php`, `archive-videogioco.php`) iniettavano tag `<span>` personalizzati con bordi e classi Tailwind all'interno di `prev_text`/`next_text`, causando sovrapposizioni e doppi bordi con le classi native `.navigation.pagination .page-numbers` definite in `style.css`.
  - Tutti i template adottano ora la sintassi standard pulita:
    ```php
    the_posts_pagination( array(
        'mid_size'  => 2,
        'prev_text' => '&larr; Precedente',
        'next_text' => 'Successivo &rarr;',
        // eventuale 'class' => 'ntn-pagination' preservata
    ) );
    ```
  - Resa visiva geometrica, pulita e al 100% coerente con il design system Nothing OS.

---

### 🗑️ 2. Rimozione Residui Legacy & Disaccoppiamento

- **Bonifica Stringhe Hardcoded (`inc/customizer.php`)**:
  - Sostituito il riferimento testuale `"per il pulsante AGGIUNGI Q-GIN"` con `"per il pulsante AGGIUNGI"`, eliminando qualsiasi dipendenza hardcoded a vecchi nomi di dominio o siti precedenti e garantendo il pieno disaccoppiamento multi-tenant.
- **Rimozione Controllo Orfano nel Pannello Admin (`tab-extra.php`)**:
  - Rimossa la riga orfana "Visualizzazione Tag" dal pannello di amministrazione del plugin Core (`inc/admin/views/tab-extra.php`). Poiché la sezione tag a fondo articolo è stata rimossa dalla UI in v3.2.9, l'opzione era diventata inattiva e fuorviante.
- **Pulizia Commenti Morti (`inc/performance.php`)**:
  - Rimosso il commento orfano `// Removed buggy function ntn_journal_lcp_image_attributes`.

---

### 🎨 3. Uniformazione Colore Badge Breaking News

- **Allineamento al Rosso Nothing OS (`inc/breaking-news.php`)**:
  - Il colore di default per il badge delle Notizie Flash / Breaking News è stato uniformato a `#dc2626` (Tailwind `red-600`), sostituendo il vecchio fallback `#e11d48` (rose-600) e armonizzandosi perfettamente con tutti gli accenti Nothing OS del tema.

---

### ✅ 4. Quality Assurance, Test Suite e Packaging

- **Certificazione PHP 8.2/8.5 Strict**: Zero warning, notice o fatal error.
- **Suite di Collaudo Automatizzata**: Eseguiti **253 test su 253 superati al 100%** ([`audit_test_suite.php`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/audit_test_suite.php)), inclusi 5 nuovi test di regressione dedicati alla bonifica (`29.16 - 29.20`).
- **Sincronizzazione Atomica 4 Header (SemVer 3.2.12)**:
  1. [`NTN_Journal_Theme_Reloaded/ntn-journal/style.css`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Theme_Reloaded/ntn-journal/style.css) (`Version: 3.2.12`)
  2. [`NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css) (`Version: 3.2.12`)
  3. [`NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php) Header (`Version: 3.2.12`)
  4. [`NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php) Costante (`NTN_JOURNAL_CORE_VERSION` = `'3.2.12'`)
- **Organizzazione Deliverable**:
  - Pacchetti ZIP e note di rilascio posizionati in [`releases/`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/releases/) e [`releases/v3.2.12/`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/releases/v3.2.12/).
