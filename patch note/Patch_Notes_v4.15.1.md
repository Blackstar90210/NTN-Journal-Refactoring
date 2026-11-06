# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.15.1 (Dati per Google: il gioco giusto negli articoli con più giochi)

Prima di questa versione, un articolo con più tag di gioco mandava a Google solo il primo gioco in ordine alfabetico, spesso quello sbagliato (per esempio una recensione di Saros che descriveva Returnal).

---

### 🐞 1. Quale gioco descrive l'articolo

Quando un articolo ha più giochi collegati, il plugin sceglie così:

1. Se la recensione ha il campo **"Gioco recensito"**, vale solo il gioco che corrisponde.
2. Altrimenti si cercano i giochi il cui nome compare nel titolo (nome intero, oppure la parte prima dei due punti: "Death Stranding 2: On the Beach" è riconosciuto da "Death Stranding 2"). Il confronto ignora maiuscole, accenti e simboli.
3. Vince il nome più lungo: "Death Stranding" non vale se il titolo parla di "Death Stranding 2", e "Assassin's Creed" non vale se nel titolo è seguito da "Black Flag Resynced".
4. Con una recensione, o con un titolo che comincia con il nome del gioco, vale solo quel gioco.
5. Nelle raccolte e nelle notizie su più giochi (per esempio "Le demo della settimana") valgono **tutti** i giochi collegati (al massimo 12), ciascuno con i propri dati: copertina, sviluppatore, editore, generi, data di uscita. Mai più un gioco scelto a caso.

### 🛠️ 2. Nel grafo di Google

- Un `VideoGame` per ogni gioco descritto, collegato all'articolo con `about`.
- Recensione con un gioco solo: `itemReviewed` indica quel gioco.
- Articolo con voto, più giochi e nessun gioco indicato (né nel titolo né in "Gioco recensito"): non diventa una Review (Google richiede il gioco recensito), resta un articolo che parla di tutti i giochi, senza voto.
- Piattaforme: valgono le categorie dell'articolo, a cui si aggiungono quelle IGDB del singolo gioco.
- Articoli con un gioco solo, o nessuno: nessun cambiamento.

### ⚠️ Dopo l'aggiornamento

- Aggiorna il plugin. Tema genitore e figlio cambiano solo il numero di versione. Svuota la cache.
- Non cambiano: la data del trailer, le Hub dei giochi, il percorso dati del plugin senza The SEO Framework.

### ✅ Test

Sezione 81 della suite (11 prove nuove). Totale 543 prove, tutte superate.
