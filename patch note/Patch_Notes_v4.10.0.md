# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.10.0 (Il tema figlio torna a contenere solo personalizzazioni)

Seconda fase della revisione di coerenza tra plugin e temi. Ricerca istantanea, filtri del catalogo, scheda del gioco e catalogo sono passati dal tema figlio al tema genitore. Le pagine del sito restano identiche, ma ora il genitore funziona anche da solo.

---

### 🧩 1. File spostati nel tema genitore

- `assets/js/live-search.js` e `assets/js/catalog-filters.js`: si caricano dal genitore, con gli stessi nomi e le stesse regole di prima (ricerca in ogni pagina, filtri nel catalogo e negli archivi di motore, franchise, sviluppatore ed editore).
- `template-parts/content-game-card.php`: la scheda del gioco. La usa anche l'endpoint REST del plugin per lo scorrimento infinito.
- `archive-videogioco.php`: il catalogo con filtri e scorrimento infinito sostituisce la vecchia versione del genitore, che non si usava più.
- Tolta la copia di `single-videogioco.php` dal figlio: era identica a quella del genitore.

### 🎨 2. CSS

- Gli stili del catalogo, delle immagini, delle gallerie e delle didascalie sono passati dallo `style.css` del figlio a `build/input.css` del genitore, in fondo al file e fuori dai layer, come prima. Il CSS compilato contiene le stesse regole aggiunte in fondo, il resto è identico byte per byte.
- Lo `style.css` del figlio ha solo l'intestazione: le tue personalizzazioni vanno lì.
- Il CSS del figlio ora si carica **dopo** il CSS compilato del genitore, così le personalizzazioni vincono a parità di specificità. I vecchi `!important` non sono stati toccati.
- Gli script del genitore usano la versione del genitore, non quella del tema attivo.

### ⚠️ 3. Ordine di aggiornamento

- Aggiorna prima il tema genitore, poi il figlio. Con il solo figlio nuovo e il genitore vecchio, ricerca e filtri non funzionano finché non arriva il genitore nuovo. Il plugin non cambia.

### ✅ 4. Test

- Aggiornati i test che leggevano i file del figlio. I test del catalogo ora disegnano la scheda del gioco vera, non più una scheda finta.
- Aggiunta la sezione 72 (il figlio contiene solo personalizzazioni). La suite passa al 100%.
