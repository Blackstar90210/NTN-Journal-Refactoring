# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.9.1 (PHP 8.0 come versione minima)

Prima fase della revisione di coerenza tra plugin e temi. Il plugin usava già una sintassi disponibile solo da PHP 8.0 (`rest-catalog.php`, riga 52), mentre il tema dichiarava PHP 7.4. Ora i requisiti dichiarati corrispondono al codice. Il sito non cambia.

---

### 🧩 1. Requisiti dichiarati

- **Tema genitore**: `Requires PHP` passa da 7.4 a 8.0.
- **Tema figlio**: aggiunte le righe `Requires at least: 6.0` e `Requires PHP: 8.0`, che mancavano.
- **Plugin**: aggiunte le righe `Requires at least: 6.0`, `Requires PHP: 8.0` e `Text Domain: ntn-journal-core`, che mancavano.
- Con PHP più vecchio di 8.0, WordPress ora rifiuta di attivare i tre componenti con un messaggio, invece di dare errore fatale.
- Aggiornati `README.md` (italiano e inglese) e `CLAUDE.md`.

### 🔍 2. Verifica di compatibilità

- PHPCompatibility su tutto il codice, con PHP 8.0 e versioni successive: nessuna incompatibilità.
- L'unico avviso riguarda PHP 8.6, dove `trim()` toglierà anche il carattere di avanzamento pagina. Non ha effetti sul sito.
- `php -l` senza errori su tutti i file.

### ✅ 3. Test

- Aggiunta la sezione 71 di `audit_test_suite.php` (requisiti dichiarati). La suite passa al 100%: 489 test superati, 0 falliti.
