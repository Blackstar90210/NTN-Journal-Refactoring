# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.13.0 (Fase 9 del restyling: pagina autore, pagine statiche e 404)

Le ultime tre pagine con il vecchio aspetto seguono ora lo stile Nothing OS. Il mockup non le prevede, quindi sono state costruite con i componenti già approvati: fascia a matrice di punti, intestazione degli archivi, pagina articolo e schede a celle.

---

### 👤 1. Pagina autore (`author.php`)

- **Testata sulla fascia**, come gli archivi:
  - percorso di navigazione ed etichetta "Autore";
  - foto dell'autore in un riquadro squadrato o, se manca, le iniziali su matrice di punti, lo stesso stile della firma negli articoli;
  - nome grande e biografia completa.
- **Numeri dell'autore**: articoli, recensioni, minuti di lettura e anno di ingresso in redazione, tenuti in memoria.
- **Link social e sito web** come etichette con "↗". Si aprono in una nuova scheda e restano protetti (`noopener noreferrer`).
- **Articoli** con le stesse schede a celle degli archivi, senza "by Autore" su ogni scheda, perché l'autore è già nella testata. Restano la paginazione e il messaggio "Nessun articolo".
- **Dati strutturati**: c'è un solo titolo H1, e i dati per Google (`Person`, con nome, descrizione e link) restano.

### 📄 2. Pagine statiche (`page.php`)

- Testata sulla fascia con percorso di navigazione e titolo grande. Il riassunto, se c'è, compare sotto il titolo.
- Immagine in evidenza in cornice, come negli articoli. Tolta l'etichetta decorativa "NTN MEDIA ENGINE".
- Testo con lo stile degli articoli, capolettera compreso. I commenti, se attivi, compaiono sotto "// Commenti".
- Non ci sono autore, data e minuti di lettura, che in una pagina come "Chi siamo" non servono.

### 🚫 3. Pagina 404 (`404.php`)

- Fascia a matrice di punti che riempie lo schermo fino al piè di pagina.
- Grande "404" rosso in Mono, etichetta "Errore 404", titolo "Pagina non trovata" e testo.
- Due **pulsanti squadrati**: "← Torna alla Home", nero che diventa rosso al passaggio, e "Pagina precedente", con il bordo.
- Riga di stato in Mono: "Status: 404 · Err:// not found".

### 🧩 4. Componenti nuovi o ampliati

- Scheda a cella (`template-parts/content.php`): nuova opzione `hide_author` per togliere "by Autore".
- Pulsanti `.ntn-btn` e `.ntn-btn--solid`, riutilizzabili in altre pagine.

---

### ✅ 5. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: `author.php`, `page.php`, `404.php` e `template-parts/content.php`, nessun errore.
- **Suite di collaudo**: **334 test su 334 superati**.
  - Aggiornato 27.6: ora verifica che la scheda comune usata dalla pagina autore protegga `get_rating_initials`.
  - Passano senza modifiche:
    - 27.4: nessun `javascript:` nella 404;
    - 27.15 e 27.16: un solo H1 e la cache dei dati nella pagina autore;
    - 29.16: paginazione;
    - 30.9: intestazioni di sezione.
  - Nuova suite 42 con 3 controlli: pagina autore, pagine statiche e 404.
- **Prova sul sito Local**:
  - **Pagine controllate**: pagina autore di Luca Maiolini (346 articoli), "Sample Page" e una pagina inesistente per la 404.
  - **Esito**: corrette in chiaro, scuro e da telefono, senza scorrimento orizzontale e senza nuovi errori nel registro PHP.
  - **Testata 3.12.0**: con i menu che il CEO ha assegnato sul sito Local, la tendina "Piattaforme" funziona anche dal vivo (PC Gaming, Console, Sony).
- **Sincronizzazione 4 header (SemVer 3.13.0, MINOR)**.
- **Pacchetti**: ZIP 3.12.0 spostati in `archive/`, creati i tre ZIP 3.13.0 con `package_release.ps1`.

### ⚠️ Note

- `template-parts/author-bio.php`, il vecchio box dell'autore, non è più usato da nessuna pagina: lo usano solo i test 17.x. Si può eliminare in una pulizia futura.
- Con questa fase tutte le pagine del tema hanno lo stile Nothing OS. Resta la fase 10, lo stile dell'editor di WordPress.
