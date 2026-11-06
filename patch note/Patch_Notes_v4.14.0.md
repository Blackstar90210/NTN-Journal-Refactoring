# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.14.0 (Google riceve gli stessi dati del gioco che vede il lettore)

Ultimo passo della fase 2 della revisione di coerenza tra plugin e temi. I dati strutturati del gioco (Schema.org `VideoGame` nelle recensioni, negli articoli e negli Hub) ora si completano con gli stessi dati della scheda del gioco. Le pagine non cambiano; cambia solo il JSON-LD che legge Google, dove prima mancavano dati.

---

### 🔎 1. Una sola strada per i dati del gioco

- `ntn_get_game_view_model()` (in `inc/api-helpers.php` del plugin) prima leggeva solo i dati scritti sull'oggetto stesso. Un articolo con il tag del gioco ma senza dati IGDB propri mandava a Google un gioco senza editore, generi, piattaforme, trailer e screenshot, anche se la scheda nella pagina li mostrava.
- Ora i campi vuoti si completano con `ntn_get_hub_igdb_info()`, la stessa funzione della scheda:
  - un tag del gioco e una scheda videogioco si completano a vicenda;
  - un articolo usa il gioco di cui è la recensione, oppure il suo unico gioco. Con più giochi e nessuna recensione corrispondente non si completa nulla, come la scheda, che in quel caso non mostra il voto.
- I dati scritti sull'oggetto vincono sempre: si riempiono solo i campi vuoti.
- Si completano solo i campi che la scheda del gioco mostra al lettore: titolo, copertina, data di uscita, sviluppatore, editore, piattaforme, generi e motore. Trailer e screenshot no: nella pagina di un articolo non si vedono, e Google chiede che i dati strutturati descrivano quello che la pagina mostra.
- La data di uscita del gioco (`datePublished`) arriva anche dal tag del gioco, con la sua precisione (giorno, mese, trimestre o anno). Prima si leggeva solo dall'articolo.
- I generi sono in italiano, come nella pagina.
- Le piattaforme IGDB del PC ("PC (Microsoft Windows)", "Windows") diventano "PC", come la categoria del sito: niente più "PC" e "PC (Microsoft Windows)" insieme.

### 📋 2. Chi usa questi dati

- Dati strutturati della recensione e dell'articolo (`schema-markup.php`) e del gioco negli Hub (`seo-extras.php`). Le chiamate non cambiano.

### ⚠️ 3. Ordine di aggiornamento

- Prima il plugin, poi il tema genitore, poi il figlio.

### ✅ 4. Test

- Nuova sezione 77: riempimento dei campi vuoti, campi propri che vincono, niente trailer e screenshot dal tag, data di uscita, nomi del PC, scelta del gioco, generi in italiano. La suite passa al 100%.
- Prima del rilascio: confronto del JSON-LD con la 4.13.0 sulle pagine del sito Local.
