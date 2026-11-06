# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.15.2 (Dati per Google: voto delle recensioni e nomi con lettere accentate)

Due difetti emersi dalla verifica della 4.15.1 sul sito online.

---

### 🐞 1. Recensione che perdeva il voto (es. Assassin's Creed Black Flag Resynced)

Con i tag "Assassin's Creed" e "Black Flag Resynced" insieme, il campo **"Gioco recensito"** corrispondeva a tutti e due i nomi. L'articolo risultava "con più giochi" e Google non riceveva il voto.

Ora, anche con il campo "Gioco recensito":
- se un gioco ha il nome scritto esattamente come nel campo, vale quello;
- altrimenti vince il nome più lungo, e un nome corto che fa da inizio a un altro gioco presente viene scartato.

### 🐞 2. Nomi scritti male (es. "Pok&amp;#233;mon")

Alcuni nomi sono salvati nel database con le lettere accentate scritte due volte come codice (`&amp;#233;`). Google riceveva il codice al posto di "é". Ora nomi di giochi, sviluppatori ed editori sono convertiti in lettere vere anche in questo caso.

### ⚠️ Dopo l'aggiornamento

- Aggiorna il plugin; tema genitore e figlio cambiano solo il numero di versione. Svuota la cache.
- Restano da sistemare a mano, nel pannello, i tag sbagliati: The Last Campfire (collegato a No Man's Sky), The Last Remnant (a Final Fantasy XVI), Haven (a Furi).
- Non cambiano: data del trailer, voto assente nelle recensioni dove non è compilato.

### ✅ Test

Sezione 81 della suite (2 prove nuove). Totale 545 prove, tutte superate.
