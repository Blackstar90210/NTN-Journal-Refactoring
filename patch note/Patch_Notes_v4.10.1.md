# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.10.1 (Protezioni per lo spostamento dei dati nel plugin)

Primo passo della fase 2 della revisione di coerenza tra plugin e temi. Le funzioni che leggono i dati dei giochi sono ancora nel tema genitore, ma ora ognuna è protetta da un controllo: se un'altra parte del sito l'ha già definita, il tema non la definisce una seconda volta. Il sito non cambia in nulla.

---

### 🛡️ 1. Funzioni protette

- In `inc/helpers.php` del tema genitore le 19 funzioni di dati (voto, categoria principale, testo alternativo, dati IGDB, giochi e recensioni di un articolo, contenuti in evidenza dell'Hub, generi) sono dentro `if ( ! function_exists() )`. Prima ce l'aveva solo `ntn_calculate_reading_time`.
- Le funzioni che stampano HTML (riga della recensione, collegamenti a studi e motori, date) restano come sono: non passeranno al plugin.

### 🧭 2. Perché serve

- WordPress carica il plugin prima del tema. Dalla 4.11.0 il plugin definirà queste funzioni: con un tema senza protezioni la stessa funzione verrebbe definita due volte e il sito si bloccherebbe con l'errore "Cannot redeclare".
- Con la 4.10.1 installata, la copia del tema viene saltata e vince quella del plugin.

### ⚠️ 3. Ordine di aggiornamento

- Installa la 4.10.1 sul sito **prima** della 4.11.0. L'ordine dentro questa versione non conta: il plugin cambia solo il numero di versione.

### ✅ 4. Test

- Aggiunta la sezione 73: ogni funzione di dati è protetta, le funzioni di presentazione no, e `helpers.php` si carica senza errori quando le funzioni esistono già. La suite passa al 100%.
