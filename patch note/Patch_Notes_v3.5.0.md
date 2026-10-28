# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.5.0 (Fase 2 del restyling: testata, menu e piè di pagina)

La cornice del sito, cioè la parte che compare su ogni pagina, ora segue lo stesso sistema della Home 3.4.0 e del mockup approvato (`docs/mockup/ntn-mockup-completo.html`). Tutte le funzioni di prima restano.

---

### 🧭 1. Testata (`header.php`)

- **Barra di sistema sulla fascia che segue il tema**: grigio chiarissimo in chiaro, nera in scuro. Contiene il testo personalizzato (Customizer) e l'orologio.
- **Testata su una griglia**: logo a sinistra, ricerca al centro, menu principale e pulsante chiaro/scuro a destra. Da telefono, nella prima riga ci sono logo e pulsante, nella seconda la ricerca a tutta larghezza.
- **Logo** in Inter nerissimo, maiuscolo, con la prima lettera rossa e il pallino. Restano il logo come immagine, il motto e il logo nascosto per la SEO.
- **Un solo pulsante chiaro/scuro**, squadrato come nel mockup, al posto dei due interruttori arrotondati (uno per il computer, uno per il telefono). Ha un nome per i lettori di schermo ("Cambia tema chiaro o scuro") e ricorda la scelta come prima.
- **Ricerca** con bordo da 1 pixel che diventa rosso quando si scrive.
- Restano gli agganci usati dagli altri moduli: barra delle breaking news, orologio, apertura dei menu da telefono.

### 📑 2. Menu "Articoli" e "Piattaforme" (`header.php`, `inc/menu-filters.php`)

- Voci in Mono con il formato `// NOME_VOCE`. La voce della pagina aperta è segnata da una linea rossa sotto ed è annunciata ai lettori di schermo (`aria-current="page"`).
- **Tolto il pallino rosso pulsante accanto a ogni voce** e l'animazione delle frecce dei sottomenu: il movimento resta solo per "live" e per i contenuti seguiti (regola 6 del restyling).
- Piattaforme come etichette squadrate; sottomenu con bordi da 1 pixel, apribili anche da tastiera come prima.
- Anche il menu di riserva (quello mostrato quando in WordPress non è assegnato un menu) usa il nuovo stile e segna la pagina aperta.

### 🔻 3. Piè di pagina (`footer.php`)

- **Corretta la sovrapposizione da telefono**: la colonna centrale non è più posizionata sopra le altre. Da telefono copyright, menu (o firma del tema) e indicatori vanno su tre righe.
- Barra di stato sulla fascia che segue il tema, con i tre indicatori quadrati (il primo rosso).
- Anno e nome del sito ora passano dall'escaping.

### 🎨 4. CSS (`build/input.css`)

Nuovi componenti della cornice: `.ntn-header`, `.ntn-topbar`, `.ntn-header-main`, `.ntn-logo`, `.ntn-search`, `.ntn-sq`, `.ntn-subnav`, `.ntn-nav-link`, `.ntn-nav-chip`, `.ntn-submenu`, `.ntn-footer`.

---

### ✅ 5. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: tutti i 79 file PHP di temi e plugin, nessun errore.
- **Suite di collaudo**: **284 test su 284 superati**. La nuova suite 33 ha 6 controlli: agganci della testata usati da `main.js`, un solo pulsante chiaro/scuro accessibile, componenti della fascia, classi e `aria-current` nei menu, menu senza pallini pulsanti con il formato `//` conservato, piè di pagina senza colonne sovrapposte. Il test 20.6, sul formato `// NOME_VOCE`, resta valido.
- **Gate di sicurezza**: nessun dato nuovo dai lettori. Le classi della voce attiva passano da `esc_attr()`, l'anno da `esc_html()`.
- **Prova sul sito Local**: testata e piè di pagina in chiaro e in scuro, da computer e da telefono; pulsante chiaro/scuro e menu "Articoli" da telefono provati con un clic; nessuno scorrimento orizzontale; nessun errore nuovo nel registro PHP.
- **Sincronizzazione 4 header (SemVer 3.5.0, MINOR)**.
- **Pacchetti**: ZIP 3.4.1 spostati in `archive/`, creati i tre ZIP 3.5.0 con `package_release.ps1`.

### ⚠️ Note

- Il menu principale (`menu-1`) e il menu del piè di pagina non sono assegnati sul sito di prova, quindi si sono visti solo i menu di riserva. Il loro codice è coperto dai test.
- La testata non è fissa in alto durante lo scorrimento. Con barra di sistema, testata e menu sarebbe troppo alta; si può valutare una versione compatta più avanti.
