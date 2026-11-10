# CLAUDE.md — Master Governance & Workspace Rules
# Progetto: NTN Journal Refactoring

> **Status:** ATTIVO E VINCOLANTE  
> **Autore:** Mario, CHRO & Chief Architect (Management Core)  
> **Approvato da:** CEO (Utente) & COO/PM (Assistente)  
> **Ultimo Aggiornamento:** 2026-10-16 (migrazione da `GEMINI.md` di Antigravity a Claude Code)  
> **Workspace Root:** `%USERPROFILE%\Sviluppo-Antigravity\Ecosistema\Progetti\Divisione_WP\Temi\NTN Journal Refactoring`

---

## 1. Visione del Progetto e Stack Tecnologico

Il progetto **NTN Journal** è un ecosistema editoriale WordPress di livello enterprise con estetica brutalista ispirata all'interfaccia **Nothing OS** (caratteri monospace *JetBrains Mono*, font *Inter*, matrici di punti, contrasti elevati bianco/nero/rosso accent, minimalismo geometrico).

### Stack Tecnologico
- **CMS Target:** WordPress 6.0+ (piena compatibilità con l'editor a blocchi Gutenberg e Site Editor)
- **Runtime Backend:** PHP 8.0+ (strict typing, null-safety, defensive programming)
- **Frontend / Styling:** Tailwind CSS v4 CLI + CSS personalizzato Nothing OS
- **Architettura Modulare:** 
  1. Parent Theme (`ntn-journal`): Framework di base e template core
  2. Child Theme (`ntn-journal-reloaded`): solo personalizzazioni (dalla 4.10.0 il sito funziona anche con il solo Parent)
  3. Core Plugin (`ntn-journal-core`): Logica applicativa, CPT, Tassonomie, REST API, Integrazione IGDB, AI Summaries, SEO Extras, Redirect & Backup

---

## 2. Gerarchia e Squadra

La catena di comando è quella dello statuto globale (`CLAUDE.md` dell'Ecosistema): `CEO → Adriano (COO globale, cioè Claude) → Nicola (Division Lead) → specialisti`. Regole locali e organigramma del progetto:

@AGENTS.md
@.claude/SQUADRA_AGENTI.md

Le skill di Nicola e degli specialisti NTN sono in `.claude/skills/`. Mario, Francesca e Maria Grazia sono C-Level globali, con le skill in `Ecosistema/.claude/skills/`.

---

## 3. Le 6 Regole Cardinali del Workspace (Tassative e Inviolabili)

### 🔴 REGOLA 1: Obbligo di Avanzamento Versione (SemVer)
**Nessuna modifica al codice sorgente di temi o plugin può essere considerata conclusa senza l'avanzamento esplicito della versione del progetto (Semantic Versioning `MAJOR.MINOR.PATCH`).**

- **PATCH (`x.y.Z`)**: Per bug fix, correzioni CSS minori, micro-refactoring, fix di compatibilità PHP.
- **MINOR (`x.Y.0`)**: Per nuove feature, nuovi CPT, nuove tassonomie, nuovi moduli/template, aggiornamenti architetturali retrocompatibili.
- **MAJOR (`X.0.0`)**: Per breaking changes, refactoring strutturali completi, modifiche incompatibili con versioni precedenti.

> [!CAUTION]
> È severamente vietato effettuare modifiche ai file sorgenti (PHP, CSS, JS) lasciando invariato il numero di versione del progetto. Qualsiasi PR o commit senza avanzamento SemVer sarà rigettato da Francesca (CQO) e da Mario (Chief Architect).

---

### 🔴 REGOLA 2: Sincronizzazione Simultanea e Atomica dei 4 Header di Versione
Tutti i componenti dell'ecosistema condividono **lo stesso identico numero di versione**. Quando la versione avanza, è obbligatorio aggiornare simultaneamente e atomicamente i seguenti **4 punti cardine**:

| # | File | Elemento da Aggiornare | Esempio |
|---|------|------------------------|---------|
| 1 | `NTN_Journal_Theme_Reloaded/ntn-journal/style.css` | Header `Version:` | `Version: 2.1.3` |
| 2 | `NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css` | Header `Version:` | `Version: 2.1.3` |
| 3 | `NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php` | Header `Version:` | `Version: 2.1.3` |
| 4 | `NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php` | Costante `NTN_JOURNAL_CORE_VERSION` | `define( 'NTN_JOURNAL_CORE_VERSION', '2.1.3' );` |

> [!IMPORTANT]
> Un disallineamento anche di un solo header tra questi 4 elementi costituisce una violazione critica di conformità.

---

### 🔴 REGOLA 3: Separazione Architetturale Rigorosa (Theme vs Plugin)
Rispettare scrupolosamente il principio di **Separation of Concerns** di WordPress:

```
┌────────────────────────────────────────────────────────┐
│                      TEMA CHILD                        │
│            (ntn-journal-reloaded / UI & CSS)           │
│  - style.css (custom styling & overrides)              │
│  - functions.php (solo enqueue & UI hooks)             │
│  - nessuna funzione essenziale: tutto è nel Parent     │
└──────────────────────────┬─────────────────────────────┘
                           │ Presentazione / Styling
                           ▼
┌────────────────────────────────────────────────────────┐
│                      TEMA PARENT                       │
│             (ntn-journal / Base Framework)             │
│  - Base templates & partials (header, footer, nav)     │
│  - Tailwind CSS output compilato                       │
└──────────────────────────┬─────────────────────────────┘
                           │ Logica & Dati
                           ▼
┌────────────────────────────────────────────────────────┐
│                     CORE PLUGIN                        │
│          (ntn-journal-core / Business Logic)           │
│  - CPT (comunicato_stampa, videogioco)                 │
│  - Custom Taxonomies (game_engine, tipologia_*, etc.)  │
│  - Meta Boxes, Custom Fields & IGDB API                │
│  - AI Summaries, SEO Extras, Sitemap, Redirects        │
└────────────────────────────────────────────────────────┘
```

- **Divieto:** NON registrare CPT, Tassonomie, Endpoints API o logica di business all'interno del tema (`functions.php`).
- **Obbligo:** Tutta la logica di dati e backend appartiene unicamente a `ntn-journal-core`. Il tema si limita a consumare e visualizzare i dati.
- **Dati dei giochi (dalla 4.11.0):** le funzioni che leggono i dati (voto, categoria principale, IGDB e, nelle versioni successive, giochi dell'articolo e contenuti dell'Hub) stanno in `ntn-journal-core/inc/game-data.php`, ognuna dentro `if ( ! function_exists() )`. Il tema tiene solo riserve vuote con lo stesso nome in `ntn-journal/inc/plugin-fallbacks.php`. Ordine di aggiornamento sul sito: plugin, poi tema parent, poi child.

---

### 🔴 REGOLA 4: Protocollo di Packaging & Housekeeping ZIP di Francesca (CQO)
La generazione dei deliverable ZIP per WordPress deve seguire il protocollo di qualità a 3 fasi:

1. **Fase 1 - Housekeeping Preventivo:**
   Prima di generare qualsiasi nuovo archivio `.zip` nella root del workspace, **spostare obbligatoriamente tutti i vecchi file `.zip` nella cartella `archive/`**. Nessun vecchio file ZIP deve essere sovrascritto accidentalmente o lasciato orfano nella root.
2. **Fase 2 - Struttura Interna Conforme:**
   - La radice dell'archivio `.zip` deve contenere **una singola cartella** corrispondente allo slug esatto del tema o plugin:
     - Parent Theme: `ntn-journal/` (SENZA versione nel nome cartella interna)
     - Child Theme: `ntn-journal-reloaded/` (SENZA versione nel nome cartella interna)
     - Core Plugin: `ntn-journal-core/` (SENZA versione nel nome cartella interna)
   - I percorsi interni devono usare il forward slash standard `/`.
3. **Fase 3 - Nomenclatura del File Pacchetto:**
   I file generati nella root del workspace devono seguire il formato:
   - `ntn-journal-parent-X.Y.Z.zip`
   - `ntn-journal-child-reloaded-X.Y.Z.zip`
   - `ntn-journal-core-X.Y.Z.zip`

---

### 🔴 REGOLA 5: Vincoli di Ambiente Windows e PowerShell
L'ambiente host di sviluppo è Windows.

- **Shell Obbligatoria:** PowerShell (`pwsh`). In Claude Code usa lo strumento PowerShell, non lo strumento Bash. Per leggere, cercare e modificare file usa gli strumenti dedicati di Claude Code (lettura, ricerca, modifica).
- **Divieto Assoluto:** Non utilizzare comandi Unix/Bash (`rm -rf`, `cp -r`, `touch`, `ls -la`, `sed`, `grep`, `cat` etc.).
- **Cmdlet e Sintassi PowerShell da Utilizzare:**
  - Creazione file/cartelle: `New-Item -ItemType File|Directory -Path ... -Force`
  - Spostamento / Archiviazione: `Move-Item -Path ... -Destination ... -Force`
  - Copia: `Copy-Item -Path ... -Destination ... -Recurse -Force`
  - Eliminazione: `Remove-Item -Path ... -Recurse -Force`
  - Creazione ZIP: OBBLIGATORIO usare lo script `powershell -ExecutionPolicy Bypass -File .\package_release.ps1 -Version X.Y.Z` situato nella root. È **severamente vietato** utilizzare `Compress-Archive` nativo, in quanto corrompe la struttura interna delle directory e i path per WordPress.
  - Ispezione percorsi: `Get-ChildItem`, `Test-Path`

---

### 🔴 REGOLA 6: Protocollo di Test, Validazione e Handoff
Nessun deliverable può essere consegnato al CEO (Utente) senza:
1. Verifica sintattica PHP (assenza di fatal error / notices con PHP 8.2).
2. Esecuzione e superamento della suite di test (`audit_test_suite.php` o test specifici di modulo).
3. Verifica della sincronizzazione dei 4 header di versione.
4. Convalida del packaging ZIP da parte di Francesca (CQO).
5. Documentazione delle modifiche nel changelog/resoconto di rilascio.

---

## 4. Alberatura del Workspace

```text
%USERPROFILE%\Sviluppo-Antigravity\Ecosistema\Progetti\Divisione_WP\Temi\NTN Journal Refactoring\
│
├── .claude/
│   ├── SQUADRA_AGENTI.md                   # Organigramma del progetto
│   └── skills/                             # Skill di Nicola e degli specialisti NTN
│
├── .agents/                                # Regole di dettaglio e archivio delle sessioni Antigravity
│   ├── rules/
│   │   └── versioning-and-releases.md      # Regola di dettaglio SemVer & Packaging
│   └── ... (sessioni e context agentici di Antigravity, solo storico)
│
├── archive/                                # Deposito storico delle release ZIP precedenti (solo locale, non tracciato)
│   ├── ntn-journal-parent-2.0.0.zip
│   ├── ntn-journal-child-reloaded-2.0.0.zip
│   ├── ntn-journal-core-2.0.0.zip
│   └── ...
│
├── docs/                                   # Documentazione architetturale e proposte di feature (solo locale, non tracciato)
│   ├── Piano_Architetturale.md
│   ├── feature_proposals.md
│   ├── piano_implementazione_backup.md
│   └── proposta_comunicati_stampa.md
│
├── NTN_Journal_Theme_Reloaded/             # Sorgenti dei Temi WordPress
│   ├── ntn-journal/                        # Parent Theme (Framework & Tailwind CSS)
│   │   ├── style.css                       # Header Versione Parent
│   │   ├── functions.php
│   │   ├── header.php / footer.php
│   │   ├── template-parts/
│   │   └── ...
│   └── ntn-journal-reloaded/               # Child Theme (UI, Custom Styling & Overrides)
│       ├── style.css                       # Header Versione Child
│       ├── functions.php
│       └── ...
│
├── NTN_Journal_Core_Plugin/                # Sorgenti del Plugin Core
│   └── ntn-journal-core/                   # Core Plugin Directory
│       ├── ntn-journal-core.php            # Main File (Header Versione + Costante)
│       ├── assets/                         # CSS/JS per Admin e Frontend API
│       └── inc/                            # Moduli PHP (CPT, Taxonomies, IGDB, SEO, etc.)
│
├── AGENTS.md                               # Statuto Aziendale e Governance Management Core
├── CLAUDE.md                               # Master Rules del Workspace (Questo documento)
├── audit_test_suite.php                    # Suite di collaudo e validazione funzionale PHP
├── patch note/                             # Note di rilascio di ogni versione (solo locale, non tracciato)
│
├── ntn-journal-parent-X.Y.Z.zip            # Deliverable ZIP Tema Parent (Versione Corrente)
├── ntn-journal-child-reloaded-X.Y.Z.zip    # Deliverable ZIP Tema Child (Versione Corrente)
└── ntn-journal-core-X.Y.Z.zip              # Deliverable ZIP Plugin Core (Versione Corrente)
```

---

## 5. Procedura Standard per Ogni Intervento (Checklist Operativa)

Prima di dichiarare completato un intervento, seguire la checklist ordinata:

- [ ] **1. Architettura:** Il codice è posizionato correttamente (Child Theme per UI, Plugin per Logica)?
- [ ] **2. Compatibilità PHP 8.2:** Tipi di dato, parametri nullabili, assenza di warning o deprecazioni?
- [ ] **3. Avanzamento Versione (SemVer):** È stato determinato il nuovo numero di versione `X.Y.Z`?
- [ ] **4. Sincronizzazione 4 Header:** Sono stati aggiornati simultaneamente i file `ntn-journal/style.css`, `ntn-journal-reloaded/style.css`, `ntn-journal-core.php` (Header) e `NTN_JOURNAL_CORE_VERSION`?
- [ ] **5. Test Suite:** È stato eseguito `php audit_test_suite.php` con esito 100% positivo?
- [ ] **6. Housekeeping:** I vecchi file ZIP in root sono stati spostati in `archive/`?
- [ ] **7. Packaging:** Sono stati generati i 3 nuovi archivi ZIP conformi con cartella singola pulita?
- [ ] **8. Handoff:** È stato redatto il report completo per il CEO (Utente)?
