# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.22.1 (Hub degli studi: indice dopo gli articoli)

Modifica richiesta dal CEO.

---

### 🧭 1. Nuovo ordine nella Hub degli sviluppatori italiani

- **Prima**: In evidenza → Indice degli studi → Tutti gli articoli.
- **Ora**: In evidenza → Tutti gli articoli → Indice degli studi.
- Il contenuto dei blocchi non cambia. Le Hub delle piattaforme (Console, Mobile, PC Gaming) restano come sono.
- Mockup aggiornato (Artifact versione 39).

---

### ✅ 2. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su `category-hub.php` del tema.
- **Suite di collaudo**: **397 test su 397 superati**, con la nuova suite 57 che controlla l'ordine dei blocchi.
- **Prova sul sito Local**: la Hub "Sviluppatori Italiani di Videogiochi" mostra In evidenza, Tutti gli articoli e Indice degli studi, in quest'ordine.
- **Sincronizzazione 4 header (SemVer 3.22.1, PATCH)**.
- **Pacchetti**: ZIP 3.22.0 spostati in `archive/`, creati i tre ZIP 3.22.1 con `package_release.ps1`.
