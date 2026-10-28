# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.14.0 (Hub di categoria: Console e Sviluppatori italiani)

Una categoria può diventare una **Hub di sezione**, con fascia a matrice di punti, disegno a punti, numeri, articolo in evidenza e indice dei contenuti. Riferimento visivo: le pagine "Hub console" e "Hub studi" del mockup (`docs/mockup/ntn-mockup-completo.html`, versione 19). Gli slug delle categorie non cambiano: sul sito vero restano `/sezione/console/microsoft/xbox-series-x/` e simili.

---

### ⚙️ 1. Impostazioni nella categoria (plugin, `inc/category-hub.php`)

- Nella pagina di modifica di ogni categoria c'è la nuova sezione **"Hub di sezione (NTN Core)"**:
  - Mostra come Hub;
  - Tipo: Console oppure Studi;
  - Titolo mostrato, per esempio "PlayStation" sulla categoria Sony;
  - Testo di presentazione;
  - Illustrazione a punti: automatica, Joy-Con, DualSense, controller Xbox, Italia o nessuna;
  - Articolo in evidenza;
  - Studi da escludere.
- Il salvataggio è protetto con nonce, controllo dei permessi (`manage_categories`) e pulizia dei dati.
- **Come si usa**:
  - la spunta va messa solo sulla Hub principale, cioè "Console" o "Sviluppatori italiani di videogiochi";
  - i marchi, cioè le figlie dirette di Console, aprono in automatico la stessa Hub con il marchio selezionato.
- I dati della Hub (marchi, piattaforme, studi, giochi, numeri) sono tenuti in memoria per 6 ore e si aggiornano a ogni pubblicazione o modifica di categorie e tag.

### 🎮 2. Hub Console (tema, `category-hub.php`)

- **Fascia di apertura**:
  - percorso di navigazione, etichette e titolo grande;
  - testo di presentazione e numeri: piattaforme, giochi, articoli e ultimo aggiornamento;
  - controller a punti del marchio. Su "Tutte" compaiono Joy-Con, DualSense e controller Xbox affiancati.
- **Selettore dei marchi**: Tutte · Microsoft · Nintendo · Sony, in ordine alfabetico. Il marchio attivo è segnato anche per i lettori di schermo.
- **"// Piattaforme"**: una scheda per console, con la console più vecchia a sinistra. Ogni scheda ha il numero di articoli, i giochi principali e l'ultimo articolo.
- **"// In evidenza"**: stessa impaginazione dell'apertura della Home, con un articolo grande e tre piccoli.
  - L'articolo grande si sceglie così: quello indicato nelle impostazioni, altrimenti l'ultima recensione, altrimenti l'ultimo articolo.
  - Se c'è un solo articolo, prende tutta la larghezza, con l'immagine a sinistra e il testo a destra.
- **"// Tutti gli articoli"**: filtro per console (`?console=…`), righe compatte con la console in rosso e paginazione.
  - Le pagine filtrate non vengono indicizzate (`noindex, follow`), per non creare doppioni nei motori di ricerca.

### 🇮🇹 3. Hub Sviluppatori italiani

- Fascia con la mappa d'Italia a punti, la stessa della Home, ed etichetta "Made in Italy".
- **"// Indice degli studi"**: gli studi sono ricavati dagli sviluppatori dei giochi del catalogo, con filtro A–Z e, per ogni studio, giochi, numero di articoli e ultimo articolo.
- Numeri: studi, giochi, articoli e ultimo aggiornamento.

### 🧭 4. SEO e navigazione

- **Percorso di navigazione**: per le categorie con genitori mostra tutti i livelli, per esempio Home // Console // Sony // PlayStation 5, con il titolo mostrato della Hub. I dati strutturati del percorso seguono gli stessi livelli.
- **Dati strutturati**: le Hub pubblicano un elenco `ItemList` delle piattaforme o degli studi.

### 🧩 5. File nuovi

- Plugin: `inc/category-hub.php`.
- Tema:
  - `category-hub.php`;
  - `inc/category-hub-template.php`, che usa il modello solo per le Hub;
  - `assets/js/dot-art.js`, con i disegni a punti e il filtro A–Z, caricato solo sulle Hub.
- Stili `.ntn-chub-*` e `.ntn-pick-btn` in `build/input.css`.

---

### ✅ 6. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: nessun errore.
- **Suite di collaudo**: **343 test su 343 superati**.
  - Nuova suite 43 con 8 controlli: impostazioni, sicurezza del salvataggio, titoli e disegni, ordine dei marchi, cache, filtro senza indicizzazione, modello del tema, script e percorso di navigazione.
  - Il test 40.1 ora ignora le costanti PHP come `STR_PAD_LEFT`, che non sono etichette.
- **Prova sul sito Local**, con la gerarchia delle categorie impostata dal CEO:
  - Hub Console con Tutte e i singoli marchi;
  - Hub Sviluppatori italiani;
  - esito corretto in chiaro, scuro e da telefono, senza scorrimento orizzontale e senza nuovi errori nel registro PHP.
- **Sincronizzazione 4 header (SemVer 3.14.0, MINOR)**.
- **Pacchetti**: ZIP 3.13.1 spostati in `archive/`, creati i tre ZIP 3.14.0 con `package_release.ps1`.

### ⚠️ Note

- **Sul sito vero**, dopo l'aggiornamento: spuntare "Mostra come Hub" solo su "Console" (tipo Console) e su "Sviluppatori italiani di videogiochi" (tipo Studi). Il titolo mostrato "PlayStation" su Sony e "Xbox" su Microsoft è facoltativo.
- Le pagine delle singole piattaforme, come PlayStation 5, restano archivi di categoria.
- Su Local la Hub Sviluppatori italiani mostra 0 studi, perché i giochi collegati non hanno ancora lo sviluppatore dal catalogo. Sul sito vero l'indice si riempie da solo.
- **Non inclusi in questa versione**:
  - il pannello di controllo della copertura;
  - l'unione dei dati strutturati con quelli di The SEO Framework;
  - i punti delle città sulla mappa d'Italia.
