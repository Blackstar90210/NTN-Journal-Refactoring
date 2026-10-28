# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.9.0 (Fase 7 del restyling: catalogo dei videogiochi)

Il catalogo dei videogiochi (`/videogioco/`) segue ora lo stile del mockup approvato (`docs/mockup/ntn-mockup-completo.html`), con la stessa intestazione degli archivi. Filtri, ordinamento, ricerca e scorrimento infinito funzionano come prima.

---

### 🎮 1. Pagina del catalogo (`ntn-journal-reloaded/archive-videogioco.php`)

- **Intestazione comune sulla fascia a matrice di punti**, come gli archivi della fase 6: percorso di navigazione, etichetta "Catalogo", titolo "Videogiochi", descrizione e **totale dei giochi in catalogo**. Il totale non cambia con i filtri, perché i filtri aggiornano la griglia senza ricaricare la pagina.
- **Filtri squadrati**: etichette in JetBrains Mono, campi con bordo da 1 pixel che diventa rosso quando sono attivi, pulsante "// Reset" rosso. Da computer restano in una barra che segue lo scorrimento. Da telefono si aprono dal pulsante "[ Filtri ]", uno sotto l'altro, con la ricerca sempre a vista.
- **Niente più lampeggio** sull'icona dei filtri e sul punto rosso dei filtri attivi.
- **Griglia a linee condivise**: 2 colonne da telefono, 4 da tablet, 5 da computer.
- **Nessun risultato**: riquadro "Vuoto" con lo stesso stile degli archivi, sia al primo caricamento sia dopo un filtro (risposta REST del plugin, `inc/rest-catalog.php`).
- Tutti gli ID e i parametri usati da `catalog-filters.js` sono invariati.

### 🃏 2. Scheda del gioco (`template-parts/content-game-card.php`)

- **Copertina intera in una cornice da 1 pixel**, con rapporto 3:4, senza filtri né zoom.
- **Titolo e anno sotto l'immagine**, al posto della banda scura sopra la foto. Al passaggio del mouse il titolo diventa rosso.
- **Copertina mancante**: matrice di punti con la scritta "NO COVER".
- La scelta della copertina (IGDB, immagine in evidenza, ripieghi) e i suggerimenti di caricamento per le prime quattro schede sono invariati.

### 🗂️ 3. Catalogo di ripiego del tema parent (`ntn-journal/archive-videogioco.php`)

Usato solo se il tema child non è attivo: stessa intestazione, stessa scheda e stessa griglia, con la paginazione classica.

### 🧩 4. Intestazione degli archivi (`template-parts/archive-head.php`)

Il contatore accetta un'unità propria: nel catalogo conta i "Giochi in catalogo" invece degli "Articoli". Gli altri archivi non cambiano.

---

### ✅ 5. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: tutti i file modificati, nessun errore.
- **Suite di collaudo**: **311 test su 311 superati**. Aggiornato 8.1, che ora cerca un anno vuoto con la nuova classe `ntn-game-year`. La nuova suite 37 ha 6 controlli:
  - intestazione e griglia del catalogo;
  - ID usati dallo script dei filtri;
  - scheda con copertina in cornice;
  - catalogo di ripiego del parent;
  - unità del contatore;
  - stile e stato vuoto della risposta REST.
- **Prova sul sito Local**:
  - **Pagine**: catalogo in tema chiaro, scuro e da telefono, con il pannello dei filtri aperto.
  - **Funzioni**:
    - lo scorrimento infinito passa da 20 a 40 giochi;
    - l'ordinamento A → Z funziona;
    - il filtro per anno senza risultati mostra il riquadro "Vuoto" e il pulsante Reset.
  - **Esito**: nessuno scorrimento orizzontale e nessun nuovo errore nel registro PHP.
- **Gate di sicurezza**: la lettura dei parametri dell'indirizzo è invariata. Titoli, anni e indirizzi passano da `esc_html`, `esc_url` e `esc_attr`.
- **Sincronizzazione 4 header (SemVer 3.9.0, MINOR)**.
- **Pacchetti**: ZIP 3.8.0 spostati in `archive/`, creati i tre ZIP 3.9.0 con `package_release.ps1`.

### ⚠️ Note

- I comunicati stampa e la pagina autore, con le pagine statiche e la 404, restano per le fasi 8 e 9.
