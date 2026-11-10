# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.31.0 (Passo 4: gli script della scheda Immagini in file)

Seguito del passo 4 del piano "NTN Journal e gli standard WordPress". Dopo le schede IGDB e Backup, tocca ai due riquadri della scheda **Immagini**: "Miniature dei PNG trasparenti" (`png-thumbnail-repair.php`) e "Rapporto sulle immagini" (`image-report.php`). Funzionano come prima.

### 📦 Cosa cambia
- Il codice della pulizia PNG sta in `assets/js/admin-png-repair.js` (80 righe). Il PHP non gli passa niente: il codice di sicurezza lo legge già dall'attributo `data-nonce` del riquadro, come prima.
- Il codice del rapporto sta in `assets/js/admin-image-report.js` (70 righe). Il PHP passa con `wp_localize_script`, nell'oggetto `ntnImageReport`, il rapporto salvato (`saved`: ultima analisi e ultima ricerca); nonce e soglia "pesante" li legge da `data-nonce` e `data-heavy`, come prima.
- Tutti e due si caricano con `wp_enqueue_script` in fondo alla pagina, con la versione del plugin nell'indirizzo.
- **Il codice è identico a prima**: confronto riga per riga con i vecchi script (cambia solo la riga che leggeva `saved`).

### ✅ Test
- Nuova suite 108 (108.1-108.2): niente `<script>` nei due file PHP, i file JavaScript non contengono PHP, leggono il nonce dal riquadro, chiamano solo azioni che hanno il loro gestore PHP (5 per la pulizia PNG, 2 per il rapporto), JavaScript valido (`node --check`).
- Suite: 642 test, 0 falliti. Versione 4.31.0 nei 4 header e nei controlli 24.x e 29.1.

### Cosa resta
Lo script della migrazione IGDB (`migrazione-igdb.php`, 62 righe), nella prossima versione. I blocchi `application/ld+json` (dati strutturati per Google) restano dove sono: sono dati, non codice.

### Ordine di installazione
Plugin, poi tema parent, poi child. (I temi sono solo ribattezzati 4.31.0.)
