# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.21.1 (indice dell'articolo corretto, blocco "Editoriali" in Home)

Due interventi chiesti dal CEO:
- un bug segnalato sul sito vero, con priorità;
- il blocco "// Approfondimenti" della Home, che porta agli editoriali e quindi si chiama "// Editoriali".

---

### 🐞 1. Bug: l'indice nella colonna mostrava i titoli di un altro articolo

- **Cosa succedeva**: sul sito vero l'articolo "AfterZero: The Last Drive è il nuovo videogioco di 3DClouds…" mostrava nella colonna l'indice di "Qual è il miglior gioco dei PAW PATROL da comprare?", il terzo articolo di "// Leggi anche".
- **Perché**: per i riquadri di "Leggi anche" (e dei Consigliati) WordPress prepara un estratto degli articoli collegati rielaborandone il testo. In quel momento la funzione dell'indice credeva di essere ancora nell'articolo principale e ne sostituiva l'indice con i titoli dell'articolo collegato. Succedeva soprattutto con gli articoli senza sottotitoli, come quello su AfterZero.
- **Correzione** (plugin, `inc/seo-extras.php`):
  - l'indice non si calcola durante la preparazione degli estratti;
  - l'indice di un articolo, una volta calcolato dal suo testo, non viene più sostituito.
- **Effetto**: gli articoli senza sottotitoli non hanno l'indice, come previsto. Gli altri hanno solo i propri titoli.

### 📰 2. Blocco "Editoriali" in Home

- **Titolo**: "// Approfondimenti" diventa **"// Editoriali"**.
- **Contenuto**: prima il blocco prendeva editoriali e speciali insieme, e su Local mostrava solo speciali. Ora mostra **solo gli editoriali**, scelta del CEO, come il link "Tutti →" che porta alla categoria Editoriali.
- Su Local il blocco ha 2 articoli, perché gli editoriali sono 2. Si riempie da solo con i prossimi.
- Gli speciali restano nella loro categoria e non compaiono più in questo blocco.
- Il mockup è allineato (Artifact aggiornato).

---

### ✅ 3. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su `front-page.php`, `inc/front-page-data.php` e `inc/seo-extras.php`.
- **Suite di collaudo**: **393 test su 393 superati**.
  - Nuova suite 55 con 2 controlli.
  - Aggiunto al banco di prova il sostituto di `doing_filter`.
- **Indice, prova sul sito Local**: su 25 articoli l'indice della colonna corrisponde in tutti ai titoli del loro testo (21 hanno l'indice). L'articolo su AfterZero non ne ha.
- **Home, prova sul sito Local**, solo in lettura:
  - il blocco si chiama "// Editoriali";
  - mostra i 2 editoriali;
  - "Tutti →" porta a /category/editoriali/;
  - nessun errore PHP.
- **Sincronizzazione 4 header (SemVer 3.21.1, PATCH)**. La memoria della Home si rinnova da sola con la nuova versione.
- **Pacchetti**: ZIP 3.21.0 spostati in `archive/`, creati i tre ZIP 3.21.1 con `package_release.ps1`.
