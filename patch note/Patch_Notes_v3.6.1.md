# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.6.1 (Autore nella testata dell'articolo)

Nella 3.6.0 l'autore era una scheda fissa nella colonna laterale (variante B). Messa insieme alla scheda del gioco e all'indice, la colonna sembrava un insieme di blocchi senza ordine, e scorrendo la scheda dell'autore si sovrapponeva a quella del gioco. Il CEO ha scelto quindi la variante A del mockup: la firma nella testata.

---

### ✍️ 1. Firma dell'autore nella testata (`template-parts/author-panel.php`, `single.php`)

- La firma sta **dentro la fascia di apertura, subito sotto la riga dei dati e prima della foto**. È visibile appena si apre l'articolo, da computer e da telefono.
- Contiene foto (o iniziali su matrice di punti), nome con link alla pagina dell'autore, "Firma di Q-Gin · dal ANNO", una riga di biografia, numero di articoli e di recensioni, link social e "Tutti i suoi articoli". I numeri restano in cache per un'ora.
- Segue il tema: testi scuri sulla fascia chiara, testi chiari su quella nera.

### 🧭 2. Colonna laterale più ordinata

- La colonna laterale torna a comparire **solo nelle recensioni e negli articoli legati a un gioco**, con la scheda di oggi. Gli altri articoli hanno il testo centrato, senza colonna vuota.
- La scheda del gioco resta ferma durante la lettura ed è l'unico elemento fisso della colonna, quindi non si sovrappone più a nulla.
- La posizione dell'indice (nel mockup in cima alla colonna, sopra la scheda del gioco) sarà affrontata più avanti, come deciso dal CEO. Oggi resta nel testo, perché lo genera il plugin.

---

### ✅ 3. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: tutti i file PHP di temi e plugin, nessun errore.
- **Suite di collaudo**: **293 test su 293 superati**. Aggiornati i test 34.7–34.9: firma nella testata tra la riga dei dati e la foto, una sola volta; colonna solo con gioco o recensione e un solo elemento fisso; escaping, link sicuri e cache dei dati dell'autore.
- **Prova sul sito Local**: recensione da computer (la scheda del gioco resta ferma a 24 px dall'alto mentre si scorre) e articolo da telefono in tema scuro, senza scorrimento orizzontale e senza nuovi errori nel registro PHP.
- **Sincronizzazione 4 header (SemVer 3.6.1, PATCH)**.
- **Pacchetti**: ZIP 3.6.0 spostati in `archive/`, creati i tre ZIP 3.6.1 con `package_release.ps1`.
- **Mockup** (`docs/mockup/ntn-mockup-completo.html`): variante A indicata come scelta, indice in cima alla colonna, sopra la scheda del gioco.
