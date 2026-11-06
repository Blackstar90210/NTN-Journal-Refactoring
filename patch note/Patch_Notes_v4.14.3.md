# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.14.3 (Dati per Google: nome del gioco e piattaforme)

Correzione dei dati strutturati dei videogiochi. Prima di questa versione Google poteva ricevere il titolo di un articolo come nome del gioco e piattaforme che non sono piattaforme (Nintendo, Indie, Guide, Mobile, Hardware, "Console").

---

### 🐞 1. Nome del gioco

- Se un articolo non aveva un nome del gioco salvato, il plugin usava il titolo dell'articolo, per esempio "Hillthorn esce il 10 novembre su PC, Switch e mobile".
- Ora il titolo di un articolo non vale più come nome di gioco (resta valido solo per la scheda videogioco).
- Il nome è quello salvato nel tag del gioco (di solito da IGDB). Se il tag non ha dati, l'articolo non invia nessun VideoGame. Nessuna casella sui tag.

### 🛠️ 2. Piattaforme

- Vincono le categorie dell'articolo. Una categoria è una piattaforma se ha la nuova casella **"È una piattaforma"** (Modifica categoria), oppure se non ha sottocategorie e sta dentro (o è) una Hub di tipo "Piattaforme" (Console, Mobile, PC Gaming).
- Marchi (Nintendo, Sony, Microsoft) e categorie come Indie, Guide, Hardware, Notizie non contano.
- Le piattaforme IGDB già salvate si aggiungono, senza doppioni anche con maiuscole diverse ("Xbox Series X|S" e "XBOX Series X|S"). IGDB non viene interrogato a ogni visita.
- "PC Gaming" resta "PC". Se non c'è nessuna piattaforma il campo non c'è più (prima compariva "PC, Console").
- Valido sia per The SEO Framework sia per i dati propri del plugin.

### ⚠️ 3. Dopo l'aggiornamento

- Aggiorna il plugin. Il tema genitore e il figlio cambiano solo il numero di versione. Svuota la cache.
- Per Mac, Linux e simili: spunta "È una piattaforma" nella loro categoria.
- Non cambiano: le Hub dei giochi, gli articoli con più giochi e la data del trailer.

### ✅ 4. Test

- Nuova sezione 79 (7 controlli). La suite passa al 100%.
