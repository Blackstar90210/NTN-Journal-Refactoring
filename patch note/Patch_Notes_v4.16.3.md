# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.16.3 (Defer solo agli script del tema, preload dell'immagine principale corretto)

Punti 8 e 11 del rapporto di audit sulla coerenza, in `ntn-journal/inc/performance.php`. Il resto del codice è invariato.

### 🛠️ 1. `defer` solo agli script del tema (punto 8)

Un filtro aggiungeva `defer` a **tutti** gli script del sito, anche a quelli di altri plugin (statistiche, cookie, moduli…). Alcuni di questi dipendono da script inline che devono girare subito, e con `defer` l'ordine cambiava.

- Ora `defer` si aggiunge solo agli script del tema che lo usavano già: `ntn-lazy-video`, `ntn-live-search`, `ntn-catalog-filters` e `glightbox`. Gli altri script del tema (`main.js`, `front-page.js`, `dot-art.js`) lo hanno già dalla loro riga di caricamento.
- Gli script di altri plugin non vengono più toccati. Il comportamento delle pagine non cambia.
- Un tema figlio o un plugin può aggiungere altri nomi con il filtro `ntn_journal_deferred_script_handles`.

### 🖼️ 2. Preload dell'immagine principale con la misura giusta (punto 11)

Il preload scaricava la misura `full` dell'immagine in evidenza, ma la pagina mostra la misura `large` (con `srcset`): il browser poteva scaricare l'immagine due volte.

- Il preload usa ora la stessa misura `large`, con `imagesrcset` e `imagesizes`, come l'immagine nella pagina.
- Si fa solo per articoli, pagine e comunicati stampa, cioè i modelli che mostrano davvero l'immagine in evidenza in testata. Non più per le schede dei videogiochi, che non la mostrano, né per la home.

Da confermare con PageSpeed dopo l'installazione: il punteggio dell'immagine principale (LCP) non deve peggiorare.

### Ordine di installazione
Plugin, poi tema parent, poi child (il cambiamento vero è nel parent).
