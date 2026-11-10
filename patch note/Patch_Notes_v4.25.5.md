# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.25.5 (Piccole correzioni dallo standard WordPress)

Secondo passo del piano "NTN Journal e gli standard WordPress". Sono correzioni di una riga, senza cambiamenti visibili ai lettori. Le segnalazioni su input, output, traduzioni e `in_array` sono a zero (PHPCS con le regole del progetto: da 1.521 a 1.441 in totale; il resto è soprattutto commenti PHPDoc e query dirette, che restano).

### 🔒 Input letti con più cura
- I nonce dei moduli (comunicato stampa, box recensione, redirect) passano per `wp_unslash` e `sanitize_text_field` prima di `wp_verify_nonce`.
- Modulo dei redirect: origine, destinazione, codice e hash da eliminare hanno un valore di riserva se il campo manca (prima c'era un avviso PHP); la pulizia dei valori è la stessa di prima.
- L'indirizzo richiesto (`REQUEST_URI`) nei redirect e nella sitemap news passa per `wp_unslash`: WordPress aggiunge le barre davanti alle virgolette anche qui. Resta solo confrontato, mai stampato.
- Controllo aggiornamenti IGDB: l'elenco degli ID viene pulito (numeri interi) già all'ingresso. Stesse risposte di prima per elenco mancante, vuoto o non valido.
- Pulizia PNG, conferma "backup fatto": `isset` + `sanitize_text_field(wp_unslash(...))`. Vale "1" come prima, qualsiasi altro valore è "no".
- Caricamento del backup: annotato il percorso del file temporaneo (lo crea PHP, non l'utente).

### 🖨️ Output con escape
- I nonce stampati dentro gli script (scheda IGDB, migrazione IGDB: 16 punti) passano per `esc_js`.
- Etichette del box comunicato stampa: `_e()` diventa `esc_html_e()` (7).
- Data `lastmod` della sitemap news: `esc_html`. LED dell'intestazione scheda: `wp_kses_post`.
- Annotati con il motivo i punti che stampano HTML fisso scritto nel codice (scheda gioco, pulsante Piattaforme, CSS dei font, sitemap già costruita).

### 🌍 Traduzioni
Tre testi con segnaposto `%s` hanno il commento per i traduttori (invito Google News, URL social nel Personalizza, "Leggi l'articolo").

### 🎯 `in_array` stretto
Tipi dello schema (`Article`, `NewsArticle`, `BlogPosting`, `WebPage`), tipo di contenuto della scheda IGDB, ID dei tag e voto della recensione (in due modelli) ora confrontano anche il tipo del valore.

### 🤫 Le `@` restano, ma spiegate
Delle 16 `@` solo davanti a funzioni sui file (`getimagesize`, `rename`, `unlink`, `file_put_contents`, `filesize`...) ho scelto di **non toglierle**: servono a non stampare avvisi PHP quando un'immagine è rovinata o manca, e un avviso nel mezzo di una risposta AJAX romperebbe il pannello. Ognuna ora dice il perché (`phpcs:ignore ... -- motivo`).

### ✅ Test
- Nuova suite 102 (102.1-102.7): nonce, campi redirect, stesse risposte di prima per elenco ID IGDB e flag backup (9 tipi di richiesta, vecchia e nuova espressione a confronto), `esc_js` e `esc_html_e`, `in_array` stretto, commenti per i traduttori e `@` annotate.
- Confronto token per token con la 4.25.4: cambiano solo i 14 file elencati nelle voci sopra.
- Suite: 623 test, 0 falliti. `php -l` su tutti i file cambiati: nessun errore.
- Versione 4.25.5 nei 4 header e nei controlli 24.x e 29.1.

### Ordine di installazione
Plugin, poi tema parent, poi child.
