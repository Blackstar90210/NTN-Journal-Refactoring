# Piano d'Azione: Bonifica, Sicurezza e Ottimizzazione Codice (Fase 3)

Questo documento definisce il piano operativo organico e completo per la risoluzione di tutte le criticità emerse dagli Audit di **Fase 1 (Sicurezza/Backend)**, **Fase 2 (Infrastruttura/DB/SEO/A11y)** e **Round 3 (Deep Code & Architecture Audit)**. 

I task sono assegnati agli specialisti dedicati sotto il coordinamento del **Division Lead** e saranno validati attraverso i **Gate Separati** stabiliti dallo Statuto Globale del Management Core.

---

## 1. Sicurezza & Hardening Codice (Responsabili: Marco, Alessandro)

- **Task 1.1 - Escaping Globale dei Template (Mitigazione XSS):**
  - **File:** `NTN_Journal_Theme_Reloaded/ntn-journal/author-bio.php` (L18-85), `content.php` (L35-40), `single.php` (L31-44), `archive.php` (L75).
  - **Azione:** Sostituzione di tutte le istruzioni `echo $variabile` prive di filtro con le apposite funzioni di escape (`esc_html()`, `esc_attr()`, `esc_url()`). Protezione specifica sui nomi categoria (`$primary_cat->name`), URL autore e classi dinamiche.
- **Task 1.2 - Validazione e Sanitizzazione Parametri di Query (Filtri GET):**
  - **File:** `NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/archive-videogioco.php` (L13-21).
  - **Azione:** Rafforzamento dei controlli sui parametri `$_GET`. Tipizzazione rigorosa e whitelist per prevenire array imprevisti o injection logiche passate a `WP_Query`.
- **Task 1.3 - Hardening Query SQL & Prepared Statements:**
  - **File:** `NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/backup-restore.php` (L404, L437).
  - **Azione:** Rifattorizzazione delle query `$wpdb->query()` e `$wpdb->get_results()` applicando sempre `$wpdb->prepare()`, eliminando ogni concatenazione o interpolazione diretta di variabili e costanti.
- **Task 1.4 - Sanitizzazione Endpoint e Chiamate API IGDB:**
  - **File:** `NTN_Journal_Core_Plugin/ntn-journal-core/inc/ajax-igdb.php` (L175, L495).
  - **Azione:** Sostituzione dei filtri rudimentali basati su `str_replace` con validatori nativi (`esc_url_raw()`, corretta combinazione di `wp_unslash()`). Sanitizzazione preventiva di `$_POST['review_url']`.

---

## 2. Backend & Compatibilità PHP 8.2+ (Responsabile: Alessandro)

- **Task 2.1 - Regolamentazione REST API Endpoints:**
  - **File:** `NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/rest-catalog.php` (L21, L42).
  - **Azione:** Dichiarazione esplicita del parametro `permission_callback` in `register_rest_route()` per gli endpoint `/games` e `/live-search`, prevenendo deprecation notices in PHP 8.2 e scongiurando accessi incontrollati.
- **Task 2.2 - Batch Processing e Memory Management:**
  - **File:** `NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/migrazione-igdb.php` e `migration-2.4.0.php`.
  - **Azione:** Suddivisione dell'elaborazione di grandi cataloghi in chunk/batch con liberazione della memoria (`gc_collect_cycles()`) e predisposizione per esecuzione via WP-CLI o heartbeat AJAX per prevenire fatal error di timeout o esaurimento memoria (OOM).

---

## 3. Database, Caching & Performance (Responsabili: DBA, Alessandro)

- **Task 3.1 - Risoluzione Rischio "Cache Stampede" in Homepage:**
  - **File:** `NTN_Journal_Theme_Reloaded/ntn-journal/front-page.php` (L69, 279, 423, 534, 676).
  - **Azione:** Eliminazione dell'abuso di `set_transient()` con scadenza rigida a 5 minuti su sezioni multiple della home. Adozione di un pattern *Stale-While-Revalidate* o scaglionamento dei tempi di scadenza (jittering) con fallback su cache persistente per scongiurare picchi di CPU al 100% durante picchi di traffico.
- **Task 3.2 - Debellamento N+1 Query & Object Caching:**
  - **File:** `NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/migrazione-igdb.php` (L131, 277, 366) e `NTN_Journal_Theme_Reloaded/ntn-journal/inc/helpers.php`.
  - **Azione:** Sostituzione delle query iterative e cicliche sui singoli post con query bulk (`get_posts` aggregate con pre-caricamento meta/terms) e introduzione di Object Caching nativo (`wp_cache_set()` / `wp_cache_get()`).
- **Task 3.3 - Ottimizzazione Indici e Ricerche Options:**
  - **File:** `NTN_Journal_Core_Plugin/ntn-journal-core/inc/redirect-manager.php` (L96).
  - **Azione:** Ristrutturazione della logica che esegue `LIKE 'ntn_redir_%'` sulla tabella `wp_options`, centralizzando i redirect in un'unica opzione serializzata o in una tabella dedicata per prevenire costosi Table Scans.

---

## 4. Conformità SEO & Dati Strutturati (Responsabili: Giacomo, Alessandro)

- **Task 4.1 - Standardizzazione Output Testata (wp_head):**
  - **File:** `NTN_Journal_Theme_Reloaded/ntn-journal/header.php` (L17-21).
  - **Azione:** Rimozione completa dei tag `<title>` e `<meta>` hardcodati a mano nel template. La gestione della testata viene delegata interamente a `wp_head()` e alle integrazioni con *The SEO Framework*.
- **Task 4.2 - Razionalizzazione JSON-LD e Disambiguazione Schema:**
  - **File:** `NTN_Journal_Core_Plugin/ntn-journal-core/inc/schema-markup.php` e `seo-extras.php` (L728-751).
  - **Azione:** Sincronizzazione dei grafi Schema con i filtri nativi di The SEO Framework evitando la generazione di blocchi JSON-LD duplicati o contrastanti sulle schede videogioco.
- **Task 4.3 - Ottimizzazione Semantica Autori e Archivi:**
  - **File:** `NTN_Journal_Theme_Reloaded/ntn-journal/author.php` (L140-157).
  - **Azione:** Revisione del markup dell'autore e delle categorie per renderli pienamente conformi alle specifiche Schema.org `Person` e `CollectionPage`, validando la corretta generazione dei canonical durante la paginazione.

---

## 5. Accessibilità (A11y) & Frontend UI (Responsabili: Elena, A11y Auditor)

- **Task 5.1 - Accessibilità Controlli e Navigazione:**
  - **File:** `NTN_Journal_Theme_Reloaded/ntn-journal/front-page.php` (L167).
  - **Azione:** Aggiunta di attributi `aria-label`, ruoli appropriati e focus visibile sui pulsanti di scorrimento del carosello, garantendo piena leggibilità tramite VoiceOver/NVDA e navigabilità da sola tastiera.
- **Task 5.2 - Testi Alternativi Dinamici e Descrittivi:**
  - **File:** `NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/assets/js/live-search.js` (L82) e `single-videogioco.php` (L163, L183).
  - **Azione:** Eliminazione degli attributi `alt=""` vuoti nei risultati AJAX e sostituzione di stringhe generiche (`alt="Trailer"`, `alt="Screenshot"`) con descrizioni semantiche contestualizzate col titolo dell'opera.
- **Task 5.3 - Bonifica Stili Inline Hardcoded:**
  - **File:** `NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/tab-press.php` (L67).
  - **Azione:** Spostamento di tutti gli stili inline cablati nel markup PHP all'interno del foglio di stile CSS dell'amministrazione.

---

## 6. DevOps, Gestione Repository & Secret Management (Responsabile: Riccardo)

- **Task 6.1 - Housekeeping Root Directory:**
  - **Azione:** Rimozione o spostamento in `archive/` di tutte le cartelle temporanee residue (`temp_parent_*`, `temp_extract_*`, `temp_child_*`, `temp_check_zip*`) e file zip sciolti dalla radice del progetto.
- **Task 6.2 - Protezione Credenziali e Secret:**
  - **File:** `NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/igdb-test-page.php` (L37-39).
  - **Azione:** Rimozione del passaggio in chiaro tramite form POST delle credenziali IGDB (Client ID e Secret). Introduzione del supporto a costanti d'ambiente (`.env` o costanti protette in `wp-config.php`).
- **Task 6.3 - Setup CI/CD & Automazione Release:**
  - **Azione:** Configurazione di pipeline GitHub Actions (`.github/workflows`) per linting automatico (PHP_CodeSniffer con standard WordPress-Core), esecuzione test e packaging pulito dei file di release, deprecando lo script manuale `package_release.ps1`.

---

## 7. Protocollo di Esecuzione & Gate Separati

In rigorosa applicazione dello **Statuto Globale del Management Core**:

1. **Nessuna Auto-Approvazione:** Gli sviluppatori (Alessandro, Elena, DBA) non possono approvare autonomamente il proprio codice.
2. **Gate di Sicurezza:** Marco ha potere di veto su qualsiasi modifica backend, query SQL o sanitizzazione prima del merge.
3. **Gate di Qualità & Accessibilità:** Francesca (QA) e l'A11y Auditor devono convalidare la non regressione visiva, la build dei pacchetti e la conformità WCAG.
4. **Handoff Finale al COO:** Il Division Lead produrrà il resoconto completo con evidenze di test solo al completamento di ciascun blocco tematico.
