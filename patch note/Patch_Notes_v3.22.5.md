# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.22.5 (Compressione dei PNG pieni salvati con trasparenza)

Correzione richiesta dal CEO dopo aver trovato la copertina di Code Exit caricata come PNG da 1,8 MB.

---

### 🖼️ 1. Controllo della trasparenza sui pixel veri

- **Prima**: il tema lasciava intatto ogni PNG salvato in formato RGBA (con canale di trasparenza), pensando che fosse un logo o un'icona. Molti programmi salvano così anche screenshot e copertine piene: quei PNG non venivano né convertiti in JPEG né compressi.
- **Ora**: se il formato permette la trasparenza, il tema controlla i pixel (nuova funzione `ntn_png_is_fully_opaque` in `inc/performance.php`). L'immagine viene ridotta a 256 pixel per il controllo e si guardano anche i quattro angoli a piena risoluzione. Se nessun pixel è trasparente, il PNG viene convertito in JPEG e compresso come le altre immagini.
- I PNG davvero trasparenti (loghi, icone, badge) restano protetti come prima.
- Il controllo usa GD o Imagick. Se il server non ha nessuna delle due librerie, il PNG resta intatto come prima.
- Vale per le immagini caricate dopo l'aggiornamento. Le immagini già presenti non cambiano: per alleggerirle va ricaricata l'immagine.

---

### ✅ 2. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su `performance.php`, `ntn-journal-core.php` e `audit_test_suite.php`.
- **Prova con immagini vere (GD)**: 6 casi su 6 corretti. La copertina di Code Exit e un PNG pieno in RGBA risultano non trasparenti; un logo con sfondo trasparente, un'immagine con un piccolo buco trasparente al centro e un PNG a palette con trasparenza risultano trasparenti; un PNG senza canale alfa è riconosciuto subito. Ogni controllo richiede meno di 80 millisecondi.
- **Suite di collaudo**: **407 test su 407 superati**, con la nuova suite 61. Nella suite 40 le costanti di Imagick sono ora escluse dal controllo delle etichette con il trattino basso, perché non sono testi visibili.
- **Sincronizzazione 4 header (SemVer 3.22.5, PATCH)**.
- **Pacchetti**: ZIP 3.22.4 spostati in `archive/`, creati i tre ZIP 3.22.5 con `package_release.ps1`.
