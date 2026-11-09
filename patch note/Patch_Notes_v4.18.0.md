# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.18.0 (Avviso se plugin e temi hanno versioni diverse)

Punto 10 dell'audit di coerenza.

### ⚠️ Avviso in bacheca sulle versioni (plugin)

Plugin, tema parent e tema child hanno sempre lo stesso numero di versione e vanno aggiornati insieme (plugin, parent, child). Se uno resta indietro, il sito può comportarsi in modo strano senza dire perché.

- Nuovo file `inc/version-check.php`: se il tema parent o il child attivo ha una versione diversa da quella del plugin, in bacheca compare un avviso con le versioni a confronto e l'ordine di installazione.
- L'avviso lo vede chi può aggiornare plugin o temi, e sparisce da solo quando le versioni coincidono.
- Con un altro tema attivo non compare. Sotto la 4.10.1 resta l'avviso già esistente.
- Funzione `ntn_core_version_mismatches()` (confronto puro, provata dai test 88.1-88.4).

### Ordine di installazione
Plugin, poi tema parent, poi child.
