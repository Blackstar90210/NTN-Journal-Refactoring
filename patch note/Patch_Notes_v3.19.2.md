# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.19.2 (data di uscita per Google)

Correzione chiesta dal CEO dopo la 3.19.1: nei testi e nei dati per Google la data di uscita dice solo quello che si sa davvero.

---

### 🔎 1. Il problema

- La descrizione delle Hub dei giochi, usata anche da Google, diceva per esempio "in uscita il 31/12/2028" per Tomb Raider: Catalyst, di cui si conosce solo l'anno.
- Anche nei dati strutturati (VideoGame) la data era "2028-12-31".

### ✅ 2. Cosa cambia

- **Testo della Hub** (presentazione nella pagina e descrizione per Google):
  - "in uscita il 23/02/2027" quando si conosce il giorno;
  - "a ottobre 2027" con il mese;
  - "nel terzo trimestre del 2027" con il trimestre;
  - "nel 2028" con solo l'anno.
  - "In uscita" o "uscito" si decide alla fine del periodo: un gioco "nel 2027" resta "in uscita" fino al 31 dicembre.
- **Dati strutturati** (Hub dei giochi, schede del gioco negli articoli e nelle recensioni): "2026-10-23", "2027-10" o "2027", solo con le parti note.
- Per i giochi non ancora aggiornati dallo Scanner IGDB, un 31 dicembre vale "solo l'anno".

---

### ✅ 3. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su `hub-seo.php`, `schema-markup.php`, `seo-extras.php`.
- **Suite di collaudo**: **383 test su 383 superati**. Nuova suite 52 con 3 controlli.
- **Prova sul sito Local**, solo in lettura:
  - Tomb Raider: Catalyst: "in uscita nel 2028", dato "2028";
  - Spine: "in uscita nel terzo trimestre del 2027", dato "2027";
  - Fable: "in uscita il 23/02/2027", dato "2027-02-23";
  - nessun errore PHP.
- **Sincronizzazione 4 header (SemVer 3.19.2, PATCH)**.
- **Pacchetti**: ZIP 3.19.1 spostati in `archive/`, creati i tre ZIP 3.19.2 con `package_release.ps1`.

### ⚠️ Note

- Il controllo SEO di Hub e categorie fatto insieme a questa versione è nel resoconto al CEO. I punti aperti non sono ancora stati toccati.
