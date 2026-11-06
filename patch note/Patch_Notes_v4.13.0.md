# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.13.0 (Articoli in evidenza dell'Hub e loro cache passano al plugin)

Quarto passo della fase 2 della revisione di coerenza tra plugin e temi. L'ultima funzione di dati del tema genitore è passata al plugin: da questa versione `inc/helpers.php` del tema contiene solo funzioni che stampano HTML. L'Hub del gioco resta identico.

---

### 📦 1. Funzione spostata nel plugin

- `ntn_get_game_featured_content` (recensione o articolo in evidenza dell'Hub del gioco) è in `inc/game-data.php` del plugin, protetta da `function_exists`.
- Cancellata `ntn_get_tag_featured_content`: nessuna parte del sito la usava.

### 🗂️ 2. Un solo nome per la cache dell'Hub

- Prima il tema salvava la cache con il nome `ntn_hub_featured_...` e il pannello del plugin la cancellava riscrivendo lo stesso nome a mano in cinque punti.
- Ora il nome nasce da una sola funzione del plugin, `ntn_core_featured_cache_key()`, nel nuovo file `inc/cache-keys.php`, caricato sempre. La usano chi salva la cache, il salvataggio degli articoli, il cambio della recensione in evidenza e "Svuota tutte le cache".
- I nomi sono gli stessi di prima, quindi la cache già salvata resta valida.

### 🛟 3. Funzione di riserva nel tema

- Senza plugin `ntn_get_game_featured_content` restituisce `false`: l'Hub si apre senza l'articolo in evidenza.

### ⚠️ 4. Ordine di aggiornamento

- Prima il plugin, poi il tema genitore, poi il figlio.

### ✅ 5. Test

- La sezione 73 ora controlla che in `helpers.php` non resti nessuna delle 19 funzioni di dati. Nuova sezione 76: funzione nel plugin, riserva nel tema, nome della cache da una sola funzione usata ovunque. La suite passa al 100%.
- Sito di prova: stesso HTML della 4.10.1; la cache dell'Hub si salva, si cancella al salvataggio dell'articolo e con "Svuota tutte le cache", poi si ricrea alla visita successiva.
