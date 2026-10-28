# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.2.13 (Release Ufficiale)

Questo aggiornamento sostituisce la freccia testuale a destra (`&rarr;`) nel pulsante di aggiunta del box **Google Feed** con la caratteristica freccia diagonale rossa in stile Nothing OS (`↗`), armonizzando l'iconografia con i collegamenti `// LEGGI` e `// LEGGI_ARTICOLO` presenti nella home e nel sito.

---

### ↗️ 1. Icona Freccia Diagonale Nothing OS su Google Feed

- **Aggiornamento Iconografico (`inc/social-cta.php`)**:
  - Nel pulsante `AGGIUNGI [SITO]` (`.ntn-google-feed-btn`), la freccia generica `→` è stata sostituita dal glifo vettoriale SVG a 45° verso l'alto a destra:
    ```html
    <svg class="w-3.5 h-3.5 text-red-600 dark:text-red-500 transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"></path>
    </svg>
    ```
- **Cromia e Interazione Tattile**:
  - **Rosso di Sistema**: L'icona eredita `text-red-600 dark:text-red-500` per risaltare immediatamente come elemento d'azione rispetto al testo uppercase.
  - **Micro-animazione su Hover**: Al passaggio del cursore (`group-hover`), la freccia si sposta in modo fluido lungo la direttrice diagonale (`translate-x-0.5 -translate-y-0.5`).
  - **Accessibilità WCAG**: Aggiunto l'attributo `aria-hidden="true"` sull'SVG decorativo.

---

### ✅ 2. Quality Assurance, Test Suite e Packaging

- **Certificazione PHP 8.2/8.5 Strict**: Zero warning, notice o fatal error.
- **Suite di Collaudo Automatizzata**: Eseguiti **254 test su 254 superati al 100%** ([`audit_test_suite.php`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/audit_test_suite.php)), incluso il test di regressione `29.21`.
- **Sincronizzazione Atomica 4 Header (SemVer 3.2.13)**:
  1. [`NTN_Journal_Theme_Reloaded/ntn-journal/style.css`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Theme_Reloaded/ntn-journal/style.css) (`Version: 3.2.13`)
  2. [`NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css) (`Version: 3.2.13`)
  3. [`NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php) Header (`Version: 3.2.13`)
  4. [`NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php) Costante (`NTN_JOURNAL_CORE_VERSION` = `'3.2.13'`)
- **Organizzazione Deliverable**:
  - Pacchetti ZIP posizionati in [`releases/`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/releases/) e [`releases/v3.2.13/`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/releases/v3.2.13/).
