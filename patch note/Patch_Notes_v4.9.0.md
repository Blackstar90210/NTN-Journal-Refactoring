# 📝 NTN Journal - Note di Rilascio (Patch Notes)

## Versione: 4.9.0 (Scheda videogioco multigioco)

Richiesta del CEO: un articolo che parla di più giochi ha ora una scheda per ciascun gioco, su PC e da telefono. Gli articoli con un gioco solo o senza giochi si comportano come prima, salvo la nuova riga della recensione negli articoli con un gioco solo.

---

### 🎮 1. Scheda con più giochi

- **Su PC**: nella colonna laterale c'è un carosello con una pagina per gioco, in ordine alfabetico, con frecce, pallini e tasti.
- **Da telefono**: stessa intestazione del PC, con contatore e tasti ← → da 44 pixel. Si cambia gioco anche col dito e con le frecce della tastiera. Senza JavaScript si vede solo il primo gioco.

### 📰 2. Riga della recensione

- Sotto ogni gioco c'è **"Leggi la recensione"** con il link, oppure l'avviso **"Non recensito"**.

### 🧩 3. Dati per gioco

- Ogni gioco legge i dati dal proprio tag, quindi i dati di due giochi non si mescolano più. La nuova funzione è `ntn_get_post_games()`.

### ⭐ 4. Voto

- Nelle recensioni con più giochi il voto compare solo sul gioco recensito dall'articolo aperto. Se la recensione non corrisponde a nessun gioco, il voto resta nascosto.

### ✅ 5. Test

- Aggiunte le sezioni 68, 69 e 70 di `audit_test_suite.php`.
