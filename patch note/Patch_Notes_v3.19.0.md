# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.19.0 (nuova ricerca, Scanner IGDB senza colori)

Nuova ricerca del sito, disegnata nel mockup (Artifact versione 29) e approvata dal CEO. In più, lo Scanner IGDB di NTN Journal Funzioni passa al solo nero, bianco e rosso e ha il collegamento al Catalogo.

---

### 🔎 1. Barra di ricerca

- **A tutta larghezza**, sotto la testata e larga come il contenuto:
  - a sinistra "// Cerca", poi il campo, a destra il pulsante **Cerca**;
  - testo a 16 pixel: da telefono il browser non ingrandisce più la pagina quando tocchi il campo;
  - bordo grigio, rosso solo mentre scrivi;
  - al posto della "×" azzurra del browser c'è **Cancella**.
- **Apertura curata**:
  - la barra scende morbida;
  - una linea rossa si disegna da sinistra a destra e si spegne;
  - campo e suggerimenti salgono in sequenza;
  - la lente diventa una ×;
  - alla chiusura la barra risale.
- Chi nel sistema ha chiesto di ridurre le animazioni la vede comparire senza movimento.
- **Esc** chiude prima i suggerimenti, poi la barra.

### 💡 2. Suggerimenti mentre scrivi

- Dalla seconda lettera, divisi in due gruppi:
  - **"// Articoli"**: fino a 4 articoli pubblicati, con categoria principale (o "Recensione") e data;
  - **"// Giochi"**: fino a 3 giochi del catalogo, con copertina, studio e numero di articoli; portano alla Hub del gioco.
- Accanto a ogni gruppo, il numero totale dei risultati.
- La parola cercata è sottolineata in rosso.
- In fondo, **"Tutti i risultati per «…» →"** apre la pagina completa.
- Con le frecce ↑ ↓ ci si sposta tra i suggerimenti, Invio apre quello scelto; senza sceglierne uno, Invio apre la pagina completa.
- Se non trova niente: "Nessun risultato per «…»", con un consiglio e il link alla ricerca completa.
- **Prima** i suggerimenti cercavano solo nel catalogo dei giochi: con «toxic» compariva "Nessun risultato" anche se l'articolo esisteva. Ora lo trovano.

### 📄 3. Pagina dei risultati

- Nel percorso in alto c'è **"Ricerca"** al posto di "Archivio".
- Il titolo è tra « », il contatore dice **"42 Risultati"**.

### 🎮 4. Scanner IGDB (NTN Journal Funzioni)

- Tolti i riquadri azzurri, rosa e gialli, e i messaggi verdi e azzurri: ora solo nero, bianco e rosso, come le altre schede. Gli errori restano rossi.
- Nuovo pulsante **"Apri il Catalogo ↗"** in alto a destra, che apre il catalogo dei videogiochi del sito in una nuova scheda.
- Le caselle spuntate di tutta la schermata NTN Journal Funzioni sono nere invece che blu (WordPress 7 le colorava di blu).

---

### ✅ 5. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su tutti i file modificati; `node --check` su `live-search.js` e `main.js`.
- **Suite di collaudo**: **376 test su 376 superati**.
  - Nuova suite 50 con 5 controlli.
  - Aggiunto al banco di prova il sostituto di `is_search`.
- **Prova sul sito Local**, solo in lettura:
  - «toxic» trova l'articolo su Toxic Crusaders;
  - «ace combat» mostra 4 articoli su 41 e la Hub del gioco; le frecce selezionano;
  - «hollow» mostra "Nessun risultato";
  - la pagina dei risultati dice "Ricerca" e "42 Risultati";
  - da telefono la barra e i suggerimenti stanno nello schermo, senza scorrimento laterale;
  - Scanner IGDB senza colori, con il link al Catalogo funzionante;
  - nessun errore PHP nuovo.
- **Sincronizzazione 4 header (SemVer 3.19.0, MINOR)**.
- **Pacchetti**: ZIP 3.18.0 spostati in `archive/`, creati i tre ZIP 3.19.0 con `package_release.ps1`.

### ⚠️ Note

- I suggerimenti restano in memoria 5 minuti: un articolo appena pubblicato può comparire con qualche minuto di ritardo. La pagina completa dei risultati lo mostra subito.
