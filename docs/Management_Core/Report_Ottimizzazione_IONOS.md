# Report Strategico: Ottimizzazioni Infrastrutturali Hosting IONOS
**Progetto:** Q-Gin / NTN Journal  
**Redatto da:** Team Tecnico (Management Core)

Questo documento illustra le ottimizzazioni lato server consigliate specificamente per l'ambiente di hosting condiviso/managed di IONOS. L'obiettivo è massimizzare le performance del sito, ridurre i tempi di caricamento (TTFB) e garantire stabilità in caso di picchi di traffico o operazioni intensive.

Per ciascun intervento tecnico, sono elencate le ripercussioni positive attese e gli eventuali rischi o aspetti a cui prestare attenzione.

---

## 1. Attivazione OPcache e Aumento Memory Limit (`.user.ini`)
*L'inserimento di un file di configurazione PHP (`.user.ini`) nella cartella principale del sito per gestire direttamente il motore PHP (consigliato l'uso di PHP 8.2 o 8.3 dal pannello IONOS).*

* **Cosa fa:** Aumenta la memoria disponibile per i processi PHP (es. `memory_limit` a 512M) e attiva OPcache, un sistema che mantiene in memoria (RAM) il codice PHP già compilato, evitando di doverlo rileggere dal disco a ogni visita.
* **Ripercussioni Positive (🟢):** 
  * Velocizza l'esecuzione generale del sito del 30-50%.
  * Riduce drasticamente il consumo di CPU sul server condiviso IONOS.
  * Previene errori bloccanti ("Fatal Memory Exhausted") durante operazioni pesanti come il backup, importazioni massive (API IGDB) o l'uso dell'editor a blocchi.
* **Ripercussioni Negative (🔴):** 
  * A seconda di come viene configurata la frequenza di aggiornamento della cache (`opcache.revalidate_freq`), le modifiche apportate ai file di codice sorgente potrebbero non essere immediatamente visibili (richiedendo fino a 60 secondi di attesa).
* **Verdetto / Azione:** **FONDAMENTALE.** È la base per garantire la stabilità di un ecosistema WordPress moderno su IONOS.

---

## 2. Sostituzione di WP-Cron con un Cronjob Reale IONOS
*WordPress esegue nativamente i suoi processi in background (pubblicazione articoli programmati, check aggiornamenti, ecc.) sfruttando il traffico dei visitatori. Disabiliteremo questo sistema via codice e imposteremo un "Cron Job" vero e proprio nel pannello IONOS.*

* **Cosa fa:** Rimuove il carico di elaborazione background dalle spalle degli utenti casuali, demandandolo a un automatismo del server che si attiva a orari regolari (es. ogni 15 minuti).
* **Ripercussioni Positive (🟢):** 
  * Risolve in modo definitivo il problema dei tempi di risposta instabili (il TTFB ballerino). I visitatori non avranno più improvvisi ritardi di caricamento causati da task eseguiti a loro insaputa.
* **Ripercussioni Negative (🔴):** 
  * Se ci si dimentica di impostare o attivare il Cron Job nel pannello IONOS (o se viene configurato male), i task programmati (come la pubblicazione di articoli schedulati) smetteranno completamente di funzionare.
* **Verdetto / Azione:** **ALTAMENTE RACCOMANDATO.** Richiede solo 5 minuti di configurazione nel pannello di controllo IONOS.

---

## 3. Hardening e Sicurezza: Blocco `xmlrpc.php` via `.htaccess`
*Aggiunta di regole restrittive nel file `.htaccess` di Apache per bloccare vecchie porte di accesso di WordPress.*

* **Cosa fa:** Nega totalmente l'accesso al file `xmlrpc.php`, storicamente usato da vecchie app o servizi per comunicare con WordPress.
* **Ripercussioni Positive (🟢):** 
  * Protegge in modo assoluto l'infrastruttura IONOS dai frequentissimi attacchi automatizzati "brute-force" botnet. Questi attacchi tendono a saturare i processi (worker) PHP dell'hosting condiviso, causando il collasso temporaneo del sito (Errori 503 Service Unavailable).
* **Ripercussioni Negative (🔴):** 
  * Impedisce l'utilizzo dell'app mobile "storica" di WordPress e interrompe alcune integrazioni legacy di plugin terzi (come Jetpack, sebbene le versioni recenti utilizzino le REST API moderne).
* **Verdetto / Azione:** **MOLTO UTILE.** I benefici in termini di stabilità e uptime compensano ampiamente le controindicazioni.

---

## 4. Caching Browser e Compressione GZIP/Brotli (`.htaccess`)
*Configurazione del file `.htaccess` per dare istruzioni ai browser su come gestire e comprimere i file.*

* **Cosa fa:** Indica al browser dell'utente di scaricare in formato compresso (risparmiando dati) e di salvare nella memoria del dispositivo i file che non cambiano mai (font, immagini, file CSS).
* **Ripercussioni Positive (🟢):** 
  * Incremento drastico dei punteggi Core Web Vitals e PageSpeed di Google.
  * Per i visitatori di ritorno (utenti affezionati), la navigazione tra le pagine diventa istantanea.
  * Si riduce la banda consumata sul server IONOS.
* **Ripercussioni Negative (🔴):** 
  * Nessuna limitazione di rilievo. L'unico accorgimento per i file CSS/JS è l'uso del "versioning" (es. inserire un numero di versione nel nome o URL del file) quando si apportano modifiche di design, per "forzare" il browser degli utenti a scaricare la nuova versione grafica.
* **Verdetto / Azione:** **FONDAMENTALE.** Operazione standard senza rischi, indispensabile per una User Experience di alto livello.
