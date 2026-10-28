# Piano di Ottimizzazione Server e Sicurezza
**Progetto:** NTN Journal Refactoring
**Ambiente Target:** WordPress su Apache
**Autori:** Marco (Security & Compliance Officer) e Alessandro (CTO & Lead Backend Developer)
**Fonte:** Report di ottimizzazione da Hosting Analyzer

Questo documento traccia in modo permanente le linee guida architetturali e i requisiti di sistema necessari per risolvere le criticità emerse in fase di audit delle prestazioni e della sicurezza del server.

---

## 🛡️ 1. Interventi di Sicurezza e Compliance DNS (Area Marco)

### HSTS (HTTP Strict Transport Security) e HSTS Preload
- **Problema:** Assenza di forzatura HTTPS, esposizione ad attacchi MitM (SSL Stripping).
- **Intervento:** Aggiungere la direttiva all'interno del file `.htaccess` (modulo `mod_headers.c`) per impostare un `max-age` di 1 anno, coprire i sottodomini e preparare il sito al preload.
- **Snippet `.htaccess`:**
  ```apache
  <IfModule mod_headers.c>
      Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" env=HTTPS
  </IfModule>
  ```
- **Prerequisito:** Assicurarsi che ogni eventuale sottodominio esponga certificati SSL validi.

### Security Headers (Anti-XSS, Clickjacking, MIME-sniffing)
- **Problema:** Il sito non espone difese standard a livello di HTTP request.
- **Intervento:** Estendere il blocco `mod_headers.c` nel `.htaccess` con le policy essenziali. Da prestare particolare attenzione alla Content Security Policy (CSP), per evitare conflitti con gli script inline del tema e dei plugin di WordPress.
- **Snippet `.htaccess`:**
  ```apache
  <IfModule mod_headers.c>
      Header always set X-Frame-Options "SAMEORIGIN"
      Header always set X-Content-Type-Options "nosniff"
      Header always set Referrer-Policy "strict-origin-when-cross-origin"
      Header always set X-XSS-Protection "1; mode=block"
      Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
      # CSP Permissiva per WordPress:
      Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;"
  </IfModule>
  ```

### Email Security (DKIM, DMARC)
- **Problema:** Rischio spoofing e alto tasso di invii finiti in spam.
- **Intervento:** 
  1. Generare chiave DKIM sul server/cPanel e creare un record TXT DNS (`[selettore]._domainkey`).
  2. Applicare una policy DMARC restrittiva. Aggiungere record DNS TXT per `_dmarc.dominio.it` con valore `v=DMARC1; p=quarantine; rua=...` per filtrare la posta contraffatta.

### Protezione dell'Infrastruttura DNS (DNSSEC e CAA)
- **Problema:** Esposizione a DNS Spoofing e ad emissione non autorizzata di certificati SSL.
- **Intervento:** 
  1. **CAA:** Aggiungere record DNS CAA (es. `@ IN CAA 0 issue "letsencrypt.org"`).
  2. **DNSSEC:** Attivare il servizio tramite il pannello del Registrar di dominio per firmare digitalmente i record DNS.

---

## ⚡ 2. Ottimizzazione Performance e Caching (Area Alessandro)

### HTTP/3 (QUIC) e Compressione Zstd
- **Problema:** Tecnologie mancanti che rallentano la delivery su dispositivi mobili e reti instabili.
- **Raccomandazione Architetturale:** L'implementazione diretta su Apache risulta sperimentale e onerosa a livello sistemistico (richiede moduli customizzati e l'apertura della porta 443 in UDP). Si delibera di esternalizzare l'onere affidandosi ad una **CDN di terza generazione (come Cloudflare)**, attivabile tramite semplice variazione dei Name Server, demandando alla CDN il supporto HTTP/3 e Zstd.

### OCSP Stapling (Latenza Handshake TLS)
- **Problema:** Aumento della latenza iniziale dovuta alla validazione dei certificati da parte del client.
- **Intervento:** Qualora non si sfrutti una CDN, modificare il file di configurazione di Apache (`httpd.conf` / `ssl.conf`) e il relativo blocco `<VirtualHost *:443>` abilitando `SSLUseStapling On`.
- **Snippet `httpd.conf`:**
  ```apache
  SSLStaplingCache "shmcb:logs/ssl_stapling(32768)"
  <VirtualHost *:443>
      SSLUseStapling On
      SSLStaplingReturnResponderErrors Off
      SSLStaplingResponderTimeout 5
  </VirtualHost>
  ```

### Header di Caching (ETag, Last-Modified, Risposte 304)
- **Problema:** Il server forza il re-download delle risorse statiche, appesantendo banda e CPU.
- **Intervento:** Modifica prioritaria del file `.htaccess`. Rimuovere eventuali istruzioni obsolete che disabilitano l'ETag (es. `Header unset ETag`) e imporre la validazione.
- **Snippet `.htaccess`:**
  ```apache
  FileETag MTime Size
  <IfModule mod_headers.c>
      <FilesMatch "\.(ico|pdf|flv|jpg|jpeg|png|gif|webp|js|css)$">
          Header set Cache-Control "public, max-age=31536000, must-revalidate"
      </FilesMatch>
  </IfModule>
  ```
- **Pagine HTML:** Il supporto ai codici `304 Not Modified` lato PHP (pagine core) dev'essere gestito implementando e configurando un Plugin di Page Caching avanzato (es. WP Rocket).

---
*Documento affiancato al resoconto di ottimizzazione prodotto dal Management Core. Costituisce requisito formale per la chiusura dei task infrastrutturali e di sicurezza.*
