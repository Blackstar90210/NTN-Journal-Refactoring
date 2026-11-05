# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.4.0 (Hub Statistiche, Fase 4: Google e controlli finali)

Quarta fase dell'Hub "Statistiche" approvata dal CEO: la pagina è pronta per i motori di ricerca. Le Hub Piattaforme e Studi non cambiano.

---

### 🔍 1. Pagine successive fuori da Google

- La pagina Statistiche non elenca articoli, quindi `/page/2/` e seguenti ripetono la prima. Ora sono **noindex, follow**, con The SEO Framework e senza. Stessa regola delle pagine con pochi articoli.
- La **prima pagina** resta indicizzabile.
- La **canonical** non punta alla prima pagina: insieme al noindex darebbe a Google due segnali contrastanti. Sulle pagine in noindex The SEO Framework la toglie da solo.
- **Tolti `rel="prev"` e `rel="next"`** su questo tipo: la pagina non ha paginazione visibile.

### 📋 2. Controllati, senza modifiche

- **Soglia delle pagine sottili**: vale anche per questo tipo. Sotto la soglia la pagina è noindex e resta fuori dalla sitemap; raggiunta la soglia, torna da sola.
- **Sitemap**: le Hub Statistiche ci entrano come le altre, con la sola prima pagina.
- **Dati strutturati**: `CollectionPage` di The SEO Framework con il suo `BreadcrumbList`, come nelle altre Hub, e nessun `ItemList`.
- **Immagine per le condivisioni**: quella dell'articolo che la Hub sceglie come "in evidenza", anche se la pagina non lo mostra.

### ✅ 3. Test

- Aggiunti 6 test (sezione 66 di `audit_test_suite.php`).
