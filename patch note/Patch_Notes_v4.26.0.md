# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.26.0 (Competenze al posto giusto e file del pacchetto)

Terzo passo del piano "NTN Journal e gli standard WordPress". Il sito si comporta come prima: cambiano chi fa cosa (plugin o tema) e i file che WordPress si aspetta di trovare in un tema e in un plugin.

### 🧭 Cosa spetta al plugin e cosa al tema
- **XML-RPC disattivato**: passa dal tema (`inc/setup.php`) al plugin (`inc/site-optimizations.php`).
- **Campi meta delle recensioni** (`_thumbnail_id`, `rating`, `pros`, `cons`, `verdict`, `reviewed_item`): lo sblocco passa dal tema (`functions.php`, funzione anonima) al plugin, funzione `ntn_core_allow_review_meta`. Stesso comportamento: gli altri campi protetti restano protetti.
- **Restano nel tema** il preload dell'immagine principale e il preconnect / DNS prefetch: riguardano come il tema disegna le sue pagine.
- Con un tema più vecchio il filtro è registrato due volte: non cambia nulla (stessa funzione, stessa priorità). Ordine di installazione sempre: plugin, parent, child.

### ⚡ Script con `defer` nel modo di WordPress
Il filtro `script_loader_tag` che aggiungeva `defer` a quattro script del tema non c'è più. Ogni script dichiara da sé `'strategy' => 'defer'` nell'`wp_enqueue_script`: ricerca istantanea, filtri del catalogo, video lazy, lightbox (i tre script di `main.js`, `front-page.js` e `dot-art.js` lo facevano già). Con WordPress 6.0-6.2 l'argomento viene ignorato (gli script restano in fondo alla pagina, senza `defer`).
- Rimosso anche il filtro `ntn_journal_deferred_script_handles`, che non risulta usato altrove.

### 📦 File del pacchetto
- **`uninstall.php` nel plugin**: alla cancellazione toglie solo le memorie (transient `ntn_*`) e le operazioni programmate. Impostazioni, chiavi API e redirect restano. Per toglierli aggiungi `define( 'NTN_CORE_REMOVE_ALL_DATA', true );` a `wp-config.php` prima di cancellare il plugin. Articoli, comunicati, schede videogioco, tag e categorie non vengono mai cancellati. Funziona anche su multisito.
- **`screenshot.png`** (1200 x 900) nel tema parent e nel child. È una copertina illustrata, non una foto del sito: si può sostituire con una schermata vera.
- **`readme.txt`** nel plugin, nel parent e nel child. Il "Stable tag" del plugin deve restare uguale alla versione (lo controlla il test 103.8).
- **Licenza**: il plugin passa da "Proprietary" a `GPL-2.0-or-later` con `License URI`; il child dichiara GPL v2 o successiva come il parent. WordPress distribuisce temi e plugin sotto GPL: se preferisci un'altra licenza si cambia con una riga.
- **Indirizzi**: `Plugin URI` (era `example.com`) e `Author URI` (era `ntn-journal.local`) puntano al GitHub del progetto.

### ✅ Test
- Nuova suite 103 (103.1-103.9): XML-RPC e campi meta nel plugin e fuori dal tema, stesso comportamento del vecchio codice, `uninstall.php` eseguito davvero con un database finto (solo cache di base, impostazioni con la costante, nessun articolo toccato), opzioni cancellate tutte esistenti nel codice, licenza, screenshot 1200x900, `readme.txt`, Stable tag, ZIP che non escludono i nuovi file.
- Test 85.1 e 90.2 riscritti per la strategia `defer` nativa.
- Suite: 632 test, 0 falliti. `php -l` e PHPCompatibility (PHP 8.0+) senza errori sui file cambiati.
- Versione 4.26.0 nei 4 header e nei controlli 24.x e 29.1.

### Ordine di installazione
Plugin, poi tema parent, poi child.
