# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.17.0 (pagina Redazione: Hub degli autori a gruppi)

Nuova pagina "Redazione", l'Hub degli autori: un indice di tutte le firme, divise in gruppi scelti dal CEO e con i Fondatori in cima. Riferimento visivo: mockup versione 25, pagina "Redazione".

---

### 👥 1. Come si attiva

1. **Pagine → Aggiungi pagina**: titolo "Redazione", indirizzo `/redazione/`. Nelle impostazioni della pagina, alla voce **Modello**, scegli **"Redazione"**.
2. Nel riquadro **"Gruppi della redazione (NTN Core)"**, nella colonna di destra, scrivi i gruppi **uno per riga**, nell'ordine in cui devono comparire. Per esempio:
   ```
   Fondatori
   Redazione
   Collaboratori
   ```
3. Il testo di presentazione sotto il titolo è il **riassunto** della pagina oppure, se manca, l'inizio del testo della pagina.
4. In **Utenti → profilo** di ogni persona, nella sezione **"Pagina Redazione (NTN Core)"**, scegli il gruppo dalla tendina **"Gruppo nella pagina Redazione"**. La prima voce, **"Non mostrare"**, lascia fuori la persona.
   - Solo chi può modificare gli utenti (gli amministratori) vede e cambia la tendina.
5. Puoi aggiungere la pagina al menu come qualsiasi altra pagina.

### 🧭 2. Com'è fatta (tema, `page-redazione.php`)

- **Fascia di apertura** come le altre Hub:
  - percorso di navigazione;
  - etichette "Chi siamo" e il numero di firme in rosso;
  - titolo grande e testo di presentazione;
  - numeri: firme e data dell'ultimo articolo.
- **"// Le firme"**: un elenco verticale diviso nei gruppi, nell'ordine scritto nella pagina. Ogni gruppo ha la sua intestazione in Mono, per esempio "// Fondatori · 2 firme". Un gruppo senza persone non compare.
- **Dentro ogni gruppo** l'ordine è alfabetico. Ogni riga ha:
  - la foto del profilo oppure, se manca, le iniziali a matrice di punti, come nella firma degli articoli;
  - il nome, che porta alla pagina dell'autore;
  - l'ultimo articolo con la data;
  - "Tutti i suoi articoli →".
- **Sul telefono** l'ultimo articolo e il link vanno a capo sotto il nome.
- Se nessuno è ancora stato assegnato a un gruppo, la pagina mostra "// Nessuna firma".

### ⚙️ 3. Dati e SEO (plugin, `inc/team-page.php`, nuovo)

- I gruppi sono salvati nella pagina e il gruppo di ogni persona nel suo profilo. Il salvataggio è protetto con nonce, controllo dei permessi e pulizia dei dati.
- L'elenco è tenuto in memoria per 6 ore e si aggiorna quando si pubblica un articolo, si modifica un profilo o si cambiano i gruppi.
- **Dati per Google**: la pagina pubblica un elenco di persone (`ItemList` di `Person`) con nome, link alla pagina dell'autore, ruolo (il nome del gruppo) e sito per cui scrivono. È utile per l'autorevolezza (E-E-A-T).

---

### ✅ 4. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: `inc/team-page.php` e `page-redazione.php`, nessun errore.
- **Suite di collaudo**: **358 test su 358 superati**. Nuova suite 47 con 4 controlli:
  - lettura dei gruppi;
  - sicurezza del salvataggio;
  - ordine e dati per Google;
  - modello del tema.
- **Prova sul sito Local**: il profilo mostra la nuova tendina con l'avviso che mancano i gruppi; il sito si apre senza errori. La pagina vera la crea il CEO: vedi la sezione 1.
- **Sincronizzazione 4 header (SemVer 3.17.0, MINOR)**.
- **Pacchetti**: ZIP 3.16.1 spostati in `archive/`, creati i tre ZIP 3.17.0 con `package_release.ps1`.
