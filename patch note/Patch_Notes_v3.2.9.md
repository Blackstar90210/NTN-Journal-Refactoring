# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.2.9 (Release Ufficiale)

Questo aggiornamento risolve il disallineamento visivo del pulsante "AGGIUNGI [SITO]" nel blocco `// GOOGLE_FEED`, separando l'intestazione eyebrow superiore e allineando perfettamente sull'asse centrale il testo descrittivo e il pulsante d'azione.

---

### 📐 Allineamento Perfetto Blocco Google Feed

- **Separazione Eyebrow Header (`// GOOGLE_FEED`)**:
  - L'etichetta `// GOOGLE_FEED` è ora posizionata come intestazione full-width in cima al blocco (`block mb-3`), coerentemente con tutti gli altri moduli Nothing OS del tema (`// CONSIGLIATI`, `// CONDIVIDI_ARTICOLO`, `// IL_VERDETTO`).
- **Allineamento Asse Testo & Pulsante**:
  - Il testo descrittivo (*"Ricevi le notizie di [Sito Web] direttamente tra le tue fonti preferite"*) e il pulsante (*"AGGIUNGI [SITO WEB] &rarr;"*) sono ora posizionati sullo stesso livello all'interno di un container flex orizzontale (`flex-col md:flex-row items-start md:items-center justify-between gap-6`).
  - Su schermi desktop/tablet il pulsante e il testo condividono ora il medesimo centro verticale senza sfalsamenti.

---

### ✅ Quality Assurance, Test Suite e Packaging

- **Certificazione PHP 8.2/8.5 Strict**: Zero warning, notice o fatal error.
- **Suite di Collaudo Automatizzata**: Eseguiti **247 test su 247 superati al 100%** (`audit_test_suite.php`).
- **Sincronizzazione Atomica 4 Header (SemVer 3.2.9)**:
  1. `NTN_Journal_Theme_Reloaded/ntn-journal/style.css` (`Version: 3.2.9`)
  2. `NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css` (`Version: 3.2.9`)
  3. `NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php` Header (`Version: 3.2.9`)
  4. `NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php` Costante (`NTN_JOURNAL_CORE_VERSION` = `'3.2.9'`)
- **Organizzazione Deliverable**:
  - Pacchetti ZIP e note di rilascio posizionati in `releases/` e `releases/v3.2.9/`.
