# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.28.0 (Passo 4, seconda schermata: il riquadro "Scansiona i tag senza gioco")

Seguito del passo 4 del piano "NTN Journal e gli standard WordPress": il codice JavaScript esce dalle pagine PHP e va in file suoi, caricati con `wp_enqueue_script`. Dopo la scheda IGDB (4.27.0), tocca al riquadro **Scansiona i tag senza gioco** che sta dentro la stessa scheda (`views/igdb-tag-scanner.php`).

Il pannello si usa come prima: scansione, conferma riga per riga o in massa, "Non è un gioco", ricerca di un altro gioco e "Rimetti in lista i tag esclusi" fanno le stesse cose.

### 📦 Cosa cambia
- Il codice sta ora in `assets/js/admin-igdb-tag-scanner.js` (versione del plugin nell'indirizzo, in fondo alla pagina). La vista PHP tiene solo il riquadro HTML.
- I due codici di sicurezza (nonce) arrivano con `wp_localize_script` nell'oggetto `ntnIgdbTagScanner`: `nonce` (scansione, conferma, "non è un gioco", ripristino) e `searchNonce` (ricerca dei giochi su IGDB). Spariscono le due `wp_json_encode( wp_create_nonce(...) )` scritte dentro lo script.
- **Il codice è identico a prima**: confronto riga per riga col vecchio script, cambiano solo le due righe che leggevano i nonce.

### ✅ Test
- Nuova suite 105 (105.1-105.2): la vista non ha più `<script>` e passa i nonce con `wp_localize_script`; il file non contiene PHP né `innerHTML`; usa il nonce di scansione di default e quello di ricerca solo per `ntn_search_igdb`, cioè quelli che i gestori PHP controllano; JavaScript valido (`node --check`).
- Test 80.8 aggiornato (legge anche il nuovo file JS).
- Suite: 637 test, 0 falliti. Versione 4.28.0 nei 4 header e nei controlli 24.x e 29.1.

### Ancora da fare nel passo 4
Scheda Backup (`tab-backup.php`), poi gli stili scritti dentro le righe HTML e il piccolo blocco `<style>` della scheda Immagini.

### Ordine di installazione
Plugin, poi tema parent, poi child. (I temi sono solo ribattezzati 4.28.0.)
