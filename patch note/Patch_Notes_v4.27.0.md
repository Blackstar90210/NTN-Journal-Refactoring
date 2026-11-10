# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.27.0 (Passo 4, prima schermata: il codice della scheda IGDB in un file suo)

Quarto passo del piano "NTN Journal e gli standard WordPress". WordPress vuole che il codice JavaScript stia in file separati e venga caricato con `wp_enqueue_script`, non scritto dentro le pagine PHP. Si fa **una schermata per versione**, provandola prima di passare alla successiva. Questa è la prima: la scheda **IGDB** di *NTN Journal Funzioni*, la più lunga (647 righe di JavaScript dentro `tab-igdb.php`).

Il sito e il pannello si usano come prima: lo scanner IGDB, la ricerca dei tag, l'associazione tag-gioco, il controllo e l'aggiornamento dei giochi fanno le stesse cose.

### 📦 Cosa cambia
- Il codice sta ora in `assets/js/admin-igdb-scanner.js`, caricato in fondo alla pagina, solo quando la scheda IGDB è aperta e lo Scanner è attivo (come prima), con il numero di versione del plugin nell'indirizzo (così il browser scarica la copia nuova dopo ogni aggiornamento).
- Le due uniche informazioni che il PHP passava al codice, i codici di sicurezza (nonce), ora arrivano con `wp_localize_script` nell'oggetto `ntnIgdbScanner` (`nonce` per la ricerca e il salvataggio, `batchNonce` per il controllo e l'aggiornamento). Le 14 `esc_js( wp_create_nonce(...) )` dentro lo script sono sparite.
- `tab-igdb.php` passa da 821 a 187 righe.
- **Il codice è identico a prima**: ho confrontato il vecchio script con il nuovo file riga per riga; cambiano solo i 14 punti dei nonce.

### ⏭️ Cosa resta del passo 4 (altre versioni, una schermata alla volta)
Script ancora dentro le pagine: scheda Backup (`tab-backup.php`, 211 righe) e controllo tag IGDB (`igdb-tag-scanner.php`, 234 righe). Poi gli stili scritti dentro le righe HTML (`style="..."`) e il piccolo blocco `<style>` della scheda Immagini.

### ✅ Test
- Nuova suite 104 (104.1-104.3): la pagina non ha più `<script>` e passa i due nonce con `wp_localize_script`; il file non contiene PHP; le 14 richieste mandano ciascuna il nonce che il proprio gestore PHP controlla (3 + 11); il file è JavaScript valido (`node --check`) e `ntnEsc` resta una funzione globale.
- Test 48.6, 102.5 aggiornati (il codice ora è nel file JS; restano 2 `esc_js` in `migrazione-igdb.php`).
- Suite: 635 test, 0 falliti. Versione 4.27.0 nei 4 header e nei controlli 24.x e 29.1.

### Ordine di installazione
Plugin, poi tema parent, poi child. (I temi non cambiano: sono solo ribattezzati 4.27.0.)
