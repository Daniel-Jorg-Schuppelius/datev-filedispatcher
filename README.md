# datev-filedispatcher

Ein Tool zur automatischen Organisation und Sortierung von Mandantendateien, die aus dem DATEV Dokumenten-Management-System (DMS) in ein Verzeichnis gezogen werden. Die Dateien werden anhand ihrer Mandantenzuordnung in die entsprechenden Verzeichnisse verschoben.

## Funktionsweise
Das Tool nutzt die DATEV API, um die Dokumente automatisiert in die passenden Mandantenordner zu sortieren. Dabei wird eine Dateistruktur erstellt, die das schnelle Wiederauffinden und eine strukturierte Ablage ermöglicht. Unterstützt werden dabei verschiedene Datei- und Bildformate.

### Hinweis zur Nutzung mit Nextcloud
`datev-filedispatcher` kann in Verbindung mit Nextcloud eingesetzt werden, um Mandantendateien einfach und sicher zu teilen. Da viele Kanzleien oder Unternehmen für die Mandantenkommunikation häufig noch auf E-Mails setzen, ermöglicht der Einsatz dieses Tools in Kombination mit Nextcloud eine strukturierte und moderne Alternative zur Bereitstellung von Dokumenten. Die Mandanten erhalten direkten Zugriff auf die für sie relevanten Dateien und können so auf dem aktuellen Stand bleiben, ohne auf E-Mail-Kommunikation angewiesen zu sein. Hierbei werden die Dateien direkt in das Nextcloudverzeichnis einsortiert.

## Voraussetzungen
Um den `datev-filedispatcher` erfolgreich auszuführen, sind folgende Tools erforderlich:

### 1. TIFF Tools
Wird zur Bearbeitung und Verarbeitung von TIFF-Dateien benötigt.
- **Windows**: [GnuWin32 TIFF Tools](https://gnuwin32.sourceforge.net/packages/tiff.htm)
- **Debian/Ubuntu**: 
  ```bash
  apt install libtiff-tools
  ```

### 2. Xpdf
Erforderlich für die Bearbeitung von PDF-Dateien.
- **Windows**: [Xpdf Download](https://www.xpdfreader.com/download.html)
- **Debian/Ubuntu**:
  ```bash
  apt install xpdf
  ```

### 3. ImageMagick
Zur Konvertierung und Bearbeitung von Bilddateien.
- **Windows**: [ImageMagick Installer](https://imagemagick.org/archive/binaries/ImageMagick-7.1.1-39-Q16-HDRI-x64-dll.exe)
- **Debian/Ubuntu**:
  ```bash
  apt install imagemagick-6.q16hdri
  ```

### 4. muPDF Tools
Für die PDF- und XPS-Dokumentverarbeitung.
- **Debian/Ubuntu**:
  ```bash
  apt install mupdf-tools
  ```

## Installation und Nutzung
1. **Voraussetzungen** installieren (siehe oben).
2. Den `datev-filedispatcher` herunterladen und konfigurieren.
3. Ausführung: Nach dem Platzieren der Dateien im Überwachungsordner beginnt die automatische Sortierung in die entsprechenden Mandantenordner.

### Bash-Skript für die Überwachung des entsprechenden Ordners
Um das Bash-Skript unter Linux als Dienst zu aktivieren, führen Sie die folgenden Befehle aus:
```bash
sudo ln -s /path/to/your/project/config/init.d/filedispatcher /etc/init.d/filedispatcher
sudo update-rc.d filedispatcher defaults
```

## Lizenz
Dieses Projekt steht unter der MIT-Lizenz. Weitere Informationen finden Sie in der Datei [LICENSE](LICENSE). Es wäre nett, wenn ihr bei einer entsprechenden kommerziellen Nutzung an mich denken würdet.
