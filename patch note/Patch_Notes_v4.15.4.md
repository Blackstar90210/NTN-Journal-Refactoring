# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.15.4 (Lettere accentate nei dati per Google con iubenda)

Sul sito online tutti i blocchi JSON-LD uscivano con le lettere trasformate in codici (`Pok&amp;#233;mon`, `non c&amp;#8217;&amp;#232;`), anche i titoli e il nome dell'editore. Sul Local no.

---

### 🐞 Causa

Il plugin **iubenda** (motore "new") rielabora tutta la pagina e converte ogni lettera non inglese in un codice numerico. Dentro gli script il codice resta scritto. Non dipende da NTN né da The SEO Framework.

### 🛠️ Correzione

Nuovo file `inc/iubenda-compat.php`: alla fine del lavoro di iubenda (filtro `iubenda_final_output`) il plugin riporta le lettere vere **solo dentro i blocchi JSON-LD**. I codici dei caratteri ASCII (virgolette, apici) restano com'erano, così il JSON resta valido. Il resto della pagina e il blocco dei cookie non vengono toccati. Senza iubenda non cambia nulla.

Include la 4.15.3 (nomi con codici doppi anche nelle Hub, nei titoli SEO, nelle descrizioni dei tag e negli studi).

### ⚠️ Dopo l'aggiornamento

- Aggiorna il plugin; tema genitore e figlio cambiano solo il numero di versione. Svuota la cache.
- Controllo: nel sorgente della pagina cerca `"headline"` dentro `application/ld+json`: deve comparire "Pokémon", non `Pok&amp;#233;mon`.
- Se non cambia, il nome del filtro di iubenda potrebbe essere diverso nella sua versione: serve guardare il suo codice.

### ✅ Test

Prova 81.12 nuova. Totale 547 prove.
