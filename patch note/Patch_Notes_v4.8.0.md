# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.8.0 (Hub Statistiche: griglia di 3 colonne per 3 righe)

Richiesta del CEO: le schede dell'Hub Statistiche tornano alle misure dell'anteprima approvata, in una griglia piena di 3 colonne per 3 righe. Le altre Hub non cambiano.

---

### 🔲 1. Griglia

- **Schede**: tre colonne da 1024 pixel in su, due da 600, una al telefono. Sono le schede degli archivi del sito, con immagine larga quanto la colonna. Tolte le cinque colonne e lo spazio interno ridotto della 4.7.0.
- **Il resto della 4.7.0 non cambia**: contatori in riga, testata a tutta larghezza, spazio verso gli articoli, titolo.

### 📄 2. Paginazione

- **Prima pagina**: apertura e 9 schede, cioè 10 articoli.
- **Pagine successive**: 9 schede, così ogni pagina completa ha 3 righe piene. L'ultima può averne meno.
- Numero di pagine, pulsanti, `rel="prev"`, `rel="next"` e canonical restano corretti, senza articoli ripetuti o saltati. Vale solo per le Hub di tipo Statistiche.

### ✅ 3. Test

- Aggiunti 4 test sulla paginazione (sezione 67 di `audit_test_suite.php`) e aggiornati quelli sullo stile.
