# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.22.2 (Immagine di anteprima adatta a Google Discover)

Correzione richiesta dal CEO dopo l'analisi sulla mancata comparsa degli articoli in Google Discover.

---

### 🖼️ 1. Immagine di anteprima a piena grandezza

- **Prima**: l'immagine di anteprima (`og:image` e immagine passata a The SEO Framework) era la versione `large` dell'immagine in evidenza, larga 1024 pixel. Google Discover chiede almeno 1200 pixel per mostrare l'immagine grande.
- **Ora**: si usa l'immagine originale caricata (`full`).
- Gli articoli già pubblicati avevano salvato l'indirizzo da 1024 pixel nel campo `_ntn_social_image_url`. Ora, se l'articolo ha un'immagine in evidenza, quel valore viene superato e si usa sempre l'originale, senza dover salvare di nuovo gli articoli.

### 📐 2. Misure reali dell'immagine

- **Prima**: le misure dichiarate a The SEO Framework erano sempre 1920×1080, anche quando il file era diverso.
- **Ora**: le misure sono lette dal file caricato in WordPress (nuova funzione `ntn_get_social_image_dimensions`). Per le copertine esterne di IGDB, di cui non si conoscono le misure, non vengono dichiarate.

---

### ✅ 3. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su `seo-extras.php`, `ntn-journal-core.php` e `audit_test_suite.php`.
- **Suite di collaudo**: **399 test su 399 superati**, con la nuova suite 58 che controlla misura e dimensioni dell'immagine di anteprima.
- **Sincronizzazione 4 header (SemVer 3.22.2, PATCH)**.
- **Pacchetti**: ZIP 3.22.1 spostati in `archive/`, creati i tre ZIP 3.22.2 con `package_release.ps1`.
- **Dopo l'installazione**: svuotare la cache del sito, poi controllare un articolo con lo strumento Controllo URL di Search Console.
