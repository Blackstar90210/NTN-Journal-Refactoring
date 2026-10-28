# Resoconto Fix Upload Immagini (Versione 2.7.12)

## Problema Riscontrato
Il problema segnalato si verificava unicamente con i file JPEG (o JPG) in fase di upload. A causa del modo in cui le librerie grafiche (in particolare ImageMagick su server Windows) gestiscono il lock sui file aperti in lettura e scrittura simultanea, l'operazione di sovrascrittura (`save`) del file originale falliva silenziosamente. Di conseguenza:
1. Le immagini grandi non venivano ridimensionate (il taglio a 1920px falliva).
2. La compressione non veniva applicata (i JPEG più piccoli di 1920px venivano addirittura ignorati a causa del flag `$modified` impostato a `false`).

Inoltre, il tentativo di eseguire il rinomina/eliminazione dello stesso file basandosi sulle estensioni (es. `immagine.JPG` vs `immagine.jpg`) esponeva a seri rischi di perdita file sui filesystem case-insensitive tipici di Windows.

## Intervento Effettuato
- **Refactoring Logica Upload (`inc/performance.php`):**
  - Si forza il flag di modifica `$modified = true` su tutte le immagini JPEG e PNG, garantendo che anche le immagini più piccole di 1920px vengano sempre ricompresse al 75% per massimizzare il risparmio (come da specifiche brutaliste del Parent Theme).
  - È stata introdotta una strategia di salvataggio in 2 step. WordPress ora salva l'immagine processata in un **file temporaneo** (`.tmp.jpg`).
  - L'oggetto editor in memoria viene esplicitamente rimosso per allentare i lock sul file originale.
  - Il vecchio file originale viene scollegato (`unlink`), e subito dopo il temporaneo viene rinominato in modo sicuro.

## Checklist Conformità
- [x] **Architettura:** Logica posizionata in `ntn-journal/inc/performance.php`.
- [x] **Compatibilità PHP 8.2:** Nessun warning.
- [x] **SemVer:** Avanzamento alla versione `2.7.12`.
- [x] **Sincronizzazione Header:** Aggiornati i 4 punti focali (`style.css` Parent/Child, Main File Plugin e Costante PHP).
- [x] **Audit Test Suite:** Eseguita con successo.
- [x] **Housekeeping & Packaging:** File zip legacy in `archive/`, creati 3 nuovi `2.7.12.zip`.
