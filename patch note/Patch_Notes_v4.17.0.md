# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.17.0 (Una sola funzione per parlare con IGDB)

Punto 9 dell'audit di coerenza. Nessuna modifica visibile al sito: cambia solo come il plugin chiede i dati a IGDB.

### 🔧 Una sola richiesta a IGDB (plugin)

Il lavoro «controlla le chiavi, prendi il token, invia la richiesta, leggi la risposta, gestisci gli errori» era scritto sei volte, in quattro file. Ora c'è `ntn_core_igdb_request( $apicalypse, $timeout )` in `inc/ajax-igdb.php`, e le sei funzioni la chiamano.

- Restituisce l'elenco dei dati letti, oppure un `WP_Error` con codice: `ntn_igdb_credentials` (chiavi mancanti o errate), `ntn_igdb_401` (token scaduto), `ntn_igdb_http` (errore di rete o risposta diversa da 200, con lo stato nei dati), `ntn_igdb_invalid` (risposta illeggibile).
- Con l'errore 401 il token salvato viene sempre cancellato, così la richiesta successiva ne chiede uno nuovo. Prima lo facevano solo alcune delle sei copie.
- Funzioni che la usano: ricerca nell'editor (`ntn_journal_ajax_search_igdb`), dettagli del gioco (`ntn_get_igdb_game_details`), controllo degli aggiornamenti (`ntn_ajax_check_igdb_updates`), aggiornamento di un tag (`ntn_core_ajax_update_single_tag_igdb`), scanner dei tag (`ntn_igdb_scan_lookup`), sincronizzazione dei franchise (`ntn_ajax_sync_franchises`).
- I messaggi agli utenti restano quelli di prima; l'unica differenza è il testo del 401 nella ricerca, ora uguale a quello delle altre funzioni.
- Nuovi test 87.1-87.3: un solo punto che invia al servizio, funzione con i controlli, sei chiamanti.

### Ordine di installazione
Plugin, poi tema parent, poi child.
