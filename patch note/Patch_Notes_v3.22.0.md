# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.22.0 (Hub Mobile e PC Gaming, disegni su griglia, tendina Piattaforme)

Tre lavori approvati dal CEO sul mockup (Artifact versioni 33–38).

---

### 📱 1. Hub Mobile e PC Gaming, come le console

- **Mobile**: Android e iOS fanno da marchi, come Nintendo, PlayStation e Xbox per le console.
  - Un marchio senza sottocategorie diventa lui stesso la scheda della piattaforma.
  - Selettore Tutte / Android / iOS; filtro degli articoli per piattaforma.
- **PC Gaming**: Hub senza marchi, con disegno proprio, in evidenza e tutti gli articoli. Il contatore "Piattaforme" non compare.
- **Disegni scelti in automatico** dal nome della categoria:
  - postazione PC per "PC";
  - telefono Android per "Android";
  - iPhone per "iOS" o "iPhone";
  - i due telefoni per "Mobile".
  - Si possono anche scegliere a mano nella scheda Hub.
- **Controllo copertura** (scheda Hub e Dashboard): un marchio senza sottocategorie, come Android e iOS, non viene più segnalato come "colonna vuota". Conta come piattaforma ed è segnalato solo se non ha articoli.
- **Testi della pagina** non più solo da console:
  - etichetta rossa "Tutte le console" per Console, "Tutte le piattaforme" per Mobile, il nome della sezione per PC Gaming, "Mobile Android" con un marchio scelto;
  - "Le piattaforme…", filtro "Tutte".
- Nella scheda Hub il tipo si chiama ora "Piattaforme: console, mobile, PC (marchi e dispositivi)".
- I dati delle Hub in memoria si rinnovano a ogni nuova versione del plugin.

### ⚫ 2. Disegni a punti su griglia fissa

- **Prima**: le forme erano disegnate libere e poi trasformate in punti. Tasti, frecce e fori potevano perdere punti o venire storti, e i tasti rossi erano cerchi sovrapposti.
- **Ora**:
  - ogni disegno ha una griglia fissa, un punto ogni 5 unità, e ogni forma si aggancia a quella griglia: tutto è simmetrico, senza punti mancanti;
  - i tasti rossi sono fatti di punti della stessa griglia.
- **Nuovi disegni**:
  - postazione PC con monitor, tastiera e mouse, con W A S D in rosso;
  - telefono Android con il foro della fotocamera;
  - iPhone con l'isola;
  - entrambi i telefoni hanno lo schermo bordato e un gioco con i comandi touch.
- **Croce del DualSense**: quattro bracci identici di 3 × 5 punti attorno a un centro pieno.
- Riguarda le Hub Console, Mobile e PC Gaming.

### 🧭 3. Tendina "Piattaforme"

- **Nuovo posto nella testata** (variante C del mockup):
  - "Piattaforme" non sta più nella riga del menu, accanto alle categorie;
  - è un pulsante squadrato a destra, con l'icona a punti del controller, prima della lente e del chiaro/scuro;
  - una linea sottile lo separa dagli altri due pulsanti;
  - a sinistra restano le categorie (cosa leggere), a destra gli strumenti per orientarsi;
  - da telefono non cambia nulla: le piattaforme restano nel pannello del menu.
- **Testata in asse**: menu e pulsanti si allineano al centro del nome del sito; il sottotitolo resta sotto il nome senza spostarli.
- Si apre **larga come il contenuto**, sotto la testata, con la riga rossa in alto e le colonne che salgono in sequenza.
- Per ogni voce principale del menu:
  - un'**icona a matrice di punti** (postazione, controller o telefono);
  - il **nome grande** e il **numero di articoli**, più "Hub" se la sezione è una Hub;
  - sotto, i **marchi** con il loro numero e le **console come piccole etichette**;
  - una voce senza sottovoci mostra la descrizione della sua categoria.
- In alto: numero di sezioni e di articoli.
- I contenuti restano quelli del menu "Piattaforme" di WordPress.
- Il pannello resta in memoria per un'ora e si rinnova quando cambiano il menu, le Hub o la versione.
- Con il mouse la tendina non si chiude passando dal pulsante al pannello. Aperta con il clic, si chiude con Esc.

---

### ✅ 4. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su `category-hub.php` (plugin e tema) e `menu-filters.php`; `node --check` su `dot-art.js` e `main.js`.
- **Suite di collaudo**: **396 test su 396 superati**.
  - Nuova suite 56 con 3 controlli.
  - Aggiornato il test 41.1 sulla tendina, che ora controlla il pulsante a destra.
  - Aggiunti al banco di prova i sostituti delle funzioni dei menu.
- **Prova sul sito Local**:
  - Hub Mobile con i due telefoni, Tutte / Android / iOS e le schede Android (22) e iOS (23);
  - Hub PC Gaming, accesa dal CEO, con la postazione e senza contatore delle piattaforme;
  - Console e Sony con i controller su griglia;
  - pulsante "Piattaforme" a destra, con la tendina di PC Gaming, Console e Mobile; si chiude con Esc e da telefono non compare;
  - nessun errore PHP.
- **Sincronizzazione 4 header (SemVer 3.22.0, MINOR)**.
- **Pacchetti**: ZIP 3.21.1 spostati in `archive/`, creati i tre ZIP 3.22.0 con `package_release.ps1`.

### ⚠️ Da fare sul sito vero

- In **NTN Journal Funzioni → Hub** accendi **Mobile** e **PC Gaming**, come su Local.
- Se vuoi Mobile nella tendina, aggiungi la voce "Mobile" (con Android e iOS sotto) al menu **Piattaforme** in Aspetto → Menu.
