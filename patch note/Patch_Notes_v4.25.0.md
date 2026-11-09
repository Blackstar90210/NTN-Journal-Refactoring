# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.25.0 (La scheda Immagini anche nella Dashboard)

La Dashboard ha ora una cella **// Immagini**, tra Redirect e SEO, con il link **Apri →** alla scheda Immagini. Il tema non cambia.

### 🔧 Cosa mostra
Il numero grande è quante immagini ha analizzato l'ultimo **Analizza** («—» se non è mai stato fatto). Sotto, in poche righe:
- peso su disco, quanti originali superano 1,5 MB e quante immagini sono senza testo alternativo;
- file mancanti sul disco, solo se ce ne sono;
- quante immagini non risultano usate e quanto spazio occupano, se è stata fatta la ricerca;
- quante miniature dei PNG trasparenti restano da riparare e quanti file sono in quarantena.

### 📌 Note
- La cella legge **soltanto risultati già salvati** (`ntn_image_report` e lo stato della riparazione dei PNG): nessun controllo, nessuna ricerca nei file o nel database ad ogni apertura della Dashboard.
- Un'analisi interrotta non conta: finché non è completa la cella dice «Nessuna analisi ancora fatta».
- Funzione `ntn_core_image_report_dashboard_summary()` in `inc/image-report.php`.

### ✅ Test
Nuova suite 97 (97.1-97.2): la cella è tra Redirect e SEO e punta alla scheda Immagini, il riepilogo non fa lavoro pesante, e prove con dati finti: nessun rapporto, rapporto completo, analisi interrotta. Suite: 598 test, 0 falliti.

### Ordine di installazione
Plugin, poi tema parent, poi child.
