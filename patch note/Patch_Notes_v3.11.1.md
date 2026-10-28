# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.11.1 (etichette senza trattino basso)

Su richiesta del CEO il trattino basso "_" al posto degli spazi non fa più parte dello stile del tema. Prima compariva in alcune etichette e in altre no, e il risultato era incoerente. Il prefisso "//" in stile codice resta.

---

### 🏷️ 1. Etichette fisse

| Prima | Ora |
|---|---|
| // RECENSIONE_IN_EVIDENZA (e anteprima, editoriale, notizia, articolo) | // RECENSIONE IN EVIDENZA |
| // Scheda_media | // Scheda media |
| // Leggi_comunicato → | // Leggi comunicato → |
| // ARTICOLI_CORRELATI, // LEGGI_QUI | // ARTICOLI CORRELATI, // LEGGI QUI |
| // CONDIVIDI_ARTICOLO | // CONDIVIDI ARTICOLO |
| // UNISCITI_ALLA_COMMUNITY, // GOOGLE_FEED | // UNISCITI ALLA COMMUNITY, // GOOGLE FEED |
| // TUTTI_GLI_ARTICOLI, // NESSUN_LINK_SOCIAL, // LEGGI_ARTICOLO (pagina autore) | con gli spazi |
| // NESSUN_RISULTATO (ricerca vuota) | // NESSUN RISULTATO |
| 404: NOT_FOUND, // PAGINA_NON_TROVATA, // TORNA_ALLA_HOME, // PAGINA_PRECEDENTE | con gli spazi |
| Pagine statiche: NTN_MEDIA_ENGINE // ID_WP_… | NTN MEDIA ENGINE // ID WP … |
| Barra in alto, testo predefinito: // NOME_SITO_V1.00 | // NOME SITO V1.00 |

### 🔤 2. Nomi trasformati in automatico

Il tema non sostituisce più gli spazi con "_" in:
- nomi di sviluppatori ed editori nella scheda tecnica della Hub, per esempio "// BANDAI NAMCO ACES INC.";
- nome del gioco nella testata degli articoli e nelle schede della Home;
- nome dell'autore nella pagina e nel box dell'autore;
- voci dei menu Articoli, Piattaforme e Piè di pagina, per esempio "// I Nostri Articoli".

Un effetto in più: i nomi lunghi ora vanno a capo tra una parola e l'altra, invece di spezzarsi a metà parola come "BANDAI_NAMCO_ACES / _INC.".

### 🛠️ 3. Testi di servizio

- Le descrizioni nel Personalizza di WordPress e il pulsante dei riassunti AI nell'editor usano le nuove forme ("// GOOGLE FEED", "// Estratto by AI").
- Il codice delle funzioni e i commenti tecnici non cambiano.

---

### ✅ 4. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: tutti i file modificati, nessun errore.
- **Suite di collaudo**: **327 test su 327 superati**.
  - Aggiornati 17.1, 20.6, 29.7, 29.11, 33.5 e 36.7, che controllavano la vecchia forma con "_". Ora controllano la nuova.
  - Nuova suite 40 con 2 controlli:
    - 40.1 cerca in tutti i file PHP dei due temi etichette con "_" e sostituzioni automatiche degli spazi: se qualcuna torna, il test fallisce;
    - 40.2 verifica che i nomi di studi e giochi restino leggibili.
- **Prova sul sito Local**:
  - **Pagine controllate**: Home, Hub di Ace Combat 8, recensione di Ace Combat 8, elenco dei comunicati e pagina 404.
  - **Esito**: nessun testo visibile contiene più "_".
- **Sincronizzazione 4 header (SemVer 3.11.1, PATCH)**.
- **Pacchetti**: ZIP 3.11.0 spostati in `archive/`, creati i tre ZIP 3.11.1 con `package_release.ps1`.

### ⚠️ Note

- **Testo della barra in alto**: se sul sito vero è stato scritto a mano nel Personalizza (per esempio "// NOME_SITO_V1.00"), resta quello salvato. Va corretto lì: *Aspetto → Personalizza*.
- **Mockup**: il mockup di riferimento (`docs/mockup/ntn-mockup-completo.html`) usa ancora "_" in alcune etichette. Va allineato se resta il riferimento per le prossime fasi.
