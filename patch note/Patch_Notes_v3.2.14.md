# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.2.14 (Release Ufficiale)

Questo aggiornamento risolve l'inconsistenza visiva nella paginazione degli articoli correlati all'interno dell'**Hub Videogiochi** (`single-videogioco.php`), modernizzando la struttura markup ed estendendo lo stile brutalista Nothing OS (`[ 1 ] [ 2 ] [ 3 ] ... [ SUCCESSIVO → ]`) a tutti i template del network.

---

### 🎮 1. Armonizzazione Paginazione Hub Videogiochi (`single-videogioco.php`)

- **Causa Radice del Problema**:
  - Negli archivi standard (`archive.php`, `sezione/speciali/recensioni/`), la funzione core `the_posts_pagination()` genera automaticamente il wrapper semantico `<nav class="navigation pagination"><div class="nav-links">`.
  - Nell'hub videogiochi (`single-videogioco.php`), la query secondaria `$articles_query` utilizzava `paginate_links( array( 'type' => 'plain' ) )` riversando l'output direttamente in un generico `<div>`, senza il wrapper `.navigation.pagination`.
  - I link numerici collassavano quindi in semplice testo non spaziato (`123`), mentre i controlli precedente/successivo utilizzavano uno snippet obsoleto `<span class="...">SUCC &gt;</span>`.
- **Risoluzione Implementata**:
  - Inserito il wrapper semantico conforme agli standard WordPress e WCAG:
    ```html
    <nav class="navigation pagination" aria-label="<?php esc_attr_e( 'Paginazione articoli correlati', 'ntn-journal' ); ?>">
        <h2 class="screen-reader-text"><?php esc_html_e( 'Paginazione degli articoli', 'ntn-journal' ); ?></h2>
        <div class="nav-links">
            <?php echo $pagination_links; ?>
        </div>
    </nav>
    ```
  - Standardizzati i testi e le entità freccia:
    - `'prev_text' => '&larr; Precedente'`
    - `'next_text' => 'Successivo &rarr;'`
  - Allineati sia il tema Parent (`ntn-journal/single-videogioco.php`) sia il tema Child (`ntn-journal-reloaded/single-videogioco.php`).

---

### 🧹 2. Pulizia e Uniformazione Template Correlati

- **Archivio Videogiochi (`archive-videogioco.php`)**:
  - Rimossa la classe personalizzata ridondante `'class' => 'ntn-pagination'` in `the_posts_pagination()`, consentendo a WordPress di generare il selettore nativo `.navigation.pagination`.
- **Tassonomia Game Engine (`taxonomy-game_engine.php`)**:
  - Rimossa la sovrascrittura di classi manuali e uniformate le entità freccia a `'&larr; Precedente'` e `'Successivo &rarr;'`.
- **CSS Defense-in-Depth (`ntn-journal/style.css`)**:
  - Estesi i selettori CSS di paginazione per intercettare sia `.navigation.pagination`, sia `.navigation`, sia `.ntn-pagination`, garantendo totale resilienza e consistenza grafica (inclusa la modalità dark).

---

### ✅ 3. Quality Assurance, Test Suite e Packaging

- **Certificazione PHP 8.2/8.5 Strict**: Zero warning, notice o fatal error.
- **Suite di Collaudo Automatizzata**: Eseguiti **255 test su 255 superati al 100%** ([`audit_test_suite.php`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/audit_test_suite.php)), con l'aggiunta dell'asserzione di conformità `29.22`.
- **Sincronizzazione Atomica 4 Header (SemVer 3.2.14)**:
  1. [`NTN_Journal_Theme_Reloaded/ntn-journal/style.css`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Theme_Reloaded/ntn-journal/style.css) (`Version: 3.2.14`)
  2. [`NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css) (`Version: 3.2.14`)
  3. [`NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php) Header (`Version: 3.2.14`)
  4. [`NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php) Costante (`NTN_JOURNAL_CORE_VERSION` = `'3.2.14'`)
- **Organizzazione Deliverable**:
  - Pacchetti ZIP posizionati in [`releases/`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/releases/) e [`releases/v3.2.14/`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/releases/v3.2.14/).
