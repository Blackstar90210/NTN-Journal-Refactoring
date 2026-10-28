# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.14.1 (giudizio della recensione su una riga)

Correzione del box "Valutazione finale" delle recensioni.

---

### 🐞 1. Il problema

Nella Hub di un videogioco, con la finestra larga tra circa 1040 e 1120 pixel, la parola del giudizio si spezzava a metà: "CONSIGLIAT" e sotto "O", con il pallino rosso staccato.

A quella larghezza la colonna "Scheda media" sta accanto al box della recensione e lo stringe. La parola però era calcolata sulla larghezza della finestra, non su quella del box, e restava troppo grande.

### 🧭 2. La soluzione (`build/input.css`)

- Il box della valutazione (`.ntn-rbox-eval`) diventa un riferimento per le misure, con `container-type: inline-size`.
- La parola del giudizio (`.ntn-verdict-word`) si misura sul box e non più sulla finestra: passa da `clamp(34px, 6vw, 60px)` a `clamp(30px, 7.5cqi, 60px)`.
- Sugli schermi larghi resta grande come prima, fino a 60 pixel. Si rimpicciolisce solo quando il box si stringe.

---

### ✅ 3. Quality Assurance, Test Suite e Packaging

- **Suite di collaudo**: **343 test su 343 superati**.
- **Prova sul sito Local**, con la Hub e la recensione di Ace Combat 8:
  - larghezze da 320 a 1700 pixel, a passi di 20;
  - tutti e quattro i giudizi: Bocciato, Consigliato con riserva, Consigliato, Must Play;
  - nessuna parola spezzata e nessun box che sborda. A 1080 pixel "CONSIGLIATO" sta su una riga.
- **Sincronizzazione 4 header (SemVer 3.14.1, PATCH)**.
- **Pacchetti**: ZIP 3.14.0 spostati in `archive/`, creati i tre ZIP 3.14.1 con `package_release.ps1`.
