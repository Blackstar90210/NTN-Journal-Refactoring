# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.16.0 (Voti alle vecchie recensioni e recensioni senza gioco)

Il Rich Results Test di Google non riconosceva molte vecchie recensioni (per esempio Pokémon Scarlatto e Violetto). Il motivo: Google considera una recensione solo se ha un **voto**, e il voto deve comparire anche nella pagina. Solo 18 recensioni su 231 avevano il voto.

---

### ✨ 1. Voti alle vecchie recensioni (Amministrazione → Recensioni)

In cima alla scheda **Recensioni** c'è il nuovo elenco **"Recensioni senza voto"**. Per ognuna mostra il giudizio già scelto nell'articolo (tag o categoria) e il voto corrispondente, con la scala che il sito usa già:

| Giudizio | Voto |
|---|---|
| Must Play (e Must Buy, Capolavoro) | 5 |
| Consigliato | 4 |
| Consigliato con riserva | 3 |
| Bocciato | 2 |

- Il voto proposto si può cambiare prima di confermare.
- **Conferma selezionati** scrive i voti scelti nella pagina; **Conferma tutte le N proposte** li scrive a tutte le recensioni che hanno un giudizio.
- Non viene scritto nulla senza conferma, un voto già presente non viene mai sostituito, e se due giudizi in contrasto sono presenti non si propone nulla.
- Dopo la conferma il voto compare nella pagina e le recensioni con gioco collegato diventano `Review` per Google.

### 🛠️ 2. Recensioni senza gioco collegato

Prima, senza gioco, la pagina di una recensione era un `WebPage` generico, senza autore né data. Ora resta un **articolo** con titolo, autore e date, senza VideoGame. Il `Review` con voto nasce quando il gioco è collegato (Scanner IGDB). Gli articoli normali senza gioco non cambiano.

### 🐞 3. Niente più testi di esempio nel riquadro del voto

- Quando una recensione non aveva pro, contro o verdetto, il riquadro del voto mostrava testi di esempio rimasti dal mockup ("Design minimale iconico…", "Assenza del caricabatterie…", "Un prodotto estremamente convincente…").
- Ora mostra solo quello che è scritto nella recensione: senza pro e contro la loro sezione non compare, con una sola colonna occupa tutta la larghezza, e senza verdetto il verdetto non compare. Il voto e il giudizio restano sempre.

### ⚠️ Dopo l'aggiornamento

- Aggiorna il plugin e il tema genitore (riquadro del voto); il figlio cambia solo il numero di versione. Svuota la cache.
- Apri Recensioni, controlla le proposte e conferma.
- La scheda di un gioco (Hub) continua a non avere un risultato speciale su Google: `VideoGame` da solo non ne dà uno, è una regola di Google.

### ✅ Test

Sezione 82 della suite (6 prove nuove). Totale 553 prove.
