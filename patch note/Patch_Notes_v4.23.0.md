# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.23.0 (Rapporto di sola lettura sulle immagini)

Nuovo riquadro **Rapporto sulle immagini** nella scheda **Extra** del pannello (`inc/image-report.php`). Serve a capire come stanno le immagini del sito prima di decidere se pulire altro. **Non cambia, non sposta e non elimina nulla**: legge soltanto, e salva solo il rapporto stesso. Il tema non cambia.

### 🔧 I due controlli
1. **Analizza** (veloce). Passa tutte le immagini a gruppi di 25, con barra di avanzamento e pulsante Interrompi. Mostra: il peso totale su disco (con le miniature) e quello dei soli originali; quanti originali pesano più di 1,5 MB, con l'elenco dei 30 più pesanti; quante immagini non hanno il testo alternativo (alcune immagini decorative possono non averlo); i file che risultano nel database ma mancano sul disco. Ogni voce è un link alla scheda dell'immagine.
2. **Cerca immagini non usate** (più lento, può richiedere alcuni minuti). Per ogni immagine controlla se è allegata a un contenuto, se è un'immagine in evidenza e se il nome del file compare in articoli, pagine, metadati, impostazioni, termini o profili utente. Il controllo è **prudente**: basta il nome del file senza estensione dentro un testo per considerarla usata, e se una domanda al database non riesce l'immagine conta come usata. Mostra quante non risultano usate, quanto spazio occupano e le 30 più pesanti.

### 📌 Note
- Il rapporto resta salvato e si rilegge senza rifare il controllo; ogni elenco tiene al massimo 200 voci.
- «Non usata» è un'indicazione, non una garanzia: prima di eliminare un'immagine controllala a mano e fai un backup.
- Solo gli amministratori lo vedono; ogni richiesta ha nonce e controllo dei permessi.

### ✅ Test
Nuova suite 95 (95.1-95.3): azioni protette, nessuna scrittura o cancellazione nel codice, elenchi con tetto, e prove con database finto: un'immagine citata in un articolo, in un'impostazione, come immagine in evidenza o allegata a un contenuto risulta usata; con una domanda al database che fallisce conta come usata. Suite: 595 test, 0 falliti (anche con simulazione CRLF).

### Ordine di installazione
Plugin, poi tema parent, poi child.
