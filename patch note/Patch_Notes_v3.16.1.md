# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.16.1 (pannello "// Seguici")

L'ultimo box dell'articolo rimasto nel vecchio stile, "Seguici sui social", diventa un pannello della colonna laterale, come approvato dal CEO (mockup versione 23).

---

### 📣 1. Pannello "// Seguici" (`inc/social-cta.php`)

- Al posto del grande riquadro "Ti è piaciuto questo articolo?" in fondo al testo.
- **Dove sta**:
  - da computer nella colonna laterale, sotto "// Fonti preferite su Google";
  - da telefono sotto l'articolo, dopo Google.
- **Com'è fatto**: le stesse celle di "// Condividi", con le sigle in Mono. C'è una cella per ogni profilo inserito nel Personalizza, in quest'ordine: **TG, FB, IG, X, BSKY, MASTO**. Le celle si dividono lo spazio in parti uguali, qualunque sia il numero di profili.
- L'intestazione mostra il numero di canali, per esempio "3 canali".
- Se nel Personalizza non c'è nessun profilo, il pannello non compare.
- I link si aprono in una nuova scheda (`noopener noreferrer me`); ogni cella ha un nome leggibile per i lettori di schermo, per esempio "Q-Gin su Telegram".
- Tolte le icone e le scritte del vecchio riquadro.

---

### ✅ 2. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica PHP 8.5**: `inc/social-cta.php` e `single.php`, nessun errore.
- **Suite di collaudo**: **354 test su 354 superati**.
  - Nuova suite 46 con 3 controlli:
    - pannello nascosto senza profili;
    - celle nell'ordine giusto con link sicuri e numero dei canali;
    - posizione nella colonna e sotto l'articolo.
  - I test 28.16 (nessun pannello senza profili) e 29.14 (richiamo protetto in `single.php`) passano senza modifiche.
- **Prova sul sito Local**: su Local non ci sono profili inseriti, quindi il pannello giustamente non compare. Per controllarne l'aspetto l'ho inserito solo nella pagina aperta nel browser, senza toccare le impostazioni: sei celle da 53 pixel, allineate a "// Condividi".
- **Sincronizzazione 4 header (SemVer 3.16.1, PATCH)**.
- **Pacchetti**: ZIP 3.16.0 spostati in `archive/`, creati i tre ZIP 3.16.1 con `package_release.ps1`.

### ⚠️ Note

- Il prefisso "Archivi:" nei titoli degli archivi lo aggiunge The SEO Framework: si toglie dalle sue impostazioni del titolo (opzione dei prefissi), non dal tema.
