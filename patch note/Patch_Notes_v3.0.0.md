# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.0.0 (Beta)

Questo documento traccia tutte le modifiche strutturali, architetturali e grafiche implementate durante l'ultima sessione di audit e refactoring del codice.

---

### 🛠️ Modifiche al Backend e Architettura (Core Plugin)
- **Nuovo Motore di Backup/Ripristino IGDB**: La tab "Backup / Ripristino" è stata completamente riscritta. Ora dispone di un bridge Vanilla JS AJAX moderno per l'esportazione in streaming (in formato JSONL) e l'importazione progressiva dei soli dati e collegamenti del database IGDB e delle Hub Page, separandoli nativamente dalle esportazioni classiche degli articoli di WordPress. Mantenuta la compatibilità all'indietro per il ripristino di file JSON pre-esistenti.
- **Risoluzione Bug Migrazione**: Disattivata l'esecuzione automatica, inutile a ogni caricamento della dashboard, dello script `migration-2.4.0.php`. Ora viene eseguita solo su esplicita richiesta (`?force_ntn_migration=1`). Corretto inoltre un errore di sintassi interno che ne comprometteva l'esecuzione.
- **Cleanup Pannello "NTN Journal Funzioni"**: Rimosse opzioni obsolete, non funzionanti o ridondanti come "Compressione Immagini" e "Link Motore Grafico".
- **Ottimizzazione Tassonomie (O(1) Lookup)**: Rivista la logica di interrogazione e associazione delle Hub Page. Sostituita una query intensiva (`posts_per_page => -1`) a favore di una ricerca diretta ottimizzata per slug, garantendo prestazioni massime anche su librerie di grandi dimensioni.

### 🛡️ Sicurezza e Conformità (Security)
- **Protezione Path Traversal**: Introdotta una rigida *whitelist* (`$allowed_tabs`) all'interno del file router della pagina di amministrazione. Questo impedisce in modo assoluto il caricamento di file non autorizzati tramite la manipolazione dei parametri dell'URL (`$_GET['tab']`).
- **Verifica ABSPATH nel Child Theme**: Aggiunto il controllo di sicurezza fondamentale `if ( ! defined( 'ABSPATH' ) ) { exit; }` all'interno del file template `content-game-card.php`, inibendo l'esecuzione diretta degli script.
- **Hardening Redirect Manager**: Corretto un bug logico dovuto a un errore di precedenza degli operatori (`&&` rispetto a `||`) nella normalizzazione degli URL e aggiunti controlli per prevenire *Notice* fatali in caso di valori nulli su `$_SERVER['REQUEST_URI']`.

### 🎨 Frontend e User Interface (Tema Parent & Child)
- **Sblocco Selezione Testi**: Rimossa la classe CSS `select-none` da elementi strutturali chiave (`front-page.php`, `404.php`, `related-posts.php`, `social-cta.php`), permettendo nuovamente ai lettori di selezionare, copiare e incollare liberamente i contenuti editoriali e i link diretti.
- **Pulizia File Ridondanti**: Rimosso il template clone `single-videogioco.php` all'interno del Child Theme, in quanto identico all'originale. Il fallback viene ora gestito correttamente in automatico dal Parent Theme, eliminando il rischio di futuri disallineamenti di codice.

### 🔍 SEO e Structured Data
- **Integrazione Google News Sitemap**: Aggiunto uno switch di controllo dedicato nella tab "Impostazioni SEO" e collegato al router interno per permettere il ping automatico dei soli contenuti d'interesse a Google News.

### ✅ Quality Assurance e Packaging (Release)
- **Certificazione 100% PHP 8.2 Strict Mode**: Zero notice, warning o errori fatali rilevati nella codebase.
- **Audit e Unit Testing**: Eseguito il check approfondito tramite la suite `audit_test_suite.php`. Risultato: **164 test eseguiti, 164 superati, 0 falliti**.
- **Packaging Validato (Zip Format)**: Sostituito il motore di compressione interno della CLI con `.NET System.IO.Compression`. Gli archivi ZIP della Beta v3.0.0 sono stati correttamente generati e conformati allo standard gerarchico dei pacchetti di installazione nativi WordPress (singola root folder, zero path root e slash Unix).
