# Piano di Implementazione: Ottimizzazioni Catalogo Videogiochi (Dettagli Tecnici)

A seguito degli accertamenti tecnici eseguiti dai sub-agenti, il piano per la **Fase 1 e Fase 2** è stato raffinato con i blocchi di codice esatti che andremo a implementare.

## Dettagli Tecnici Accertati

### 1. Iniezione SSR (Server-Side Rendering)
Per evitare il caricamento vuoto, in `archive-videogioco.php` implementeremo un `WP_Query` esplicito per stampare le prime 20 card, recuperando i metadati (cover, anno) con la stessa logica di fallback attualmente presente nel backend AJAX.
L'ordinamento base sarà `meta_value_num` decrescente basato su `_ntn_game_release`.

### 2. Rifattorizzazione JS (`catalog-filters.js`)
Lo script JavaScript non innescherà più una chiamata AJAX al primo caricamento (`DOMContentLoad`). Introdurremo una flag `isInitialLoad` che bypasserà la fetch iniziale, mettendosi in ascolto solo per l'infinite scroll (Pagina 2+) o per la modifica dei filtri da parte dell'utente.

### 3. Skeleton Loader (Anti-CLS)
Durante le chiamate AJAX attivate dall'utente (quando cambia un filtro), la griglia verrà svuotata e popolata temporaneamente con 20 "Scheletri" animati in puro stile Nothing OS, costruiti con le classi Tailwind (es. `bg-zinc-800 animate-pulse`).

### 4. Filtri Mobile Responsive
Invece di duplicare l'HTML della barra filtri, inietteremo un pulsante per il toggle della visibilità (`md:hidden`) che tramite un listener JS permetterà di espandere/collassare il blocco `ntn-catalog-filters` sugli smartphone.

---

## User Review Required

Tutti i test e gli accertamenti confermano che l'implementazione simultanea della Fase 1 (SSR) e della Fase 2 (JS + UI) nel file `archive-videogioco.php` e `catalog-filters.js` è sicura e non viola le regole di separazione Theme/Plugin dello Statuto Aziendale.

Procedendo, modificherò attivamente il codice sorgente del tema Child e incrementerò il numero di versione (SemVer).

## Proposed Changes

### [MODIFY] [archive-videogioco.php](file:///F:/Sviluppo/Divisione_WP/NTN%20Journal%20Refactoring/NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/archive-videogioco.php)
- Sostituzione della riga 95-97 (grid vuota) con il blocco completo `WP_Query` e la formattazione HTML speculare ad `ajax-catalog.php`.
- Inserimento del Toggle UI per i filtri mobile e del bottone "Reset Filtri".

### [MODIFY] [catalog-filters.js](file:///F:/Sviluppo/Divisione_WP/NTN%20Journal%20Refactoring/NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/assets/js/catalog-filters.js)
- Ristrutturazione logica per saltare la prima fetch AJAX (riconoscendo l'HTML già popolato dal server).
- Inserimento dell'HTML degli Skeletons nel flusso di attesa `isLoading`.
- Aggiunta listener per il tasto "Reset Filtri".
- (Opzionale/Integrato) `history.pushState` per il salvataggio dei filtri nell'URL.

## Verification Plan
1. Salverò i file modificati (PHP e JS).
2. Avanzerò il numero di versione nei 4 header previsti dalle regole `GEMINI.md`.
3. Verificherò con il comando CLI (o tramite script di test locale) che la pagina PHP sia priva di Syntax Error.
