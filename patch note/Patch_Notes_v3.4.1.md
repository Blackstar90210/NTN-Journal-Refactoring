# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.4.1 (Correzione: articoli con commenti)

Questo aggiornamento corregge l'errore fatale che bloccava ogni articolo con almeno un commento. L'errore era presente anche nelle versioni pubblicate fino alla 3.2.15.

---

### 🐞 1. Errore fatale negli articoli con commenti

- **Sintomo**: gli articoli con commenti mostravano "Si è verificato un errore critico" (risposta 500). Sul sito di prova capitava con l'articolo `hello-world`.
- **Causa**: `comments.php` passava a `wp_list_comments()` la funzione `ntn_journal_custom_comment`, ma la dichiarava solo in fondo al file, dentro un `if ( ! function_exists() )`. PHP crea le funzioni dichiarate dentro un `if` solo quando l'esecuzione arriva a quella riga. Al momento della chiamata la funzione non esisteva ancora, quindi `call_user_func()` falliva.
- **Correzione**: la funzione è stata spostata nel nuovo file `inc/comments-template.php`, caricato da `functions.php` all'avvio del tema. `comments.php` contiene solo il modello.

### 🧩 2. Lista dei commenti con tag bilanciati

La stessa funzione chiudeva ogni commento con `</li>`, ma WordPress lo chiude già da solo (`Walker_Comment::end_el`). Ne risultava un `</li>` in più per commento. Ora il tag resta aperto e lo chiude WordPress.

---

### ✅ 3. Quality Assurance, Test Suite e Packaging

- **Prima e dopo sul sito Local**: `/hello-world/` passa da 500 a 200, mostra il commento e la lista ha un `<li>` aperto e uno chiuso.
- **Suite di collaudo**: **278 test su 278 superati**. La nuova suite 32 ha 3 controlli: funzione definita nel file caricato all'avvio, `<li>` lasciato aperto, esecuzione reale del callback su un commento di prova. Aggiornati i test 24.x e 29.1 (versione) e il 28.18, che ora cerca l'escaping della data nel nuovo file.
- **Gate di sicurezza**: il codice del commento è invariato. L'escaping di data, ora e iniziali resta lo stesso; l'avatar è markup generato da WordPress.
- **Sincronizzazione 4 header (SemVer 3.4.1, PATCH)**.
- **Pacchetti**: ZIP 3.4.0 spostati in `archive/`, creati i tre ZIP 3.4.1 con `package_release.ps1`.
