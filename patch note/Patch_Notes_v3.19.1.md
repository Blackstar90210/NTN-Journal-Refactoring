# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.19.1 (date di uscita nel catalogo)

Richiesta del CEO: nel catalogo dei videogiochi, sotto la copertina, la data completa quando è nota, la finestra di uscita (mese o trimestre) quando è nota solo quella, altrimenti l'anno. E un ordine che metta insieme i giochi con una data o una finestra. Scelta la soluzione B, la precisione presa da IGDB.

---

### 📅 1. Il problema

- IGDB salva i giochi di cui conosce solo l'anno al **31 dicembre**. Esempio: Tomb Raider: Catalyst risulta "31/12/2028".
- Dalla data salvata non si poteva quindi capire se il giorno fosse vero o no.

### 🔌 2. Plugin: quanto è precisa la data

- Quando lo Scanner IGDB collega o aggiorna un gioco, chiede a IGDB anche le date per piattaforma.
- Dal testo che IGDB usa per ogni data ("Oct 23, 2026", "Oct 2026", "Q4 2026", "2026", "TBD") ricava la precisione:
  - giorno;
  - mese;
  - trimestre;
  - anno;
  - da annunciare.
- Se più piattaforme hanno la stessa data, vale la meno precisa: se una dice "2027" e un'altra "31 dicembre 2027", quel 31 dicembre è il segnaposto di IGDB.
- La precisione si salva sul tag e sul videogioco (`_ntn_game_release_precision`).
- "Scollega" e "Reset" la cancellano insieme agli altri dati IGDB.
- Il backup la include già.

### 🎮 3. Catalogo: cosa si vede sotto la copertina

| IGDB conosce | Si vede |
|---|---|
| il giorno | **23.10.2026** |
| il mese | **Ott 2027** |
| il trimestre | **Q3 2027** |
| solo l'anno, o niente | **2027**, come prima |

Vale sia per la prima pagina del catalogo sia per le pagine caricate con i filtri.

### ↕️ 4. Catalogo: ordine per data

- Dentro ogni anno:
  1. **prima i giochi con solo l'anno**, in ordine alfabetico;
  2. **poi tutti gli altri in ordine di data**, che siano giorno, mese o trimestre.
- A parità di data va prima la finestra più vaga: "Q4 2027" prima di "31.12.2027".
- Esempio del 2027, dal più recente:
  - AfterZero … Tom Clancy's Rainbow 6 Tactics, tutti "2027";
  - poi Kingdom Hearts IV Q4 2027, LEGO Skylines 31.12.2027, Spine Q3 2027, Exodus 07.04.2027, Clutch Q1 2027, Fable 23.02.2027…
- Dal più vecchio vale l'ordine inverso: i giochi con solo l'anno chiudono il loro anno.
- Nota su LEGO Skylines: il 31.12.2027 come giorno esatto viene da IGDB stesso. L'ho verificato sulla pagina pubblica di IGDB, dove il dato risulta ancora in attesa di convalida. Se IGDB lo corregge, basta premere "Aggiorna" su quel gioco.

---

### ✅ 5. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su tutti i file modificati.
- **Suite di collaudo**: **380 test su 380 superati**. Nuova suite 51 con 4 controlli:
  - lettura della precisione;
  - salvataggio e cancellazione;
  - data mostrata;
  - ordinamento.
- **Prova sul sito Local**, dopo "Aggiorna tutti" lanciato dal CEO:
  - giorni, mesi e trimestri si vedono come nella tabella sopra;
  - l'ordine del 2027 è quello dell'esempio;
  - nessun errore PHP.
- **Sincronizzazione 4 header (SemVer 3.19.1, PATCH)**.
- **Pacchetti**: ZIP 3.19.0 spostati in `archive/`, creati i tre ZIP 3.19.1 con `package_release.ps1`.

### ⚠️ Da fare dopo l'aggiornamento del sito vero

- I giochi già nel catalogo non hanno ancora la precisione, quindi mostrano l'anno finché non vengono aggiornati da IGDB:
  1. apri NTN Journal Funzioni → Scanner IGDB;
  2. premi "Visualizza Tag Collegati";
  3. premi "Aggiorna tutti".
- I giochi collegati da qui in avanti la ricevono subito.
