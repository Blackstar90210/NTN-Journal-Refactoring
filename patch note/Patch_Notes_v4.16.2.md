# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.16.2 (Ricerca e catalogo senza righe infinite nel database, test 56.3)

### 🛡️ 1. Ricerca e catalogo pubblici non riempiono più il database (punto 7 del rapporto di audit)

Gli indirizzi pubblici `/wp-json/ntn-journal/v1/live-search` e `/wp-json/ntn-journal/v1/games` salvavano nel database (tabella delle opzioni) una riga per ogni richiesta diversa. Chiunque, senza accedere, poteva chiedere mille testi diversi e creare mille righe. Nel catalogo bastava anche aggiungere un parametro inventato (`?x=1`, `?x=2`…).

- **Ricerca (`live-search`)**: il testo cercato è libero, quindi non si salva più nel database. Si tiene in memoria solo se il sito ha una cache in memoria (Redis, Memcached); senza, ogni ricerca viene calcolata di nuovo. Il browser e le CDN continuano a tenerla un minuto.
- **Catalogo (`games`)**: la chiave della cache usa solo i parametri noti (pagina, per pagina, ordine, anno, ricerca e i quattro filtri), non più tutti quelli ricevuti. Nel database si salvano solo le richieste con poche combinazioni possibili: senza ricerca libera, senza filtri, senza anno e fino alla ventesima pagina. Le altre combinazioni (con un filtro, un anno o una ricerca) restano solo nella cache in memoria, se c'è.
- **Pulizia una tantum**: la prima volta che si apre l'amministrazione, il plugin elimina le righe lasciate dalle versioni precedenti (`ntn_livesearch2_*` e `ntn_games_*`).
- **«Svuota tutta la memoria»** ora pulisce anche le memorie di ricerca e catalogo.

Cosa vedi sul sito: nulla. Senza Redis le ricerche con un filtro nel catalogo possono essere un po' più lente la prima volta, ma il database non si gonfia più.

### 🧪 2. Test 56.3 sul controllo dei file con le righe di Windows

Il test controllava un file del tema cercando un a capo `\n`, mentre su Windows i file hanno `\r\n`. Ora la lettura dei file normalizza gli a capo (come già facevano altri test), e così passano anche i test 53.1, 59.1, 62.8, 63.9 e 66.3 che avevano lo stesso problema. Nessun cambio al sito: è solo la suite di collaudo.

### Ordine di installazione
Plugin, poi tema parent, poi child (il cambiamento vero è nel plugin).
