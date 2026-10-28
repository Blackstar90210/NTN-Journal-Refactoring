# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.11.0 (SEO delle Hub dei videogiochi)

Questa fase migliora il modo in cui Google legge le Hub dei videogiochi (`/videogioco/nome-gioco/`). Le correzioni partono dall'analisi SEO chiesta dal CEO e sono in ordine di importanza. L'aspetto delle pagine cambia solo per la nuova cella "In breve" nella scheda tecnica.

---

### 🧩 1. Dati strutturati corretti (plugin: nuovo `inc/hub-seo.php`, `inc/schema-markup.php`, `inc/seo-extras.php`)

Prima la Hub era dichiarata come "Article" con 0 parole e un autore. Ora è una **pagina di raccolta** (`CollectionPage`) che parla di un **videogioco** (`VideoGame`). Del videogioco sono indicati:
- nome e copertina;
- sviluppatori come autori, ed editore;
- generi, piattaforme e data di uscita;
- collegamento alla scheda IGDB (`sameAs`) e descrizione.

Funziona sia senza plugin SEO sia con The SEO Framework attivo, che prima trasformava la Hub in "NewsArticle". Anche l'anteprima social della Hub è ora di tipo `website` invece di `article`.

### ✍️ 2. Testo di presentazione automatico (plugin `ntn_get_hub_auto_summary()`, tema `template-parts/hub-header.php`)

Nella scheda tecnica della Hub compare una nuova cella a tutta larghezza, **"In breve"**, con un breve testo costruito dai dati IGDB. Ha gli stessi bordi e la stessa etichetta in Mono delle altre celle. La prima versione, un paragrafo libero sotto il titolo, è stata scartata dal CEO perché poco coesa con l'insieme. Esempio:

> "Ace Combat 8: Wings of Theve è un videogioco sviluppato da Bandai Namco Aces Inc. e pubblicato da Bandai Namco Entertainment, uscito il 02/10/2026 su Xbox Series X|S, PC (Microsoft Windows) e PlayStation 5. Qui trovi tutte le recensioni, le anteprime e le notizie di NTN Journal sul gioco."

- Il testo scrive "in uscita" oppure "uscito" in base alla data.
- Lo stesso testo diventa la **meta description** e la descrizione nei dati strutturati, quando la Hub non ne ha una propria.

### 📝 2a. Testo della redazione nella Hub (`single-videogioco.php` parent e child, `hub-header.php`)

Prima il testo scritto nell'editor della Hub non compariva da nessuna parte. Ora:
- **Se c'è un testo**, compare come prima sezione della colonna di sinistra, **"// Panoramica"**, sopra il contenuto in evidenza e allineata a "// Scheda_media". Ha lo stesso stile di testo degli articoli, con il capolettera. La cella "In breve" sparisce, per non ripetere le stesse informazioni.
- **Se l'editor è vuoto**, la Hub resta com'è, con la cella "In breve" automatica.
- **L'indice automatico** non viene aggiunto, perché il testo di una Hub è breve.
- **Il capolettera** resta dentro la sezione anche quando il testo è di una sola riga, così lo spazio prima di "// Recensione_in_evidenza" resta regolare.
- **Per Google**: un testo scritto dalla redazione, unico per ogni gioco, vale più del testo automatico. Con The SEO Framework la meta description viene ricavata da questo testo. Consiglio: 80–150 parole per i giochi più importanti.

### 🔎 2b. Titolo per Google e collegamento a The SEO Framework (`inc/hub-seo.php`)

- **Titolo per Google e per la scheda del browser**: il nome del gioco con una parte descrittiva. Il titolo grande nella pagina (H1) resta il solo nome del gioco, quindi l'aspetto non cambia.
  - Con almeno una recensione: "Ace Combat 8: Wings of Theve – Recensione, notizie e scheda".
  - Senza recensioni: "Kingdom Hearts IV – Notizie e scheda del gioco".
  - Per sapere se c'è una recensione il plugin fa una ricerca, tenuta in memoria per un'ora.
- **The SEO Framework (v5 e successive)**: il plugin passa a TSF il titolo e il testo "In breve", che TSF usa per la meta description e le anteprime social. Un titolo o una descrizione scritti a mano nel riquadro di TSF della Hub hanno sempre la precedenza. Filtri usati, verificati nella documentazione ufficiale di TSF: `the_seo_framework_title_from_generation` e `the_seo_framework_description_excerpt`.
- **Ritocchi ai tag di TSF**: TSF tratta la Hub come un articolo. Il plugin, tramite il filtro generale `the_seo_framework_meta_render_data`, imposta l'anteprima social come `website`. Sulle pagine successive (`/page/2/`) corregge canonical e `og:url` perché puntino a sé stesse e aggiunge "Pagina 2" al titolo, che TSF numera solo per gli articoli divisi in più pagine.

### 🗺️ 3. Sitemap più pulita (sitemap di WordPress)

- **Tag dei giochi esclusi**: la sitemap non elenca più i tag collegati a IGDB, che rimandano alla Hub con un redirect 301. Sul sito Local i tag elencati scendono da 723 a 627. Per la sitemap di The SEO Framework l'esclusione c'era già.
- **Comunicati in noindex esclusi**: i comunicati stampa segnati "noindex" non compaiono nella sitemap.

### 🔗 4. Pagine successive della Hub

`/videogioco/nome-gioco/page/2/` ora indica sé stessa come pagina canonica, invece della pagina 1. Così Google può trovare anche gli articoli più vecchi del gioco.

### 🏷️ 5. Dettagli

- "Tutti gli articoli" nella Hub è ora un titolo H2, prima era un H3 senza H2 sopra. L'aspetto non cambia. Le modifiche valgono nel tema parent e nel child.
- Le immagini delle schede articolo hanno sempre il testo alternativo: quello dell'immagine oppure, se manca, il titolo dell'articolo.
- I titoli di sezione in stile codice ("// Recensione_in_evidenza", "// Scheda_media") restano come li ha scelti il CEO.

---

### ✅ 6. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: tutti i file modificati, nessun errore.
- **Suite di collaudo**: **325 test su 325 superati**.
  - Aggiornato 7.12: ora controlla il nuovo testo automatico, con gli sviluppatori uniti in italiano.
  - Aggiornato 30.9: ora la Hub ha 5 titoli di sezione, con "// Panoramica".
  - Aggiunti al banco di prova i sostituti di `wp_specialchars_decode`, `wp_date`, `has_filter`, `remove_filter` e le costanti di tempo di WordPress.
  - Nuova suite 39 con 9 controlli: caricamento del modulo, testo automatico, lettura delle date IGDB, dati strutturati della Hub, canonical e sitemap, testo visibile e titoli, titolo per Google e collegamento a TSF, ritocchi ai tag di TSF, "// Panoramica" con il testo dell'editor.
- **Prova sul sito Local** (Hub di Ace Combat 8):
  - **Dati strutturati**: c'è un solo blocco, una `CollectionPage` con il `VideoGame`, e non c'è più l'"Article".
  - **Pagina**: la cella "In breve" è visibile in chiaro, scuro e da telefono; la pagina 2 ha il canonical giusto; tutte le immagini hanno il testo alternativo.
  - **Titoli**: controllati su Ace Combat 8 (con recensione, anche a pagina 2), Kingdom Hearts IV e LEGO Skylines (senza recensione).
  - **Panoramica**: provata nel banco di prova (test 39.9) e sulla Hub di Onimusha: Way of the Sword, dove il CEO ha scritto un testo nell'editor. Controllata in chiaro, scuro e da telefono: "In breve" sparisce e la meta description di TSF viene presa dal testo della redazione. Le Hub senza testo restano identiche.
  - **Registro PHP**: nessun nuovo errore.
- **Gate di sicurezza**: il testo automatico passa da `esc_html`, gli indirizzi da `esc_url_raw` e i dati strutturati da `wp_json_encode`.
- **Sincronizzazione 4 header (SemVer 3.11.0, MINOR)**.
- **Pacchetti**: ZIP 3.10.0 spostati in `archive/`, creati i tre ZIP 3.11.0 con `package_release.ps1`.

### ⚠️ Note e verifiche sul sito vero

- **Prova con The SEO Framework 5.2.0**, installato dal CEO sul sito Local:
  - **Hub di Ace Combat 8 (pagine 1 e 2) e di Kingdom Hearts IV**: titolo, meta description dal testo "In breve", `og:type` website, canonical e `og:url` corretti, dati strutturati `CollectionPage` + `VideoGame`.
  - **Articoli normali**: non sono toccati. La recensione di Ace Combat 8 resta `article`, con dati strutturati `WebPage+Review` e `VideoGame`.
  - Il collegamento richiede TSF 5.0 o successivo.
- **Sitemap di The SEO Framework: impostazione da cambiare sul sito vero.** TSF mette nella sitemap al massimo 250 contenuti, il valore predefinito. Sul sito Local i 500 articoli riempiono la sitemap e delle 171 Hub ne entra una sola. Va alzato il limite in *SEO → Impostazioni → Sitemap → "Sitemap query limit"*, per esempio a 2000, valore che deve superare articoli, Hub e pagine messi insieme. È un'impostazione del CEO: il codice non la modifica.
- **Separatore del titolo**: TSF usa "-" prima del nome del sito, il titolo della Hub usa "–". Per uniformarli si può scegliere "–" in *SEO → Impostazioni → Titolo → Separatore*.
- **Redirect anomalo sul sito Local**: `/tag/remedy-entertainment/` porta alla pagina di accesso di WordPress. Probabilmente è una regola di prova nel gestore dei redirect: conviene controllarla.
- **Da sistemare più avanti (non riguarda questa versione)**: nel box della recensione in evidenza della Hub, a certe larghezze dello schermo intorno ai 1000 pixel, la parola del voto "CONSIGLIATO" va a capo spezzandosi in "CONSIGLIA / TO".
