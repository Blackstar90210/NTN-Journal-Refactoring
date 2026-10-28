# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.20.0 (Hub di categoria e tag per Google)

Interventi decisi dal CEO dopo il controllo SEO di Hub e categorie su Local (punti 1, 2 e 3).

---

### 🔎 1. Cosa mancava

- **Hub di categoria** (Console, Mobile, Sviluppatori italiani e pagine dei marchi come Microsoft): nessuna descrizione per Google e nessuna immagine per le condivisioni.
- **Sitemap di The SEO Framework**: TSF non elenca le categorie, quindi le Hub di categoria non c'erano.
- **Pagine dei tag**, per esempio /tag/3dclouds/: indicizzabili ma senza descrizione.

### ✅ 2. Hub di categoria: descrizione e immagine

- **Descrizione per Google automatica**:
  - l'introduzione della Hub, se c'è;
  - altrimenti un testo costruito dai suoi dati. Esempi:
    - Console: "Notizie, recensioni e anteprime su Console: Xbox One, Xbox Series X|S, Nintendo Switch, Nintendo Switch 2 e altre 2 piattaforme…";
    - Microsoft: "Notizie, recensioni e anteprime su Microsoft: Xbox One, Xbox Series X|S…";
    - Sviluppatori italiani: "Gli studi di Sviluppatori Italiani di Videogiochi e i loro giochi: 3DClouds. Annunci, anteprime e recensioni…".
- Una descrizione scritta a mano, nella categoria o nel riquadro di TSF, ha sempre la precedenza.
- **Immagine per Facebook, X e WhatsApp**: quella dell'articolo in evidenza della Hub.

### 🗺️ 3. Sitemap

- Entrano nella sitemap di TSF le Hub di categoria e le pagine dei marchi delle Hub Console.
- Per ognuna la data dell'ultimo articolo.
- Le Hub senza articoli restano fuori.
- Le categorie delle singole piattaforme non ci sono, per evitare pagine povere. Google le trova comunque dai collegamenti.
- TSF tiene la sitemap in memoria: le nuove voci compaiono alla prossima rigenerazione, per esempio quando si pubblica o si aggiorna un articolo.

### 🏷️ 4. Pagine dei tag

- Descrizione automatica per i tag che non ne hanno una:
  - **studi**: "3DClouds: tutte le notizie, le anteprime e le recensioni di NTN Journal sullo studio e sui suoi giochi, tra cui AfterZero: The Last Drive e Formula Legends." I giochi vengono dallo sviluppatore IGDB, solo se il nome corrisponde davvero;
  - **altri tag**: "Tutti gli articoli di NTN Journal su …: notizie, anteprime e recensioni."
- I tag dei giochi collegati a IGDB non cambiano: portano alla Hub del gioco.

### 🚦 5. Pagine con pochi articoli fuori da Google

Proposta discussa con il CEO.

- **Due soglie**, modificabili in NTN Journal Funzioni → SEO, voce "Pagine con pochi articoli":
  - **10 articoli** per tag e categorie, comprese le Hub di categoria e le pagine dei marchi;
  - **3 articoli** per le Hub dei videogiochi, perché hanno già scheda, copertina e trailer.
- **Sotto la soglia** la pagina:
  - è in **noindex, follow**: Google non la mostra, ma segue i suoi collegamenti agli articoli;
  - è **fuori dalla sitemap**.
- **Raggiunta la soglia**, la pagina torna da sola.
- Con 0 la regola è spenta. Una pagina messa in noindex a mano resta in noindex.
- Le categorie contano anche gli articoli delle sottocategorie. Le Hub dei giochi contano gli articoli del tag del gioco.
- La **Dashboard** (casella SEO) e la scheda SEO mostrano quante pagine sono nascoste.
- Il **backup** include le due soglie.
- Nuovo modulo del plugin: `inc/seo-thin-pages.php`.
- **Su Local, con 10 e 3**, sono nascosti:
  - 607 tag;
  - 11 categorie, tra cui la Hub "Sviluppatori italiani", che ha un solo articolo;
  - 149 Hub dei giochi su 172.
- Restano visibili, per esempio, Console, Microsoft, Notizie e la Hub di Ace Combat 8.

---

### ✅ 6. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su `category-hub.php` e `hub-seo.php`.
- **Suite di collaudo**: **387 test su 387 superati**.
  - Nuova suite 53 con 4 controlli.
  - Aggiunto al banco di prova il sostituto di `get_terms`.
- **Prova sul sito Local**, solo in lettura:
  - descrizioni e immagini presenti su Console, Microsoft, Mobile e Sviluppatori italiani;
  - descrizione presente su /tag/3dclouds/;
  - nessun errore PHP.
- La sitemap non è stata rigenerata, per non svuotare la memoria di TSF.
- **Sincronizzazione 4 header (SemVer 3.20.0, MINOR)**.
- **Pacchetti**: ZIP 3.19.2 spostati in `archive/`, creati i tre ZIP 3.20.0 con `package_release.ps1`.

### ⚠️ Note

- Le categorie normali, per esempio Notizie, non hanno ancora una descrizione. Si può scrivere a mano nella categoria, oppure decidere un testo automatico in una prossima versione.
