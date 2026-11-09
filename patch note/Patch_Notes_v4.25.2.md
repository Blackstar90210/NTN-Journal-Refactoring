# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.25.2 (Blocco vero dell'enumerazione degli autori)

Fino alla 4.25.1 il "blocco" dell'enumerazione autori in realtà rimandava `/?author=1` alla pagina `/author/nome-utente/`: chiunque poteva così scoprire i nomi utente del sito, compreso quello dell'amministratore. Questa patch chiude davvero quella porta, solo per i visitatori.

### 🔒 Cosa cambia per i visitatori (non loggati)
1. **`/?author=N` e `/?author_name=...` danno 404.** Niente più rimando alla pagina autore: chi prova i numeri uno dopo l'altro non ottiene nessun nome. Il rimando automatico di WordPress (`redirect_canonical`) viene saltato per queste richieste.
2. **`/author/admin/` dà 404.** La pagina autore dell'utente con login `admin` non esiste più per i visitatori. Le altre pagine autore (`/author/nome/`) restano come prima, perché servono ai lettori e alla SEO (E-E-A-T).
3. **Elenco utenti dell'API chiuso.** `/wp-json/wp/v2/users` (e le rotte figlie) spariscono per chi non ha fatto l'accesso. Il sito pubblico non le usa.
4. **"admin" fuori dalla sitemap utenti** di WordPress (`wp-sitemap-users-1.xml`).

### 👤 Cosa non cambia
- Chi ha fatto l'accesso vede tutto come prima (pagine autore, API utenti usata dall'editor a blocchi).
- Il pannello di amministrazione non è toccato.

### 🧭 Interpretazione scelta
- "Admin nascosto" è inteso come **utente con login `admin`**, non "qualunque amministratore": gli altri autori con ruolo di amministratore che scrivono articoli mantengono la loro pagina pubblica. L'elenco dei login nascosti è modificabile con il filtro `ntn_core_hidden_author_logins` (predefinito: `admin`).
- Per `?author=` e `?author_name=` la risposta è un **404 normale** del tema (non un rimando alla home), così i motori di ricerca non indicizzano nulla e non si rivela se l'utente esiste.
- La protezione vale solo per i visitatori: così redazione ed editor non perdono nulla.

### 📌 Note tecniche
- File modificato: `ntn-journal-core/inc/site-optimizations.php` (funzioni `ntn_core_hidden_author_logins`, `ntn_core_author_protection_applies`, `ntn_core_author_not_found`, `ntn_core_block_author_enumeration`, `ntn_core_hide_rest_users`, `ntn_core_sitemap_hide_authors`).
- Il controllo gira su `template_redirect` con priorità 1, prima del rimando di WordPress.
- Nessuna modifica al database.
- Se un plugin SEO (es. The SEO Framework) genera una sua sitemap autori, quella va controllata dalle sue impostazioni.

### ✅ Test
Nuova suite 99 (99.1-99.2): sette situazioni simulate (visitatore / loggato / pannello, `?author=1`, `?author_name`, `/author/admin/`, pagina autore normale) più API utenti e sitemap. Suite: 608 test, 0 falliti.

### Ordine di installazione
Plugin, poi tema parent, poi child.
