# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.29.0 (Passo 4, terza schermata: la scheda Backup)

Seguito del passo 4 del piano "NTN Journal e gli standard WordPress": il codice JavaScript esce dalle pagine PHP e va in file suoi, caricati con `wp_enqueue_script`. Dopo la scheda IGDB (4.27.0) e il riquadro "Scansiona i tag senza gioco" (4.28.0), tocca alla scheda **Backup** di *NTN Journal Funzioni* (`tab-backup.php`): esportazione e importazione dei dati Core a lotti, con la barra di avanzamento.

Esportazione e importazione funzionano come prima.

### 📦 Cosa cambia
- Il codice sta ora in `assets/js/admin-backup.js` (versione del plugin nell'indirizzo, in fondo alla pagina). `tab-backup.php` passa da 359 a 159 righe e tiene solo l'HTML e il codice di sicurezza.
- Il PHP passa due valori con `wp_localize_script`, nell'oggetto `ntnBackup`: `ajaxUrl` (indirizzo di `admin-ajax.php`) e `nonce` (codice di sicurezza `ntn_backup_nonce`, lo stesso che controllano i due gestori `ntn_backup_export` e `ntn_backup_import`). Spariscono le due `wp_json_encode(...)` scritte dentro lo script.
- **Il codice è identico a prima**: confronto riga per riga col vecchio script, cambiano solo le due righe che leggevano questi valori.

### ✅ Test
- Nuova suite 106 (106.1-106.2): la pagina non ha più `<script>` e passa indirizzo e nonce con `wp_localize_script`; il file non contiene PHP; esportazione e importazione mandano il nonce che i gestori PHP controllano; JavaScript valido (`node --check`).
- Suite: 639 test, 0 falliti. Versione 4.29.0 nei 4 header e nei controlli 24.x e 29.1.

### Ancora da fare nel passo 4
Gli stili scritti dentro le righe HTML (`style="..."`) e il piccolo blocco `<style>` della scheda Immagini.

### Ordine di installazione
Plugin, poi tema parent, poi child. (I temi sono solo ribattezzati 4.29.0.)
