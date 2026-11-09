# NTN Journal - WordPress Theme

**NTN Journal** is a brutalist WordPress theme inspired by the design language of Nothing OS, built for the video game news site **q-gin.info**. It is made of three parts that share the same version number: the parent theme, the child theme **NTN Journal Reloaded** and the **NTN Journal Core** plugin.

*Read this document in: [English](#english) | [Italiano](#italiano)*

**Version:** 4.20.1 · **Authors:** Luca Maiolini & Divisione_WP

---

<a name="english"></a>
## 🇬🇧 English

### 🧩 The three parts

| Part | Folder | Role |
|---|---|---|
| Parent theme | `ntn-journal` | Page templates (including the video game catalog and game Hub), Tailwind CSS v4 styles, front-end scripts (including live search) |
| Child theme | `ntn-journal-reloaded` | Your own customizations only (empty since 4.10.0) |
| Core plugin | `ntn-journal-core` | All data and logic: post types, taxonomies, IGDB, SEO, redirects, backup, Hubs, admin panel |

The theme displays the data, the plugin manages it. The theme keeps working if the plugin is disabled, but most features need it.

### 🌟 Key Features

*   **Nothing OS Aesthetics**: black, white and red, dot-matrix bands and illustrations, 1 px lines, sharp corners, no shadows, gradients or photo zoom. **Inter** for reading, **JetBrains Mono** for labels, dates and numbers, both served locally.
*   **Light and Dark Mode**: a single toggle button that remembers the reader's choice.
*   **Home Page**: one large and three small lead stories, latest news, reviews, editorials, upcoming releases, a Hub band with the dot-matrix map of Italy, Twitch and YouTube widget areas.
*   **Articles**: large header, author byline, reading time, numbered subheadings, table of contents, "Recommended" boxes placed by text length, tags, share buttons, "Follow us" panel, Google preferred sources box, "Read also".
*   **Reviews**: rating box with five LEDs, Pros & Cons (one per line), verdict, game card in the sidebar and a quick card on mobile.
*   **Multi-game Card**: an article about two or more games shows one page per game, in alphabetical order: a carousel in the sidebar on desktop, arrows and swipe on mobile. Each game keeps its own data and has a review row, "Leggi la recensione" or "Non recensito". The rating appears only on the game the open review is about, and stays hidden if the review matches none of them.
*   **Video Game Catalog**: filters without page reload, infinite scroll, release dates shown with the precision known from IGDB.
*   **Hubs**: video game Hub, category Hubs (Consoles, Mobile, PC Gaming, Italian developers), studio Hub on the studio tag, Team page with authors in groups.
*   **IGDB Integration**: covers, trailers, screenshots, developers, publishers, platforms, genres and engines.
*   **Press Releases**: dedicated post type, list and page.
*   **SEO**: Schema.org structured data, The SEO Framework integration, sitemaps, Google News sitemap, thin pages kept out of Google, Google Discover-ready preview image.
*   **Redirects**: single and folder redirects, out-of-range page numbers, option to stop WordPress from guessing URLs.
*   **AI Summaries**: one-click excerpt in the block editor with **Google Gemini**, with **Groq** as fallback.
*   **Breaking News bar**, **backup and restore**, **API key check**.
*   **Performance**: uploaded images resized to 1920 px and converted to JPEG when not transparent, deferred scripts, preloads, data caching.
*   **Accessibility**: labels for screen readers, keyboard navigation, checked contrasts.

### ⚙️ Requirements
*   **WordPress**: 6.0 or higher (tested up to 7.x)
*   **PHP**: 8.0 or higher (tested up to 8.5)
*   **Optional**: The SEO Framework 5.x

### 🚀 Installation
1. Upload and activate the plugin `ntn-journal-core-X.Y.Z.zip` from **Plugins > Add New**.
2. Upload the parent theme `ntn-journal-parent-X.Y.Z.zip` from **Appearance > Themes > Add New**. Do not activate it.
3. Upload the child theme `ntn-journal-child-reloaded-X.Y.Z.zip` and **activate NTN Journal Reloaded**. It must always stay active.

To update, upload the plugin first, then the parent theme, then the child theme. From 4.11.0 the game data functions live in the plugin; with an older plugin, game pages show no game data until the plugin is updated.

### 🛠️ Configuration
*   **Appearance > Customize**: top bar text, logo, site name, recommended and related posts, watermark, Home Hub band, share buttons, Google box, social profiles.
*   **Appearance > Menus**: four locations (Primary, Content, Platforms, Footer).
*   **Appearance > Widgets**: Main Sidebar, Twitch Live and YouTube Hub for the Home page.
*   **NTN Journal** (admin menu): Dashboard, Hub, Team, Reviews, Press Releases, IGDB Scanner, Breaking News, Redirects, API Services, SEO, Extra, Backup.

### 🤖 AI Configuration
1. Go to **NTN Journal > API Services** in your WordPress dashboard.
2. Paste your Gemini API key and Groq API key. Saved keys are never shown again on the page.
3. Click **Verify** to run a free test request.
4. In the block editor, use the **AI Generation (Summary)** panel to create the excerpt.

---

<a name="italiano"></a>
## 🇮🇹 Italiano

### 🧩 I tre pezzi

| Pezzo | Cartella | Ruolo |
|---|---|---|
| Tema parent | `ntn-journal` | Modelli delle pagine (compresi catalogo dei videogiochi e Hub del gioco), stile con Tailwind CSS v4, script del sito (compresa la ricerca dal vivo) |
| Tema child | `ntn-journal-reloaded` | Solo le tue personalizzazioni (vuoto dalla 4.10.0) |
| Plugin | `ntn-journal-core` | Tutti i dati e la logica: tipi di contenuto, tassonomie, IGDB, SEO, redirect, backup, Hub, pannello di gestione |

Il tema mostra i dati, il plugin li gestisce. Il tema funziona anche con il plugin spento, ma gran parte delle funzioni ha bisogno del plugin.

### 🌟 Caratteristiche Principali

*   **Estetica Nothing OS**: nero, bianco e rosso, fasce e disegni a matrice di punti, linee da 1 pixel, angoli vivi, niente ombre, sfumature o zoom sulle foto. **Inter** per leggere, **JetBrains Mono** per etichette, date e numeri, entrambi caricati dal sito.
*   **Tema chiaro e scuro**: un solo pulsante che ricorda la scelta del lettore.
*   **Home**: una notizia grande e tre piccole in apertura, ultime notizie, recensioni, editoriali, prossime uscite, fascia Hub con la cartina dell'Italia a punti, aree widget per Twitch e YouTube.
*   **Articoli**: testata grande, firma dell'autore, tempo di lettura, sottotitoli numerati, indice, box "Consigliati" inseriti in base alla lunghezza del testo, tag, condivisione, pannello "Seguici", box "Fonti preferite su Google", "Leggi anche".
*   **Recensioni**: box del voto con cinque LED, pro e contro (uno per riga), verdetto, scheda del gioco nella colonna laterale e scheda rapida da telefono.
*   **Scheda multigioco**: un articolo su due o più giochi mostra una pagina per gioco, in ordine alfabetico: carosello nella colonna laterale su PC, frecce e scorrimento col dito da telefono. Ogni gioco ha i suoi dati e una riga per la recensione, "Leggi la recensione" o "Non recensito". Il voto compare solo sul gioco di cui parla la recensione aperta e resta nascosto se la recensione non corrisponde a nessuno.
*   **Catalogo dei videogiochi**: filtri senza ricaricare la pagina, scorrimento infinito, date di uscita con la precisione nota da IGDB.
*   **Hub**: Hub del videogioco, Hub di categoria (Console, Mobile, PC Gaming, Sviluppatori italiani), Hub dello studio sul tag dello studio, pagina Redazione con gli autori a gruppi.
*   **Integrazione IGDB**: copertine, trailer, screenshot, sviluppatori, editori, piattaforme, generi e motori grafici.
*   **Comunicati stampa**: tipo di contenuto, elenco e pagina dedicati.
*   **SEO**: dati strutturati Schema.org, integrazione con The SEO Framework, sitemap, sitemap Google News, pagine con pochi articoli fuori da Google, immagine di anteprima adatta a Google Discover.
*   **Redirect**: singoli e per cartella, pagine numerate oltre la fine, opzione per impedire a WordPress di "tirare a indovinare" gli indirizzi.
*   **Riassunti con l'IA**: estratto con un clic nell'editor a blocchi con **Google Gemini**, con **Groq** di riserva.
*   **Barra Breaking News**, **backup e ripristino**, **verifica delle chiavi API**.
*   **Prestazioni**: immagini caricate ridotte a 1920 px e convertite in JPEG se non trasparenti, script differiti, precaricamenti, dati in memoria.
*   **Accessibilità**: etichette per i lettori di schermo, navigazione da tastiera, contrasti verificati.

### ⚙️ Requisiti
*   **WordPress**: 6.0 o superiore (provato fino alla 7.x)
*   **PHP**: 8.0 o superiore (provato fino alla 8.5)
*   **Facoltativo**: The SEO Framework 5.x

### 🚀 Installazione
1. Carica e attiva il plugin `ntn-journal-core-X.Y.Z.zip` da **Plugin > Aggiungi nuovo**.
2. Carica il tema parent `ntn-journal-parent-X.Y.Z.zip` da **Aspetto > Temi > Aggiungi nuovo**. Non attivarlo.
3. Carica il tema child `ntn-journal-child-reloaded-X.Y.Z.zip` e **attiva NTN Journal Reloaded**. Deve restare sempre attivo.

Per aggiornare carica prima il plugin, poi il tema parent, poi il tema child. Dalla 4.11.0 le funzioni dei dati dei giochi sono nel plugin: con un plugin più vecchio le pagine dei giochi restano senza dati finché non aggiorni il plugin.

### 🛠️ Configurazione
*   **Aspetto > Personalizza**: testo della barra in alto, logo, nome del sito, articoli consigliati e correlati, filigrana, fascia Hub della Home, condivisione, box Google, profili social.
*   **Aspetto > Menu**: quattro posizioni (Primary, Gestione Contenuti, Gestione Piattaforme, Footer).
*   **Aspetto > Widget**: Sidebar Principale, Twitch Live e YouTube Hub per la Home.
*   **NTN Journal** (menu della bacheca): Dashboard, Hub, Redazione, Recensioni, Comunicati, Scanner IGDB, Breaking News, Redirect, Servizi API, SEO, Extra, Backup.

### 🤖 Configurazione AI
1. Vai su **NTN Journal > Servizi API** nella bacheca di WordPress.
2. Incolla la API Key di Gemini e quella di Groq. Le chiavi salvate non vengono più mostrate nella pagina.
3. Premi **Verifica** per fare una richiesta di prova gratuita.
4. Nell'editor a blocchi usa il riquadro **Generazione AI (Riassunto)** per creare l'estratto.

---

## 👨‍💻 Authors & License
*   **Authors**: Luca Maiolini (@Blackstar90210) & Divisione_WP
*   **License**: NTN Journal parent theme under GNU General Public License v2 or later; NTN Journal Core plugin proprietary
