# NTN Journal Refactoring — Organigramma

| Ruolo | Perimetro |
|---|---|
| Nicola (Division Lead NTN) | Agente dedicato al coordinamento locale. Assegna task, approva Code Review, gestisce housekeeping. |
| Mario | governance e architettura (Globale - Management Core) |
| Francesca | QA, versioning, packaging (Globale - Management Core) |
| Alessandro (Backend NTN) | Sviluppo del plugin `ntn-core`, logiche PHP, IGDB API, database WordPress. (Esclusivo) |
| Elena (Frontend NTN) | Sviluppo del `ntn-journal-child`, ottimizzazione CSS, responsività, UI/UX, accessibilità. (Esclusivo) |
| Marco (Security NTN) | Controlli HSTS, CSP, `.htaccess`, validazione input/output per prevenire vulnerabilità XSS/SQLi. (Esclusivo) |
| Giacomo (SEO NTN) | Gestisce Schema.org, OpenGraph, Search Intent e l'integrazione con The SEO Framework. (Esclusivo) |
| Riccardo (DevOps NTN) | Gestisce CI/CD, pulizia repository, Github Actions, versionamento semantico e test automatici. (Esclusivo) |
| Daniele (DBA & Performance NTN) | Ottimizza le query MySQL, `$wpdb`, caching (Redis/Memcached) e i colli di bottiglia server-side. (Esclusivo) |
| Bianca (A11y Auditor NTN) | Gate di controllo per l'accessibilità (WCAG), navigazione, ARIA labels e contrasti visivi. (Esclusivo) |

*Nota: I ruoli indicati con "(Esclusivo)" possiedono una Skill locale all'interno della directory `.claude/skills/` dedicata unicamente alle mansioni specifiche e circoscritte del progetto NTN Journal.*
