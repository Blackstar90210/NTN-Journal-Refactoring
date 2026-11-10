# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.25.4 (Regole di stile WordPress e formattazione automatica)

Primo passo del piano "NTN Journal e gli standard WordPress". **Nessun cambiamento di comportamento**: cambia solo l'aspetto del codice (spazi, rientri, a capo, virgolette), non quello che il sito fa.

### 📐 Regole di stile nel progetto
Nuovo file `phpcs.xml.dist` nella radice del repository (non entra negli ZIP). Dice a PHP_CodeSniffer di controllare plugin, tema parent e tema child con le regole ufficiali WordPress:
- domini di testo `ntn-journal-core`, `ntn-journal`, `ntn-journal-reloaded`;
- prefissi `ntn_` / `NTN_` (il codice nuovo del plugin usa `ntn_core_`, vedi `AGENTS.md`);
- WordPress 6.0+ e PHP 8.0+, come dichiarato negli header;
- le funzioni del progetto che verificano il nonce (`ntn_journal_core_admin_nonce_ok`, `ntn_igdb_scan_guard`, `ntn_core_png_repair_guard`, `ntn_core_image_report_guard`), così PHPCS non segnala più falsi "nonce mancante".

### 🧹 Formattazione automatica (phpcbf)
98 file PHP sistemati in automatico. Segnalazioni PHPCS: **da 3.845 a 1.521**.

Controllo di equivalenza: ho confrontato il codice prima e dopo pezzo per pezzo (token PHP, senza spazi e commenti). Le sole differenze sono forme equivalenti:
- virgola finale negli elenchi su più righe (152);
- `$i++` da solo diventa `++$i` (25);
- graffe aggiunte agli `if` su una riga (11);
- virgolette doppie diventate singole dove non c'erano variabili (19);
- `else if` / `else { if` / `else : if … endif` diventati `elseif` (3, tra cui il logo testuale nell'header).
L'HTML stampato è invariato, a parte gli spazi.

### ✅ Test
- I controlli della suite che leggono il testo del codice (37.4, 39.6, 56.2, 56.3, 65.13, 72.2, 72.5, 80.6, 86.3, 87.1, 98.3) ora ignorano a capo, rientri, allineamenti e virgola finale (`ntn_t_fpos` / `ntn_t_fcount`), perché la formattazione li spezzava su più righe. Il testo cercato resta lo stesso, parola per parola.
- Nuova suite 101: regole PHPCS presenti e corrette (101.1), funzioni del nonce dichiarate ed esistenti (101.2), il confronto ignora solo gli spazi e non fa passare codice diverso (101.3).
- Versione 4.25.4 nei 4 header e nei controlli 24.x e 29.1.
- Suite: 616 test, 0 falliti. `php -l` su tutti i file cambiati: nessun errore. PHPCompatibility (PHP 8.0+): nessun problema.

### Come si usa (facoltativo)
Con PHP_CodeSniffer e WordPress Coding Standards installati: `phpcs` dalla radice del repository mostra cosa resta; `phpcbf` sistema la formattazione dei file nuovi.

### Ordine di installazione
Plugin, poi tema parent, poi child.
