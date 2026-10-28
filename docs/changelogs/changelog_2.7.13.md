# Resoconto Fix Upload Immagini e Gestione Gutenberg (Versione 2.7.13)

## Problema Riscontrato
In seguito al rilascio 2.7.12, hai segnalato un comportamento imprevisto: il caricamento di immagini grandi produceva comunque due versioni all'interno di WordPress, di cui una molto pesante (l'originale) e una scalata (`-scaled`), vanificando lo scopo dell'ottimizzazione alla radice.

Le cause individuate erano due:
1. **Bypass tramite Gutenberg (Sideload):** I caricamenti effettuati tramite il nuovo block editor (REST API) non innescano l'hook nativo `wp_handle_upload`, ma utilizzano una funzione interna differente (`wp_handle_sideload`). La nostra routine di ottimizzazione veniva quindi aggirata del tutto per la maggior parte dei caricamenti.
2. **Fallimento operazione Rinomina / Overwrite (Windows):** La logica di rinomina da file temporaneo a file finale, introdotta nella versione precedente per aggirare presunti lock di `ImageMagick`, causava in realtà un errore di scrittura critico sui server Windows se il file finale esisteva già. Come risultato l'immagine ottimizzata non veniva mappata e l'immagine "gigante" proseguiva il suo corso fino alla routine nativa di WordPress, che la ridimensionava lasciando su disco però anche l'originale intatto (comportamento nativo _big_image_size_threshold_).

## Intervento Effettuato
- **Integrazione Gutenberg (`wp_handle_sideload`):** Ho agganciato la funzione di ottimizzazione (`ntn_journal_optimize_upload_size_and_format`) sia a `wp_handle_upload` che a `wp_handle_sideload`. Ora il ridimensionamento a 1920px e la conversione a JPEG si applicano a *tutti* i caricamenti, compresi quelli da editor a blocchi.
- **Refactoring Salvataggio Immagine:** Ho rimosso l'inutile file temporaneo (dal momento che `WP_Image_Editor` è in grado di sovrascrivere il sorgente in modo sicuro). Adesso si interviene in maniera *in-place* annullando i problemi di lock e di rename-failure di Windows.
- **Disabilitazione Scaling Nativo:** Per evitare l'insorgenza futura di immagini "orfane", ho disabilitato del tutto la soglia limite nativa di WordPress (`big_image_size_threshold = false`), dal momento che ci pensa già il nostro tema in fase embrionale.

## Checklist Conformità
- [x] **Architettura:** Logica in `ntn-journal/inc/performance.php`.
- [x] **SemVer:** Avanzamento alla versione `2.7.13`.
- [x] **Sincronizzazione Header:** Aggiornati `style.css` e Costanti.
- [x] **Audit Test Suite:** 164 Pass, 0 Errori.
- [x] **Packaging ZIP:** File obsoleti trasferiti in `archive/`, creati 3 nuovi `2.7.13.zip`.
