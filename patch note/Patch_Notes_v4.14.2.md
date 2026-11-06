# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.14.2 (La scheda del videogioco non allarga più la pagina)

Correzione urgente. In un articolo con più giochi, come "Le demo della settimana: End of Ember, Pizzapocalypse e altri 3 videogiochi", la scheda del videogioco nella colonna laterale si allargava a circa 800 px, spingeva la pagina oltre lo schermo e mostrava la copertina enorme. Succedeva su schermi larghi (da 1280 px in su), dove la colonna laterale è visibile.

---

### 🐞 1. Causa

- La colonna laterale è una griglia (`.ntn-art-aside-sticky`) con una sola colonna implicita. Una colonna implicita prende la larghezza minima dei suoi contenuti.
- La copertina dei giochi è uno screenshot IGDB con `width="1280" height="720"`. Nel carosello le sue dimensioni facevano crescere la scheda, e con lei la colonna e la pagina intera (larghezza 1833 px su schermo da 1440 px).
- Su telefono e tablet non succede, perché la scheda laterale non c'è: lì c'è la testata mobile.

### 🛠️ 2. Correzione (solo CSS del tema genitore)

- `.ntn-art-aside-sticky` ora ha `grid-template-columns: minmax(0, 1fr)`: la colonna resta di 320 px qualunque cosa contenga.
- `.ntn-gcard` ha `min-width: 0`: la scheda può restringersi alla colonna.
- Provato sulla pagina vera con il CSS nuovo: scheda larga 320 px e pagina larga quanto lo schermo a 1280, 1440 e 1920 px; testata mobile a 390 px invariata.

### ⚠️ 3. Ordine di aggiornamento

- Basta aggiornare il tema genitore. Il plugin e il tema figlio cambiano solo il numero di versione.
- Dopo l'aggiornamento svuota la cache del sito.

### ✅ 4. Test

- Nuova sezione 78: la correzione è nel CSS sorgente e in quello compilato. La suite passa al 100%.
