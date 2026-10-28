# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.12.0 (testata e menu come nel mockup)

La testata segue ora il mockup approvato (`docs/mockup/ntn-mockup-completo.html`): una sola riga con logo, menu e pulsanti quadrati, al posto delle due righe di prima. Le scelte sono del CEO: testata su una riga, voci senza "//", ricerca nella lente, barra in alto invariata.

---

### 🧭 1. Testata su una riga (`header.php`, `inc/menu-filters.php`)

- **Logo, menu, lente e chiaro/scuro sulla stessa riga.** Sparisce la seconda riga con "// ARTICOLI" e "// PIATTAFORME".
- **Voci del menu senza "//"**, in Mono, grigie. La voce della pagina in cui ti trovi è più scura e sottolineata in rosso.
- **Ordine delle voci nella riga**:
  - voci del menu *Gestione Contenuti*, oppure, se non è assegnato, l'elenco di ripiego Tutti, Notizie, Editoriali, Recensioni, Autori;
  - voce **"Piattaforme ▾"**, che apre una tendina con il menu *Gestione Piattaforme*. Si apre al passaggio del mouse, con la tastiera e al tocco;
  - voci del menu *Primary*, se assegnato.
- **Sottomenu** delle voci con figli: restano a tendina, come prima.

### 🔍 2. Ricerca nella lente

- Il campo di ricerca sempre visibile diventa un **pulsante quadrato con la lente**. Al clic apre una striscia sotto la testata con il campo, già pronto per scrivere.
- **La ricerca dal vivo dei giochi** (copertina e titolo mentre scrivi) funziona come prima.
- **Sulla pagina dei risultati** la striscia è già aperta, con il testo cercato e "CANCELLA".
- **Esc** chiude la striscia e riporta il cursore sulla lente.

### 📱 3. Menu da telefono e tablet

- Sotto i 1024 pixel le voci non stanno su una riga: compare un **terzo pulsante quadrato** con le tre linee.
- Apre un pannello con tutti i menu in gruppi: **Articoli**, **Piattaforme**, **Altro** (menu Primary).
- Nel pannello i sottomenu sono sempre aperti, così ogni voce si raggiunge con un solo tocco.
- Lente e menu si escludono a vicenda: aprendo l'uno si chiude l'altro.

### 🦶 4. Piè di pagina

Era già come il mockup. Cambia solo un dettaglio: le voci del menu *Footer* non hanno più il prefisso "//".

### 🛠️ 5. Dettagli tecnici

- **Script `assets/js/main.js`**: i vecchi menu a fisarmonica "Articoli" e "Piattaforme" sono sostituiti dai due pannelli e dalla tendina. Il menu viene stampato due volte, nella riga e nel pannello: nel pannello le voci non hanno `id`, per non duplicarli.
- **Stile `build/input.css`**: rimosse le regole della seconda riga (`.ntn-subnav`), del menu Primary separato (`.ntn-mainnav`) e delle piattaforme a etichette (`.ntn-nav-chip`).
- **Barra in alto**: testo, orologio e notizie in evidenza restano invariati.

---

### ✅ 6. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su `header.php` e `inc/menu-filters.php`; `node --check` su `main.js`.
- **Errore temporaneo durante il lavoro**: alle 21:18 il registro PHP ha segnato un errore. Per pochi secondi `header.php` chiamava la nuova funzione dei menu prima che fosse scritta. Non si è più ripetuto. Ora la testata la chiama solo se esiste (`function_exists`).
- **Suite di collaudo**: **331 test su 331 superati**.
  - **Test aggiornati** alla nuova testata: 20.6, 33.1, 33.3, 33.4 e 33.5.
  - **Banco di prova**: aggiunti menu finti (`has_nav_menu`, `wp_nav_menu`) per provare i menu assegnati.
  - **Nuova suite 41**, con 4 controlli:
    - riga della testata con tendina Piattaforme e menu Primary;
    - pannello da telefono senza `id` duplicati;
    - elenco di ripiego senza menu assegnati;
    - lente e pulsante del menu accessibili, ricerca aperta sui risultati, Esc.
- **Prova sul sito Local**:
  - **Home da computer**: testata su una riga.
  - **Ricerca**: lente, ricerca dal vivo con "ace com" ed Esc.
  - **Telefono**: pannello del menu in chiaro e in scuro.
  - **Pagina dei risultati**: ricerca "battlefield", con la striscia già aperta.
  - **Esito**: nessuno scorrimento orizzontale e nessun nuovo errore nel registro PHP.
- **Sincronizzazione 4 header (SemVer 3.12.0, MINOR)**.
- **Pacchetti**: ZIP 3.11.1 spostati in `archive/`, creati i tre ZIP 3.12.0 con `package_release.ps1`.

### ⚠️ Note

- **Menu non provati dal vivo**: sul sito Local non è assegnato nessun menu di WordPress, quindi la tendina "Piattaforme" e le voci del menu Primary sono state provate solo nel banco di prova. Sul sito vero, dopo l'aggiornamento, conviene aprire la tendina e il pannello da telefono.
- **Molte voci da computer**: se il menu *Gestione Contenuti* ha molte voci, la riga va a capo sotto il logo invece di tagliarle.
