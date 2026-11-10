# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.32.0 (Passo 4: lo script della pagina Migrazione IGDB in un file)

Ultimo script ancora scritto dentro una pagina PHP del pannello: la pagina **Strumenti > Migrazione IGDB** (`migrazione-igdb.php`): migrazione dei tag con dati IGDB nelle schede Videogioco e sincronizzazione dei Franchise. Funziona come prima.

### 📦 Cosa cambia
- Il codice sta in `assets/js/admin-igdb-migration.js` (62 righe), caricato con `wp_enqueue_script` in fondo alla pagina, con jQuery come dipendenza (lo script lo usava già) e la versione del plugin nell'indirizzo.
- Il codice di sicurezza (`ntn_igdb_migrate_nonce`) passa con `wp_localize_script` nell'oggetto `ntnIgdbMigration`. Spariscono le due `esc_js( wp_create_nonce(...) )` dentro lo script: nel plugin non c'è più nessun nonce stampato dentro un blocco di script.
- **Il codice è identico a prima**: confronto riga per riga, cambiano solo i due punti che leggevano il nonce.

### ✅ Test
- Nuova suite 109 (109.1-109.2): niente `<script>` né `esc_js` nel file PHP, i due pulsanti restano, il file JavaScript non contiene PHP, manda il nonce che i due gestori PHP controllano (migrazione e franchise), JavaScript valido (`node --check`).
- Test 102.5 aggiornato: ora nel plugin non resta nessun nonce stampato in uno script.
- Suite: 644 test, 0 falliti. Versione 4.32.0 nei 4 header e nei controlli 24.x e 29.1.

### Passo 4: stato finale
Nessuno script e nessun blocco di stile è più scritto dentro le pagine PHP del pannello (versioni 4.27.0 - 4.32.0). Restano, per scelta, i blocchi `application/ld+json` (sono dati per Google) e gli attributi `style="..."` in riga (circa 280).

### Ordine di installazione
Plugin, poi tema parent, poi child. (I temi sono solo ribattezzati 4.32.0.)
