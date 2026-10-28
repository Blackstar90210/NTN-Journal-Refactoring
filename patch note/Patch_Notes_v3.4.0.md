# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.4.0 (Fase 3 del restyling: Home)

La Home è stata ricostruita sul modello del mockup approvato (`docs/mockup/ntn-mockup-completo.html`), usando i componenti e la tavolozza della versione 3.3.0. È la prima pagina che cambia faccia davvero. La fase 2 (testata e menu) è stata anticipata dalla Home su richiesta del CEO del 17.10.2026: testata e piè di pagina restano quelli di oggi.

---

### 🏠 1. Nuova Home (`front-page.php`)

Dall'alto in basso:

1. **Apertura a matrice di punti al posto del carosello.** La fascia segue il tema: grigio chiarissimo in chiaro, nera in scuro. Contiene la riga dell'edizione (giorno, data, articoli di oggi, numero di recensioni e di giochi nel catalogo), un articolo grande e tre piccoli. Si vedono tutti subito, senza aspettare lo scorrimento. Gli articoli sono quelli fissati in evidenza in WordPress; se sono meno di quattro, si completano con gli ultimi pubblicati.
2. **Ultime notizie** come lista compatta su due colonne: ora (o giorno), gioco collegato in rosso con il pallino, titolo e minuti di lettura.
3. **Recensioni** a celle, con i **cinque LED** del voto e il giudizio (Bocciato, Consigliato con riserva, Consigliato, Must Play). Il lettore di schermo annuncia "Voto N su 5".
4. **Fascia dell'Hub di sezione**, con la cartina dell'Italia a puntini che segue il tema. La categoria si sceglie dal Customizer (vedi punto 3). Se la categoria non esiste, la fascia non compare.
5. **Prossime uscite dal catalogo**: tabella con data, gioco, studio e piattaforme, letta dal catalogo dei videogiochi (`_ntn_game_release`). Le uscite entro 14 giorni sono in rosso. Compare solo se ci sono uscite future.
6. **Approfondimenti**: editoriali e speciali a celle, senza ripetere gli articoli già mostrati sopra.
7. **Live e video**: le aree widget Twitch e YouTube della Home restano, in una sezione dedicata che compare solo se sono attive.

Il gioco collegato a un articolo è il primo tag con i dati IGDB, con la stessa regola già usata dalle Hub dei giochi. Le foto mancanti diventano copertine a puntini.

### 🗂️ 2. Dati della Home (`inc/front-page-data.php`, nuovo)

- Il tema legge solo articoli e meta già esistenti, senza registrare nuovi dati (Regola 3).
- Tutti i blocchi sono in un'unica cache di 10 minuti. Il nome della cache contiene la versione del tema, quindi un aggiornamento la rigenera. La cache si svuota quando si pubblica, si modifica o si cestina un articolo o un gioco. Prima la Home aveva 5 cache separate, che scadevano da sole senza svuotarsi alla pubblicazione.

### ⚙️ 3. Nuove opzioni nel Customizer (Impostazioni Tema)

- **Home: categoria della fascia Hub**: elenco delle categorie, predefinita `sviluppatori-italiani-di-videogiochi`. "Nessuna fascia" la nasconde.
- **Home: cartina dell'Italia nella fascia Hub**: mostra la cartina e l'etichetta "Made in Italy" (attiva di base).

### 🎨 4. CSS e script

- `build/input.css`: componenti della Home (riga dell'edizione, apertura, lista compatta, celle da 2/3/4 colonne, LED, fascia Hub, tabella delle uscite, contenitore `.ntn-wrap` con margini sicuri anche da telefono).
- `assets/js/front-page.js`: il codice del carosello è sostituito dal disegno della cartina, che legge il colore dei puntini dal tema e si ridisegna quando si passa da chiaro a scuro.

---

### ✅ 5. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: 36 file PHP modificati o nuovi, nessun errore.
- **Suite di collaudo**: **275 test su 275 superati**. La nuova suite 31 contiene 9 controlli: niente carosello, blocchi presenti e condizionati, widget conservati, escaping di ogni dato stampato, cache legata alla versione e svuotata alla pubblicazione, LED accessibili, opzioni del Customizer sanificate, script della cartina, caricamento del modulo dati. Aggiornati i test 24.x e 29.1 (versione), 27.7 (il controllo sul giudizio passa al nuovo modulo dati) e 30.9 (intestazioni della nuova Home).
- **Gate di sicurezza**: ogni dato stampato passa da `esc_html`, `esc_url` o `esc_attr`. L'unica eccezione è l'immagine generata da `get_the_post_thumbnail()` di WordPress, che è già sicura. L'opzione del Customizer è sanificata con `sanitize_title`. Nessun dato in ingresso dai lettori.
- **Sincronizzazione 4 header (SemVer 3.4.0, MINOR)**.
- **Pacchetti**: ZIP 3.3.0 spostati in `archive/`. Creati `ntn-journal-parent-3.4.0.zip`, `ntn-journal-child-reloaded-3.4.0.zip` e `ntn-journal-core-3.4.0.zip`, conformi (una sola cartella radice, percorsi con `/`, versione interna 3.4.0).
- **Prova sul sito Local**: Home in chiaro e in scuro, da computer (1280 px) e da telefono (375 px), senza scorrimento orizzontale e senza nuovi errori nel registro PHP.

### ⚠️ Note aperte

- **Fascia Hub e Prossime uscite non visibili sul sito di prova**: Local non ha la categoria degli sviluppatori italiani e il catalogo è vuoto. Per vedere la fascia basta scegliere un'altra categoria, per esempio "Indie", in Aspetto → Personalizza → Impostazioni Tema. Il codice è coperto dai test, ma questi due blocchi non sono stati visti dal vivo.
- **Errore fatale nei commenti** (presente dalla 3.2.15, vedi note 3.3.0): ancora da correggere con una patch dedicata.
- **Hub di categoria**: il piano `docs/piano_hub_categoria.md` indicava la versione 3.3.0, ora usata dalla fase Fondamenta. La Hub uscirà con una versione successiva.
