# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.11.0 (Voto, categoria e dati IGDB passano al plugin)

Secondo passo della fase 2 della revisione di coerenza tra plugin e temi. Nove funzioni che leggono i dati sono passate dal tema genitore al plugin. Hanno gli stessi nomi, quindi i modelli delle pagine non cambiano e il sito resta identico.

---

### 📦 1. Funzioni spostate nel plugin

Nuovo file `inc/game-data.php` nel plugin, con:

- `get_post_rating` (voto della recensione) e `ntn_calculate_reading_time` (tempo di lettura).
- `ntn_get_primary_category` (categoria principale, con la stessa cache di prima).
- `ntn_get_image_alt` (testo alternativo dell'immagine).
- `ntn_get_igdb_data`, `ntn_get_hub_igdb_info`, `ntn_get_tag_igdb_info`, `ntn_translate_igdb_genres` (dati IGDB e generi in italiano).
- `ntn_get_game_hub_url` (indirizzo dell'Hub del gioco).

Ogni funzione è dentro `if ( ! function_exists() )`. Il plugin ora le chiama direttamente: catalogo REST, dati strutturati, Hub e anteprime social funzionano anche con un altro tema attivo.

### 🛟 2. Funzioni di riserva nel tema

- Nuovo file `inc/plugin-fallbacks.php` nel tema genitore: per ognuna delle 9 funzioni c'è una versione vuota con lo stesso nome (nessun voto, nessuna categoria, nessun dato IGDB). Con il plugin attivo vengono saltate; con il plugin spento le pagine si aprono senza i dati dei giochi invece di dare errore.

### 🛡️ 3. Controllo di versione tra plugin e tema

- Il plugin carica `inc/game-data.php` solo se il tema NTN Journal è alla 4.10.1 o successiva. Con un tema più vecchio lascia le funzioni al tema, così non può esserci l'errore "Cannot redeclare", e mostra in bacheca un avviso che chiede di aggiornare il tema. Con un altro tema le carica sempre.

### ⚠️ 4. Ordine di aggiornamento

- Da questa versione: **prima il plugin, poi il tema genitore, poi il figlio.**
- Se aggiorni prima il tema, finché il plugin resta alla versione vecchia le pagine dei giochi sono senza dati (per esempio la descrizione dell'Hub perde sviluppatore e data di uscita). Nessun errore: torna tutto appena aggiorni il plugin.

### ✅ 5. Test

- La suite carica i file come WordPress: prima il plugin, poi il tema.
- La sezione 73 ora controlla le funzioni di dati rimaste nel tema. Nuova sezione 74: le 9 funzioni sono nel plugin e non nel tema, le riserve esistono e restituiscono valori vuoti, il controllo di versione funziona (4.10.0 no, 4.10.1 e 4.11.0 sì, altro tema sì), il plugin non controlla più che il tema le abbia definite. La suite passa al 100%.
- Sito di prova: plugin e temi nuovi danno lo stesso HTML della 4.10.1; plugin nuovo con il tema 4.10.0 o 4.10.1 non dà errori e lo stesso HTML; plugin vecchio o spento con il tema nuovo non dà errori.
