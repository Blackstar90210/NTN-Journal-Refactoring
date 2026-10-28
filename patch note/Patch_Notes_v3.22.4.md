# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.22.4 (Pro e contro con la virgola nel Box Recensione)

Correzione segnalata dal CEO: nel Box Recensione un contro con una virgola veniva spezzato in due voci.

---

### 🧾 1. Pro e contro con la virgola

- **Prima**: se nel campo Pro o Contro c'era una sola riga con una virgola, il Box Recensione la divideva in più voci ("Pacing lento, soprattutto nelle prime ore" diventava "Pacing lento" e "soprattutto nelle prime ore"). Con due o più righe, invece, le virgole restavano nel testo.
- **Ora**: ogni riga è sempre una voce e le virgole restano nel testo, come indica l'etichetta dell'editor ("uno per riga"). Il Box Recensione si comporta come l'anteprima della scheda Recensioni e i dati per Google, che già dividevano solo per righe.
- **Da tenere presente**: le vecchie recensioni con pro o contro scritti su una sola riga e separati da virgole ora mostrano un'unica voce lunga. Per sistemarle basta andare a capo dopo ogni voce.
- File modificato: `ntn-journal/template-parts/review-original-box.php` (tema Parent).

---

### ✅ 2. Quality Assurance, Test Suite e Packaging

- **Verifica sintattica**: PHP 8.5 su `review-original-box.php`, `ntn-journal-core.php` e `audit_test_suite.php`.
- **Suite di collaudo**: **404 test su 404 superati**, con la nuova suite 60 (un pro e un contro con la virgola restano una voce ciascuno).
- **Sincronizzazione 4 header (SemVer 3.22.4, PATCH)**.
- **Pacchetti**: ZIP 3.22.3 spostati in `archive/`, creati i tre ZIP 3.22.4 con `package_release.ps1`.
- **Dopo l'installazione**: svuotare la cache del sito e controllare una recensione con un contro che contiene una virgola.
