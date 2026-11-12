=== NTN Journal Core ===
Contributors: blackstar90210
Requires at least: 6.0
Requires PHP: 8.0
Stable tag: 4.34.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Dati e logica del sito NTN Journal: videogiochi, comunicati stampa, tassonomie, IGDB, SEO, redirect, backup e pannello di controllo.

== Description ==

NTN Journal Core è la parte "dati" dell'ecosistema NTN Journal (tema parent `ntn-journal`, tema child `ntn-journal-reloaded`, questo plugin). Il tema mostra i dati, il plugin li gestisce.

* Tipi di contenuto: Videogioco e Comunicato Stampa, con le tassonomie collegate.
* Scheda videogioco con i dati di IGDB (cache locale: il sito non dipende da IGDB per essere visibile).
* Dati strutturati e sitemap per Google (articoli, recensioni, videogiochi, news).
* Redirect, backup e ripristino delle impostazioni, pannello di controllo in bacheca.
* Ottimizzazioni del sito: immagini, emoji, intestazioni, protezione degli autori, XML-RPC disattivato.

== Installation ==

1. Carica la cartella `ntn-journal-core` in `wp-content/plugins/` oppure installa lo ZIP dalla pagina Plugin.
2. Attiva il plugin.
3. Ordine di aggiornamento consigliato: prima il plugin, poi il tema parent, poi il tema child.

== Uninstall ==

Cancellando il plugin vengono tolte solo le memorie (cache) e le operazioni programmate. Impostazioni, chiavi API e redirect restano nel database.
Per togliere anche quelle, aggiungi a `wp-config.php` prima di cancellare il plugin:

`define( 'NTN_CORE_REMOVE_ALL_DATA', true );`

Articoli, comunicati stampa, schede videogioco, tag e categorie non vengono mai cancellati dal plugin.

== Changelog ==

Le note di ogni versione sono nella cartella `patch note` del repository.
