# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.2.15 (Release Ufficiale)

Questo aggiornamento rimuove il prefisso `www.` dall'URL del pulsante **Google Feed** (`ntn_journal_google_feed_box`), consentendo al catalogo e alle API di Google (Google Preferred Sources / Google News) di identificare e rilevare correttamente l'editore e il nome del sito web.

---

### 🌐 1. Risoluzione Rilevamento Editore su Google Feed

- **Causa del Mancato Rilevamento**:
  - Il pulsante `AGGIUNGI [SITO]` sfrutta il deeplink ufficiale:
    `https://www.google.com/preferences/source?q=[DOMINIO]`
  - Quando il sito WordPress è configurato con `www.` (es. `https://www.q-gin.info`), il valore estratto da `home_url()` conteneva `www.q-gin.info`.
  - Nei registri e nel Publisher Center di Google, le fonti editoriali sono indicizzate mediante il dominio radice (apex domain, es. `q-gin.info`). La ricerca con `q=www.q-gin.info` impediva a Google di associare la testata e restituiva "Nessuna fonte trovata".
- **Intervento Implementato (`inc/social-cta.php`)**:
  - Applicata sanitizzazione con regex sia al dominio ricavato automaticamente da `home_url()`, sia a eventuali URL personalizzati inseriti nel Customizer:
    ```php
    // Rimozione del prefisso www. dal dominio
    $domain = preg_replace( '/^www\./i', '', (string) $domain );
    $feed_url = 'https://www.google.com/preferences/source?q=' . rawurlencode( $domain );

    // Pulizia di sicurezza su URL personalizzati con query parameter q=www.
    $feed_url = preg_replace( '/([?&]q=)www\./i', '$1', (string) $feed_url );

    // Pulizia del nome visualizzato se impostato con prefisso www.
    $site_name = preg_replace( '/^www\./i', '', (string) get_bloginfo( 'name' ) );
    ```
  - Ora il pulsante indirizza verso `https://www.google.com/preferences/source?q=q-gin.info`, permettendo a Google di visualizzare istantaneamente la card con il nome, il logo e l'opzione di aggiunta della fonte alle preferite.

---

### ✅ 2. Quality Assurance, Test Suite e Packaging

- **Certificazione PHP 8.2/8.5 Strict**: Zero warning, notice o fatal error.
- **Suite di Collaudo Automatizzata**: Eseguiti **256 test su 256 superati al 100%** ([`audit_test_suite.php`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/audit_test_suite.php)), inclusa la nuova asserzione `29.23`.
- **Sincronizzazione Atomica 4 Header (SemVer 3.2.15)**:
  1. [`NTN_Journal_Theme_Reloaded/ntn-journal/style.css`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Theme_Reloaded/ntn-journal/style.css) (`Version: 3.2.15`)
  2. [`NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css) (`Version: 3.2.15`)
  3. [`NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php) Header (`Version: 3.2.15`)
  4. [`NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php) Costante (`NTN_JOURNAL_CORE_VERSION` = `'3.2.15'`)
- **Organizzazione Deliverable**:
  - Pacchetti ZIP posizionati in [`releases/`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/releases/) e [`releases/v3.2.15/`](file:///c:/Users/Luca-Maiolini/Sviluppo-Antigravity/Sviluppo/Divisione_WP/Temi/NTN%20Journal%20Refactoring/releases/v3.2.15/).
