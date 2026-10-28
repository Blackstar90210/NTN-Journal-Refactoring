# Documento Strategico di Ottimizzazione: Catalogo Videogiochi (Frontend)
**Progetto:** NTN Journal Refactoring  
**Redatto da:** Management Core (Mario, Alessandro, Elena, Marco, Francesca)  
**Approvato da:** CEO (Utente)

Questo documento sintetizza le analisi del team esclusivamente per la porzione **Frontend, Codice e Architettura** del Catalogo Videogiochi (Child Theme e Core Plugin). Isola gli interventi ad alto impatto, analizzandone nel dettaglio le ripercussioni positive e negative.

---

## Ottimizzazioni Frontend e Codice (Catalogo Videogiochi)

La pagina del Catalogo attuale fa un uso intensivo di chiamate AJAX, ma delega tutto il caricamento iniziale a JavaScript. Questo approccio presenta diverse inefficienze UX e SEO.

### A. SSR Iniziale (Server-Side Rendering dei primi 20 giochi)
*Modifica di `archive-videogioco.php` per stampare subito i post nel codice HTML iniziale generato dal server, lasciando ad AJAX solo la gestione dei filtri e l'infinite scroll successivo.*

* **Ripercussioni Positive (🟢):** 
  * **SEO massimizzato:** Googlebot e crawler vedono istantaneamente i contenuti del catalogo alla prima scansione, senza dover attendere o renderizzare pesanti script JS.
  * **LCP (Largest Contentful Paint) istantaneo:** Il visitatore vede le card dei primi giochi nel momento esatto in cui la pagina viene caricata, eliminando la fastidiosa schermata vuota iniziale col loader.
* **Ripercussioni Negative (🔴):** 
  * Incrementa la complessità del codice, poiché il layout (HTML/CSS) della "card" del videogioco dovrà essere condiviso e sincronizzato tra la fase PHP (caricamento pagina) e l'endpoint AJAX (risposte filtro).
* **Verdetto:** **FONDAMENTALE.** È la singola modifica con il maggiore impatto percepito e SEO.

### B. History API: Sincronizzazione URL e Filtri
*Aggiornamento dinamico dell'URL del browser (es. `?franchise=halo&year=2024`) tramite JavaScript quando l'utente seleziona le varie opzioni dai dropdown.*

* **Ripercussioni Positive (🟢):** 
  * Rende i filtri "condivisibili": un utente può copiare il link della sua ricerca avanzata e condividerlo sui social.
  * Ripristina la corretta funzionalità dei tasti "Avanti" e "Indietro" del browser, preservando lo stato della ricerca.
* **Ripercussioni Negative (🔴):** 
  * Introduce un livello di complessità nello script `catalog-filters.js` (necessità di parsare i parametri in ingresso all'avvio e aggiornare lo stato cronologia senza forzare reload della pagina).
* **Verdetto:** **ALTAMENTE RACCOMANDATO.** Trasforma una banale pagina web in una Single Page Application (SPA) reattiva.

### C. UI/UX: Skeleton Loader & Ridisegno Filtri Mobile
*Inserimento di placeholder animati stile "Nothing OS" durante il caricamento e ristrutturazione della barra filtri per dispositivi smartphone.*

* **Ripercussioni Positive (🟢):** 
  * **Skeleton:** Azzeramento dei salti visivi (CLS - Cumulative Layout Shift). Il caricamento risulta fluido ed esteticamente premium, coerente con lo stile brutalista.
  * **Mobile Filters:** Una UI collassabile a comparsa (accordion) restituisce oltre il 40% dello schermo verticale al contenuto primario (i giochi) sugli smartphone.
  * Aggiunta del tasto **"Reset Filtri"** (attualmente assente ma critico per la User Experience).
* **Ripercussioni Negative (🔴):** 
  * Nessuna limitazione funzionale; comporta unicamente del lavoro extra in fase di implementazione UI (Tailwind CSS, icone SVG e script di toggle).
* **Verdetto:** **FONDAMENTALE.**

### D. Migrazione da `admin-ajax.php` a REST API Endpoints
*Spostare le richieste dei filtri dall'obsoleto `admin-ajax.php` verso un endpoint dedicato, es. `/wp-json/ntn-journal/v1/games`.*

* **Ripercussioni Positive (🟢):** 
  * Prestazioni lato server quasi raddoppiate (meno RAM/CPU impiegate) non dovendo inizializzare l'intero ambiente di amministrazione WordPress a ogni richiesta.
  * Consente il caching distribuito via proxy/CDN per le query ricorrenti.
* **Ripercussioni Negative (🔴):** 
  * Richiede un refactoring netto in backend (smantellamento di una parte di `ajax-catalog.php` e riscrittura in formato REST Controller).
* **Verdetto:** **CONSIGLIATO (Fase 2).** Se abbinato all'SSR Iniziale (Punto A), la sua priorità si abbassa diventando un eccellente refactoring architetturale di seconda fase.
