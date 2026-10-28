# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.21.0 (Hub dello studio)

Idea del CEO: trasformare la pagina di ogni studio in una Hub dove trovare i videogiochi trattati nel sito. Disegnata nel mockup (Artifact versioni 30 e 31) e approvata, con gli articoli prima dei giochi come chiesto in un commento.

---

### 🏢 1. Quali pagine diventano Hub

- Il tag di uno studio diventa la sua Hub **sullo stesso indirizzo**, per esempio /tag/3dclouds/: niente slug nuovi, niente rimandi, niente da accendere a mano.
- Lo diventa ogni tag il cui nome corrisponde allo **sviluppatore IGDB** di almeno un gioco del catalogo.
  - Contano solo gli sviluppatori, non gli editori.
  - Il nome si confronta senza spazi, simboli e maiuscole: "3D Clouds" e "3DClouds" sono lo stesso studio.
- Gli altri tag restano come prima. I tag dei giochi continuano a portare alla Hub del gioco.

### 🖼️ 2. La pagina

- **Testata a matrice di punti**, come le altre Hub:
  - etichetta "Studio", più "Made in Italy" se lo studio è nell'indice della Hub "Sviluppatori italiani";
  - nome e descrizione automatica, oppure quella scritta a mano nel tag;
  - contatori: giochi, articoli, ultimo articolo.
- **Percorso**: Home // Sviluppatori italiani di videogiochi // 3DClouds.
- **"// Tutti gli articoli"** subito sotto la testata.
  - In rosso il gioco dello studio di cui parla l'articolo.
  - In grigio il nome dello studio, se l'articolo parla dello studio.
- **"// Giochi dello studio"** dopo gli articoli:
  - le copertine del catalogo, dal gioco più recente;
  - data o finestra di uscita come nel catalogo (per esempio "2027" o "18.09.2025");
  - numero di articoli;
  - ogni scheda porta alla Hub del gioco.
- **Collegamenti**:
  - l'indice della Hub "Sviluppatori italiani" porta già alle pagine degli studi;
  - nella scheda della Hub di ogni gioco, "Sviluppatore" porta già al tag dello studio, che ora è la sua Hub.

### 🔎 3. Google

- La Hub dello studio è visibile con **almeno 2 giochi nel catalogo oppure 10 articoli**. Sotto la soglia resta utile ai lettori ma nascosta a Google (noindex, follow).
- Dati per Google: l'elenco dei giochi dello studio (ItemList di VideoGame). Si spegne con il freno d'emergenza dello Schema Markup.
- Nuovo modulo del plugin: `inc/studio-hub.php`. Nuova pagina del tema: `studio-hub.php`, caricata da `tag.php`.

### 📅 4. Date di uscita nelle schede del gioco

Richiesto dal CEO prima del commit.

- Prima:
  - la scheda della Hub del gioco, quella del gioco negli articoli e nelle recensioni (da computer e da telefono) mostravano "Uscita 31/12/2028" anche quando si conosce solo l'anno;
  - la tabella "Prossime uscite" della Home avrebbe mostrato "31.12.2026".
- Ora mostrano solo le parti note, come il catalogo:
  - "23/02/2027" con il giorno (nella Home "23.10.2026");
  - "Ottobre 2027" con il mese;
  - "Q3 2027" con il trimestre;
  - "2028" con solo l'anno.
- Nella Home l'evidenza "a breve" vale solo per i giochi con il giorno.
- Senza precisione salvata, cioè gioco non ancora aggiornato dallo Scanner IGDB, un 31 dicembre vale "solo l'anno" e le altre date restano complete.
- Nuova funzione del tema: `ntn_journal_release_label()`. La precisione è ora anche nei dati IGDB del tema (`release_precision`).

---

### ✅ 5. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su tutti i file modificati e nuovi.
- **Suite di collaudo**: **391 test su 391 superati**.
  - Nuova suite 54 con 4 controlli.
  - Aggiunto al banco di prova il sostituto di `remove_accents`.
- **Prova sul sito Local**, solo in lettura:
  - /tag/3dclouds/ è la Hub dello studio: "Made in Italy", percorso, 2 giochi (AfterZero: The Last Drive "2027", Formula Legends "18.09.2025"), 7 articoli;
  - è di nuovo visibile a Google, perché ha 2 giochi;
  - dati strutturati presenti;
  - i tag normali (Steam, Retroware…) restano archivi;
  - i tag dei giochi portano alla loro Hub;
  - schede del gioco: Tomb Raider: Catalyst "2028", Spine "Q3 2027", Fable "23/02/2027", AfterZero nell'articolo "2027";
  - nessun errore PHP.
- **Sincronizzazione 4 header (SemVer 3.21.0, MINOR)**.
- **Pacchetti**: ZIP 3.20.0 spostati in `archive/`, creati i tre ZIP 3.21.0 con `package_release.ps1`.

### ⚠️ Note

- L'elenco degli studi è tenuto in memoria per un'ora: un gioco appena collegato con lo Scanner IGDB compare nella Hub del suo studio entro un'ora.
