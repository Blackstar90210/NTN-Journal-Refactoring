# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.14.3 (Dati per Google: nome del gioco e piattaforme)

Correzione dei dati strutturati dei videogiochi. Prima di questa versione Google poteva ricevere il titolo di un articolo come nome del gioco e piattaforme che non sono piattaforme (Nintendo, Indie, Guide, Mobile, Hardware, "Console").

---

### 🐞 1. Nome del gioco

- Se un articolo non ha un nome del gioco salvato, il plugin usava il titolo dell'articolo, per esempio "Hillthorn esce il 10 novembre su PC, Switch e mobile".
- Ora il titolo di un articolo non vale più come nome di gioco. Resta valido solo per la scheda videogioco. Un articolo senza gioco collegato non invia più nessun VideoGame.
- Per riavere il gioco nei dati per Google basta compilare il tag del gioco (nome o ID IGDB).

### 🛠️ 2. Piattaforme

- Le piattaforme dei dati strutturati vengono solo dalle categorie dell'articolo, senza dipendere da IGDB.
- Una categoria è una piattaforma se non ha sottocategorie e sta dentro (o è) una Hub di tipo "Piattaforme", come Console, Mobile o PC Gaming. Marchi (Nintendo, Sony, Microsoft) e categorie come Indie, Guide, Hardware, Notizie non contano.
- "PC Gaming" resta scritto "PC". Se l'articolo non ha piattaforme il campo non c'è più (prima compariva "PC, Console").
- Valido sia per The SEO Framework sia per i dati propri del plugin.

### ⚠️ 3. Ordine di aggiornamento

- Aggiorna il plugin. Il tema genitore e il figlio cambiano solo il numero di versione.
- Svuota la cache del sito dopo l'aggiornamento.
- Non cambiano: le Hub dei giochi (le loro piattaforme restano quelle salvate), gli articoli con più giochi e la data del trailer.

### ✅ 4. Test

- Nuova sezione 79 (4 controlli). La suite passa al 100%.
