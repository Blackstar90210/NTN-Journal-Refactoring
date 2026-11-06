# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.12.0 (Giochi e recensioni di un articolo passano al plugin)

Terzo passo della fase 2 della revisione di coerenza tra plugin e temi. Le funzioni che trovano i giochi di un articolo e le loro recensioni sono passate dal tema genitore al plugin. Hanno gli stessi nomi: la scheda del gioco, anche quella multigioco, resta identica.

---

### 📦 1. Funzioni spostate nel plugin

In `inc/game-data.php` del plugin:

- `ntn_get_post_games`: i giochi di un articolo, uno per tag, in ordine alfabetico.
- `ntn_apply_post_meta_to_game`: i dati scritti sull'articolo vincono su quelli del tag (articoli con un gioco solo).
- Le sei funzioni interne che usano: `ntn_resolve_game_term`, `ntn_get_term_game`, `ntn_get_post_meta_game`, `ntn_get_game_review`, `ntn_format_post_game`, `ntn_game_sort_key`.

Ogni funzione è dentro `if ( ! function_exists() )`. `ntn_journal_game_review_row`, che stampa la riga "Leggi la recensione" o "Non recensito", resta nel tema.

### 🛟 2. Funzioni di riserva nel tema

- In `inc/plugin-fallbacks.php`: senza plugin `ntn_get_post_games` restituisce un elenco vuoto e `ntn_apply_post_meta_to_game` lascia i dati come sono. Le pagine si aprono senza la scheda del gioco.

### ⚠️ 3. Ordine di aggiornamento

- Prima il plugin, poi il tema genitore, poi il figlio. Se aggiorni prima il tema, finché il plugin resta alla 4.11.0 gli articoli non mostrano la scheda del gioco. Nessun errore.

### ✅ 4. Test

- Nuova sezione 75: le 8 funzioni sono nel plugin e non nel tema, le riserve esistono e funzionano, la riga della recensione resta nel tema. La sezione 73 controlla le funzioni di dati ancora nel tema. La suite passa al 100%.
