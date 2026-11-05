# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.1.0 (Statistiche dell'Hub di categoria, Fase 1: solo dati)

Prima fase dell'Hub "Statistiche" approvata dal CEO. Il plugin calcola i dati, ma le pagine del sito non cambiano ancora: l'interfaccia arriverà nella fase successiva.

---

### 📊 1. Nuovi dati per le Hub di categoria

- **Nuova funzione** `ntn_core_get_category_hub_stats()`, nel nuovo file `inc/category-hub-stats.php`. Per una Hub, sottocategorie comprese, restituisce:
  - articoli pubblicati in totale e nell'anno solare corrente;
  - autore più prolifico dell'anno (nome, conteggio, link), o nessuno se nell'anno non ci sono articoli; a parità vince chi ha pubblicato per ultimo;
  - data dell'ultimo articolo;
  - videogiochi distinti collegati agli articoli: due tag con lo stesso ID IGDB, o senza ID ma con lo stesso titolo, contano come un gioco solo.
- **Query leggere**: tre query al database, senza scorrere gli articoli uno a uno.
- **Cache di 24 ore**, con la stessa invalidazione delle Hub. A inizio anno i dati si ricalcolano da soli.

### 🔄 2. Cache delle Hub

- Quando cambia il collegamento di un tag a un gioco IGDB (ID o titolo), la cache delle Hub si svuota. Prima non succedeva, quindi anche le Hub esistenti ora si aggiornano prima dopo questa modifica.

### ✅ 3. Test

- Aggiunti 11 test (sezione 63 di `audit_test_suite.php`).
