# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.8.0 (Fase 6 del restyling: archivi e Hub dei giochi)

La Hub di ogni videogioco e gli archivi (categorie, tag, motori grafici, ricerca, pagina del blog) seguono ora il mockup approvato (`docs/mockup/ntn-mockup-completo.html`, pagina Hub gioco). Le funzioni di prima restano tutte.

---

### 🎮 1. Hub del videogioco (`single-videogioco.php` nel child e nel parent, `template-parts/hub-header.php`)

La Hub è la pagina `/videogioco/nome-gioco/`: i tag dei giochi rimandano lì.

- **Intestazione sulla fascia a matrice di punti** che segue il tema, con percorso di navigazione, etichette "Hub page" e "Vedi su IGDB.com", e titolo molto grande.
- **Copertina IGDB intera in una cornice a destra**, al posto dello sfondo sfumato e velato. Da telefono si vedono solo testo e dati, per arrivare subito ai contenuti.
- **Contatori nuovi**: articoli, recensioni, anteprime e data dell'ultimo articolo, calcolati dal tag del gioco e tenuti in cache per un'ora.
- **Scheda tecnica a celle**: uscita, sviluppatore ed editore con i link agli studi, genere, fino a 8 piattaforme, motore grafico con il link al suo archivio.
- **Contenuto in evidenza**, scelto come prima con la priorità Recensione > Anteprima > Editoriale > Notizia (o a mano dalla redazione). Le etichette che nel mockup mostravano la priorità sono state tolte su richiesta del CEO: per il lettore non hanno interazione e possono confondere. Se è una recensione compare il suo box senza il verdetto, come prima, più il link "Leggi la recensione completa". Altrimenti compare una scheda grande.
- **Intestazioni allineate**: "// Recensione_in_evidenza" e "// Scheda_media" stanno alla stessa altezza, con la linea sotto allo stesso livello (su segnalazione del CEO). Prima il link attorno al titolo di sinistra lo faceva scendere di qualche pixel.
- **Scheda media** (ora scritta "// Scheda_media"): trailer che si carica solo al clic e screenshot numerati che si ingrandiscono (GLightbox), con bordi squadrati e senza filtri.
- **Tutti gli articoli del gioco** a celle, con la stessa paginazione di prima.
- La copia del tema parent è identica a quella del child, e un test lo controlla.

### 🗂️ 2. Archivi (`archive.php`, `tag.php`, `taxonomy-game_engine.php`, `index.php`, nuovo `template-parts/archive-head.php`)

- **Intestazione comune sulla fascia**: percorso di navigazione, etichetta del tipo (Categoria, Tag, Archivio per data, Motore grafico, Ricerca, Tutti gli articoli), titolo grande **senza il prefisso automatico** "Categoria:", descrizione e numero di articoli.
- **Ricerca**: titolo con il testo cercato e numero di risultati.
- **Motore grafico**: resta il testo di presentazione automatico quando l'archivio non ha una descrizione.
- **Articoli a celle** con linee condivise, al posto dei riquadri separati.

### 🃏 3. Scheda articolo comune (`template-parts/content.php`)

Usata da archivi, Hub, ricerca e blog: foto in cornice da 1 pixel con rapporto fisso (senza filtri né zoom), etichetta della categoria, etichetta rossa del voto, riga dei dati (autore, data, minuti) e titolo. Nella Hub, come contenuto in evidenza, diventa grande e mostra anche il sommario.

---

### ✅ 4. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: tutti i file PHP di temi e plugin, nessun errore.
- **Suite di collaudo**: **305 test su 305 superati**. I test 13.1–13.3 sull'intestazione della Hub e 29.22 sulla paginazione passano senza modifiche. Aggiornati 27.12 (niente più separatore "•", resta il controllo sui caratteri corrotti) e 29.6 (immagine a rapporto fisso al posto di `h-44`). La nuova suite 36 ha 7 controlli: intestazione con copertina in cornice, contatori e scheda tecnica, corpo della Hub senza etichette di priorità, parent e child allineati, scheda a cella, archivi con l'intestazione comune, intestazioni della Hub allineate.
- **Prova sul sito Local**: Hub di Ace Combat 8 (chiaro, scuro e telefono), categoria Recensioni, archivio Unreal Engine 5, ricerca, pagina 2 di una categoria, Hub di Battlefield 6: tutte rispondono 200, senza scorrimento orizzontale e senza nuovi errori nel registro PHP.
- **Gate di sicurezza**: tutti i dati passano da `esc_html`, `esc_url` o `esc_attr`. I link agli studi e ai motori e la paginazione sono generati dalle funzioni di WordPress e del tema già in uso.
- **Sincronizzazione 4 header (SemVer 3.8.0, MINOR)**.
- **Pacchetti**: ZIP 3.7.0 spostati in `archive/`, creati i tre ZIP 3.8.0 con `package_release.ps1`.

### ⚠️ Note

- Il catalogo dei videogiochi (`/videogioco/`), la pagina autore e i comunicati stampa restano per le fasi 7, 8 e 9.
