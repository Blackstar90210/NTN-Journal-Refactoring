# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.17.1 (correzioni a NTN Journal Funzioni)

Correzioni emerse dalla verifica della schermata **NTN Journal Funzioni** del 18.10.2026, approvate punto per punto dal CEO. In più, le iniziali a punti al posto della sagoma grigia di WordPress.

---

### 🧹 1. Caselle e spiegazioni

- **"Abilita Integrazione IGDB"** (Servizi API): tolta. Veniva salvata ma nessuna parte del sito la leggeva. È stata tolta anche dal backup; i vecchi file di backup restano compatibili e quella voce viene ignorata.
- **Impostazione dei tag nascosta** (Extra): tolta. A ogni salvataggio veniva scritta come "no", ma non aveva una casella e nessuno la usava.
- **"Link Videogioco (Hub Pages)"** (Extra): riscritta la spiegazione. Ora dice cosa fa (i link dei tag dei giochi portano alla Hub del gioco) e cosa succede se la spegni (i link tornano all'archivio del tag; le Hub esistenti restano online).
- **"Slot Commenti"** (Extra): ora nasconde i commenti anche nelle pagine statiche, non solo negli articoli.

### 🧠 2. Memoria del sito

- Il pulsante **"Svuota tutta la memoria"** (Extra), che prima svuotava solo le Hub dei videogiochi, ora svuota anche:
  - numeri delle Hub;
  - Hub di categoria;
  - pagina Redazione;
  - Home;
  - numeri degli autori.

### 🛑 3. Freno d'emergenza dei dati per Google

- L'interruttore della scheda SEO si chiama ora **"Schema Markup: tutti i dati per Google"** (decisione del CEO).
- Spento, il plugin non stampa **nessun** dato strutturato: articoli, recensioni, video, FAQ, percorso, sito, Hub dei giochi, Hub di categoria e pagina Redazione.
- Serve se si adotta un plugin SEO più completo.

### 🔐 4. Sicurezza

- **Scanner IGDB e ricerca dei giochi nell'editor**: i nomi di tag e giochi (e i messaggi di errore di IGDB) si mostrano sempre come testo, mai come codice. Un nome "costruito ad arte" non può più eseguire codice nella schermata dell'amministratore.
- **Recensioni e Comunicati**: i link "Modifica" e "Visualizza sul sito" passano dal controllo standard di WordPress e si aprono in una nuova scheda in sicurezza.
- **Salvataggi**:
  - si fanno prima di disegnare la pagina, poi la pagina si ricarica da sola, così aggiornandola il browser non rimanda il modulo;
  - i dati in arrivo vengono puliti;
  - i moduli delle altre schede (Redirect, Backup) non vengono toccati.

### 🔑 5. Chiavi API (Servizi API)

- Le chiavi **non vengono più scritte nella pagina**.
  - Il campo resta vuoto con la scritta "Chiave salvata: scrivi qui per cambiarla".
  - Lasciato vuoto, la chiave non cambia.
  - "Cancella" la toglie.
- Ogni servizio (Google Gemini, Groq, IGDB) mostra uno stato:
  - **Attiva**: l'ultima richiesta è andata a buon fine;
  - **Salvata, non verificata**: la chiave c'è ma non è ancora stata usata;
  - **Errore**: con il motivo (chiave non valida o scaduta, limite di richieste, servizio che non risponde);
  - **Mancante**.
- Lo stato si aggiorna da solo quando il sito usa davvero il servizio (riassunti con l'IA, ricerca IGDB). Accanto c'è la data dell'ultimo controllo.
- **"Verifica"** fa una richiesta di prova **gratuita**:
  - Gemini e Groq: l'elenco dei modelli disponibili, che non consuma crediti;
  - IGDB: un nuovo accesso da Twitch.
- Nuovo modulo del plugin: `inc/api-status.php`.

### 🧭 6. Menu e titolo

- Il menu a sinistra evidenzia la voce della scheda aperta, non più sempre "Dashboard".
- Il titolo della finestra mostra il nome della scheda, per esempio "Servizi API · NTN Journal".

### 👤 7. Iniziali a punti al posto della sagoma grigia (tema)

- Nella firma degli articoli, nella pagina autore e nella pagina Redazione:
  - se l'autore ha una foto, si vede la foto;
  - se non ce l'ha, al posto della sagoma grigia di WordPress compaiono le sue **iniziali a matrice di punti**.
- Nuova funzione del tema `ntn_journal_author_avatar()`.

---

### ✅ 8. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su tutti i file modificati; `node --check` su `admin-igdb.js`.
- **Suite di collaudo**: **365 test su 365 superati**.
  - Nuova suite 48 con 7 controlli.
  - Aggiornato il test 21.9 sul backup.
  - Aggiunto al banco di prova il sostituto di `get_avatar_url`.
- **Prova sul sito Local**, solo in lettura, senza premere salvataggi né "Verifica":
  - le 10 schede si aprono senza errori PHP, ognuna con il proprio titolo e la propria voce evidenziata nel menu;
  - in Servizi API i quattro campi sono vuoti e i tre servizi risultano "Salvata, non verificata";
  - nella pagina Redazione l'utente senza foto mostra la "L" a punti.
- **Sincronizzazione 4 header (SemVer 3.17.1, PATCH)**.
- **Pacchetti**: ZIP 3.17.0 spostati in `archive/`, creati i tre ZIP 3.17.1 con `package_release.ps1`.

### ⚠️ Note

- Dopo l'aggiornamento, in Servizi API premi **"Verifica"** su ogni servizio per vedere subito se le chiavi sono "Attive".
- Prossimo passo: **3.18.0**, la schermata unica con le schede "Hub" e "Redazione", da disegnare prima nel mockup.
