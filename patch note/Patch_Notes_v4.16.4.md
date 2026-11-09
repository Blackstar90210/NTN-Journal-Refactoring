# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.16.4 (Apostrofi nei dati per Google, copertine del catalogo)

Due difetti trovati con la verifica di Q-Gin dopo l'installazione della 4.16.3.

### 🛠️ 1. Niente più codici HTML nei dati per Google (plugin)

Nei blocchi JSON-LD restavano apostrofi scritti `&#039;`, per esempio nella descrizione dell'autore («Un po&#039; geek») e in un passaggio di una recensione. Sono testi che The SEO Framework riceve già «protetti» per l'HTML. In JSON-LD il testo è testo semplice, quindi Google avrebbe mostrato il codice così com'è.

- Nuova funzione `ntn_core_schema_decode_entities()` in `inc/seo-extras.php`: decodifica `&#039;`, `&amp;`, `&quot;` e simili in ogni testo del grafo dei dati strutturati.
- Agisce con priorità 99 sul filtro `the_seo_framework_schema_graph_data`, cioè dopo tutti gli altri. Numeri e altri valori non cambiano, e il JSON lo scrive poi The SEO Framework con le sue regole, quindi virgolette e barre restano protette.
- Si somma alla correzione per iubenda della 4.15.4 (lettere accentate).

### 🖼️ 2. Copertine del catalogo: alta priorità solo alle prime 4 (parent)

La scheda del gioco dava «alta priorità» (`fetchpriority="high"`, caricamento immediato) a tutte le 20 copertine del catalogo, invece che alle sole prime 4 sopra la piega. Il motivo: la scheda leggeva `$wp_query` per sapere la posizione, ma l'elenco del catalogo usa un'altra ricerca, la cui posizione non è quella.

- `template-parts/content-game-card.php`: la posizione ora arriva da chi disegna l'elenco (`position` e `first_page`). Alta priorità solo alle prime 4 schede della **prima** pagina; le altre, e le pagine caricate dopo con lo scorrimento infinito, restano `loading="lazy"`.
- `archive-videogioco.php` (catalogo) e `inc/rest-catalog.php` (scorrimento infinito e filtri) passano la posizione.
- Con WordPress 6.0, che non passa gli argomenti alle parti di tema, tutte le copertine restano `lazy`.

### Ordine di installazione
Plugin, poi tema parent, poi child.
