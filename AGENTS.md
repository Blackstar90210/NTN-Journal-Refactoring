# NTN Journal Refactoring — Regole locali operative

Il progetto segue `CEO → Adriano — COO globale → Division Lead Nicola (`nicola-lead-ntn_refactoring`) → specialisti`. Mario, Francesca, Alessandro, Elena e Marco non costituiscono una catena alternativa: sono specialisti trasversali coordinati dal Division Lead.

La RACI locale deve essere mantenuta come dettaglio operativo, subordinato allo statuto globale.

## Regole di codice

### Prefissi dei nomi nel plugin (dalla 4.25.3)
- Nel plugin `ntn-journal-core` il codice **nuovo** (funzioni, opzioni, hook, campi meta, transient) usa il prefisso `ntn_core_`.
- Il codice esistente con `ntn_` o `ntn_journal_` **non si rinomina**: niente rinomine in massa, perché sono nomi già salvati nel database, usati dal tema o da snippet esterni.
- `ntn_journal_` resta per quanto è condiviso con il tema (funzioni con riserva vuota in `ntn-journal/inc/plugin-fallbacks.php`, impostazioni lette da entrambi).
- I campi delle recensioni senza prefisso (`rating`, `pros`, `cons`, `verdict`, `reviewed_item`) restano con questi nomi: sono dichiarati con `register_post_meta` in `inc/review-meta.php`.

## Ambiente di Sviluppo
- **PHP Executable Path:** L'eseguibile `php.exe` risiede al seguente percorso (WinGet):
  `C:\Users\Luca-Maiolini\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe`
