# Original User Request

## 2026-08-14T15:38:53Z

> Requested team: A small focused team (one implementer, then repeated adversarial review).

This is a single self-contained fix; keep it small and focused.

Eseguire una revisione del codice (code review) e un'ispezione indipendente delle modifiche recentemente apportate ai file del tema e del plugin per risolvere il problema delle cover IGDB (archivio videogioco e The SEO Framework). L'obiettivo è scovare eventuali bug residui, problemi di performance o edge cases non gestiti.

Working directory: f:\Sviluppo\Divisione_WP\NTN Journal Refactoring
Integrity mode: demo

## Requirements

### R1. Ispezione Logica di Fallback (Archivio Videogioco)
Verificare che la logica di fallback in `archive-videogioco.php` (sia nel parent che nel child theme) e nella funzione `ntn_get_hub_igdb_info()` gestisca correttamente l'assenza totale di cover senza generare notice PHP o comportamenti anomali del layout.

### R2. Sicurezza dei Filtri Open Graph (The SEO Framework)
Controllare le funzioni e i filtri inseriti in `seo-extras.php` per garantire che l'elaborazione degli argomenti forniti da The SEO Framework sia completamente type-safe in PHP 8.2 (es. cosa succede se al filtro viene passato un array invece di una stringa?).

## Acceptance Criteria

### Verifica Oggettiva tramite Test Automatizzati
- [ ] Il team deve produrre uno script PHP eseguibile (`audit_test_suite.php`) che simuli chiamate estreme alle funzioni modificate (es. passando valori `null`, `false`, o array malformati ai filtri TSF).
- [ ] Lo script di test deve passare con successo se il codice gestisce correttamente tutti i casi limite senza produrre Fatal Error o Notice. Se ci sono falle nel codice attuale, lo script le evidenzierà fallendo.
