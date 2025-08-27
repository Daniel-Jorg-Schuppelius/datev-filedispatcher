# datev-filedispatcher

A tool for automatically organizing and sorting client files that are pulled from the DATEV Document Management System (DMS) into a directory. The files are moved into the corresponding client directories based on their assignment.

## Functionality
The tool uses the DATEV API to automatically sort documents into the appropriate client folders. This creates a file structure that enables quick retrieval and structured storage. Various file and image formats are supported.

### Note on Using with Nextcloud
`datev-filedispatcher` can be used in conjunction with Nextcloud to share client files easily and securely. Since many law firms or companies still rely on email for client communication, using this tool in combination with Nextcloud provides a structured and modern alternative for document distribution. Clients have direct access to their relevant files and can stay up to date without relying on email communication. The files are directly sorted into the Nextcloud directory.

## Requirements
The following tools are required to successfully run `datev-filedispatcher`:

### 1. TIFF Tools
Required for processing and handling TIFF files.
- **Windows**: [GnuWin32 TIFF Tools](https://gnuwin32.sourceforge.net/packages/tiff.htm)
- **Debian/Ubuntu**: 
  ```bash
  apt install libtiff-tools
  ```

### 2. Xpdf
Required for handling PDF files.
- **Windows**: [Xpdf Download](https://www.xpdfreader.com/download.html)
- **Debian/Ubuntu**:
  ```bash
  apt install xpdf
  ```

### 3. ImageMagick
For converting and processing image files.
- **Windows**: [ImageMagick Installer](https://imagemagick.org/script/download.php#windows)
- **Debian/Ubuntu**:
  ```bash
  apt install imagemagick-6.q16hdri
  ```

### 4. muPDF Tools
For processing PDF and XPS documents.
- **Debian/Ubuntu**:
  ```bash
  apt install mupdf-tools
  ```

## Installation and Usage
1. Install the **requirements** (see above).
2. Download and configure `datev-filedispatcher`.
3. Execution: Once files are placed in the monitored directory, the automatic sorting into the corresponding client folders begins.

### Bash Script for Monitoring the Corresponding Directory
To activate the Bash script as a service on Linux, run the following commands:
```bash
sudo ln -s /path/to/your/project/config/init.d/filedispatcher /etc/init.d/filedispatcher
sudo update-rc.d filedispatcher defaults
```

## License
This project is licensed under the MIT License. For more information, see the [LICENSE](LICENSE) file. It would be nice if you consider supporting me for any commercial use.
