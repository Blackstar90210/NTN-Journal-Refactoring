# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.19.0 (Registro delle memorie e riquadro «cosa viene svuotato»)

Punto 6 dell'audit di coerenza.

### 🗂️ Registro unico delle memorie (plugin)

Il pulsante «Svuota tutta la memoria» aveva un suo elenco di nomi, scritto a parte rispetto ai punti del codice che creano le memorie: una memoria nuova non aggiunta all'elenco non veniva svuotata. Oggi il pulsante ne conosceva 9 famiglie, ma nel codice ce ne sono di più.

- Nuovo file `inc/cache-registry.php` con `ntn_core_cache_registry()`: per ogni memoria, nome, cosa contiene, quanto dura, l'inizio del nome salvato e se il pulsante la svuota (con il motivo, se resta).
- `ntn_core_flush_all_caches()` non ha più un elenco suo: legge il registro.
- Ora il pulsante svuota anche: Hub degli sviluppatori (`ntn_studio_`), gioco con o senza recensione, collegamenti articoli-schede (`ntn_vg_link_`), tag per motore grafico, pagine escluse da Google perché povere (`ntn_seo_thin_`), mappa Google News, controlli del pannello, menu a tendina delle piattaforme e la categoria principale dei giochi (quando c'è una cache del server).
- Restano volutamente al loro posto: riassunti dell'intelligenza artificiale, dati dei video YouTube, risposte e token di IGDB e contatori delle schede IGDB e Backup. Il riquadro dice perché.
- Con una cache persistente del server (Redis, Memcached) le memorie salvate lì non compaiono nel database: restano invalidate dalla versione per le Hub di categoria e la Redazione, come prima.

### 📋 Riquadro informativo (scheda Extra)

Sopra il pulsante c'è un riquadro che elenca, in italiano, cosa viene svuotato (con cosa contiene e quanto dura) e cosa non viene toccato, e ricorda che articoli, pagine, impostazioni, redirect e backup non sono mai coinvolti. Si genera dal registro, quindi non resta mai indietro.

### ✅ Test
- Nuovi test 89.1-89.4, tra cui un controllo che ogni nome di memoria usato nel plugin e nel tema parent sia coperto da una voce del registro: aggiungere una memoria senza dichiararla fa fallire i test.
- Test 48.3, 76.2 e 84.4 aggiornati per leggere il registro invece dell'elenco vecchio (stessi controlli, nuova posizione).

### Ordine di installazione
Plugin, poi tema parent, poi child.
