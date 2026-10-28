# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.3.0 (Fase 1 del restyling: Fondamenta)

Questo aggiornamento crea la base comune del nuovo stile Nothing OS, ricavata dal mockup approvato (`docs/mockup/ntn-mockup-completo.html`), e toglie dal sito gli elementi estranei al sistema. L'impaginazione delle pagine non cambia: cambiano colori, linee, angoli e intestazioni di sezione. Piano di riferimento: `docs/piano_fase1_fondamenta.md`.

---

### 🎨 1. Tavolozza unica per chiaro e scuro (`build/input.css`)

- Tutti i colori del nuovo sistema sono variabili `--ntn-*` definite una sola volta: tema chiaro su `:root`, tema scuro ridefinito sulla classe `.dark` (lo stesso meccanismo del pulsante chiaro/scuro di oggi).
- **Fascia di apertura che segue il tema**, su richiesta del CEO: grigio chiarissimo `#f4f4f5` con puntini scuri nel tema chiaro, nera con puntini chiari nel tema scuro.
- Il grigio dei testi piccoli nel tema chiaro è `#71717a`. Il rosso dei testi sulla fascia chiara è `#b91c1c`, perché `#dc2626` su quel grigio non raggiunge il contrasto 4,5:1.
- Tailwind ora legge anche i modelli e gli script del tema child (`@source`), così le classi usate solo nel child vengono compilate.

### 🧱 2. Componenti comuni (livello `components`)

Pronti per le fasi successive: fascia di apertura con matrice di punti (`.ntn-band`), etichetta (`.ntn-chip`, `.ntn-chip--red`), intestazione di sezione (`.ntn-sec-head`, `.ntn-sec-title`, `.ntn-sec-aside`), griglia a celle (`.ntn-cells`, `.ntn-cell`), riga di lista compatta (`.ntn-row`), riga dei dati in Mono (`.ntn-meta`), segno rosso di studio o gioco (`.ntn-entity`), pallino pulsante (`.ntn-dot`), contatore (`.ntn-stat-k`, `.ntn-stat-v`), copertina mancante a punti (`.ntn-ph`) e cornice da 1 pixel per le foto (`.ntn-frame`).

### 🧹 3. Pulizia Nothing OS (35 file)

- **Via ombre, zoom e filtri sulle foto**: tolte tutte le ombre (anche i bagliori rossi del box "Consigliati"), lo zoom e il sollevamento al passaggio del mouse, i filtri di luminosità e contrasto. Le foto si vedono come le carica la redazione.
- **Angoli vivi**: tolti gli arrotondamenti. I cerchi restano solo per i pallini di stato. Diventano squadrati l'interruttore chiaro/scuro, le frecce del carosello, i numeri delle classifiche in home, l'avatar dei commenti, il pulsante flottante delle breaking news e il pulsante play del trailer.
- **Sfumature e sfocature sostituite da veli uniformi** dove servono a leggere il testo sopra un'immagine: schede del catalogo, carosello della home, intestazione delle Hub dei giochi, scheda del gioco nella recensione, filigrana sulle foto e barra dei filtri del catalogo.

### 🧭 4. Intestazioni di sezione uniformi

Home (Editoriali, Ultime pubblicazioni, Recensioni), articoli correlati, pagina autore, Hub dei videogiochi (parent e child) e titoli dei widget usano ora la stessa intestazione: etichetta Mono grigia a sinistra, linea sotto.

### ♿ 5. Accessibilità

- I testi piccoli grigi passano da `zinc-400` a `zinc-500` nel tema chiaro e da `zinc-500`/`zinc-600` a `zinc-400` nel tema scuro (contrasto da 2,6–4,3:1 a 4,8–8,2:1).
- Chi ha attivato "riduci movimento" sul dispositivo non vede più pulsare i pallini.

---

### ✅ 6. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: 33 file PHP modificati, nessun errore.
- **Suite di collaudo**: **266 test su 266 superati** (`audit_test_suite.php`), con la nuova suite 30 (10 controlli su tavolozza, componenti, CSS compilato, assenza di ombre/zoom/filtri/sfumature, cerchi solo per i pallini, intestazioni e contrasti). Aggiornati i test 24.x e 29.1 alla versione 3.3.0.
- **Gate di sicurezza**: il confronto parola per parola delle modifiche mostra solo classi CSS, regole di stile e numero di versione. Nessuna logica PHP, nessun dato in ingresso o in uscita e nessun escaping è cambiato.
- **Sincronizzazione 4 header (SemVer 3.3.0, MINOR)**: `ntn-journal/style.css`, `ntn-journal-reloaded/style.css`, header e costante `NTN_JOURNAL_CORE_VERSION` di `ntn-journal-core.php`. Il plugin cambia solo il numero di versione.
- **Pacchetti**: ZIP 3.2.15 spostati in `archive/`. Creati con `package_release.ps1` `ntn-journal-parent-3.3.0.zip`, `ntn-journal-child-reloaded-3.3.0.zip` e `ntn-journal-core-3.3.0.zip`, ognuno con una sola cartella radice senza versione, percorsi con `/` e versione interna 3.3.0.
- **Prova sul sito Local `ntn-journal-test`**: home, articolo, recensione, catalogo, pagina autore e 404, in chiaro e in scuro, da computer e da telefono. Nessuno scorrimento orizzontale da telefono.

### ⚠️ Note aperte (non introdotte da questa versione)

- **Errore fatale nei commenti**: negli articoli con commenti, `comments.php` chiama `ntn_journal_custom_comment` prima di definirla (la funzione è dichiarata più in basso dentro un `if`). Il registro di Local lo riporta alle 10:10 del 17.10.2026, prima di queste modifiche, quindi è presente anche nella 3.2.15. Da correggere con una patch dedicata.
- **Piè di pagina da telefono**: il nome del sito e la scritta del tema si sovrappongono. Era già così. Rientra nella fase 2 (testata e piè di pagina).
- **Catalogo vuoto sul sito di prova**: le schede dei videogiochi non si sono potute vedere dal vivo. Il codice è coperto dai test.
