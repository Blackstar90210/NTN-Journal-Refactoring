# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.30.0 (Passo 4, stile della scheda Immagini nel foglio di stile)

Seguito del passo 4 del piano "NTN Journal e gli standard WordPress". Dopo gli script (schede IGDB e Backup), questa versione sposta l'unico blocco `<style>` scritto dentro una pagina del pannello: quello della scheda **Immagini** (`tab-immagini.php`).

La scheda ha lo stesso aspetto di prima.

### 📦 Cosa cambia
- Il blocco `<style>.ntn-images-tab > hr:first-of-type { display: none; }</style>` e lo `style="max-width: 100%; margin-top: 0;"` del riquadro passano in `assets/css/admin.css` (che si carica già in tutta la pagina *NTN Journal Funzioni*), con le stesse tre regole: riquadro a tutta larghezza, nessun margine in alto, primo filetto nascosto.
- `tab-immagini.php` non ha più né `<style>` né attributi `style`.

### ✅ Test
- Nuova suite 107 (107.1): la vista non ha `<style>` né `style=`; le tre regole sono in `admin.css`; `admin.css` si carica nella pagina del pannello.
- Suite: 640 test, 0 falliti. Versione 4.30.0 nei 4 header e nei controlli 24.x e 29.1.

### Cosa resta
Gli attributi `style="..."` scritti riga per riga nelle altre schede (circa 280): vedi la proposta nel messaggio di rilascio. Sono i meno urgenti: WordPress non li vieta, e spostarli può cambiare l'aspetto se una regola del pannello li batte in specificità.

### Ordine di installazione
Plugin, poi tema parent, poi child. (I temi sono solo ribattezzati 4.30.0.)
