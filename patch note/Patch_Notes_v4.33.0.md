# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.33.0 (Introiti stimati su Steam nelle Hub dei videogiochi)

Ogni Hub di un gioco presente su Steam mostra una stima degli incassi netti del gioco su Steam, accanto ai contatori sotto il titolo. Il pannello mostra la stessa stima per tutti i giochi.

### 📦 Cosa cambia
- **Hub del gioco**: nella riga dei contatori compare la voce "Introiti stimati su Steam*" con l'intervallo netto in forma breve (es. "400-799 mln $", cifra in rosso). Sotto c'è la dicitura "* Stima basata sulle recensioni Steam · solo versione PC · non è un dato ufficiale". Se la stima manca, la voce e la dicitura non compaiono.
- **Pannello, Scanner IGDB → "Visualizza tag collegati"**: nuova colonna di sola lettura "Introiti Steam (stima netta)" con intervallo, recensioni, prezzo, data dell'aggiornamento e ID Steam. Per i giochi senza stima mostra il motivo: gratuito, senza prezzo, non su Steam, poche recensioni, non trovato, in attesa del primo controllo.
- **Calcolo** (come steam-revenue-calculator.com): copie vendute = recensioni × 25 a × 50; netto = copie × prezzo di listino in dollari × 0,295. Dal lordo si tolgono prezzi regionali (-9%), sconti (-20%) e rimborsi (-12%); da quello che resta si tolgono la quota di Steam (30%) e l'IVA (20%). Nessuna stima per giochi gratuiti, senza prezzo, senza pagina Steam o con meno di 50 recensioni. I numeri si possono cambiare da `wp-config.php` (`NTN_CORE_STEAM_COPIES_MIN`, `NTN_CORE_STEAM_COPIES_MAX`, `NTN_CORE_STEAM_NET_SHARE`, `NTN_CORE_STEAM_MIN_REVIEWS`).
- **Da dove arrivano i dati**: l'ID Steam da IGDB (`external_games`, fonte 1 = Steam); recensioni e prezzo da Steam, senza chiave API (`IStoreBrowseService/GetItems` e `appdetails`). Il vecchio indirizzo `appreviews/...?json=1` oggi non dà più i dati.
- **Aggiornamento**: una volta a settimana, in secondo piano (WP-Cron), a lotti di 20 giochi, con 2 richieste a Steam per lotto e 1 secondo di pausa. Quando un gioco viene collegato o aggiornato da IGDB passa per primo e ha la stima entro un minuto. Le pagine del sito leggono solo la stima salvata e non chiedono mai niente a Steam.
- "Scollega" e "Scollega tutti" tolgono anche i dati Steam. La cancellazione del plugin toglie le due operazioni programmate.
- Nessun dato aggiunto per Google (JSON-LD invariato).

### 📁 File
- Plugin: nuovo `inc/steam-revenue.php`; modificati `inc/ajax-igdb.php`, `inc/admin/igdb-ajax-handlers.php`, `inc/game-data.php` (nuova `ntn_journal_get_steam_revenue()`), `ntn-journal-core.php`, `uninstall.php`.
- Tema parent: `template-parts/hub-header.php`, `inc/plugin-fallbacks.php` (riserva vuota), `build/input.css` e CSS compilato.

### ✅ Test
- Nuova suite 110 (110.1-110.8b): ID Steam da IGDB, calcolo e casi senza stima, lettura delle risposte di Steam, salvataggio, lotti con IGDB, giro settimanale, colonna del pannello, voce nella Hub e riserva vuota del tema.
- Test 103.4 aggiornato (operazioni programmate tolte alla cancellazione del plugin).
- Suite: 653 test, 0 falliti. Versione 4.33.0 nei 4 header e nei controlli 24.x e 29.1.
- Provato sul Local: 262 giochi controllati; Hub di Cyberpunk 2077 su computer e telefono, in tema chiaro e scuro; Hub senza stima (Kingdom Hearts IV) senza voce.

### Dopo l'installazione
La prima stima di tutti i giochi arriva da sola nell'ora dopo la prima visita al pannello, poi a lotti di 20 al minuto.

### Ordine di installazione
Plugin, poi tema parent, poi child. (Il child è solo ribattezzato 4.33.0.)
