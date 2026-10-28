# Piano di lavoro — Fase 1: Fondamenta del nuovo stile

> **Redatto da:** Nicola (Division Lead NTN) per Adriano (COO), con il parere architetturale di Mario
> **Data:** 2026-10-17
> **Riferimento visivo:** mockup completo `docs/mockup/ntn-mockup-completo.html` (https://claude.ai/artifact/EVHgQceYe1k29ky2jwMKT6), approvato dal CEO come punto di arrivo
> **Piani collegati:** `docs/piano_restyling_estetico.md` (fasi del restyling), `docs/piano_hub_categoria.md` (Hub di categoria)
> **Versione di partenza:** 3.2.15 → **versione di arrivo:** 3.3.0 (MINOR)

---

## Decisioni prese

| # | Decisione | Esito | Motivo |
|---|---|---|---|
| 1 | Da dove si parte | **Prima le fondamenta, poi la Hub di categoria** | Il mockup completo ora fissa lo stile di tutto il sito. Costruire prima i componenti comuni evita di scrivere la Hub con regole sue e di doverla rifare dopo. |
| 2 | Dove si lavora | **Tema parent** | Il parent contiene già il CSS compilato da Tailwind e 25 dei 29 modelli di pagina. Copiare i modelli nel child raddoppierebbe i file da mantenere. La Regola 3 separa il tema (aspetto) dal plugin (dati): spostare l'aspetto dal child al parent resta dentro il tema e non la viola. |
| 3 | Dove si prova | **Sito Local `ntn-journal-test`** | Temi e plugin sono collegati direttamente alle cartelle del progetto: ogni modifica si vede subito, senza installare ZIP. |
| 4 | Cartina della Hub | **Senza pallini rossi nella prima versione** | Manca il dato "città" degli studi. |
| 5 | Carosello | **Come nel mockup** | Approvato insieme al mockup: carosello sostituito da "una grande e tre piccole". Si applica nella fase 3, non in questa. |
| 6 | Fasce di apertura | **Seguono il tema** | Richiesta del CEO del 17.10.2026: nel tema chiaro fondo grigio chiarissimo (`#f4f4f5`) con puntini, cartina e testi scuri; i testi rossi sulla fascia chiara usano `#b91c1c`, perché `#dc2626` su quel grigio non arriva al contrasto minimo 4,5:1 (le etichette rosse piene restano `#dc2626`); nel tema scuro restano nere con puntini chiari e rosso `#ef4444`. Vale per apertura della home, testata dell'articolo, intestazioni delle Hub, fascia degli sviluppatori, valutazione della recensione e barra delle ultime notizie. Le variabili della fascia fanno parte della tavolozza di questa fase. |

Di conseguenza la Hub di categoria esce dopo questa fase, come versione **3.4.0**, e usa i componenti creati qui.

---

## Handoff

- **Obiettivo:** Creare la base comune del nuovo stile (colori, caratteri, linee e componenti) e togliere dal sito gli elementi estranei a Nothing OS. Così le fasi successive (testata, home, articolo, Hub) montano pezzi già pronti e il sito resta coerente mentre cambia.
- **Divisione:** NTN Journal Refactoring
- **Division Lead:** Nicola (`nicola-lead-ntn_refactoring`)
- **Scope:**
  1. **Tavolozza unica** per chiaro e scuro in `build/input.css` (blocco `@theme` e variabili per `.dark`): fondo, testo, grigi di gerarchia, linee, rosso, rosso su nero, colori della fascia di apertura, diversi tra chiaro e scuro (decisione 6). Il grigio dei testi piccoli nel tema chiaro diventa `#71717a`.
  2. **Componenti comuni** come classi in `build/input.css`, ricavati dal mockup: fascia di apertura con matrice di punti, etichetta (chip), intestazione di sezione, griglia a celle, riga di lista compatta, riga dei dati in Mono, segno rosso con pallino (studio, gioco), contatore, paginazione, copertina segnaposto a punti.
  3. **Pulizia** dei modelli del parent e del child: via angoli arrotondati, ombre, sfumature, zoom delle foto al passaggio del mouse, filtri di luminosità e contrasto. Oggi sono circa 150 punti in 30 file. Restano i cerchi veri (pallini, avatar rotondi se previsti).
  4. **Intestazioni di sezione uniformate** su tutte le pagine con il componente del punto 2.
  5. **Ricompilazione** di `style-tailwind-output.css` con `npm run build`.
  6. Versione 3.3.0 sui 4 header, test, pacchetti ZIP.
- **Non-scope:**
  - Nuove impaginazioni: home, articolo, recensione, Hub e catalogo cambiano struttura nelle loro fasi (2–7).
  - Hub di categoria (versione 3.4.0, piano a parte).
  - Plugin `ntn-journal-core`: cambia solo il numero di versione.
  - Pagina Redazione.
- **Specialisti richiesti:** Elena (frontend), A11y Auditor (contrasti e movimento), Riccardo (test). Gate separati: Marco (sicurezza) e Francesca (QA e release). Mario approva il sistema dei componenti prima della pulizia.
- **File/aree autorizzate:**
  - Tema parent: `build/input.css`, `style-tailwind-output.css` (ricompilato), `style.css`, i modelli `.php` e `template-parts/` che contengono gli elementi da togliere, `editor-style.css` solo per la tavolozza.
  - Tema child: `style.css`, `template-parts/content-game-card.php`, `archive-videogioco.php`, `single-videogioco.php` (solo pulizia).
  - Plugin: solo `ntn-journal-core.php` (versione).
  - Test: `audit_test_suite.php`.
  - Le cartelle `WordPress 7.1` e `wordpress-7.0.4` restano intoccabili.
- **Criteri di accettazione:**
  1. Le pagine hanno la stessa struttura di oggi: cambiano solo colori, linee, angoli e intestazioni di sezione.
  2. Nessun angolo arrotondato, ombra, sfumatura, zoom o filtro sulle foto, in chiaro e in scuro.
  3. Ogni colore del tema arriva dalla tavolozza unica: niente nuovi colori scritti a mano nei modelli.
  4. Contrasti conformi WCAG AA, pallino fermo con "riduci movimento".
  5. Nessun errore PHP 8.2, suite di test al 100%, 4 header a 3.3.0, 3 ZIP conformi.
- **Test/evidenze:**
  - Prima e dopo sul sito Local: home, articolo, recensione, Hub di un gioco, categoria, catalogo, comunicato, autore, 404. In chiaro e in scuro, da computer e da telefono.
  - Ricerca automatica nei file per confermare che gli elementi tolti non ci siano più.
  - Nuovi test in `audit_test_suite.php` sulla tavolozza e sull'assenza degli elementi tolti. Aggiornamento dei test esistenti che controllano classi cambiate.
  - Rapporti di A11y Auditor, Marco e Francesca.
- **Rischi e blocchi:**
  - **Test che controllano classi precise:** alcuni dei 256 test verificano classi o colori che cambieranno. Riccardo li aggiorna insieme alle modifiche, senza abbassarne il rigore.
  - **Tema scuro:** oggi funziona con la classe `.dark` sulla pagina, non con le preferenze del sistema. Il nuovo sistema si aggancia a quella classe, così il pulsante chiaro/scuro continua a funzionare come oggi.
  - **Differenze temporanee:** finché le fasi 3–7 non arrivano, home e articolo avranno i colori e le linee nuovi ma l'impaginazione vecchia.
  - Nessun blocco al momento.
- **Stato:** `review` (implementazione conclusa il 17.10.2026, in attesa della verifica del CEO sul sito Local; dettagli in `patch note/Patch_Notes_v3.3.0.md`). Su richiesta del CEO la fase 3 (Home) è stata anticipata e costruita subito sopra questa base come versione 3.4.0 (`patch note/Patch_Notes_v3.4.0.md`).
- **Decisione richiesta al CEO:** Verificare il sito Local e approvare il commit della versione 3.3.0.

---

## Passi

| # | Passo | Chi | Cosa consegna |
|---|---|---|---|
| 1 | Tavolozza e componenti | Elena | `build/input.css` con tavolozza e componenti, CSS ricompilato |
| 2 | Approvazione del sistema | Mario | Via libera su nomi e struttura dei componenti |
| 3 | Pulizia e intestazioni | Elena | Modelli senza elementi estranei, intestazioni uniformi |
| 4 | Controlli visivi | Nicola | Prima e dopo sul sito Local, mostrati al CEO |
| 5 | Accessibilità | A11y Auditor | Contrasti e movimento |
| 6 | Test | Riccardo | Suite aggiornata, al 100% |
| 7 | Gate sicurezza | Marco | Nessun nuovo dato in uscita senza escaping |
| 8 | Gate QA e release | Francesca | Versione 3.3.0, ZIP vecchi in `archive/`, 3 ZIP nuovi |
| 9 | Handoff finale | Nicola → Adriano | Rapporto per il CEO |
