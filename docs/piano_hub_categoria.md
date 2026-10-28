# Piano di lavoro — Hub di categoria (Sviluppatori Italiani)

> **Redatto da:** Nicola (Division Lead NTN) per Adriano (COO)
> **Data:** 2026-10-17
> **Riferimento visivo approvato dal CEO:** anteprima "Hub Sviluppatori Italiani" (https://claude.ai/artifact/3j76WkKQbXy5AzigHYy8Be)
> **Versione di partenza:** 3.2.15 → **versione di arrivo:** 3.3.0 (MINOR: nuova funzione retrocompatibile)

---

## Handoff

- **Obiettivo:** Permettere di trasformare qualsiasi categoria in una "Hub di sezione" con un layout dedicato, diverso dalla Hub dei videogiochi. Il primo caso d'uso è "Sviluppatori Italiani di Videogiochi", con un indice degli studi ricavato in automatico dal campo "Sviluppatore" dei giochi del catalogo IGDB (soluzione C, scelta dal CEO).
- **Divisione:** NTN Journal Refactoring
- **Division Lead:** Nicola (`nicola-lead-ntn_refactoring`)
- **Scope:**
  1. Impostazioni Hub nella schermata di modifica della categoria: attivazione, testo di presentazione, articoli in evidenza, raggruppamento per sviluppatore IGDB, studi da escludere, decorazione dell'intestazione (nessuna / Italia a punti).
  2. Calcolo automatico dell'indice degli studi: articoli della categoria → tag dei giochi con dati IGDB → sviluppatori. Per ogni studio: giochi, numero di articoli, ultimo articolo e link al tag dello studio, se esiste.
  3. Pannello "Controllo copertura" in amministrazione: articoli senza gioco IGDB, giochi con più sviluppatori, studi esclusi.
  4. Template della Hub di categoria nel tema child: intestazione con contatori e cartina a punti, sezione "In evidenza", indice A–Z filtrabile, lista compatta di tutti gli articoli con la paginazione attuale.
  5. Dati strutturati (CollectionPage + ItemList degli studi).
  6. Avanzamento della versione a 3.3.0 su tutti e 4 gli header, test, pacchetti ZIP.
- **Non-scope:**
  - Pagina Redazione: sarà la prossima questione, con un piano a parte.
  - Pallini rossi sulle città reali degli studi: richiedono un dato "città" che oggi non esiste. Nella v1 la cartina è solo decorativa e senza pallini, salvo decisione diversa del CEO.
  - Pagine dedicate ai singoli studi: si riusano gli archivi dei tag esistenti.
  - Modifiche agli indirizzi (URL) delle categorie: `/sezione/...` resta invariato.
  - Pulizia editoriale degli articoli fuori tema (per esempio Halo): la fa la redazione. Il sistema si limita a segnalarli e a permetterne l'esclusione.
- **Specialisti richiesti:** Alessandro (backend), Elena (frontend), Giacomo (SEO), DBA (prestazioni), A11y Auditor (accessibilità), Riccardo (test). Gate separati: Marco (sicurezza) e Francesca (QA e release).
- **File/aree autorizzate:**
  - Plugin: `NTN_Journal_Core_Plugin/ntn-journal-core/inc/category-hub.php` (nuovo), `inc/schema-markup.php`, `ntn-journal-core.php` (inclusione modulo e versione).
  - Tema child: `NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/category-hub.php` (nuovo), `template-parts/hub-category-*.php` (nuovi), `assets/js/italy-dots.js` (nuovo), `functions.php`, `inc/enqueue.php`, `style.css`.
  - Tema parent: solo `style.css` (versione).
  - Test: `audit_test_suite.php`.
  - Le cartelle `WordPress 7.1` e `wordpress-7.0.4` restano intoccabili.
- **Criteri di accettazione:**
  1. Con l'opzione Hub spenta, la categoria appare esattamente come oggi.
  2. Con l'opzione accesa, l'indirizzo `/sezione/sviluppatori-italiani-di-videogiochi/` mostra il nuovo layout, fedele all'anteprima, in tema chiaro e scuro, da computer e da telefono.
  3. L'indice mostra solo gli studi ricavati da IGDB e non quelli esclusi. I nomi uguali con grafie diverse ("34 BigThings" / "34BigThings") vengono uniti.
  4. Se esiste un tag con il nome dello studio, la scheda porta a quel tag.
  5. Il filtro A–Z funziona anche senza JavaScript, con tutti gli studi visibili, ed è utilizzabile da tastiera e da lettore di schermo.
  6. La paginazione degli articoli funziona (`/page/2/` e seguenti).
  7. Se il plugin è disattivato, il tema torna all'archivio normale senza errori.
  8. La pagina non diventa più lenta: l'indice è calcolato una volta e salvato in cache, e la cache si aggiorna quando si pubblica o si modifica un articolo o un gioco.
  9. I 4 header sono a 3.3.0, la suite di test passa al 100% e i 3 ZIP sono conformi.
- **Test/evidenze:**
  - Nuovi test in `audit_test_suite.php` per il calcolo dell'indice: sviluppatori multipli, nomi duplicati, esclusioni, articoli senza gioco, categoria vuota.
  - Verifica sintattica PHP 8.2 di tutti i file toccati.
  - Screenshot della pagina in tema chiaro e scuro, da computer e da telefono, su un sito di prova locale.
  - Rapporto di Marco (sicurezza), del DBA (numero di query e tempi) e dell'A11y Auditor.
  - Validazione dei dati strutturati (Rich Results Test) a cura di Giacomo.
  - Verifica dei pacchetti da parte di Francesca.
- **Rischi e blocchi:**
  - **Copertura IGDB:** i giochi piccoli non presenti su IGDB non generano lo studio. Il pannello "Controllo copertura" serve proprio a individuarli.
  - **Co-sviluppatori stranieri:** IGDB può indicare anche partner esteri, da gestire con l'elenco degli studi esclusi.
  - **Tailwind:** il CSS compilato del parent potrebbe non contenere le nuove classi usate nel child. Elena deve verificarlo e, se serve, aggiungere le regole a `ntn-journal-reloaded/style.css` senza ricompilare il parent.
  - **Prestazioni su categorie molto grandi:** il calcolo va limitato e messo in cache (controllo del DBA).
  - Nessun blocco al momento.
- **Stato:** `planned`
- **Decisione richiesta al CEO:** Approvare l'avvio dei lavori secondo questo piano. Confermare inoltre che nella v1 la cartina resta solo decorativa, senza i pallini rossi sulle città.

---

## Fasi e assegnazioni

| # | Fase | Chi | Cosa consegna |
|---|------|-----|---------------|
| 1 | Dati e impostazioni | Alessandro | Modulo `category-hub.php`: campi della categoria (con nonce e permessi), funzione che calcola l'indice degli studi con cache e relativa invalidazione, pannello "Controllo copertura" |
| 2 | Template e grafica | Elena | `category-hub.php` nel child, parti di template, cartina a punti caricata solo sulla Hub, filtro A–Z con miglioramento progressivo, verifica delle classi Tailwind |
| 3 | SEO | Giacomo | Schema CollectionPage + ItemList degli studi, controllo di titolo, canonical e paginazione |
| 4 | Prestazioni | DBA | Verifica di query e cache su categoria reale (~100 articoli) e su categoria grande |
| 5 | Accessibilità | A11y Auditor | Contrasti (rosso su nero), tastiera, `aria-pressed`, riduzione movimento |
| 6 | Test | Riccardo | Nuovi casi in `audit_test_suite.php` |
| 7 | Gate sicurezza | Marco | Sanitizzazione input, escaping output, nonce e permessi |
| 8 | Gate QA e release | Francesca | Versione 3.3.0 sui 4 header, ZIP vecchi in `archive/`, 3 nuovi ZIP con `package_release.ps1` |
| 9 | Handoff finale | Nicola → Adriano | Rapporto per il CEO con evidenze |

Le fasi 1 e 2 sono in sequenza, perché il template usa i dati del plugin. Le fasi 3, 4 e 5 possono procedere in parallelo dopo la 2. I gate 7 e 8 arrivano per ultimi e nessuno specialista approva il proprio lavoro.

## Note tecniche per gli specialisti

- **Da dove vengono gli sviluppatori:** `_ntn_game_developer` (term meta sui tag dei giochi, valori separati da virgola), con fallback sul post meta del CPT `videogioco`. Un tag è considerato "gioco" se ha `_ntn_igdb_game_id` o `_ntn_game_title`, come già fa `tag.php`.
- **Collegamento studio → tag:** riusare la stessa logica di `ntn_link_companies_to_tags()` (ricerca per nome, poi per slug).
- **Separazione dei ruoli (Regola 3):** dati, impostazioni e schema stanno nel plugin. Il tema legge solo tramite una funzione pubblica del plugin (es. `ntn_core_get_category_hub_data()`), protetta da `function_exists()`.
- **Instradamento del template:** filtro `category_template` nel child, attivo solo se la categoria ha la Hub accesa. In ogni altro caso si usa `archive.php`.
