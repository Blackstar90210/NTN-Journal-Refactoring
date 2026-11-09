# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.22.0 (Data vera di caricamento dei trailer YouTube)

Nei dati per Google (JSON-LD) ogni trailer è un `VideoObject` con il campo `uploadDate`. Fino alla 4.21.0 il campo conteneva la data di pubblicazione dell'articolo, che non è la data in cui il video è stato caricato su YouTube. Ora, con una chiave API di YouTube, il plugin legge la data vera. Il tema non cambia.

### 🔧 Come funziona
- Nuovo servizio **YouTube (data dei trailer)** nella scheda **Servizi API**, con il suo campo chiave e il pulsante **Verifica**. La chiave è quella della *YouTube Data API v3* (Google Cloud Console, gratuita) ed è **facoltativa**.
- Il codice è in `inc/youtube-upload-date.php`. La data si legge **mai mentre la pagina si carica**: se non è ancora salvata, il plugin mette in coda un lavoro in secondo piano (una sola richiesta per video) e per intanto la pagina usa la data dell'articolo, come prima.
- Conservazione: data trovata per **1 anno**; video non trovato (privato o rimosso) **7 giorni**; errore di rete o chiave rifiutata **1 ora**, e il servizio mostra «Errore» con il motivo.
- La chiave viaggia in un'intestazione della richiesta, non nell'indirizzo. Non viene mai mostrata nella pagina.
- Senza chiave, o se YouTube non risponde, il sito si comporta **esattamente come nella 4.21.0**.
- Le 4 posizioni dei trailer (`schema-markup.php` ×3 e `seo-extras.php`) usano la stessa funzione `ntn_core_youtube_upload_date()`.
- Le date salvate sono nel registro delle memorie (scheda Extra) e **non** vengono cancellate da «Svuota tutta la memoria». La chiave entra nell'esportazione e nel ripristino del backup del plugin.

### ✅ Test
Nuova suite 94 (94.1-94.4): le 4 posizioni usano la funzione; senza chiave, con id non valido o con video non trovato resta la data dell'articolo; con chiave e data mancante viene messo in coda un solo lavoro; durata di conservazione per esito buono, mancante ed errore; chiave nell'intestazione e non nell'indirizzo; Verifica con chiave buona, rifiutata e mancante. Suite: 592 test, 0 falliti (anche con simulazione CRLF).

### Ordine di installazione
Plugin, poi tema parent, poi child.
