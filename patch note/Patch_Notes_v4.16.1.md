# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.16.1 (Via i testi di esempio dal riquadro della recensione)

Nel riquadro finale della recensione comparivano Pro, Contro e Verdetto di esempio ("Design minimale iconico ad alto contrasto", "Assenza del caricabatterie nella confezione retail", "Un prodotto estremamente convincente…") quando l'articolo non li aveva scritti. Succedeva a tutte le recensioni con un voto ma senza Pro, Contro o Verdetto, quindi anche a quelle a cui il voto è stato dato con lo strumento della 4.16.0.

### 🛠️ Correzione

- `ntn-journal/template-parts/review-original-box.php`: tolti i testi di esempio. Ora **Pro**, **Contro** e **Verdetto** compaiono solo se scritti nella recensione.
- Se c'è solo uno tra Pro e Contro, la sua colonna occupa tutta la larghezza; se non c'è né l'uno né l'altro, il blocco non compare.
- Se manca il Verdetto, la sezione «Il verdetto» non compare.
- Il voto, il giudizio e i LED restano invariati.

### Ordine di installazione
Come sempre: plugin, poi tema parent, poi child (il cambiamento vero è nel parent).
