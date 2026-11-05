# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.2.0 (Hub Statistiche, Fase 2: impostazioni)

Seconda fase dell'Hub "Statistiche" approvata dal CEO. Il nuovo tipo si può scegliere e salvare, ma la pagina dedicata arriverà nella fase successiva: per ora usa l'impaginazione comune delle Hub.

---

### ⚙️ 1. Nuovo tipo di Hub (NTN Journal Funzioni → Hub)

- **Statistiche** compare nel menu "Tipo" accanto a Piattaforme e Studi, con gli stessi campi comuni: titolo mostrato, testo di presentazione, illustrazione e articolo in evidenza.
- **Marchi e studi esclusi** non compaiono: per questo tipo non servono. Le sottocategorie restano archivi normali.
- **Salvataggio** con gli stessi controlli di prima. Un tipo sconosciuto torna a Piattaforme, come già avveniva.

### 🧩 2. Il tipo è riconosciuto ovunque

- **Controllo copertura**: segnala una Hub Statistiche senza articoli.
- **Descrizione per Google**: se manca il testo di presentazione, ne usa una sui numeri della sezione.
- **Tema**: userà `category-hub-statistiche.php` appena esiste; fino ad allora resta `category-hub.php`, senza l'etichetta "Made in Italy" e senza indice degli studi.
- **Backup**: il tipo è già incluso nell'esportazione e nell'importazione JSONL con le altre impostazioni delle Hub.

### ✅ 3. Hub esistenti e test

- Le Hub già accese mantengono il loro tipo. L'unica differenza visibile è il contatore "Giochi".
- **Giochi** (scelta del CEO): in tutte le Hub di categoria il contatore considera ora tutti gli articoli, non più solo gli ultimi 200.
- Aggiunti 8 test (sezione 64 di `audit_test_suite.php`).
