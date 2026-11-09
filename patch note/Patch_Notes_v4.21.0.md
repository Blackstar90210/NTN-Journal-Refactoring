# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.21.0 (Riparazione delle miniature dei PNG trasparenti)

La 4.20.1 ha corretto i nuovi caricamenti: le miniature dei PNG trasparenti restano PNG. Le immagini caricate prima hanno ancora miniature JPG con lo sfondo pieno. Questa versione aggiunge lo strumento per sistemarle, nel plugin (`inc/png-thumbnail-repair.php`). Il tema non cambia.

### 🧰 Dove si trova
Pannello NTN Journal → scheda **Extra** → riquadro **Miniature dei PNG trasparenti**. Solo gli amministratori lo vedono e lo possono usare.

### 🔧 I tre passi
1. **Controlla** (non cambia nulla). Passa tutte le immagini PNG a gruppi, con una barra di avanzamento, e trova quelle davvero trasparenti con miniature JPG. Mostra quante sono e i nomi; le immagini che non si possono leggere vengono saltate e segnalate.
2. **Ripara**. Rifà le miniature delle immagini trovate, 5 alla volta, con la barra di avanzamento. Si può interrompere e riprende da dove si era fermato. Le nuove miniature sono PNG trasparenti. L'immagine originale non viene mai toccata. Prima di rifare, il plugin si segna i nomi dei vecchi file JPG. Se il server non ha abbastanza memoria per un'immagine, la salta e la segnala. Se la correzione 4.20.1 non è attiva (plugin e temi con versioni diverse), il passo si rifiuta.
3. **Pulizia dei vecchi JPG, in due tempi**:
   - **3a. Sposta in quarantena.** Ogni vecchio JPG viene spostato in `uploads/ntn-quarantena/` (stessa struttura anno/mese) solo se il suo nome non compare da nessuna parte: articoli e pagine, metadati, impostazioni e widget, termini, utenti, commenti. Nel dubbio il file resta al suo posto e viene segnalato. La cartella è protetta da `index.php` e `.htaccess`. Il pulsante **Ripristina** riporta tutti i file al loro posto.
   - **3b. Elimina definitivamente.** Pulsante separato, attivo solo dopo almeno **7 giorni** di quarantena e solo spuntando **"Ho fatto un backup della cartella uploads"**. Il controllo è fatto anche dal server.

### ✅ Test
Nuova suite 93 (93.1-93.5): azioni protette da nonce e permessi, gruppi da 5, originale mai cancellato, e prove con file e database finti: un JPG citato in un articolo o in un widget **non** viene spostato, solo i file delle immagini riparate entrano in quarantena, l'eliminazione richiede 7 giorni e il backup, Ripristina riporta tutto.

Provato sul Local con le 8 immagini trasparenti: trovate 8, riparate 8 (tutte le miniature PNG), 36 vecchi JPG in quarantena e 2 lasciati perché ancora citati (uno da un articolo di prova), Ripristina completo, eliminazione rifiutata prima dei 7 giorni e senza backup.

### Consiglio d'uso
Prima installa la versione su tutti e tre i pacchetti, poi esegui Controlla e Ripara. Per la pulizia: sposta in quarantena, controlla il sito per qualche giorno e, solo dopo un backup, elimina.

### Ordine di installazione
Plugin, poi tema parent, poi child.
