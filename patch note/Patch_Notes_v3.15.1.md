# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.15.1 (testo da telefono a 20 pixel; eliminato il vecchio box dell'autore)

Due interventi piccoli: la dimensione del testo degli articoli sul telefono e una pulizia del tema.

---

### 📱 0. Testo degli articoli sul telefono (`style.css`)

Sotto i 768 pixel il testo era a 22 pixel, mentre il mockup ne prevede 18. Il CEO ha scelto una via di mezzo, **20 pixel**. I titoli sono ridotti in proporzione:

| Elemento | Prima | Ora |
|---|---|---|
| Testo dell'articolo e riassunto della recensione | 22 px | **20 px** |
| H2 | 28 px | **24 px** |
| H3 | 25 px | **22 px** |
| H4 | 23 px | **21 px** |

Il sito da computer non cambia.

### 🧹 1. Eliminato `template-parts/author-bio.php`

- Era il vecchio box dell'autore in fondo agli articoli, con lo stile di prima del restyling.
- Dalla fase 4 (3.6.x) gli articoli usano la firma nella testata, `template-parts/author-panel.php`. Il vecchio file non era più richiamato da nessuna pagina: lo usavano solo i test 17.x.

### 🧪 2. Test della suite 17 aggiornati

- I controlli 17.1–17.5 ora provano la firma attuale, `author-panel.php`, con gli stessi dati di prova:
  - nome con gli spazi normali;
  - biografia, oppure il testo di riserva "Redattore per…";
  - link sicuro alla pagina dell'autore;
  - link social e sito web, aperti in sicurezza (`noopener noreferrer`);
  - ripiego su `href="#"` se il link dell'autore non è valido.
- Nuovo 17.6: verifica che il vecchio file non ci sia più.
- Aggiunti al banco di prova i sostituti di `count_user_posts` e `number_format_i18n`.

---

### ✅ 3. Quality Assurance, Test Suite e Packaging

- **Suite di collaudo**: **346 test su 346 superati**, senza avvisi PHP.
- **Prova sul sito Local**, sulla recensione di Ace Combat 8:
  - la pagina si apre con la firma dell'autore e il piè di pagina;
  - sul telefono il testo è a 20 pixel, gli H2 a 24 e il capolettera resta proporzionato, senza scorrimento orizzontale;
  - nessun nuovo errore nel registro PHP.
- **Sincronizzazione 4 header (SemVer 3.15.1, PATCH)**.
- **Pacchetti**: ZIP 3.15.0 spostati in `archive/`, creati i tre ZIP 3.15.1 con `package_release.ps1`.
