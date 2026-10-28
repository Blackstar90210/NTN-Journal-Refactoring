# Implementazione Tab "Backup / Ripristino" per NTN Journal Funzioni

Questo piano descrive l'aggiunta di una funzionalità di esportazione e importazione dati (in formato JSON) all'interno della pagina "NTN Journal Funzioni".

## User Review Required

> [!IMPORTANT]
> **Scelta dei dati RAWG da includere**
> Attualmente i dati RAWG (titolo, cover, generi, ecc.) sono salvati nei "meta" di ogni singolo articolo e tag (nel database, collegati all'ID univoco dell'articolo). L'esportazione includerà *tutti* questi dati. Durante il ripristino, i meta verranno riassociati agli ID degli articoli originali. Questo è perfetto per una migrazione staging -> produzione se gli ID degli articoli coincidono. Se gli ID non coincidono, i dati non troveranno l'articolo giusto. Ti va bene questa limitazione tecnica intrinseca di WordPress?

> [!WARNING]
> **Sicurezza dell'Importazione**
> L'importazione sovrascriverà i redirect attuali e le impostazioni delle Breaking News. Sei d'accordo nell'implementare un alert di conferma (con Javascript) prima dell'upload per evitare sovrascritture accidentali?

## Proposed Changes

La nuova funzionalità sarà aggiunta come quarto tab nella pagina esistente. Verranno implementate le logiche di download del file JSON e l'upload (tramite form sicuro).

### Menu e Tab UI (admin-functions-page.php)

#### [MODIFY] [admin-functions-page.php](file:///home/lucamaiolini/Sviluppo/NTN%20Journal/ntn-journal-fixed%20%5BTest%5D/ntn-journal/inc/admin-functions-page.php)
- Aggiunta del quarto tab "Backup / Ripristino" nell'intestazione HTML (`<a href="?page=ntn-journal-functions&tab=backup"...>`).
- Aggiunta della condizione `elseif ( $active_tab === 'backup' )` per renderizzare l'interfaccia:
  - Form con pulsante "Esporta Dati in JSON".
  - Form per l'Upload del file JSON (con `wp_nonce_field` per sicurezza e verifica permessi).

### Logica di Esportazione e Importazione

Verrà creato un nuovo file dedicato per mantenere il codice pulito, seguendo i principi della skill "Enrico Cleaner" e "Maria Grazia Security".

#### [NEW] [backup-restore.php](file:///home/lucamaiolini/Sviluppo/NTN%20Journal/ntn-journal-fixed%20%5BTest%5D/ntn-journal/inc/backup-restore.php)
- **Funzione di Export**:
  - Intercetta la richiesta POST di esportazione.
  - Recupera da `wp_options`: `ntn_journal_redirects` e le 7 opzioni `ntn_breaking_news_*`.
  - Recupera da `wp_postmeta`: tutti i meta RAWG (es. `_ntn_game_title`, `_ntn_game_cover`) per tutti i post.
  - Recupera da `wp_termmeta`: tutti i meta RAWG per i tag.
  - Genera un file `.json` strutturato in array organizzati, e forza il download nel browser (`header('Content-Type: application/json')`).
- **Funzione di Import**:
  - Intercetta il caricamento del file (verificando il Nonce e `current_user_can('manage_options')`).
  - Decodifica il JSON caricato.
  - Salva/sovrascrive i dati in `wp_options`, `wp_postmeta` e `wp_termmeta` in modo sicuro (sanitizzando e verificando i formati previsti).

#### [MODIFY] [functions.php](file:///home/lucamaiolini/Sviluppo/NTN%20Journal/ntn-journal-fixed%20%5BTest%5D/ntn-journal/functions.php)
- Aggiunta del `require_once get_template_directory() . '/inc/backup-restore.php';` per includere le nuove logiche nel core del tema.

## Verification Plan

### Manual Verification
1. Andare nella bacheca > NTN Journal Funzioni > Backup / Ripristino.
2. Cliccare su Esporta e verificare che venga scaricato un file JSON contenente i redirect, news e RAWG meta.
3. Modificare temporaneamente una Breaking News o cancellare un Redirect dall'interfaccia.
4. Importare il file JSON precedentemente scaricato.
5. Verificare che i dati originari (News e Redirect) siano stati ripristinati correttamente come nel backup originale.
