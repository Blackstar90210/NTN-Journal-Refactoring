# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.15.0 (Fase 10 del restyling: stile dell'editor)

Ultima fase del restyling. Mentre scrive, la redazione vede nell'editor di WordPress il testo dell'articolo come apparirà sul sito: stessi caratteri, colori e misure.

---

### ✍️ 1. Editor di WordPress (`editor-style.css`)

Il file, usato solo dentro l'editor, è stato riscritto prendendo le misure dal testo degli articoli pubblicati (`.ntn-prose` in `build/input.css` e le regole `.prose` e `.wp-block-*` di `style.css`):

- **Titolo dell'articolo**: Inter nero pesantissimo, in maiuscolo, con le righe strette della fascia di apertura.
- **Testo**: Inter a 18 pixel, interlinea 1,7, grigio scuro. Il grassetto è nero.
- **Capolettera** grande sul primo paragrafo.
- **Titoli**:
  - **H2** in maiuscolo con la riga sopra e il numero "01, 02…" in Mono grigio, come sul sito;
  - **H3** in maiuscolo;
  - **H4, H5 e H6** con la riga sotto.
- **Link** rossi e sottolineati.
- **Citazioni** in Mono, con il bordo rosso a sinistra, il fondo grigio chiarissimo e la fonte in maiuscolo.
- **Immagini** con la cornice da 1 pixel; **didascalie** in Mono grigio, centrate.
- **Elenchi, tabelle, codice e separatori** come sul sito, senza angoli arrotondati.
- L'editor resta sempre chiaro, anche per chi legge il sito con il tema scuro.

Il mockup non ha più etichette con "_": 22 punti corretti, compreso l'indice degli studi generato dallo script. L'Artifact è alla versione 20.

---

### ✅ 2. Quality Assurance, Test Suite e Packaging

- **Suite di collaudo**: **345 test su 345 superati**.
  - Nuova suite 44 con 2 controlli: caricamento dello stile, testo, capolettera, H2 numerati, link, citazioni e didascalie.
  - Il test 30.8 (nessun angolo arrotondato in `editor-style.css`) passa senza modifiche.
- **Prova sul sito Local**, nell'editor della recensione di Ace Combat 8:
  - titolo, capolettera, testo, H2 numerati, immagini e didascalie corrispondono all'articolo pubblicato;
  - la larghezza del testo è di 896 pixel, come in `theme.json`.
- **Sincronizzazione 4 header (SemVer 3.15.0, MINOR)**.
- **Pacchetti**: ZIP 3.14.1 spostati in `archive/`, creati i tre ZIP 3.15.0 con `package_release.ps1`.

### ⚠️ Note

- Con questa fase il piano del restyling (`docs/piano_restyling_estetico.md`, fasi 0–10) è completo.
- Restano aperti:
  - l'indice dell'articolo nella colonna laterale;
  - la dimensione del testo sul telefono (22 pixel contro i 18 del mockup);
  - l'eliminazione di `template-parts/author-bio.php`, che non è più usato.
