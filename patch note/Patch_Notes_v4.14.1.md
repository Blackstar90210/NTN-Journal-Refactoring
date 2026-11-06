# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.14.1 (La tabella delle uscite della home scrive "PC")

Completa il cambio di nome della 4.14.0: anche la tabella delle uscite in home page scrive "PC" al posto di "PC (Microsoft Windows)".

---

### 🏠 1. Home

- `inc/front-page-data.php` (tema genitore): le piattaforme di ogni gioco in uscita passano da `ntn_core_schema_platform_names()` prima di essere mostrate. Il valore salvato è un testo separato da virgole: viene diviso, normalizzato e riunito con ", ".
- Senza il plugin (o con un plugin più vecchio) la tabella resta com'era.
- Cambia solo il nome del PC; date, giochi e sviluppatori sono quelli di prima.

### ⚠️ 2. Ordine di aggiornamento

- Prima il plugin, poi il tema genitore, poi il figlio.

### ✅ 3. Test

- Nuovo controllo 77.8 nella sezione 77. La suite passa al 100%, tranne il 56.3 già fallito prima (a capo di Windows).
