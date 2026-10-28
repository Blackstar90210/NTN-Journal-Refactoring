# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.7.0 (Fase 5 del restyling: recensione)

Il box della recensione, la scheda del gioco nella colonna laterale e la scheda rapida da telefono seguono ora il mockup approvato (`docs/mockup/ntn-mockup-completo.html`, pagina Recensione). Tutte le funzioni e tutti i dati di prima restano.

---

### ⭐ 1. Box della recensione (`template-parts/review-original-box.php`)

- **Striscia dell'origine** ("// Origine del videogioco") in cima, come prima.
- **Valutazione sulla fascia a matrice di punti** che segue il tema: il giudizio (Bocciato, Consigliato con riserva, Consigliato, Must Play) è la scritta più grande del box e porta sempre al suo tag, con lo stesso ripiego di prima se il tag non esiste.
- **I cinque LED** diventano quadrati veri e più grandi, con la stessa regola di oggi: accesi pieni, l'ultimo acceso rosso e luminoso, spenti grigi. Accanto c'è il **voto in numero** ("4 / 5 · Consigliato"); il lettore di schermo annuncia "Voto 4 su 5".
- **Pro e contro senza il verde**: il "+" è nero o bianco, il "−" è rosso. Accanto all'intestazione c'è il loro numero.
- **Il verdetto** resta in fondo; nella Hub del gioco continua a nascondersi (`hide_verdict`).

### 🎮 2. Scheda del gioco nella colonna laterale (`template-parts/review-sidebar-desktop.php`)

- Cornice da 1 pixel con intestazione "// Scheda gioco · IGDB".
- **Copertina intera, senza il velo scuro** di prima.
- Nome del gioco con link alla sua Hub.
- **Dati tecnici a celle**: sviluppatore ed editore con i link agli studi, uscita (gg/mm/aaaa), genere, motore grafico con link, piattaforme e origine della copia.
- **Valutazione finale** con il giudizio e i cinque LED, poi il **pulsante "Vedi su IGDB.com"** a tutta larghezza (`noopener noreferrer nofollow`, come prima).

### 📱 3. Scheda rapida da telefono (`template-parts/review-header-mobile.php`)

- Copertina, nome del gioco, piattaforme e uscita, e nelle recensioni anche il **giudizio con i cinque LED**.
- **Correzione**: prima la scheda compariva anche negli articoli senza gioco, con il titolo dell'articolo al posto del gioco. Ora compare solo dove c'è un gioco o un voto.

### ✍️ 3b. Nome dell'autore una sola volta (`single.php`)

Su segnalazione del CEO: la riga dei dati diceva "by Nome" e subito sotto la firma ripeteva il nome. Ora la riga dei dati contiene solo gioco collegato, data, tempo di lettura e aggiornamento. L'autore compare una volta, nella firma, con foto e link alla sua pagina. Stesso cambiamento nel mockup.

### 🎨 4. CSS (`build/input.css`)

Nuovi componenti: `.ntn-rbox-*`, `.ntn-verdict-word`, `.ntn-scorebox`, `.ntn-leds--xl`, `.ntn-gcard-*`, `.ntn-igdb-btn`, `.ntn-rmob-*`. I LED usano gli stessi componenti della Home (`.ntn-leds`, `.ntn-led`), quindi le recensioni hanno lo stesso aspetto in Home e nell'articolo.

---

### ✅ 5. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: tutti i file PHP di temi e plugin, nessun errore.
- **Suite di collaudo**: **298 test su 298 superati**. I test 13.4–13.10 (piattaforme, data di uscita, generi, pulsante IGDB, ripiego del link al giudizio) e 28.11–28.14 passano con i nuovi modelli senza modifiche. La nuova suite 35 ha 5 controlli: LED e voto accessibile, pro e contro senza verde, funzioni conservate, scheda del gioco, scheda da telefono.
- **Prova sul sito Local**: recensione di Ace Combat 8 da computer (chiaro e scuro) e da telefono, senza scorrimento orizzontale. Pagine principali e pagina con commenti rispondono 200 e il registro PHP non ha voci nuove.
- **Gate di sicurezza**: tutti i dati passano da `esc_html`, `esc_url`, `esc_attr` o `wp_kses_post`. I link a studi e motori sono generati dalle funzioni di prima, già sanificate.
- **Sincronizzazione 4 header (SemVer 3.7.0, MINOR)**.
- **Pacchetti**: ZIP 3.6.1 spostati in `archive/`, creati i tre ZIP 3.7.0 con `package_release.ps1`.

### ⚠️ Note

- Sul sito di prova il catalogo dei giochi è vuoto, quindi non si è potuta vedere la Hub di un gioco, dove il box compare senza verdetto. Il codice è lo stesso del box della recensione ed è coperto dal test 35.3.
