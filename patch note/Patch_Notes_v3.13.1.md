# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.13.1 (tendina "Piattaforme" a colonne)

Correzione della tendina "Piattaforme" della testata, segnalata dal CEO dopo aver inserito sul sito Local delle voci su più livelli (Console › Sony › PlayStation 5).

---

### 🐞 1. Il problema

La tendina della 3.12.0 era pensata per un elenco semplice. Con voci su più livelli:
- la freccia di "Console" andava a capo sotto la parola;
- il sottomenu di "Console" si apriva appena il mouse passava sulla tendina, sopra le altre voci, e non quando passava su "Console".

### 🧭 2. La soluzione: pannello a colonne (`inc/menu-filters.php`, `build/input.css`, `assets/js/main.js`)

- **Ogni voce principale del menu Piattaforme diventa una colonna.** Il titolo è in Mono grassetto e, se la voce ha figli, è sottolineato.
- **I sottolivelli stanno sotto, sempre visibili**: rientrati, con un filo a sinistra, più chiari a ogni livello. Niente più tendine dentro la tendina.
- **La voce della pagina in cui ti trovi** è più scura, con un segno rosso a sinistra.
- **Il pannello è largo quanto serve** e, se uscirebbe dal bordo destro dello schermo, si sposta a sinistra da solo.
- **Da telefono**, nel pannello del menu, le voci con sottolivelli occupano una riga intera e i sottolivelli stanno in colonna sotto, con lo stesso filo.

Il pannello è pronto anche per le future Hub dei marchi: per esempio PlayStation con PlayStation 5 e PlayStation 4, Xbox con Series X|S e One, Nintendo con Switch 2 e Switch.

---

### ✅ 3. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su `inc/menu-filters.php`; `node --check` su `main.js`.
- **Suite di collaudo**: **335 test su 335 superati**. Aggiornato 41.1 (le voci principali della tendina sono titoli di colonna). Nuovo 41.5: colonne, sottolivelli sempre visibili e rientrati, nessuna freccia interna.
- **Prova sul sito Local**, con il menu Piattaforme del CEO (PC Gaming; Console › Sony › PlayStation 5):
  - **Da computer**: due colonne affiancate, il pannello resta dentro lo schermo.
  - **Da telefono**: gruppo Piattaforme ordinato, senza scorrimento orizzontale.
- **Sincronizzazione 4 header (SemVer 3.13.1, PATCH)**.
- **Pacchetti**: ZIP 3.13.0 spostati in `archive/`, creati i tre ZIP 3.13.1 con `package_release.ps1`.

### ⚠️ Note

- Sul sito vero le categorie sono già organizzate come Console › Microsoft › Xbox Series X, con la base `/sezione/`. Le future Hub dei marchi potranno usare queste categorie senza cambiare gli indirizzi.
