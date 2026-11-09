# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.25.1 (Correzioni minori dell'audit)

Otto piccole correzioni emerse dall'audit del codice. Nessuna funzione nuova e nessun cambiamento visibile ai lettori: il sito si comporta come prima, ma alcune cose ora si aggiornano quando devono.

### 🔧 Correzioni
1. **Permalink rigenerabili.** Salvando la scheda Extra con un valore diverso per i link ai videogiochi, il plugin cancellava un vecchio segnale (`ntn_rewrite_rules_flushed_v2`) che nessuno leggeva più: le regole degli indirizzi non venivano rigenerate. Ora le regole hanno un numero di versione (`ntn_rewrite_rules_version`) e si rigenerano da sole quando cambia, all'attivazione del plugin, al salvataggio di Extra e dopo un ripristino di backup. Al primo caricamento dopo l'aggiornamento il sito le rigenera una volta.
2. **Script IGDB dell'editor aggiornato.** `admin-igdb.js` era registrato con la versione fissa `1.0.0`: dopo un aggiornamento il browser poteva tenere la copia vecchia. Ora usa la versione del plugin.
3. **Script dei disegni a punti delle Hub.** `dot-art.js` sta nel tema parent ma prendeva la versione del tema attivo (il child). Ora prende quella del parent, come gli altri file del parent.
4. **Tolta un'opzione fantasma.** `ntn_journal_enable_game_engine_links` veniva letta ma nessuna pagina la salvava, quindi valeva sempre "sì". Tolta la lettura: i nomi dei motori grafici restano linkati al loro archivio.
5. **SEO di base senza ripiego sul tema.** Se l'impostazione SEO di base non era mai stata salvata, il plugin ripiegava su un'impostazione del tema che non esiste più. Ora il valore predefinito è direttamente "sì" (stesso risultato di prima).
6. **Una sola domanda "c'è un altro plugin SEO?".** La condizione Yoast / Rank Math / The SEO Framework era copiata tre volte. Ora è una sola funzione, `ntn_core_other_seo_plugin_active()`, con lo stesso risultato.
7. **Pannello Piattaforme aggiornato subito.** Il menu a tendina delle piattaforme resta in memoria per un'ora e si rinnovava solo se cambiava il numero di voci: rinominare o spostare una voce non si vedeva subito. Ora, quando si salva un menu, la sua memoria (`ntn_pdrop_`) viene svuotata.
8. **Tailwind legge anche il plugin.** Il plugin stampa HTML con classi Tailwind (percorso, indice, box), ma la compilazione leggeva solo i due temi. Aggiunto il plugin alle fonti di `build/input.css` e ricompilato `style-tailwind-output.css` (+201 byte, nessuna classe tolta).

### 📌 Note
- Nessuna modifica al database oltre al nuovo segnale `ntn_rewrite_rules_version`; i vecchi `ntn_rewrite_rules_flushed_v2` e `_v3` vengono cancellati alla prima rigenerazione.
- Restano fuori da questa patch, come deciso: il blocco dell'elenco degli autori (attende la decisione di Luca), `register_post_meta`, `theme.json` v3, le misure delle immagini e i prefissi.

### ✅ Test
Nuova suite 98 (98.1-98.8), un test per ogni correzione, con prove reali dove possibile: rigenerazione delle regole con segnali finti, rilevamento dei plugin SEO in cinque situazioni, svuotamento della memoria del menu con un database finto. Suite: 606 test, 0 falliti.

### Ordine di installazione
Plugin, poi tema parent, poi child.
