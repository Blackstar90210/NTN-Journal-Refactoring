# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.34.0 (Scheda Scanner IGDB riordinata e aggiornamento dei soli tag cambiati)

La 4.33.0 era la build Steam, annullata: questa versione riparte dalla 4.32.0.

### 📦 Cosa cambia nella scheda NTN Journal > Scanner IGDB
Nuovo ordine, dall'alto in basso:
1. **Attiva lo Scanner IGDB** (l'interruttore, come prima).
2. I tre riquadri **Seleziona Tag WP**, **Cerca nel Database IGDB** e **Articolo in evidenza (Opzionale)**, affiancati, con sotto il pulsante **Salva Associazione**.
3. **Scansiona i tag senza gioco**, con il nuovo pulsante **Nascondi risultati / Mostra risultati** per chiudere e riaprire la tabella. Il browser ricorda se l'avevi chiusa; una nuova scansione la riapre.
4. **Riepilogo Database**, con le tabelle "Articoli/Tag collegati" e il nuovo pulsante **Aggiorna solo i collegamenti con modifiche**.

### 🔄 "Aggiorna solo i collegamenti con modifiche"
- Chiede a IGDB, a blocchi di 500 giochi, solo `updated_at` e `checksum` dei giochi collegati ai tag (nessun dato pesante).
- Risincronizza, uno alla volta e con una pausa di un secondo, solo i tag il cui gioco è cambiato. Usa la stessa funzione del pulsante "Aggiorna" del singolo tag.
- **Criterio**: a ogni risincronizzazione il tag salva l'"impronta" del gioco (`_ntn_core_igdb_fingerprint`: id, `updated_at`, `checksum`). Un tag è cambiato se il `checksum` su IGDB è diverso da quello salvato. Per i tag sincronizzati prima della 4.34.0, che non hanno impronta, conta se `updated_at` su IGDB è più recente dell'ultima sincronizzazione; se non lo è, l'impronta attuale viene salvata come punto di partenza.
- I giochi non più presenti su IGDB vengono saltati e contati a parte.
- Prima di aggiornare chiede conferma con il numero dei tag da risincronizzare. Alla fine mostra quanti sono andati a buon fine e quali no.
- Solo pannello di amministrazione (permesso `manage_options`): il sito pubblico non chiama IGDB.
- Limite noto: IGDB cambia `updated_at` e `checksum` quando cambia la scheda del gioco. Se cambia solo un dato collegato (per esempio una copertina sostituita), il gioco può non risultare modificato: in quel caso resta il pulsante "Aggiorna" del singolo tag o "Aggiorna tutti".

### ✅ Test
- Nuova suite 110 (110.1-110.4): ordine delle sezioni, tabella apribile/chiudibile (aria-expanded, localStorage dentro try/catch), nuovo gestore protetto da nonce e permessi con richieste a blocchi di 500, impronta salvata dalla risincronizzazione e cancellata dallo scollegamento.
- Test 104.2 aggiornato: il file della scheda ha 2 richieste in più con il codice di sicurezza "batch".
- Suite: 648 test, 0 falliti. Versione 4.34.0 nei 4 header e nei controlli 24.x e 29.1.
- Prova sul sito Local: ordine della scheda, apertura/chiusura della tabella, controllo modifiche (187 tag cambiati su 262) e risincronizzazione dei soli tag cambiati, senza errori PHP.

### Ordine di installazione
Plugin, poi tema parent, poi child. (I temi sono solo ribattezzati 4.34.0.)
