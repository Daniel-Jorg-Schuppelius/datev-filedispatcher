# DATEV File Dispatcher - AI Agent Guide

## Architecture Overview

This is a PHP file processing system that automatically organizes documents from DATEV DMS into client-specific directories. The system uses a **service-based architecture** with pattern matching and preprocessing.

### Core Components

1. **Entry Point**: [`src/DatevFileDispatcher.php`](src/DatevFileDispatcher.php) - CLI script that processes individual files
2. **FileDispatcher**: [`src/Helper/FileDispatcher.php`](src/Helper/FileDispatcher.php) - Central orchestrator using dynamic service discovery
3. **Service Layer**: Auto-discovered classes implementing file processing patterns
4. **PreProcessing**: TIFF conversion, PDF processing, multi-page handling

## Key Architectural Patterns

### Dynamic Service Discovery
Services are auto-loaded from directories using `ConfigToolkit\ClassLoader`:
- **File Services** (`src/Services/`): Process files by pattern matching (e.g., `DMSBasicFileService`)
- **PreProcess Services** (`src/PreProcessServices/`): Handle format conversions before main processing

### Pattern-Based File Processing
Each service defines a **regex pattern** to match specific filename formats:
```php
// Example from DMSBasicFileService
protected const PATTERN = '/^([0-9]+) - (.+?)(?: - (\d{4}_\d+))?\.pdf$/i';
// Matches: "219628 - Lohn Mandantenunterlagen.pdf"
```

### Service Inheritance Hierarchy
```
FileServiceInterface
├── FileServiceAbstract (base functionality)
│   ├── DMSFileServiceAbstract (DMS-specific)
│   └── PeriodicFileServiceAbstract (scheduled processing)
└── PreProcessFileServiceInterface
    └── PreProcessFileServiceAbstract
```

## Configuration System

### JSON-based Config
- **Main**: `config/config.json` (from `config.json.sample`)
- **Test**: `config/testconfig.json` (for unit tests)
- **Singleton Pattern**: `Config::getInstance()` with lazy loading

### Critical Config Keys
```json
{
  "DatevAPI": [{"key": "resourceurl", "value": "https://127.0.0.1:58452"}],
  "Path": [{"key": "internalStore", "value": "/path/with/{tenant}"}],
  "ExcludedFolders": ["/excluded/path1", "/excluded/path2"]
}
```

**Important**: `internalStore` path MUST contain `{tenant}` placeholder for client-specific directories.

## Development Workflow

### Adding New File Services

1. **Create Service Class** in `src/Services/` extending `DMSFileServiceAbstract`
2. **Define Pattern**: Set `PATTERN` constant for filename matching
3. **Implement Methods**:
   - `extractDataFromFile()`: Parse filename components
   - `getDestinationFilename()`: Generate output filename
4. **Auto-Discovery**: Class automatically loaded by `FileDispatcher`

### Adding PreProcessing

1. **Create in** `src/PreProcessServices/` extending `PreProcessFileServiceAbstract` 
2. **File Type Check**: Use `FILE_EXTENSION_PATTERN` to match extensions
3. **Processing Logic**: Implement `preProcess()` method for format conversion

### Testing Conventions

- **Test Structure**: Mirrors `src/` directory in `tests/`
- **Base Classes**: Extend `DocumentTest` for API-integrated tests
- **Sample Files**: Place test files in `.samples/` directory
- **API Mocking**: Set `$this->apiDisabled = true` to skip API calls

### External Tool Dependencies

**Required for file processing**:
- **TIFF Tools** (Windows: GnuWin32, Linux: libtiff-tools)
- **Xpdf** (PDF text extraction)
- **ImageMagick** (image conversion)
- **muPDF Tools** (PDF processing)

## Common Development Tasks

### Running Tests
```bash
composer test
# OR
vendor/bin/phpunit
```

### Processing File Manually
```bash
php src/DatevFileDispatcher.php "/path/to/file.pdf"
```

### Service Debugging
- Services use `ERRORToolkit\Traits\ErrorLog` for consistent logging
- Check logs for pattern matching failures and API errors
- Use `Config::getInstance()->setDebug(true)` in tests

## Integration Points

### DATEV API Integration
- **SDK**: `daniel-jorg-schuppelius/datev-php-sdk`
- **Endpoints**: `ClientsEndpoint`, `DocumentsEndpoint`, `PayrollClientsEndpoint`
- **Factory Pattern**: `APIClientFactory::getClient()`

### File System Integration
- **CommonToolkit**: File handling utilities (`File::isReady()`, `File::wait4Ready()`)
- **StorageFactory**: Client directory path resolution with `{tenant}` substitution
- **InternalStoreMapper**: Maps DATEV clients to local directory structure

## Key Conventions

- **Namespace**: All classes use `App\` namespace mapped to `src/`
- **Error Handling**: Use `ErrorLog` trait, throw exceptions for critical failures
- **File Naming**: Use descriptive patterns matching DATEV filename conventions
- **German Logging**: Log messages are in German (business requirement)
- **Path Handling**: Use forward slashes, normalize with `realpath()`

When implementing new features, follow the existing service discovery pattern and ensure proper pattern matching for reliable file processing.