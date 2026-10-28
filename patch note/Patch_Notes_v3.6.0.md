# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.6.0 (Fase 4 del restyling: pagina articolo)

La pagina dell'articolo segue ora il mockup approvato (`docs/mockup/ntn-mockup-completo.html`, pagina Articolo). Il box della recensione con i cinque LED e la scheda del gioco nella colonna laterale restano quelli di oggi: saranno rifatti nella fase 5.

---

### 📰 1. Testata dell'articolo (`single.php`)

- **Sulla fascia a matrice di punti** che segue il tema (grigio chiarissimo in chiaro, nera in scuro), a tutta larghezza.
- Percorso di navigazione (breadcrumb) in cima, poi etichetta della categoria, che resta un link, ed etichetta rossa con il giudizio se l'articolo è una recensione.
- **Titolo molto grande**, in maiuscolo, come nel mockup.
- **Sottotitolo** con la stessa regola di prima: estratto, poi campo `custom_h2`, poi primo sottotitolo del testo. La nota "Estratto by AI" non è più gialla ma usa il grigio della tavolozza.
- **Riga dei dati in Mono**: gioco collegato in rosso con il pallino (porta alla sua Hub), autore con link alla sua pagina, data e ora, tempo di lettura, "Aggiornato" solo se l'articolo è stato modificato almeno un giorno dopo l'uscita.
- **Foto in evidenza a tutta larghezza** dentro la fascia, senza filtri, con la didascalia dell'immagine se c'è. La filigrana resta, come prima attivabile dal Customizer.

### 📖 2. Corpo dell'articolo

- Testo a 18 px su una colonna più larga e leggibile, con la **prima lettera grande**.
- **Sottotitoli numerati** (01, 02, 03…) con una linea sopra, come nel mockup.
- L'indice dei contenuti generato dal plugin resta nel testo, con il bordo sottile della tavolozza.
- Tutto il resto resta nello stesso ordine: box della recensione, condivisione, Google Feed, invito ai social, articoli correlati, commenti (ora con l'intestazione "// Commenti (N)").

### ✍️ 2b. Autore sempre in vista (soluzione B scelta dal CEO)

Q-Gin vuole che l'autore sia sempre visibile. Tra le tre soluzioni mostrate nel mockup (firma nella testata, scheda nella colonna laterale, barra di lettura) il CEO ha scelto la scheda nella colonna laterale.

- **Su schermi larghi** (da 1280 px) ogni articolo ha ora la colonna laterale, non solo le recensioni. In cima c'è la nuova **scheda dell'autore** (`template-parts/author-panel.php`), che **resta ferma per tutta la lettura**. Nelle recensioni la scheda del gioco sta sotto e scorre normalmente.
- **Su telefono e tablet** la colonna non c'è: la scheda, in versione compatta, compare in cima al testo, subito dopo la foto.
- **Contenuto**: foto dell'autore (o iniziali), nome con link alla sua pagina, "Firma di Q-Gin · dal ANNO", biografia, numero di articoli e di recensioni, link social e "Tutti i suoi articoli". I numeri sono in cache per un'ora.
- **La scheda in fondo all'articolo è stata tolta.** Il file `template-parts/author-bio.php` resta nel tema per eventuali altri usi.

### 📱 3. Correzione per telefono (`style.css`)

Alcune vecchie regole per telefono aggiungevano margini a tutta la pagina e ingrandivano il sottotitolo con `!important`. Ora valgono solo per le pagine non ancora rifatte. La Home e l'articolo, segnati con la classe `.ntn-main`, gestiscono da soli i margini, e le fasce arrivano ai bordi dello schermo. Questo corregge anche la Home 3.4.0, che da telefono aveva lo stesso margine.

### 🎨 4. CSS (`build/input.css`)

Nuovi componenti della pagina articolo (`.ntn-art-*`, `.ntn-author-box`, `.ntn-comments`). Le regole del testo (`.ntn-prose.prose`) sono fuori dai livelli di Tailwind, perché devono prevalere sulle regole `.prose` già presenti in `style.css`.

---

### ✅ 5. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: tutti i 79 file PHP di temi e plugin, nessun errore.
- **Suite di collaudo**: **293 test su 293 superati**. La nuova suite 34 ha 9 controlli: testata sulla fascia, funzioni dell'articolo conservate e nello stesso ordine, priorità del sottotitolo, sottotitoli numerati e lettera grande, data di aggiornamento ed escaping, regole per telefono limitate alle pagine vecchie, scheda dell'autore nella colonna e in cima al testo su telefono, scheda che resta ferma durante la lettura, escaping e cache dei dati dell'autore. Restano validi i test 29.2, 29.3 e 29.14 sull'ordine dei box e sui controlli di sicurezza.
- **Prova sul sito Local**: articolo normale (con indice) e recensione (con colonna laterale), in chiaro e in scuro, da computer e da telefono, senza scorrimento orizzontale. Home, articoli e pagina con commenti rispondono 200 e il registro PHP non ha voci nuove.
- **Gate di sicurezza**: ogni dato stampato passa da `esc_html`, `esc_url` o `esc_attr`.
- **Sincronizzazione 4 header (SemVer 3.6.0, MINOR)**.
- **Pacchetti**: ZIP 3.5.0 spostati in `archive/`, creati i tre ZIP 3.6.0 con `package_release.ps1`.

### ⚠️ Note

- Da telefono il testo dell'articolo resta a 22 px, per una scelta già presente in `style.css`. Si può allineare ai 18 px del mockup se preferisci.
- L'etichetta della categoria mostra la categoria principale dell'articolo. Per la recensione di Ace Combat 8 sul sito di prova è "Notizie", perché così è impostata in WordPress.
