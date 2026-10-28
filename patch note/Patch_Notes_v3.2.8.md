# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 3.2.8 (Release Ufficiale)

Questo aggiornamento introduce il restyling e l'armonizzazione tipografica completa dei link in stile *Nothing OS*, con la freccia a matrice di punti simmetrica nel box consigliati, la conformità WCAG 2.4.4 per screen reader, l'integrazione del blocco Google Preferred Sources e la suite di audit 100% compliant.

---

### 🎨 Tipografia & Stile Brutalista Nothing OS

- **Uniformazione Colore Link Inline & Box Consigliati**:
  - Tutti i link interni al testo degli articoli e il modulo `// CONSIGLIATI` condividono ora il rosso signature del brand `#dc2626` (red-600).
  - Stati interattivi hover coordinati: tonalità più scura `#b91c1c` in Light Mode e più luminosa `#ef4444` in Dark Mode.
  - Rimossa qualsiasi sottolineatura dal titolo dell'articolo consigliato (`!no-underline`), garantendo che il testo mantenga la medesima tonalità del corpo dell'articolo (`text-zinc-700 dark:text-zinc-300 font-sans font-bold`).

- **Freccia a Matrice di Punti Simmetrica & Indicatore Dot**:
  - Inserita la freccia decorativa SVG a matrice di 7 dot luminosi prima del testo `// LEGGI_QUI`.
  - Il link ipertestuale cliccabile è rigorosamente circoscritto alla sola dicitura `// LEGGI_QUI`.
  - Preservato il pallino rosso pulsante al termine del link.

- **Layout Articoli Singoli (`single.php`)**:
  - Sequenza di chiusura ordinata: Blocco **Google Feed** &rarr; Blocco **Community / Social CTA** &rarr; Modulo **Articoli Correlati (Bottom)**.
  - Rimossa la sezione tag ridondante in calce al testo.

---

### ♿ Accessibilità, Hardening & Conformità (WCAG 2.4.4)

- **Accessibilità Screen Reader**:
  - Aggiunto `aria-label="Leggi l'articolo: [Titolo]"` all'ancora `// LEGGI_QUI` per fornire contesto completo agli screen reader.
  - Applicato `aria-hidden="true"` alla freccia a matrice di punti e al dot pulsante per neutralizzare letture vocali di elementi puramente decorativi.
- **Defensive Guarding PHP**:
  - Inserito il controllo `function_exists( 'ntn_journal_social_cta_box' )` prima del rendering del blocco social.
- **Internazionalizzazione & Dinamicità**:
  - Nome sito reso dinamico con `get_bloginfo( 'name' )` nelle stringhe i18n del box Google Feed.
- **De-duplicazione CSS**:
  - Eliminate le regole base ridondanti in Dark Mode da `style.css`.

---

### ✅ Quality Assurance, Test Suite e Packaging

- **Certificazione PHP 8.2/8.5 Strict**: Zero warning, notice o fatal error.
- **Suite di Collaudo Automatizzata**: Eseguiti **247 test su 247 superati al 100%** (`audit_test_suite.php`).
- **Sincronizzazione Atomica 4 Header (SemVer 3.2.8)**:
  1. `NTN_Journal_Theme_Reloaded/ntn-journal/style.css` (`Version: 3.2.8`)
  2. `NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css` (`Version: 3.2.8`)
  3. `NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php` Header (`Version: 3.2.8`)
  4. `NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php` Costante (`NTN_JOURNAL_CORE_VERSION` = `'3.2.8'`)
- **Organizzazione Deliverable**:
  - Pacchetti ZIP e note di rilascio archiviati nella directory dedicata `releases/` e `releases/v3.2.8/`.
