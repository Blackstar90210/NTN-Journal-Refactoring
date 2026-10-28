# Piano di lavoro — Aggiornamento estetico di tutto il sito

> **Redatto da:** Nicola (Division Lead NTN) per Adriano (COO)
> **Data:** 2026-10-17
> **Riferimento visivo:** anteprima "Hub Sviluppatori Italiani" (https://claude.ai/artifact/3j76WkKQbXy5AzigHYy8Be)
> **Piano collegato:** `docs/piano_hub_categoria.md` (versione 3.3.0)
> **Stato:** `planned`, in attesa delle decisioni del CEO (sezione finale)

---

## 1. Da dove nasce

L'anteprima della Hub non introduce uno stile nuovo. Prende il pezzo più riuscito del tema attuale, l'intestazione nera delle Hub dei videogiochi (`template-parts/hub-header.php`), e lo applica in modo coerente a tutta la pagina. Il resto del sito usa invece la stessa filosofia in modo meno rigoroso.

L'obiettivo è portare tutto il sito allo stesso livello di coerenza dell'anteprima, senza cambiare identità: restano nero, bianco e rosso, Inter e JetBrains Mono, i contrassegni `//`, il tema chiaro e quello scuro.

## 2. Le differenze tra il sito di oggi e l'anteprima

| Aspetto | Oggi | Nell'anteprima |
|---|---|---|
| **Titoli** | Grassetto forte, dimensioni contenute | Grassetto massimo, molto grandi, righe strette: il titolo "pesa" come un manifesto |
| **Contrasto tra caratteri** | Inter e Mono mescolati senza una regola fissa | Regola chiara: Inter per titoli e testo, Mono solo per etichette, date, numeri e contatori |
| **Schede degli articoli** | Riquadri separati, con ombra, margini interni e immagine che si ingrandisce al passaggio del mouse | Griglia "a celle" con linee condivise, nessuna ombra, nessuno zoom: il cambio di colore del titolo basta |
| **Elementi estranei a Nothing OS** | 96 punti nel codice con angoli arrotondati, ombre, sfumature, zoom e filtri sulle foto (26 file) | Angoli vivi, linee da 1 pixel, nessuna sfumatura |
| **Momento nero** | Solo nelle Hub dei giochi | Ogni pagina importante si apre con una fascia nera a matrice di punti, anche nel tema chiaro |
| **Numeri** | Sparsi nel testo | Contatori in evidenza (studi, giochi, articoli, data di aggiornamento) |
| **Liste** | Quasi sempre schede con immagine | Anche liste compatte a righe (data, titolo, minuti), più leggibili e veloci |
| **Intestazioni di sezione** | Ogni pagina ha la sua variante (verticale, orizzontale, con o senza linea) | Una sola: etichetta Mono grigia a sinistra, nota a destra, linea sotto |
| **Colori** | Scritti a mano in ogni file, con molte forzature (`!important`) | Una sola tavolozza definita in un punto: cambiarla aggiorna tutto il sito |

## 3. Le regole del nuovo sistema

1. **Tre colori e basta.** Nero, bianco, rosso. I grigi servono solo a gerarchia e linee. Il rosso è un segnale (studio, link, "live", voto), mai una decorazione.
2. **Due caratteri con ruoli fissi.** Inter per leggere, JetBrains Mono per catalogare.
3. **Una linea da 1 pixel al posto di ombre e riquadri.** Le sezioni si separano con le linee, non con lo spazio colorato.
4. **La matrice di punti è la firma.** Compare nelle fasce nere e nelle copertine mancanti, non sopra le foto.
5. **Le foto non si toccano.** Niente filtri di luminosità o contrasto, niente zoom: le foto degli articoli restano come le carica la redazione, dentro una cornice a 1 pixel.
6. **Il movimento è raro.** Il pallino rosso pulsante si usa solo per "live" e per gli studi o i giochi seguiti. Chi ha attivato "riduci movimento" sul dispositivo non lo vede pulsare.
7. **Leggibilità prima dello stile.** Il grigio chiaro dell'anteprima (`#a1a1aa`) su fondo bianco è troppo debole per i testi piccoli: nel tema chiaro si usa un grigio più scuro (`#71717a`). Il rosso scritto su nero usa la tonalità più chiara (`#ef4444`).

## 4. Fasi e pagine

Ogni fase segue lo stesso ciclo: **anteprima → approvazione del CEO → sviluppo → controlli → pacchetti**. Nessuna fase parte senza che Luca abbia visto e approvato la sua anteprima. Ogni fase è un rilascio a sé (avanzamento MINOR), così il sito migliora un pezzo alla volta e si può tornare indietro su una singola fase.

| Fase | Pagine e parti | Cosa cambia | Peso |
|---|---|---|---|
| **0. Pilota: Hub di categoria** | Hub "Sviluppatori italiani" | Già pianificata in `piano_hub_categoria.md`. Diventa la prima pagina costruita con il nuovo sistema e fa da banco di prova | Medio |
| **1. Fondamenta** | Nessuna pagina in particolare: tutto il sito | Tavolozza unica per chiaro e scuro, scala dei titoli, componenti comuni (etichette, intestazione di sezione, griglia a celle, riga di lista, copertina a punti, contatori, paginazione). Rimozione di angoli arrotondati, ombre, sfumature e zoom | Medio |
| **2. Cornice del sito** | Testata, menu, ricerca, barra delle ultime notizie, piè di pagina | Testata più asciutta come nell'anteprima, menu in Mono, voce attiva evidenziata, pulsanti squadrati | Medio |
| **3. Home** | `front-page.php` | Apertura nera con il titolo principale; sezione "In evidenza" (una grande e tre piccole) al posto del carosello; ultime notizie come lista compatta; blocchi per sezione con la stessa intestazione | Alto |
| **4. Articolo** | Articolo singolo, scheda autore, articoli correlati, condivisione, commenti, invito ai social, indice dei contenuti | Titolo grande, riga dei dati in Mono, gioco collegato mostrato come etichetta rossa con pallino, citazioni e immagini con le nuove regole | Alto |
| **5. Recensione** | Riquadro del voto (computer e telefono), box del gioco originale | Voto come contatore grande in Mono, scheda tecnica a celle | Medio |
| **6. Archivi** | Categoria, tag, Hub dei giochi, motore di gioco, ricerca, archivio per data | Apertura nera con contatori per tutte le Hub; griglia a celle e lista compatta per gli archivi normali | Medio |
| **7. Catalogo videogiochi** | Archivio del catalogo, filtri, scheda del videogioco, scheda nel catalogo (tema child) | Filtri come pulsanti squadrati (come l'indice A–Z dell'anteprima), schede a celle | Medio |
| **8. Comunicati stampa** | Archivio e comunicato singolo | Lista compatta per data, fonte in evidenza | Basso |
| **9. Pagine di servizio** | Pagina autore (in vista della pagina Redazione), pagine statiche, 404, nessun risultato | Stesso linguaggio, con la matrice di punti nella 404 | Basso |
| **10. Editor** | Aspetto dell'editor di WordPress (`editor-style.css`) | La redazione vede in scrittura gli stessi caratteri e colori del sito | Basso |

**Ordine consigliato:** prima ciò che compare su ogni pagina o che vedono più lettori (fasi 1–4), poi il resto. Le fasi 5–10 possono cambiare ordine secondo le priorità di Luca.

## 5. Chi fa cosa

| Ruolo | Compito |
|---|---|
| Nicola | Coordina le fasi, prepara le anteprime, redige l'handoff di ogni fase |
| Mario | Decide dove vivono i modelli rinnovati (vedi rischio 1) e approva il sistema della fase 1 |
| Elena | Realizza componenti e pagine |
| A11y Auditor | Contrasti, tastiera e lettori di schermo a ogni fase |
| Giacomo | Controlla che titoli, struttura e dati strutturati restino invariati |
| DBA | Verifica che le pagine non rallentino (soprattutto la home) |
| Riccardo | Aggiorna i test |
| Marco | Gate di sicurezza |
| Francesca | Versione, pacchetti e controllo finale di ogni rilascio |

## 6. Rischi

1. **Dove si modificano i modelli.** Quasi tutte le pagine stanno nel tema parent, mentre la Regola 3 assegna la grafica al child. Le strade sono due: rinnovare i modelli nel parent (un solo posto, più semplice da mantenere) oppure copiarli nel child (rispetta la regola alla lettera, ma raddoppia i file). Decide Mario con il CEO prima della fase 1.
2. **Tailwind.** Il CSS del parent è già compilato. Le nuove regole vanno scritte come componenti con la tavolozza unica, così non serve ricompilare a ogni fase.
3. **Foto vere.** L'anteprima usava copertine segnaposto. Ogni anteprima delle fasi 3–7 deve usare foto reali del sito, per verificare che il sistema regga con immagini colorate e di qualità diversa.
4. **Carosello della home.** Sostituirlo con una sezione fissa cambia il modo in cui sono presentati gli articoli in evidenza. È una scelta del CEO (vedi decisioni).
5. **Abitudini dei lettori.** Un cambio a fasi evita lo stacco netto, ma per qualche settimana alcune pagine saranno nel vecchio stile e altre nel nuovo. Le fasi 1 e 2 riducono la differenza perché uniformano subito colori, linee e cornice.
6. **Prestazioni.** La cartina e la matrice di punti si caricano solo dove servono. Il DBA controlla la home prima del rilascio.

## 7. Decisioni richieste al CEO

1. **Pilota:** costruiamo prima la Hub (fase 0, già pianificata) e ricaviamo da lì il sistema (fase 1), oppure facciamo prima le fondamenta e poi la Hub? *Consiglio: prima la Hub, perché la sua estetica è già approvata.*
2. **Carosello:** in home sostituiamo il carosello con la sezione fissa "una grande e tre piccole"? *Consiglio: sì, perché mostra tutti gli articoli in evidenza subito, senza aspettare che scorrano.*
3. **Fascia nera:** la vogliamo anche nel tema chiaro, come nell'anteprima, su home e Hub? *Consiglio: sì, è il tratto più riconoscibile.*
4. **Ordine delle fasi 5–10:** va bene quello proposto?
5. **Prossimo passo:** dopo l'approvazione di questo piano, Nicola prepara l'anteprima della home e dell'articolo con foto reali.
