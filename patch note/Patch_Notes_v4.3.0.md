# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.3.0 (Hub Statistiche, Fase 3: la pagina)

Terza fase dell'Hub "Statistiche" approvata dal CEO: le Hub di questo tipo hanno ora la loro pagina, fedele al mockup.

---

### 📊 1. Nuova pagina (`category-hub-statistiche.php`)

- **Fascia di apertura** uguale alle altre Hub: briciole, etichette "Hub di sezione" e "Statistiche", titolo e testo di presentazione, se c'è. Il disegno a punti compare solo se l'illustrazione è impostata.
- **Quattro numeri**, su quattro colonne da 768 pixel e su due al telefono:
  - **Articoli**, con quanti sono dell'anno in corso;
  - **Autore più prolifico** dell'anno, con il link alla sua pagina; se nell'anno non ci sono articoli, un trattino e "nessun articolo nel…";
  - **Ultimo articolo**, con "oggi", "ieri" o "N giorni fa", calcolati a ogni visita;
  - **Videogiochi trattati**, collegati a IGDB.
- **Il corpo della pagina resta vuoto**: nessun elenco di articoli e nessuna paginazione.

### 🎨 2. Stile

- Poche regole nuove, con i colori della fascia: valgono in chiaro e in scuro.
- Compilando il CSS sono uscite quattro classi Tailwind non usate da nessun modello: nessuna pagina cambia.

### ⚙️ 3. Pannello e test

- La spiegazione del tipo nel pannello Hub ora descrive la pagina vera.
- Le Hub Piattaforme e Studi restano sul loro modello e non cambiano.
- Aggiunti 8 test (sezione 65 di `audit_test_suite.php`).
