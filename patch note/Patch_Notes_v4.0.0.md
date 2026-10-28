# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.0.0 (Redirect per cartella e indirizzi che non esistono)

Versione principale decisa dal CEO: chiude il ciclo 3.x, dopo che il tema è stato rivisto da cima a fondo nelle versioni precedenti. Da questa versione l'autore dei due temi e del plugin è **Luca Maiolini & Divisione_WP**.

Funzione richiesta dal CEO dopo l'analisi dell'indicizzazione di q-gin.info in Search Console: 590 pagine in errore 404, quasi tutte vecchi indirizzi `/category/…` e `/tag/…` rimasti senza deviazione dopo il passaggio a `/sezione/` e `/argomento/`. Alcuni finivano anche su articoli a caso o su file immagine, perché WordPress provava a indovinare la pagina.

---

### 🔀 1. Redirect per cartella (NTN Journal Funzioni → Redirect)

- **Nuova sezione** sotto i redirect singoli: ogni indirizzo che inizia con la cartella di origine viene portato nella cartella di destinazione, con il resto dell'indirizzo invariato. Esempio: con `/category/` → `/sezione/`, l'indirizzo `/category/console/microsoft/` diventa `/sezione/console/microsoft/`. Restano invariati anche i parametri dopo il `?`.
- **Suggerimento con pulsante "Aggiungi"**: se le categorie o i tag del sito usano cartelle diverse da quelle di WordPress (come `/sezione/` e `/argomento/` su Q-Gin), la sezione propone le regole per i vecchi `/category/` e `/tag/`. Con un clic vengono aggiunte, e il suggerimento sparisce.
- **Ogni regola** ha il contatore delle visite e la data dell'ultimo utilizzo, come i redirect singoli. Si può disattivare senza cancellarla.
- **Precedenza**: i redirect singoli scattano prima delle regole per cartella. Tra due regole vince la cartella più lunga, per esempio `/author/blackstar90210/` prima di `/author/`.
- **Controlli al salvataggio**: niente regole su tutto il sito (`/`) o sulle cartelle di sistema (`/wp-…`), niente indirizzi di altri siti e niente regole che si rimandano all'infinito (destinazione dentro l'origine, oppure due regole che si rimandano a vicenda).
- Deviazione permanente (301).

### 📄 2. Indirizzi che non esistono

- **Pagine numerate oltre la fine** (casella attiva di serie): se un archivio esiste ma la sua pagina numerata no (es. `/sezione/notizie/page/999/`), il visitatore va alla prima pagina dell'archivio invece che all'errore. Vale per categorie, tag, autori, tassonomie del tema, pagine con elenco e Home. Deviazione temporanea (302), perché con nuovi articoli quella pagina può tornare a esistere.
- **Non tirare a indovinare** (interruttore spento di serie): quando un indirizzo non esiste, WordPress porta il visitatore sulla pagina dal nome più simile. Acceso, mostra invece la pagina "non trovato" (errore 404). Non tocca gli articoli veri né le regole per cartella.

### 💾 3. Backup

- Le regole per cartella e i due interruttori entrano nel backup di NTN Core. Al ripristino passano gli stessi controlli della scheda.
- **Correzione**: ripristinando un backup, le due soglie "articoli minimi" della scheda SEO (tag e categorie, Hub dei videogiochi) diventavano "no", cioè 0. La regola che tiene fuori da Google le pagine con pochi articoli si spegneva senza avvisare. Ora tornano come numeri da 0 a 100, come nella scheda SEO. Un valore non numerico lascia la soglia com'è.

### ✍️ 4. Autore

- Campo "Autore" dei due temi (prima "Luca Maiolini & Antigravity AI") e del plugin NTN Core (prima "NTN Journal Dev Team (Alessandro & Mario)") cambiato in **Luca Maiolini & Divisione_WP**.

---

### ✅ 5. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.2 e PHP 8.5 su `redirect-manager.php` e `backup-restore.php`.
- **Prove sul sito Local** (impostato con `/sezione/` e `/argomento/` come q-gin.info):
  - `/category/console/microsoft/` → `/sezione/console/microsoft/`
  - `/tag/electronic-arts/` → `/argomento/electronic-arts/` (prima finiva su un articolo a caso)
  - `/author/blackstar90210/page/2/` → `/author/valerio-sintesi/page/2/`
  - `/category/console/page/999/` → `/sezione/console/`
  - `/categoryfoo/` resta un 404
  - con l'interruttore acceso, un indirizzo incompleto dà 404 e gli articoli veri si aprono normalmente
  - pulsanti "Aggiungi", "Disattiva/Attiva", "Salva opzioni" provati dal browser; casi sbagliati rifiutati con un messaggio chiaro; richieste senza nonce valido ignorate.
- **Audit di sicurezza (Marco)**: superato. Permessi, nonce, escaping, deviazioni solo verso il dominio del sito.
- **Suite di collaudo**: **417 test su 417 superati**, con la nuova suite 62 (compreso il ripristino delle soglie).
- **Sincronizzazione 4 header (SemVer 4.0.0, MAJOR per decisione del CEO)**.
- **Pacchetti**: ZIP precedenti spostati in `archive/`, creati i tre ZIP 4.0.0 con `package_release.ps1`. La 3.23.0 preparata durante il lavoro non è mai stata rilasciata: i suoi ZIP restano in `archive/` solo come traccia.
