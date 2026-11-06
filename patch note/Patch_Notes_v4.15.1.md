# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.15.1 (Dati per Google: il gioco giusto negli articoli con più giochi)

Prima di questa versione, un articolo con più tag di gioco mandava a Google solo il primo gioco in ordine alfabetico, spesso quello sbagliato (per esempio una recensione di Saros che descriveva Returnal).

---

### 🐞 1. Quale gioco descrive l'articolo

Quando un articolo ha più giochi collegati, il plugin sceglie così:

1. Se la recensione ha il campo **"Gioco recensito"**, vale solo il gioco che corrisponde.
2. Altrimenti valgono i giochi il cui nome compare nel titolo dell'articolo (il confronto ignora maiuscole, accenti e simboli).
3. Altrimenti valgono **tutti** i giochi collegati (al massimo 12), ciascuno con i propri dati: copertina, sviluppatore, editore, generi, data di uscita. Mai più un gioco scelto a caso.

### 🛠️ 2. Nel grafo di Google

- Un `VideoGame` per ogni gioco descritto, collegato all'articolo con `about`.
- Recensione con un gioco solo: `itemReviewed` indica quel gioco.
- Recensione con più giochi e nessun "Gioco recensito": niente `itemReviewed` a caso, l'articolo parla di tutti.
- Piattaforme: valgono le categorie dell'articolo, a cui si aggiungono quelle IGDB del singolo gioco.
- Articoli con un gioco solo, o nessuno: nessun cambiamento.

### ⚠️ Dopo l'aggiornamento

- Aggiorna il plugin. Tema genitore e figlio cambiano solo il numero di versione. Svuota la cache.
- Non cambiano: la data del trailer, le Hub dei giochi, il percorso dati del plugin senza The SEO Framework.

### ✅ Test

Sezione 81 della suite (8 prove nuove). Totale 540 prove, tutte superate.
