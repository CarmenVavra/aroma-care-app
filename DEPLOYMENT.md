# Deployment auf netcup – aromacare.caryssa.at

Nach der einmaligen Einrichtung reicht **ein Befehl**, um den aktuellen Stand online zu stellen:

```bash
./deploy.sh
```

Das Skript prüft den Git-Stand, führt Tests, Pint und Larastan aus, baut lokal ein Release
(`git archive` + Composer ohne Dev-Pakete + `npm run build`), lädt es per `scp` hoch und schaltet es per `ssh` scharf:
Migrationen, Event-Cache und ein atomarer Wechsel des Symlinks `app`.
Auf dem Server werden **weder git noch Composer noch Node** gebraucht – nur PHP ≥ 8.2 und eine Shell.
Deployt wird immer der letzte Commit (`HEAD`), nie der Arbeitsordner.

Unter Windows in der Git Bash (oder einer anderen Bash) ausführen. Benötigt werden `git`, `php`, `composer`, `npm`, `ssh`, `scp` und `tar`.

---

## Einmalige Einrichtung

### 1. Lokal

```bash
cp .deploy.env.example .deploy.env
```

In `.deploy.env` die SSH-Daten aus dem netcup CCP bzw. aus Plesk eintragen (`DEPLOY_USER`, `DEPLOY_HOST`, optional `DEPLOY_PHP`).
Die Datei steht in `.gitignore`.

**SSH-Schlüssel (empfohlen):** Ohne Schlüssel fragt jedes Deployment mehrmals nach dem Passwort.

```bash
ssh-keygen -t ed25519
cat ~/.ssh/id_ed25519.pub
```

Den ausgegebenen Public Key auf dem Server in `~/.ssh/authorized_keys` eintragen (oder in Plesk unter „SSH-Schlüssel“).

### 2. netcup / Plesk

Für die Subdomain `aromacare.caryssa.at`:

1. **SSH-Zugang** fürs Webhosting aktivieren (Shell `/bin/bash`, chrooted ist ok).
2. **PHP 8.3** (mindestens 8.2) einstellen. In denselben PHP-Einstellungen `open_basedir` auf `{WEBSPACEROOT}{/}{:}{TMP}{/}` setzen.
   Mit dem Standardwert `{DOCROOT}…` kann PHP nur `public/` lesen, und jede Seite liefert einen 500-Fehler.
   Benötigte Erweiterungen: `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `curl`, `xml`, `ctype`, `tokenizer`. Sie sind bei netcup standardmäßig aktiv.
3. **Postfach** für den Absender (hier `admin@caryssa.at`). Es wird für „Passwort vergessen“ gebraucht.
4. **SSL**: Let's-Encrypt-Zertifikat für die Subdomain aktivieren und „HTTP → HTTPS umleiten“ einschalten.
5. **Dokumentstamm**: `aromacare.caryssa.at/app/public` (so ist es bereits eingestellt).

### 3. Server vorbereiten

```bash
./deploy.sh --setup
```

Das legt `~/aromacare.caryssa.at/aromacare-app/` an, mit `releases/`, `shared/storage/`, `shared/database/database.sqlite` und `shared/.env`.
Die `.env` entsteht aus `.env.production.example` und bekommt einen frisch generierten `APP_KEY`.
Der Befehl kann gefahrlos mehrfach laufen, eine vorhandene `.env` bleibt unverändert.

`MAIL_*` und `LEGAL_*` werden dabei aus der lokalen `.env` übernommen. Danach per SSH oder im Plesk-Dateimanager
`aromacare.caryssa.at/aromacare-app/shared/.env` prüfen (Passwörter in einfache Hochkommas setzen):

| Variable | Wert |
| --- | --- |
| `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | aus Schritt 2.3: SMTP-Server, Postfach-Adresse und Passwort. Der Absender muss das Postfach sein. |
| `LEGAL_NAME`, `LEGAL_POSTAL_CODE`, `LEGAL_CITY`, `LEGAL_COUNTRY`, `LEGAL_COUNTRY_CODE` (AT/DE), `LEGAL_EMAIL` | Angaben für Impressum und Datenschutzerklärung (Privatperson, ohne Straße und Telefon). Mit Platzhaltern bricht `./deploy.sh` ab. |

### 4. Erstes Deployment

```bash
./deploy.sh
```

Beim ersten Mal schiebt das Skript den von Plesk angelegten Ordner `aromacare.caryssa.at/app` automatisch beiseite
(`app.plesk-<datum>`) und ersetzt ihn durch den Symlink auf das erste Release.
Danach https://aromacare.caryssa.at öffnen, ein Konto registrieren, fertig.

**Datenbank:** SQLite in `shared/database/database.sqlite`, es muss keine MySQL-Datenbank angelegt werden.
Cache und Sitzungen liegen als Dateien in `shared/storage/` (kein Redis nötig).
Backup: die Datei `database.sqlite` herunterladen.

---

## Alltag

| Was | Wie |
| --- | --- |
| Neue Version online stellen | Änderungen committen, dann `./deploy.sh` |
| Nur den Build prüfen | `./deploy.sh --build-only` (lädt nichts hoch) |
| Notfall ohne Tests | `./deploy.sh --skip-tests` |
| Nicht committete Änderungen im Arbeitsordner | `./deploy.sh --allow-dirty` (deployt trotzdem nur `HEAD`) |
| Logs ansehen | `~/aromacare.caryssa.at/aromacare-app/shared/storage/logs/laravel-*.log`, PHP-Fehler in `/logs/aromacare.caryssa.at/error_log` |
| Rollback | `cd ~/aromacare.caryssa.at && ls aromacare-app/releases`, dann `ln -sfn aromacare-app/releases/<älteres-release> app`. Migrationen werden dabei nicht zurückgedreht. |

## Struktur auf dem Server

```
~/aromacare.caryssa.at/
├── app -> aromacare-app/releases/20261102-120000-abc1234   ← Dokumentstamm: app/public
└── aromacare-app/
    ├── releases/                                         ← die letzten 5 Releases
    ├── shared/
    │   ├── .env
    │   ├── database/database.sqlite                      ← SQLite-Datenbank (bleibt bei jedem Release)
    │   └── storage/                                      ← Logs, Sessions, Caches
    └── uploads/                                          ← temporär während des Deployments
```

## Mails & Queue

Im Webhosting läuft kein dauerhafter Queue-Worker. Deshalb steht in der Produktions-`.env` `QUEUE_CONNECTION=sync`:
Die „Passwort vergessen“-Mail wird direkt beim Absenden des Formulars verschickt.
Die Notification ist weiterhin `ShouldQueue`. Auf einem Server mit Worker reicht `QUEUE_CONNECTION=database` plus `php artisan queue:work`.

## Fehlersuche

- **„Kein PHP >= 8.2 gefunden“**: In Plesk unter „PHP-Einstellungen“ steht der Pfad, z. B. `/usr/local/php83/bin/php`. Diesen in `.deploy.env` als `DEPLOY_PHP` eintragen.
- **500-Fehler nach dem Deployment**: Zuerst `/logs/aromacare.caryssa.at/error_log` und `shared/storage/logs` prüfen. Steht dort „open_basedir restriction“, `open_basedir` wie in Schritt 2.2 setzen. Danach kontrollieren, ob die DB-Zugangsdaten in `shared/.env` stimmen.
- **Chroot-Shell**: Die SSH-Shell sieht andere absolute Pfade als der Webserver (`/aromacare.caryssa.at/…` statt `/var/www/vhosts/…`). Deshalb sind die Links zu `shared/` relativ, und es gibt kein `config:cache`, `view:cache` oder `route:cache`.
- **`/up` liefert 500, die Log-Datei nennt `open_basedir` und einen Pfad ohne `/var/www/vhosts/…`**: Ein Routen-Cache enthält Pfade aus der SSH-Shell. Im aktiven Release `php artisan route:clear` ausführen (`cd ~/aromacare.caryssa.at/app`).
- **Migration schlägt fehl**: Das alte Release bleibt aktiv, der Wartungsmodus wird automatisch beendet. Den Fehler beheben, committen und erneut `./deploy.sh` ausführen.
- **Passwort-Mails kommen nicht an**: `MAIL_*` in `shared/.env` prüfen; der Absender (`MAIL_FROM_ADDRESS`) muss das angelegte Postfach sein.
