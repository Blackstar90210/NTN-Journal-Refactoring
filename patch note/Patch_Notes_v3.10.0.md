# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.10.0 (Fase 8 del restyling: comunicati stampa)

L'elenco dei comunicati stampa (`/comunicato-stampa/`) e la pagina del singolo comunicato seguono ora lo stile Nothing OS del resto del sito. Il mockup non aveva una pagina per i comunicati, quindi sono stati usati i componenti già approvati: l'intestazione degli archivi e la pagina articolo.

Su indicazione del CEO il **giallo resta l'accento dei soli comunicati**, perché fa parte dell'estetica di Nothing OS 4 e 5. Il giallo ora è un colore del tema, con una tonalità più scura in chiaro per restare leggibile su bianco.

---

### 📰 1. Elenco dei comunicati (`archive-comunicato_stampa.php`)

- **Intestazione comune sulla fascia**: etichetta "Press room", titolo "Comunicati stampa", una riga che spiega che il testo non è della redazione e il numero di comunicati.
- **Comunicati a celle** con linee condivise: etichetta gialla "PR", data e ora, titolo, riga "Da:" con editore, studio e agenzia, sommario e link "// Leggi_comunicato →" sempre allineato in fondo.
- Al passaggio del mouse compaiono un filo giallo a sinistra e il titolo in giallo.
- Paginazione e messaggio "Nessun comunicato" con lo stile degli archivi.

### 📄 2. Pagina del comunicato (`single-comunicato_stampa.php`)

- **Testata sulla fascia** come la pagina articolo: percorso di navigazione, etichetta gialla "Comunicato stampa ufficiale" che porta all'elenco, titolo grande e data.
- **Scheda del comunicato a celle**: editore, studio, agenzia PR, fonte ufficiale e press kit. I due link esterni restano `nofollow`. Editore, studio e agenzia compaiono una sola volta, senza la riga "Da:" ripetuta.
- **Immagine in evidenza** in cornice, come negli articoli.
- **Testo** con lo stesso stile degli articoli. Sono stati tolti gli stili gialli scritti dentro il template.
- **Nota finale** ("// Nota") in un riquadro con il filo giallo: dice che il testo è un comunicato ufficiale e non è della redazione.

### 🧭 3. Percorso di navigazione (plugin, `inc/seo-extras.php`)

Per i contenuti che non sono articoli, il passaggio intermedio ora è la loro sezione e non una categoria.

| Pagina | Prima | Ora |
|---|---|---|
| Comunicato | Home // Notizie | Home // Comunicati stampa |
| Hub di un gioco | — | Home // Videogiochi, con il link al catalogo |

Lo stesso vale per i dati strutturati (BreadcrumbList) letti dai motori di ricerca.

---

### ✅ 4. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: tutti i file modificati, nessun errore.
- **Suite di collaudo**: **316 test su 316 superati**. I test 16.1–16.7 sui dati del comunicato, 28.15 sui link `nofollow`, 29.16 sulla paginazione e 30.8 sugli angoli squadrati passano senza modifiche. La nuova suite 38 ha 5 controlli:
  - elenco con l'intestazione comune;
  - testata, scheda e nota del comunicato;
  - nessuno stile giallo scritto nei template;
  - editore, studio e agenzia nominati una sola volta;
  - percorso di navigazione per sezione.
- **Prova sul sito Local**:
  - **Pagine**: elenco e comunicato "Ground Zero Hero" in chiaro, scuro e da telefono.
  - **Percorso di navigazione** controllato anche nella Hub di Ace Combat 8.
  - **Esito**: nessuno scorrimento orizzontale e nessun nuovo errore nel registro PHP.
- **Gate di sicurezza**: tutti i dati passano da `esc_html`, `esc_url` o `esc_attr`. La nota finale passa da `wp_kses_post`, come prima.
- **Sincronizzazione 4 header (SemVer 3.10.0, MINOR)**.
- **Pacchetti**: ZIP 3.9.0 spostati in `archive/`, creati i tre ZIP 3.10.0 con `package_release.ps1`.

### ⚠️ Note

- La sezione dei comunicati per ora non è in uso sul sito, per ragioni SEO.
- La pagina autore, le pagine statiche e la 404 restano per la fase 9.
