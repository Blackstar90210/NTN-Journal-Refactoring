# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.24.0 (Scheda Immagini nel pannello)

Gli strumenti per le immagini hanno una scheda tutta loro. Nessun cambiamento nel sito e nel funzionamento degli strumenti. Il tema non cambia.

### 🗂️ Cosa cambia
- Nuova scheda **Immagini** nel pannello NTN Journal, subito dopo **Extra** (anche nel menu a sinistra). File `inc/admin/views/tab-immagini.php`.
- Ci sono i due riquadri che prima stavano in fondo alla scheda Extra:
  - **Miniature dei PNG trasparenti** (4.21.0): Controlla, Ripara, quarantena ed eliminazione;
  - **Rapporto sulle immagini** (4.23.0): Analizza e Cerca immagini non usate.
- La scheda **Extra** torna a contenere solo le sue impostazioni e il pulsante **Svuota tutta la memoria**.
- Indirizzo diretto: `wp-admin/admin.php?page=ntn-journal-functions&tab=immagini`.

### ✅ Test
Nuovo test 96.1. I test 93.1 e 95.1 controllano ora la scheda Immagini. Suite: 596 test, 0 falliti.

### Ordine di installazione
Plugin, poi tema parent, poi child.
