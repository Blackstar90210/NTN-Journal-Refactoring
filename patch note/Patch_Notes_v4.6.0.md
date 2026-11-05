# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.6.0 (Hub Statistiche: apertura e schede)

Variante scelta dal CEO per la sezione degli articoli dell'Hub Statistiche. Le altre Hub non cambiano.

---

### 📰 1. Nuova sezione "Tutti gli articoli"

- **Prima pagina**: l'articolo più recente in grande, con immagine, titolo molto grande, autore, data, minuti e riassunto. È sempre l'ultimo pubblicato, non la scelta "in evidenza".
- **Sotto**: gli altri articoli nelle stesse schede degli archivi del sito, a linee condivise, su tre colonne (due su tablet, una al telefono).
- **Dalla pagina 2 in poi**: solo le schede, senza apertura. La paginazione resta quella di prima.
- **Immagini**: copertina dell'articolo con il suo testo alternativo. Se manca, l'apertura mostra la copertina a punti e le schede l'immagine predefinita del tema. Solo l'immagine dell'apertura si carica subito, le altre quando servono.
- **Categoria senza articoli**: compare il messaggio "Nessun articolo".

### 🧩 2. Altre Hub e Google

- L'Hub Statistiche non usa più `template-parts/hub-article-feed.php`, che resta per le altre Hub di categoria.
- Canonical, `rel="prev"` e `rel="next"`, dati strutturati e soglia delle pagine sottili restano quelli della 4.5.0.

### ✅ 3. Test

- La sezione 65 di `audit_test_suite.php` controlla apertura, schede, paginazione, copertina mancante ed escaping generando davvero la pagina.
