# DATEV File Dispatcher

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/github/license/Daniel-Jorg-Schuppelius/datev-filedispatcher)](https://github.com/Daniel-Jorg-Schuppelius/datev-filedispatcher/blob/main/LICENSE)

Ein PHP-Tool zur automatischen Organisation und Sortierung von Mandantendateien aus dem DATEV Document Management System (DMS). Die Dateien werden basierend auf ihrer Zuordnung automatisch in die entsprechenden Mandantenverzeichnisse verschoben.

## 🚀 Features

- **Automatische Dateisortierung**: Dokumente werden automatisch in die passenden Mandantenordner einsortiert
- **DATEV API Integration**: Direkte Anbindung an die DATEV DMS API
- **Pattern-basierte Verarbeitung**: Flexible Service-Architektur mit Regex-Pattern-Matching und fester Prüfreihenfolge
- **PreProcessing**: TIFF-Konvertierung, PDF-Verarbeitung, Multi-Page-Handling
- **Nextcloud-Integration**: Direkte Sortierung in Nextcloud-Verzeichnisse für einfache Mandantenkommunikation
- **Robuster Betrieb**: Wiederholung liegengebliebener Dateien, begrenzte Parallelität, gehärtete systemd-Unit
- **Erweiterbar**: Einfaches Hinzufügen neuer File-Services durch dynamische Service-Discovery

## 📋 Voraussetzungen

- PHP 8.2, 8.3 oder 8.4
- DATEV Account mit API-Zugang
- Composer
- Externe Tools (siehe Installation)

## 📦 Installation

### Klonen des Repositories

```bash
# Mit Submodulen klonen
git clone --recurse-submodules https://github.com/Daniel-Jorg-Schuppelius/datev-filedispatcher.git
cd datev-filedispatcher
composer install --no-dev

# Oder falls bereits geklont, Submodule initialisieren
git submodule update --init --recursive
```

### Automatische Installation der Abhängigkeiten (Linux)

Auf Debian/Ubuntu können alle Abhängigkeiten automatisch installiert werden:

```bash
sudo apt install jq
./installscript/install-dependencies.sh
```

Das Skript scannt automatisch das `vendor/`-Verzeichnis und installiert alle erforderlichen Tools, die in `*executables.json` Konfigurationsdateien definiert sind. Für den Dienst wird zusätzlich `inotify-tools` benötigt.

### Manuelle Installation der externen Tools

#### 1. TIFF Tools
Erforderlich für die Verarbeitung von TIFF-Dateien.
- **Windows**: [GnuWin32 TIFF Tools](https://gnuwin32.sourceforge.net/packages/tiff.htm)
- **Debian/Ubuntu**:
  ```bash
  apt install libtiff-tools
  ```

#### 2. Xpdf
Erforderlich für die PDF-Verarbeitung.
- **Windows**: [Xpdf Download](https://www.xpdfreader.com/download.html)
- **Debian/Ubuntu**:
  ```bash
  apt install xpdf
  ```

#### 3. ImageMagick
Für die Konvertierung und Verarbeitung von Bilddateien.
- **Windows**: [ImageMagick Installer](https://imagemagick.org/script/download.php#windows)
- **Debian/Ubuntu**:
  ```bash
  apt install imagemagick-6.q16hdri
  ```

#### 4. muPDF Tools
Für die Verarbeitung von PDF- und XPS-Dokumenten.
- **Debian/Ubuntu**:
  ```bash
  apt install mupdf-tools
  ```

#### 5. qpdf
Für PDF-Manipulation und -Reparatur.
- **Windows**: [qpdf Releases](https://github.com/qpdf/qpdf/releases)
- **Debian/Ubuntu**:
  ```bash
  apt install qpdf
  ```

#### 6. inotify-tools (nur Dienst unter Linux)
- **Debian/Ubuntu**:
  ```bash
  apt install inotify-tools
  ```

## ⚙️ Konfiguration

### Konfigurationsdatei erstellen

```bash
cp config/config.json.sample config/config.json
chmod 600 config/config.json
```

Ein anderer Ablageort (z. B. `/etc/filedispatcher/config.json`) wird über die Umgebungsvariable `FILEDISPATCHER_CONFIG` gesetzt. Fehlt die Konfiguration, bricht der Dispatcher mit einer klaren Meldung ab.

### Konfigurationsoptionen

Die Konfiguration erfolgt über `config/config.json`:

| Sektion | Schlüssel | Beschreibung |
|---------|-----------|--------------|
| `DatevAPI` | `resourceurl` | URL zur DATEV API (Standard: `https://127.0.0.1:58452`) |
| `DatevAPI` | `user` | Benutzername für API-Authentifizierung |
| `DatevAPI` | `password` | Passwort für API-Authentifizierung |
| `DatevAPI` | `verifySSL` | TLS-Zertifikatsprüfung, Standard `true` |
| `DatevAPI` | `caFile` | Optional: PEM-Datei mit dem (selbstsignierten) Zertifikat von DATEVconnect |
| `Path` | `internalStore` | Pfad zum internen Speicher mit `{tenant}` Platzhalter für Mandantenverzeichnisse |
| `Logging` | `log` | Log-Ausgabeziel (`Console`, `File`, `Null`) |
| `Logging` | `level` | Log-Level (`emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug`), Standard `info` |
| `Logging` | `path` | Pfad zur Log-Datei |
| `Debugging` | `debug` | Debug-Modus aktivieren (`true`/`false`) |
| `Zip` | `maxEntries`, `maxBytes`, `maxRatio` | Grenzen beim Entpacken von ZIP-Archiven (Standard: 1000 Einträge, 500 MB, Verhältnis 100) |

### Zugangsdaten per Umgebungsvariable

`DATEV_API_URL`, `DATEV_API_USER`, `DATEV_API_PASSWORD` und `DATEV_API_CA_FILE` haben Vorrang vor der `config.json`. So muss das Passwort nicht im Klartext im Projektverzeichnis liegen, z. B. in `/etc/default/filedispatcher` mit `chmod 600`.

### TLS-Verifizierung

Die Zertifikatsprüfung ist standardmäßig aktiv. Für ein selbstsigniertes DATEVconnect-Zertifikat gibt es zwei Wege, ohne `verifySSL` auf `false` zu setzen:

- **`caFile`**: Das Zertifikat als PEM-Datei ablegen (z. B. `/etc/filedispatcher/datevconnect-ca.pem`) und den Pfad in `DatevAPI.caFile` oder `DATEV_API_CA_FILE` eintragen. Es gilt dann nur für den Dispatcher. Setzt eine Version von `php-api-toolkit` mit `setCaBundle()` voraus; ist sie nicht installiert, bricht der Dispatcher mit einer Meldung ab.
- **Zertifikatsspeicher des Systems**: Debian/Ubuntu: nach `/usr/local/share/ca-certificates/` kopieren und `update-ca-certificates` ausführen. Das Zertifikat gilt dann für alle Programme.

Ohne Prüfung gehen die Zugangsdaten ungeschützt gegen Mitlesen über das Netz.

### Datenschutz im Log

Mitarbeiternamen erscheinen nur auf dem Log-Level `debug`. Dateinamen und Pfade enthalten trotzdem personenbezogene Daten, die Log-Datei gehört daher in ein Verzeichnis, das nur der Dienstbenutzer und seine Gruppe lesen können.

## 📚 Verwendung

### Einzelne Datei verarbeiten

```bash
php src/DatevFileDispatcher.php "/pfad/zur/datei.pdf"
```

### Unverarbeitete Dateien auflisten

```bash
php src/DatevFileDispatcher.php --list "/pfad/zum/eingang" [mindestalter_in_sekunden]
```

Gibt die Dateien NUL-getrennt aus. Ignorierte Dateien (`log.txt`, Lockfiles, Anleitungen zu Arbeitnehmer online), versteckte und leere Dateien sowie `ExcludedFolders` fehlen in der Liste.

## 🛠️ Betrieb als Dienst (Linux)

`scripts/filedispatcher.sh` überwacht das Eingangsverzeichnis mit `inotifywait` und übergibt neue Dateien an den Dispatcher. Zusätzlich gilt:

- **Wiederholung**: Beim Start und danach alle `FILEDISPATCHER_RESCAN_INTERVAL` Sekunden werden liegengebliebene Dateien erneut verarbeitet, z. B. nach einer Sicherung des DATEV-Servers.
- **Parallelität**: Höchstens `FILEDISPATCHER_MAX_JOBS` Dateien gleichzeitig je Quelle, damit große Lohnläufe DATEVconnect nicht überlasten.
- **Rechte**: Neu erzeugte Dateien bekommen die umask `FILEDISPATCHER_UMASK` (Standard `0027`: Dienstbenutzer schreibt, Gruppe liest, alle anderen nichts).
- **Locks**: Lockfiles liegen in einem Verzeichnis, das nur dem Dienstbenutzer gehört (Standard `/run/filedispatcher`).

Alle Einstellungen stehen mit Beschreibung in [config/default/filedispatcher.sample](config/default/filedispatcher.sample).

### systemd (empfohlen)

```bash
sudo useradd --system --no-create-home --shell /usr/sbin/nologin filedispatcher
sudo cp config/systemd/filedispatcher.service /etc/systemd/system/
sudo cp config/default/filedispatcher.sample /etc/default/filedispatcher
sudo chmod 600 /etc/default/filedispatcher
# ExecStart und ReadWritePaths in der Unit an die eigenen Pfade anpassen
sudo systemctl daemon-reload
sudo systemctl enable --now filedispatcher
```

Die Unit läuft als eigener Benutzer und ist gehärtet (`ProtectSystem=strict`, `PrivateTmp`, `NoNewPrivileges` u. a.). Beschreibbar sind nur Eingang und Mandantenablage (`ReadWritePaths`) sowie das Logverzeichnis `/var/log/filedispatcher`, das systemd selbst anlegt (`LogsDirectory`). Die Vorlage der `config.json` loggt bereits dorthin. Ein Log unter `/tmp` wäre wegen `PrivateTmp` nur im privaten Temp-Verzeichnis des Dienstes sichtbar.

**Nextcloud**: Neu erzeugte Dateien sind für die Gruppe nur lesbar (umask `0027`). Damit Nextcloud sie lesen kann, den Webserver in die Gruppe des Dienstes aufnehmen (z. B. `usermod -aG filedispatcher www-data`) und die Mandantenablage dieser Gruppe zuordnen (`chgrp -R filedispatcher` und `chmod g+s` auf die Verzeichnisse). Sollen Nutzer die Dateien in Nextcloud auch ändern oder löschen können, `FILEDISPATCHER_UMASK=0007` setzen.

### init.d (ohne systemd)

```bash
sudo useradd --system --no-create-home --shell /usr/sbin/nologin filedispatcher
sudo ln -s /pfad/zum/projekt/config/init.d/filedispatcher /etc/init.d/filedispatcher
sudo update-rc.d filedispatcher defaults
```

Das Skript startet den Dienst als `DAEMON_USER` (Standard `filedispatcher`, änderbar in `/etc/default/filedispatcher`). Existiert der Benutzer nicht, startet der Dienst nicht.

### ImageMagick absichern

Der Dienst verarbeitet Dateien aus dem Eingang mit externen Programmen. In `/etc/ImageMagick-6/policy.xml` sollten nur die benötigten Formate erlaubt sein, z. B.:

```xml
<policy domain="coder" rights="none" pattern="*" />
<policy domain="coder" rights="read|write" pattern="{TIFF,TIF,PDF,JPEG,PNG}" />
```

## 🏗️ Projektstruktur

```
src/
├── DatevFileDispatcher.php     # CLI Einstiegspunkt
├── Config/
│   └── Config.php              # Konfigurationsmanagement (Singleton)
├── Contracts/
│   ├── Abstracts/              # Basis-Klassen (FileHandlerAbstract, FileService-, PreProcess-Basen)
│   └── Interfaces/             # Interface-Definitionen
├── Enums/
│   └── PreProcessResult.php    # Ergebnis der Vorverarbeitung (Done, Skipped, Abort)
├── Factories/
│   ├── APIClientFactory.php    # DATEV API Client Factory
│   ├── LoggerFactory.php       # Logger Factory
│   └── StorageFactory.php      # Storage Path Factory
├── Helper/
│   ├── FileDispatcher.php      # Zentrale Orchestrierung
│   ├── FilenameSanitizer.php   # Bereinigung der Zieldateinamen
│   └── InternalStoreMapper.php # Mandanten-Verzeichnis-Mapping
├── PreProcessServices/         # Vorverarbeitung (TIFF, PDF, etc.)
│   ├── DuplicateNumberProcessFileService.php
│   ├── PDFNameProcessFileService.php
│   ├── PDFScannerCodeProcessFileService.php
│   ├── PDFTimeCodeProcessFileService.php
│   └── TiffPreProcessFileService.php
├── Services/                   # Datei-Services (Pattern-basiert)
│   ├── DMSBasicFileService.php
│   └── Payroll/                # Lohnabrechnungs-Services
└── Traits/
    └── PeriodicFileServiceTrait.php
config/
├── config.json.sample          # Vorlage der Konfiguration
├── default/                    # Vorlage für /etc/default/filedispatcher
├── init.d/                     # init.d-Skript
└── systemd/                    # systemd-Unit
```

## 🔌 Service-Architektur

### File Services

Services werden automatisch aus dem `src/Services/` Verzeichnis geladen und verarbeiten Dateien basierend auf Regex-Patterns:

| Service | Pattern-Beispiel | Beschreibung |
|---------|------------------|--------------|
| `DMSBasicFileService` | `219628 - Dokument.pdf` | Standard DMS-Dateien |
| Payroll Services | `20542_10_2024_Lohnjournal_AA0.pdf` | Lohnabrechnungs-Dokumente |

Passen mehrere Patterns, gewinnt der Service mit der höchsten `PRIORITY` (Standard `0`), bei Gleichstand der alphabetisch erste. Generische Fallbacks wie der `EmployeeFileService` haben eine negative Priorität. Der `ServiceRoutingTest` prüft für jeden Service, dass seine Beispieldateien bei ihm landen. Ein neuer Service braucht dort einen Testfall.

Ein typischer Lohn-Service besteht nur aus Konstanten:

```php
class PayrollJournalFileService extends PayrollFileServiceAbstract {
    // 00000_10_2024_Lohnjournal_AA0.pdf
    protected const PATTERN = '/^' . self::PERIOD_PREFIX . 'Lohnjournal' . self::EVALUATION_SUFFIX . '\.pdf$/i';

    protected const DOCUMENT_TYPE = "Lohnjournal";
}
```

- `DOCUMENT_TYPE`: Zieldateiname `<DOCUMENT_TYPE>.pdf`. Enthält das Pattern die Gruppe `employee` (`self::EMPLOYEE`), lautet er `<DOCUMENT_TYPE>-<Nr>_<Nachname>_<Vorname>.pdf`.
- `DESTINATION_EXTENSION`: andere Endung als `pdf`.
- `FORCE_YEARLY_STORAGE`: immer im Jahresordner ablegen, unabhängig von `PerYear`/`PerPeriod`.

### PreProcess Services

Vorverarbeitungs-Services aus `src/PreProcessServices/`. `preProcess()` liefert ein `PreProcessResult`:

| Ergebnis | Bedeutung |
|----------|-----------|
| `Done` | Vorverarbeitung erledigt, weiter mit den File Services |
| `Skipped` | Nicht zuständig, nächster PreProcess Service bzw. generische Vorverarbeitung |
| `Abort` | Datei bleibt liegen, z. B. solange Seiten eines mehrseitigen TIFFs fehlen |

| Service | Beschreibung |
|---------|--------------|
| `TiffPreProcessFileService` | Mehrseitige TIFFs zusammenführen und in PDF umwandeln |
| `PDFNameProcessFileService` | DMS-Kürzel (BvFA, BaFA, ...) aus dem Dateinamen entfernen |
| `PDFScannerCodeProcessFileService` | Scanner-Code aus dem Dateinamen entfernen |
| `PDFTimeCodeProcessFileService` | Zeitstempel aus dem Dateinamen entfernen |
| `DuplicateNumberProcessFileService` | Zusätze wie ` (1)` oder ` - Kopie` entfernen |

Die Umbenennungs-Services ändern nur den Dateinamen, nie das Verzeichnis.

## 🧪 Tests

### Test-Konfiguration

```bash
cp config/testconfig.json.sample config/testconfig.json
```

### Tests ausführen

```bash
composer test
# Formatierung, statische Analyse und Tests
composer qa
```

## 📖 Abhängigkeiten

- [datev-php-sdk](https://github.com/daniel-jorg-schuppelius/datev-php-sdk) (^1.0) - DATEV API SDK

## 📄 Lizenz

Dieses Projekt ist unter der [MIT-Lizenz](https://github.com/Daniel-Jorg-Schuppelius/datev-filedispatcher/blob/main/LICENSE) lizenziert.

## 💖 Unterstützung

Wenn Ihnen dieses Projekt gefällt und es Ihnen bei Ihrer Arbeit hilft, würde ich mich sehr über eine Spende freuen!

[![GitHub Sponsors](https://img.shields.io/badge/Sponsor-GitHub-ea4aaa)](https://github.com/sponsors/Daniel-Jorg-Schuppelius)
[![PayPal](https://img.shields.io/badge/Donate-PayPal-blue)](https://www.paypal.com/donate/?hosted_button_id=X43UQQVDKL76Y)

## 👤 Autor

**Daniel Jörg Schuppelius**

- Website: [schuppelius.org](https://schuppelius.org/)
- E-Mail: [info@schuppelius.org](mailto:info@schuppelius.org)
