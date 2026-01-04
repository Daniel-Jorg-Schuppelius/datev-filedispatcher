# DATEV File Dispatcher

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/github/license/Daniel-Jorg-Schuppelius/datev-filedispatcher)](https://github.com/Daniel-Jorg-Schuppelius/datev-filedispatcher/blob/main/LICENSE)

Ein PHP-Tool zur automatischen Organisation und Sortierung von Mandantendateien aus dem DATEV Document Management System (DMS). Die Dateien werden basierend auf ihrer Zuordnung automatisch in die entsprechenden Mandantenverzeichnisse verschoben.

## 🚀 Features

- **Automatische Dateisortierung**: Dokumente werden automatisch in die passenden Mandantenordner einsortiert
- **DATEV API Integration**: Direkte Anbindung an die DATEV DMS API
- **Pattern-basierte Verarbeitung**: Flexible Service-Architektur mit Regex-Pattern-Matching
- **PreProcessing**: TIFF-Konvertierung, PDF-Verarbeitung, Multi-Page-Handling
- **Nextcloud-Integration**: Direkte Sortierung in Nextcloud-Verzeichnisse für einfache Mandantenkommunikation
- **Erweiterbar**: Einfaches Hinzufügen neuer File-Services durch dynamische Service-Discovery

## 📋 Voraussetzungen

- PHP 8.2, 8.3 oder 8.4
- DATEV Account mit API-Zugang
- Composer
- Externe Tools (siehe Installation)

## 📦 Installation

### Composer

```bash
composer require daniel-jorg-schuppelius/datev-filedispatcher
```

### Klonen des Repositories

```bash
# Mit Submodulen klonen
git clone --recurse-submodules https://github.com/Daniel-Jorg-Schuppelius/datev-filedispatcher.git

# Oder falls bereits geklont, Submodule initialisieren
git submodule update --init --recursive
```

### Automatische Installation der Abhängigkeiten (Linux)

Auf Debian/Ubuntu können alle Abhängigkeiten automatisch installiert werden:

```bash
sudo apt install jq
./installscript/install-dependencies.sh
```

Das Skript scannt automatisch das `vendor/`-Verzeichnis und installiert alle erforderlichen Tools, die in `*executables.json` Konfigurationsdateien definiert sind.

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

## ⚙️ Konfiguration

### Konfigurationsdatei erstellen

```bash
cp config/config.json.sample config/config.json
```

### Konfigurationsoptionen

Die Konfiguration erfolgt über `config/config.json`:

| Sektion | Schlüssel | Beschreibung |
|---------|-----------|--------------|
| `DatevAPI` | `resourceurl` | URL zur DATEV API (Standard: `https://127.0.0.1:58452`) |
| `DatevAPI` | `user` | Benutzername für API-Authentifizierung |
| `DatevAPI` | `password` | Passwort für API-Authentifizierung |
| `DatevAPI` | `verifySSL` | SSL-Zertifikatsprüfung (`true` für Produktion, `false` für selbstsignierte Zertifikate) |
| `Path` | `internalStore` | Pfad zum internen Speicher mit `{tenant}` Platzhalter für Mandantenverzeichnisse |
| `Logging` | `log` | Log-Ausgabeziel (`Console`, `File`, `Null`) |
| `Logging` | `level` | Log-Level (`emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug`) |
| `Logging` | `path` | Pfad zur Log-Datei |
| `Debugging` | `debug` | Debug-Modus aktivieren (`true`/`false`) |

### SSL-Verifizierung

Für Entwicklungsumgebungen mit selbstsignierten Zertifikaten setzen Sie `verifySSL` auf `false`. In der Produktion sollte dies immer auf `true` gesetzt sein.

## 📚 Verwendung

### Einzelne Datei verarbeiten

```bash
php src/DatevFileDispatcher.php "/pfad/zur/datei.pdf"
```

### Als Linux-Service einrichten

```bash
sudo ln -s /pfad/zum/projekt/config/init.d/filedispatcher /etc/init.d/filedispatcher
sudo update-rc.d filedispatcher defaults
```

## 🏗️ Projektstruktur

```
src/
├── DatevFileDispatcher.php     # CLI Einstiegspunkt
├── Config/
│   └── Config.php              # Konfigurationsmanagement (Singleton)
├── Contracts/
│   ├── Abstracts/              # Basis-Klassen
│   └── Interfaces/             # Interface-Definitionen
├── Factories/
│   ├── APIClientFactory.php    # DATEV API Client Factory
│   ├── LoggerFactory.php       # Logger Factory
│   └── StorageFactory.php      # Storage Path Factory
├── Helper/
│   ├── FileDispatcher.php      # Zentrale Orchestrierung
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
    ├── FileServiceTrait.php
    └── PeriodicFileServiceTrait.php
```

## 🔌 Service-Architektur

### File Services

Services werden automatisch aus dem `src/Services/` Verzeichnis geladen und verarbeiten Dateien basierend auf Regex-Patterns:

| Service | Pattern-Beispiel | Beschreibung |
|---------|------------------|--------------|
| `DMSBasicFileService` | `219628 - Dokument.pdf` | Standard DMS-Dateien |
| Payroll Services | `Lohn*.pdf` | Lohnabrechnungs-Dokumente |

### PreProcess Services

Vorverarbeitungs-Services aus `src/PreProcessServices/`:

| Service | Beschreibung |
|---------|--------------|
| `TiffPreProcessFileService` | TIFF zu PDF Konvertierung |
| `PDFNameProcessFileService` | PDF-Namensextraktion |
| `PDFScannerCodeProcessFileService` | Scanner-Code Verarbeitung |
| `PDFTimeCodeProcessFileService` | Zeitcode-Verarbeitung |
| `DuplicateNumberProcessFileService` | Duplikat-Erkennung |

## 🧪 Tests

### Test-Konfiguration

```bash
cp config/testconfig.json.sample config/testconfig.json
```

### Tests ausführen

```bash
composer test
# oder
vendor/bin/phpunit
```

## 📖 Abhängigkeiten

- [datev-php-sdk](https://github.com/daniel-jorg-schuppelius/datev-php-sdk) (^0.4.1) - DATEV API SDK
- [php-api-toolkit](https://github.com/daniel-jorg-schuppelius/php-api-toolkit) - Basis-Klassen für API-Integration
- [GuzzleHttp](https://github.com/guzzle/guzzle) - HTTP Client
- [PSR-3 Logger](https://www.php-fig.org/psr/psr-3/) - Logging-Interface

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
