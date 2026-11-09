# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.20.0 (Ottimizzazioni del sito dal tema al plugin)

Punto 5 dell'audit di coerenza. Nessuna modifica visibile al sito: cambia dove sta il codice.

### 🔧 Cosa si sposta

Il file `inc/performance.php` del tema mescolava due cose: ottimizzazioni delle pagine del tema e ottimizzazioni del **sito**, che non dipendono dal tema e sparirebbero cambiando tema (per esempio la conversione delle immagini caricate). Le seconde ora stanno nel plugin, nel nuovo file `inc/site-optimizations.php`:

- qualità JPEG (75 invece di 82, se l'opzione del tema è attiva) e blocco dello scaling automatico di WordPress oltre 2560 px;
- conversione dei PNG pieni in JPEG e ottimizzazione dei file caricati, con il controllo dei pixel per non toccare i PNG trasparenti;
- emoji disattivate e pulizia dell'intestazione (versione di WordPress, RSD, shortlink, link REST e oEmbed);
- Gravatar Hovercards disattivati;
- l'indirizzo `/?author=1` riporta all'indirizzo vero dell'autore.

Nel tema restano le tre cose legate alle sue pagine: preload dell'immagine principale (LCP), DNS prefetch e `defer` degli script del tema.

### 🛡️ Nessun conflitto con il tema vecchio

Le funzioni nuove si chiamano `ntn_core_…` (prima `ntn_journal_…` e `ntn_png_…`), quindi con il tema vecchio installato non c'è «Cannot redeclare». Il plugin si carica prima del tema: finché il tema NTN è sotto la 4.20.0 il plugin **non** attiva queste ottimizzazioni (le fa ancora il tema); dalla 4.20.0 del tema le fa il plugin. Con un altro tema le fa sempre il plugin. Se si aggiorna il tema ma non il plugin, queste ottimizzazioni restano spente finché non si aggiorna il plugin: per questo l'ordine è plugin, parent, child (e l'avviso della 4.18.0 segnala le versioni diverse).

### ✅ Test
Nuovi test 90.1-90.4. I test sul controllo dei pixel dei PNG, sull'autore e sul commento superfluo leggono ora il file del plugin (27.19, 28.6, 29.19, 61.1-61.3).

### Ordine di installazione
Plugin, poi tema parent, poi child.
