# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.16.0 (fine dell'articolo: indice, Consigliati, tag, condivisione, Google, Leggi anche)

La parte dell'articolo che restava nel vecchio stile segue ora il mockup approvato dal CEO (`docs/mockup/ntn-mockup-completo.html`, versione 22, pagina Articolo).

---

### 🧭 1. Colonna laterale su tutti gli articoli (`single.php`)

- Da 1280 pixel in su la colonna c'è su ogni articolo, in quest'ordine:
  - "// Indice";
  - "// Scheda del videogioco", solo se l'articolo ha un gioco o una recensione;
  - "// Condividi";
  - "// Fonti preferite su Google".
- **Colonna "intelligente"**, senza barra di scorrimento (scelta del CEO):
  - se ci sta nello schermo resta ferma in alto;
  - se è più alta, scorre con la pagina finché compare il suo fondo, poi si ferma;
  - tornando su la segue all'indietro.
  - La posizione la calcola `assets/js/main.js`. Senza JavaScript la colonna scorre semplicemente con la pagina.
- **Da telefono e schermi stretti** l'indice sta in cima al testo; condivisione e Google stanno sotto l'articolo, dopo i tag.

### 📑 2. Indice dei contenuti (plugin `inc/seo-extras.php`, tema)

- È un pannello "// Indice" con il numero di sezioni. Le voci sono numerate 01, 02… come i sottotitoli dell'articolo; i sottotitoli di terzo livello compaiono rientrati, senza numero.
- La sezione che si sta leggendo è in rosso e cambia mentre si scorre (`aria-current="location"` per i lettori di schermo).
- Il plugin crea l'indice una sola volta e lo mette a disposizione del tema per la colonna, con le nuove funzioni `ntn_core_get_toc_items()` e `ntn_core_toc_markup()`. Gli ID dei titoli e la gestione dei titoli doppi non cambiano, quindi i link già condivisi alle sezioni continuano a funzionare.

### 💡 3. Box "// Consigliati" (`inc/related-posts.php`)

- **Nuovo aspetto**: miniatura a sinistra, etichetta "// Consigliati", titolo e "Leggi qui →" in rosso. Tutto il box è un unico link.
- **Nuova regola di comparsa** (scelta del CEO):
  - sotto le 150 parole: nessun box;
  - fino a 300: uno, a metà;
  - fino a 500: due, al 25% e al 75%;
  - oltre 500: tre, al 25%, 50% e 75%.
- Ogni box propone un articolo diverso: prima quelli con gli stessi tag, poi quelli della stessa categoria.
- Il box va dopo il paragrafo più vicino al punto indicato e non entra mai dentro citazioni, figure, elenchi, tabelle o altri blocchi. Le parole si contano senza immagini, gallerie, contenuti incorporati e didascalie.
- **Prova su Local**:

  | Articolo | Parole | Box |
  |---|---|---|
  | Hello world | 15 | nessuno |
  | Undermire | 280 | uno, al 56% |
  | Toxic Crusaders | 302 | due, al 15% e al 77% |
  | Nyra and the Garden of Moros | 408 | due, al 29% e al 67% |
  | Recensione di Ace Combat 8 | 1.361 | tre, al 23%, 53% e 74% |

  Gli scarti rispetto alle percentuali dipendono dalla lunghezza dei paragrafi.

### 🏷️ 4. Tag dell'articolo (`template-parts/article-tags.php`, nuovo)

- In fondo al testo: il gioco collegato in rosso con il pallino, gli altri tag come "#Nome".

### 📤 5. "// Condividi" (`inc/share-buttons.php`)

- Cinque celle con le sigle in Mono: **X, BSKY (Bluesky), FB, WA, Link**. Niente Mastodon (decisione del CEO).
- Tolti LinkedIn, Telegram, Reddit, Pinterest ed Email.
- "Link" copia l'indirizzo dell'articolo e per un attimo mostra "OK". Se il browser non permette la copia, usa un metodo di riserva.

### 🔎 6. "// Fonti preferite su Google" (`inc/social-cta.php`)

- Al posto del box "// GOOGLE FEED": pannello con la matrice di punti che sfuma a destra, testo breve e pulsante squadrato "Aggiungi su Google ↗".
- Il link a Google resta quello di prima (preferenze delle fonti, senza "www.").

### 📰 7. "// Leggi anche" (`inc/related-posts.php`)

- Al posto di "// Articoli correlati": tre celle con immagine, gioco o categoria, data e titolo, come le schede della Home.
- Non ripete gli articoli già proposti nei "Consigliati".

### 🧹 8. Altro

- La scheda del gioco nella colonna si intitola "// Scheda del videogioco".
- Tolte da `style.css` le regole dei vecchi box "Consigliati" e "Google feed".

---

### ✅ 9. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 sui file modificati; `node --check` su `main.js`.
- **Suite di collaudo**: **351 test su 351 superati**, senza avvisi PHP.
  - Aggiornati i test 29.4, 29.7, 29.11, 29.12, 29.15, 29.21 e 34.8, che controllavano i vecchi box.
  - Nuovo 29.12b: punti sicuri e conteggio delle parole.
  - Nuova suite 45 con 4 controlli: indice, ordine della colonna, condivisione e tag con "Leggi anche".
- **Prova sul sito Local**:
  - recensione di Ace Combat 8 e articolo su Nyra, da computer (chiaro e scuro) e da telefono;
  - colonna intelligente provata scendendo e risalendo, nessuna barra di scorrimento;
  - "Copia link" funzionante;
  - nessuno scorrimento orizzontale e nessun nuovo errore nel registro PHP.
- **Sincronizzazione 4 header (SemVer 3.16.0, MINOR)**.
- **Pacchetti**: ZIP 3.15.1 spostati in `archive/`, creati i tre ZIP 3.16.0 con `package_release.ps1`.

### ⚠️ Note

- Il box "Seguici sui social" (`ntn_journal_social_cta_box`) non è stato ridisegnato: compare solo se nel Personalizza sono inseriti i link ai profili, e su Local non lo sono.
- Nelle recensioni lunghe, con la scheda del videogioco, l'indice esce di vista mentre si scende: è il comportamento scelto per evitare la barra di scorrimento.
