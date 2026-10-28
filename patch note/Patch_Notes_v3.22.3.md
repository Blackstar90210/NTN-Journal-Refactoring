# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.22.3 (SEO delle pagine dei marchi e delle Hub degli studi)

Correzioni richieste dal CEO dopo il controllo SEO del sito online (q-gin.info) con la versione 3.22.2.

---

### 📱 1. Pagine dei marchi senza sottocategorie (Android, iOS)

- **Prima** (dalla 3.22.0): sulla pagina di un marchio senza sottocategorie, il marchio era elencato come unica piattaforma di sé stesso. La descrizione per Google diceva "Notizie, recensioni e anteprime su Android: Android, …"; la pagina mostrava la sezione "Piattaforme" con una sola scheda che rimandava alla pagina stessa e l'elenco per Google (ItemList) con un solo elemento.
- **Ora**: sulla sua stessa pagina il marchio non è più una piattaforma. La descrizione diventa "Notizie, recensioni e anteprime su Android, con tutti gli articoli di Q-Gin.", la sezione "Piattaforme" e il contatore non compaiono (come nella Hub PC Gaming) e l'elenco per Google non viene scritto.
- La Hub Mobile resta com'era: Android e iOS restano le sue due piattaforme.

### 🧭 2. Percorso per Google della Hub dello studio

- **Prima** (dalla 3.21.0): la pagina mostrava Home // Sviluppatori Italiani di Videogiochi // 3DClouds, ma The SEO Framework passava a Google solo "Q-Gin > 3DClouds".
- **Ora**: anche nei dati strutturati di The SEO Framework il percorso include la Hub che elenca lo studio (nuova funzione `ntn_core_studio_tsf_breadcrumb`).

### 🗺️ 3. Hub degli studi nella sitemap

- The SEO Framework non elenca i tag. Le Hub degli studi visibili a Google (almeno 2 giochi nel catalogo oppure la soglia di articoli) ora vengono aggiunte alla sitemap, con la data dell'ultimo articolo. Quelle sotto la soglia restano fuori.

### 🖼️ 4. Immagine di condivisione della Hub dello studio

- **Prima**: condividendo la pagina di uno studio compariva il logo del sito (512×512).
- **Ora**: si usa l'immagine originale dell'ultimo articolo dello studio che ne ha una, con le sue misure reali.

---

### ✅ 5. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su `studio-hub.php`, `category-hub.php`, `ntn-journal-core.php` e `audit_test_suite.php`.
- **Suite di collaudo**: **403 test su 403 superati**, con la nuova suite 59 (marchi senza sottocategorie, percorso dello studio, sitemap e immagine delle Hub degli studi).
- **Sincronizzazione 4 header (SemVer 3.22.3, PATCH)**.
- **Pacchetti**: ZIP 3.22.2 spostati in `archive/`, creati i tre ZIP 3.22.3 con `package_release.ps1`.
- **Dopo l'installazione**: svuotare la cache del sito; per la sitemap, salvare una volta le impostazioni di The SEO Framework (o attendere il suo aggiornamento automatico), poi controllare `/sitemap.xml`, `/sezione/mobile/android/` e `/argomento/3dclouds/`.
