# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.18.0 (NTN Journal Funzioni, schermata unica)

La schermata **NTN Journal Funzioni** è stata ridisegnata con l'aspetto Nothing OS del mockup (versione 27) e ora raccoglie tutto in un posto solo, comprese le **Hub di categoria** e la **Redazione**, che prima si gestivano da schermate diverse di WordPress. Approvata dal CEO.

---

### 🎨 1. Aspetto

- Stile Nothing OS: nero, bianco e rosso, angoli vivi, nessuna ombra, sempre chiaro.
- Caratteri **Inter** e **JetBrains Mono** inclusi nel plugin (circa 110 KB): la schermata non dipende dal tema attivo né da Google Fonts.
- Lo stile e i caratteri si caricano **solo** in questa schermata: il resto della bacheca di WordPress non cambia.
- In alto il titolo "NTN Journal Funzioni", la versione del plugin e le schede: Dashboard, Hub, Redazione, Recensioni, Comunicati, Scanner IGDB, Breaking News, Redirect, Servizi API, SEO, Extra, Backup.

### 📊 2. Dashboard

- Una casella per ogni funzione con lo **stato reale** del sito e il link "Apri":
  - Hub accese e quante categorie da controllare;
  - Redazione: firme e gruppi;
  - ultima recensione e ultimo comunicato;
  - stato delle chiavi API (Attiva / Errore / Mancante);
  - giochi collegati da IGDB, Breaking News, Redirect attivi, SEO.

### 🧭 3. Nuova scheda Hub

- Elenco delle Hub accese, con **Modifica**, **Spegni** e **Vedi**.
- Nella modifica: tipo, titolo, introduzione, illustrazione, articolo in evidenza (tra gli ultimi 30), studi da escludere (Studi), titoli dei marchi (Console).
- Riquadro **"Trasforma una categoria in Hub"**: scegli la categoria, scegli il tipo (con la spiegazione di ciascuno), "Accendi la Hub".
- **Controllo copertura**: segnala i marchi senza piattaforme, le piattaforme senza articoli e i giochi senza sviluppatore (al massimo 20 voci).
- Pulsante "Svuota la memoria delle Hub".
- Nella schermata della categoria di WordPress resta solo un rimando alla scheda Hub.

### 👥 4. Nuova scheda Redazione

- I gruppi (per esempio Fondatori, Redazione, Collaboratori) si **riordinano** con le frecce ↑ ↓ o trascinandoli, si rinominano, si aggiungono e si tolgono.
- Le persone si aggiungono da un elenco degli utenti che possono scrivere articoli; ognuna sta in **un solo gruppo**.
- Ordine dentro i gruppi: **alfabetico**, **scelto a mano** o **per numero di articoli**.
- Tolti la tendina nel profilo utente e il riquadro nella pagina: tutto si fa da qui.
- Se manca la pagina Redazione, la scheda chiede di crearla.

### 📚 5. Recensioni e Comunicati

- **20 per pagina** con la ricerca, invece di caricarne fino a 500 insieme.
- Recensioni: filtri per giudizio (Must Play, Consigliato, Con riserva, Bocciato) e voto a LED.
- Ogni riga si apre con "Apri", anche da tastiera: pro, contro e verdetto per le recensioni; fonte, materiale stampa, embargo e "non indicizzato" per i comunicati.
- Comunicati: la nota in fondo a ogni comunicato si imposta in cima alla scheda.

### 💾 6. Backup

- Il backup ora salva e ripristina anche la **Redazione**: gruppi, ordine e persone (riconosciute dal nome utente).
- Le impostazioni delle Hub di categoria erano già incluse e lo restano.

### 🔁 7. Compatibilità

- I dati restano dove erano: nessuna impostazione di Hub o Redazione va persa con l'aggiornamento.
- Tornando a una versione precedente, i dati restano validi; si perde solo il nuovo ordine "per numero di articoli" o "scelto a mano".

---

### ✅ 8. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su tutti i file modificati; `node --check` su `admin-screen.js`.
- **Suite di collaudo**: **371 test su 371 superati**.
  - Nuova suite 49 con 6 controlli.
  - Aggiornati i test 43.1 (Hub) e 47.2 (Redazione).
- **Prova sul sito Local**, solo in lettura, senza premere salvataggi:
  - le 12 schede si aprono senza errori PHP, ognuna evidenziata nel menu;
  - caratteri Inter e JetBrains Mono caricati dal plugin;
  - la Dashboard mostra 2 Hub, 3 gruppi in Redazione, 3 servizi API attivi;
  - la schermata della categoria mostra il rimando alla scheda Hub;
  - il profilo utente non ha più la tendina della Redazione.
- **Sincronizzazione 4 header (SemVer 3.18.0, MINOR)**.
- **Pacchetti**: ZIP 3.17.1 spostati in `archive/`, creati i tre ZIP 3.18.0 con `package_release.ps1`.

### ⚠️ Note

- Prova almeno un salvataggio nelle schede Hub e Redazione prima di aggiornare il sito vero.
