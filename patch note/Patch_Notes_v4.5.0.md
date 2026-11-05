# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.5.0 (Hub Statistiche: tornano gli articoli)

Richiesta del CEO dopo aver visto la pagina di prova: l'Hub Statistiche deve elencare gli articoli. Corregge la scelta del mockup, che lasciava vuoto il corpo della pagina.

---

### 📰 1. Elenco degli articoli

- **Sotto la fascia con i quattro numeri** ora c'è "// Tutti gli articoli", uguale alle altre Hub: data, gioco collegato, titolo, minuti di lettura e paginazione. Sono compresi gli articoli delle sottocategorie.
- **Categoria senza articoli**: compare il messaggio "Nessun articolo".
- **Un solo elenco**: Hub di categoria e Hub Statistiche usano lo stesso pezzo di template (`template-parts/hub-article-feed.php`). Le Hub esistenti restano identiche.

### 🔍 2. Google

- **Tolto il `noindex` introdotto nella 4.4.0**: ora `/page/2/` e seguenti hanno contenuti propri e si comportano come nelle altre Hub, con canonical propria e `rel="prev"` e `rel="next"`.
- **Soglia delle pagine sottili**: invariata, vale anche per questo tipo.

### ✅ 3. Test

- Aggiunti 2 test sull'elenco e sullo stato vuoto (sezione 65). La sezione 66 è stata adattata alla nuova regola: ora ha 3 test invece di 6.
