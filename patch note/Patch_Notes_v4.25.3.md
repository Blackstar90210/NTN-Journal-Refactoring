# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.25.3 (Campi delle recensioni dichiarati e regola dei prefissi)

Due punti minori rimasti dall'audit. Nessun cambiamento visibile ai lettori e nessun dato spostato.

### 🔧 Campi delle recensioni dichiarati a WordPress
I cinque campi del box recensione (`rating`, `pros`, `cons`, `verdict`, `reviewed_item`) erano salvati senza prefisso e WordPress non li conosceva. Ora sono dichiarati con `register_post_meta` in `inc/review-meta.php`, **con i nomi di sempre**: nessuna rinomina e nessuna migrazione, i dati restano dove sono.

| Campo | Tipo | Controllo al salvataggio |
|---|---|---|
| `rating` | testo (come oggi) | numero da 1 a 5; sopra 5 diventa 5; vuoto, zero, negativo o non numerico diventano "" (nessun voto), cioè come il sito e i dati per Google lo leggevano già |
| `pros`, `cons` | testo su più righe | tolto l'HTML; i vecchi valori salvati come elenco restano elenco (il ripristino da backup non li perde) |
| `verdict` | testo su più righe | tolto l'HTML |
| `reviewed_item` | testo | tolto l'HTML; un elenco viene rifiutato |

- Valgono per gli articoli (`post`), dove sta il box recensione. Un valore per articolo (`single`).
- Può modificarli solo chi può modificare l'articolo (`edit_post`).
- **Non esposti nell'API** (`show_in_rest` spento): nessuna parte del sito li legge da lì, quindi nulla cambia per l'editor, il pannello o i lettori.
- Box recensione, dati per Google (JSON-LD), "Recensioni senza voto", pannello Recensioni, backup e IGDB leggono e scrivono gli stessi campi di prima.

### 📐 Regola dei prefissi (solo regola, nessuna rinomina)
Scritta in `AGENTS.md`, sezione "Regole di codice":
- nel plugin il codice **nuovo** usa il prefisso `ntn_core_`;
- il codice esistente con `ntn_` o `ntn_journal_` **non si rinomina**;
- `ntn_journal_` resta per quanto è condiviso con il tema.
Le nuove funzioni di questa patch la seguono già (`ntn_core_register_review_meta`, `ntn_core_sanitize_review_*`).

### 🩹 Correzione della suite
Nella 4.25.2 la versione era stata alzata dopo l'ultima esecuzione della suite, e i controlli 24.x e 29.1 si aspettavano ancora 4.25.1: su `main` la suite segnava 6 fallimenti (solo controlli sul numero di versione, nessun effetto sul sito). Ora si aspettano 4.25.3.

### ✅ Test
Nuova suite 100 (100.1-100.5), con prove reali in un processo PHP separato: la registrazione dei 5 campi, il controllo del voto con 14 valori (compresi quelli del ripristino da backup), i valori che il box salva oggi rimasti identici, i nomi invariati nelle letture e la regola in AGENTS.md. Suite: 613 test, 0 falliti.

Prova sul Local: dati per Google di 3 recensioni identici con e senza la patch; recensione salvata con voto, pro, contro, verdetto e gioco rimasti identici.

### Ordine di installazione
Plugin, poi tema parent, poi child.
