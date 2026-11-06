# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.15.0 (Scanner dei tag IGDB)

Nuovo strumento nel pannello IGDB: cerca i giochi dei tag rimasti senza dati e prepara una lista da confermare, riga per riga o in massa. Comprende anche le correzioni dei dati per Google della 4.14.3.

---

### ✨ 1. Scansione

- Nel pannello IGDB c'è il nuovo riquadro "Scansiona i tag senza gioco".
- Il pulsante "Scansiona" cerca su IGDB, per nome esatto, i tag degli articoli che non hanno un gioco collegato. Lavora a piccoli lotti e **non modifica nulla**.
- La lista è divisa in tre gruppi:
  - **Sicuri**: un solo gioco, con il tag presente nel titolo dell'articolo. Sono già selezionati.
  - **Da scegliere**: più giochi con lo stesso nome (menu con l'anno), oppure un risultato meno certo.
  - **Nessun risultato**: si può cercare un nome diverso direttamente dalla riga.

### ✅ 2. Conferma

- Ogni riga ha **Conferma**, **Salta** e **Non è un gioco**. "Conferma selezionati" approva tutte le righe spuntate.
- La conferma usa lo stesso salvataggio del pannello: collega il tag a IGDB e **crea la pagina Hub pubblica**, che diventa la destinazione del tag.
- "Non è un gioco" viene ricordato e il tag non torna in lista. "Rimetti in lista i tag esclusi" annulla questa scelta. Steam, Meta, Amazon, Playground e BULKHEAD non vengono mai proposti.
- La scansione si può rilanciare quando serve: propone solo i tag ancora da decidere.

### 🛠️ 3. Sotto il cofano

- Il salvataggio del pannello è ora la funzione `ntn_igdb_link_tag_to_game()`, usata anche dallo scanner. Per il pannello non cambia nulla, salvo che ora segnala un errore se la Hub non si può creare.
- Tutte le richieste controllano nonce e permesso `manage_options`.

### ⚠️ 4. Ordine di aggiornamento

- Aggiorna il plugin. Il tema genitore e il figlio cambiano solo il numero di versione.
- Prima di una conferma in massa, controlla la lista: ogni conferma pubblica una nuova pagina che Google potrà leggere.

### ✅ 5. Test

- Nuova sezione 80 (8 controlli).

**Version:** 4.15.0 · **Authors:** Luca Maiolini & Divisione_WP
