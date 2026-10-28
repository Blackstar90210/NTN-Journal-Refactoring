# Piano d'Azione Architetturale: Progetto "NTN Journal"

Questo documento definisce le linee guida strategiche per lo sviluppo, la personalizzazione e il refactoring del progetto **NTN Journal**, puntando a massimizzare le performance (mobile-first), la pulizia del codice e la manutenibilità futura, riducendo a zero l'uso di plugin commerciali.

---

## 1. Organizzazione Agentica (Task Force)

Per garantire la massima qualità e il rispetto delle rigorose fasi di sviluppo (produzione, testing, controllo e backup), il progetto è gestito da una **Task Force** di 4 sub-agenti iper-specializzati, orchestrati dal Project Manager:

1. **`NTN_SysAdmin`**: Amministratore di sistema. Si occupa della preparazione dell'ambiente, del copia/incolla massivo iniziale dei file sorgenti, della sicurezza dei dati e della creazione di backup e punti di ripristino per ogni evenienza.
2. **`NTN_Developer`**: Sviluppatore tecnico. Esegue materialmente il refactoring del codice, la stesura della logica PHP e la migrazione dal tema al plugin unico `ntn-core`.
3. **`NTN_UI_Tester`**: Specialista Frontend. Verifica che l'interfaccia grafica (HTML/CSS) e le performance, specialmente su dispositivi mobile, non subiscano alterazioni dopo lo scorporo della logica.
4. **`NTN_Quality_Control`**: Specialista QA. Effettua l'housekeeping (pulizia file inutili), vigila sul rispetto dell'alberatura e si occupa dell'impacchettamento finale (creazione dei file ZIP per la release).

---

## 2. Fasi di Attuazione (Roadmap)

L'intero piano architetturale viene attuato in **4 fasi progressive e sicure**:

### Fase 1: Creazione e Setup del Child Theme (`ntn-journal-child`)
- Creazione della cartella `ntn-journal-child`.
- Creazione del file `style.css` con riferimento al tema genitore (`Template: ntn-journal`).
- Creazione del file `functions.php` e del modulo `inc/enqueue.php` per garantire il corretto caricamento a cascata dei CSS.

### Fase 2: Creazione del Plugin Unico (`ntn-core`) e Migrazione Moduli
- Creazione della cartella `wp-content/plugins/ntn-core/` e del file master `ntn-core.php`.
- Spostamento ordinato dei moduli di dati/funzionalità da `ntn-journal/inc/` a `ntn-core/inc/`.
- Spostamento degli asset JS dedicati all'admin/API in `ntn-core/assets/`.

### Fase 3: Pulizia del Tema Genitore (`ntn-journal`)
- Rimozione dal `functions.php` di `ntn-journal` delle inclusioni dei file migrati in `ntn-core`, per evitare doppie definizioni o conflitti.
- Mantenimento nel tema genitore (o nel child) dei soli template di visualizzazione (es. `single-comunicato_stampa.php`, `archive-comunicato_stampa.php`).

### Fase 4: Collaudo, Attivazione e Test
- Attivazione del plugin `NTN Journal Core` e del tema `NTN Journal Child` da bacheca WordPress.
- Verifica del corretto caricamento dei Custom Post Types, dell'integrazione IGDB, dei metadati delle recensioni e dell'aspetto visivo.

---

## 3. Il Child Theme (Tema Figlio): Cos'è e cosa contiene

### Definizione
Un Child Theme è un tema che eredita tutte le funzionalità e lo stile del tema genitore (in questo caso, *NTN Journal*). Funziona come una "patina protettiva": le modifiche fatte qui sovrascrivono il genitore, ma non verranno mai cancellate quando il tema genitore riceverà aggiornamenti di sicurezza o nuove versioni.

### Quali parti devono essere gestite dal Child Theme?
Nel Child Theme devono risiedere **esclusivamente** le modifiche legate all'estetica e alla presentazione visiva. 
Nello specifico:
- **`style.css`**: Per sovrascrivere o aggiungere regole CSS personalizzate per il design (es. colori, margini, tipografia, responsive design per mobile).
- **File di Template (es. `header.php`, `footer.php`, `single.php`)**: Se è necessario modificare la struttura HTML della pagina (ad esempio, per spostare il logo, cambiare l'ordine degli elementi in un articolo o rifare il footer).
- **`functions.php` (Lato UI)**: Solo per funzioni strettamente legate all'interfaccia, come:
  - Registrare nuovi menu di navigazione.
  - Registrare nuove "Aree Widget" (Sidebar).
  - Modificare la lunghezza del riassunto (excerpt) degli articoli.
  - Modificare elementi specifici del tema genitore tramite i suoi *Hook*.

> [!WARNING]  
> Il `functions.php` del Child Theme non deve mai diventare un "cestino" in cui buttare codice a caso. Se una funzione non riguarda l'aspetto grafico, deve risiedere nel plugin di supporto.

---

## 4. Separazione delle Funzioni: Il Plugin Unico "NTN Core"

La regola d'oro nello sviluppo WordPress professionale è la **Separazione degli Intenti (Separation of Concerns)**:
- **Il Tema / Child Theme** si occupa del *vestito* (UI, CSS, HTML).
- **Il Plugin** si occupa della *logica di business e dei dati* (Custom Post Types, Tassonomie, API esterne, Campi personalizzati).

### La Strategia del "Plugin Unico di Supporto" (`ntn-core`)
Per evitare di intasare la bacheca di WordPress con decine di micro-plugin sparsi (mantenendo la lista dei plugin snella e pulita), tutte le funzionalità non grafiche vengono racchiuse in un **unico plugin proprietario modulare: `NTN Journal Core` (`ntn-core`)**.

### Riutilizzo del Codice Esistente e Zero Perdita Dati
1. **Nessun codice da riscrivere da zero:** I moduli già presenti e funzionanti nella cartella `ntn-journal/inc/` verranno riutilizzati e spostati direttamente dentro `ntn-core/inc/`.
2. **Nessuna migrazione database necessaria:** WordPress memorizza i contenuti (post type, metadati, voti, tassonomie) nel database tramite chiavi fisse (slug). Poiché il plugin `ntn-core` utilizzerà gli stessi identici slug e funzioni, **tutti i dati storici del sito rimarranno perfettamente intatti e associati**.

---

## 5. Organizzazione del Codice Sorgente

Sia il Child Theme che il plugin Core adotteranno un'architettura **Modulare** con cartella `inc/`, evitando file monolitici.

### Alberatura del Child Theme (`ntn-journal-child`)
```text
ntn-journal-child/
│
├── style.css               (Dichiarazione Child Theme e CSS personalizzato)
├── functions.php           (Carica esclusivamente i moduli UI tramite require_once)
│
├── assets/                 (Risorse statiche di stile)
│   ├── css/
│   │   └── custom-mobile.css
│   └── js/
│       └── custom-interactions.js
│
└── inc/                    (Codice PHP legato alla sola interfaccia)
    ├── setup.php           (Supporti tema, menu aggiuntivi)
    ├── enqueue.php         (Caricamento ordinato dei fogli di stile genitore/figlio)
    └── hooks.php           (Filtri e azioni per modificare l'output visivo)
```

### Alberatura del Plugin Unico (`ntn-core`)
```text
wp-content/plugins/ntn-core/
│
├── ntn-core.php            (File principale del plugin con intestazione WP e loader)
│
├── assets/                 (Script e stili specifici delle funzionalità/admin)
│   ├── js/
│   │   ├── admin-igdb.js
│   │   └── ai-summary-gutenberg.js
│   └── css/
│
└── inc/                    (Moduli logici spostati da ntn-journal/inc/)
    ├── cpt-comunicati-stampa.php  (Custom Post Type Comunicati Stampa)
    ├── taxonomies.php             (Tassonomie personalizzate)
    ├── ajax-igdb.php              (Integrazione API IGDB)
    ├── review-meta.php            (Campi personalizzati e voti recensioni)
    ├── rating-labels.php          (Etichette e calcolo valutazioni)
    ├── ai-summaries.php           (Generazione riassunti AI)
    ├── redirect-manager.php       (Gestione redirect)
    ├── backup-restore.php         (Strumenti di backup/restore)
    ├── seo-extras.php             (Logiche SEO)
    ├── schema-markup.php          (Dati strutturati Schema.org)
    └── sitemap.php                (Generazione sitemap)
```
