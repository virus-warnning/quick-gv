# QuickGV

A MediaWiki extension to render SVG diagrams with minimal dot syntax.

See: https://www.mediawiki.org/wiki/Extension:QuickGV

## Requirements

### Runtime

- PHP >= 8.3.36
- MediaWiki >= 1.43
- [Graphviz](https://graphviz.org/) — the `dot` binary must be available in `$PATH`

### Development

- PHP >= 8.3.36
- `ast` extension (for Phan)
- Composer

## Installation

Add to your MediaWiki root `composer.local.json`:

```json
{
    "require": {
        "virus-warnning/quick-gv": "^1.0"
    }
}
```

Then run:

```bash
composer update
```

## Configuration

No required configuration. The following constants in `src/ExtensionConstants.php` can be configured:

| Constant | Default | Description |
|----------|---------|-------------|
| `ENABLE_SVG_CACHE` | `true` | Enable MD5-based SVG caching to skip re-conversion |
| `DUMP_DEBUG_FILES` | `true` | Write debug artifacts (`.dot`, `.txt`) to the `debug/` directory |

## Usage

In any MediaWiki page, use the `<quickgv>` parser hook:

```wikitext
<quickgv theme="default" name="mychart">
digraph G {
    A[Start] --> B{Decision}
    B -->|Yes| C[Action]
    B -->|No| D[Other]
}
</quickgv>
```

### Attributes

| Attribute | Required | Default | Description |
|-----------|----------|---------|-------------|
| `theme`   | No       | `default` | Color theme (`cold`, `warm`, `sakura`, `default`) |
| `name`    | No       | `G`     | Graph name (1–25 chars) used as the SVG cache filename |
| `usage`   | No       | —       | Graphviz layout engine (`dot`, `neato`, `fdp`, `sfdp`, `twopi`, `circo`) |
| `dump-env`| No       | `false` | Display an environment info table below the SVG |
| `dump-gv` | No       | `false` | Display the generated dot syntax below the SVG |

### Theme Options

| Theme | Description |
|-------|-------------|
| `default` | Graphviz default color palette |
| `cold`    | Cool blue-green color scheme |
| `warm`    | Warm red-orange color scheme |
| `sakura`  | Sakura pink color scheme |

## File Structure

```
|-- extension.json              # 📋 MediaWiki extension registration
|-- composer.json               # 📦 Composer dependencies
|-- phpcs.xml                   # ✅ PHPCS config (MediaWiki standard)
|-- phpunit.xml                 # 🧪 PHPUnit config
|-- LICENSE                     # 📄 MIT License
|-- src/
|   |-- ExtensionConstants.php  # 🏷️ Extension constants
|   |-- Hook.php                # 🪝 MediaWiki hook registration & main logic
|   |-- Command.php             # 🖥️ Shell command execution (ShellBox)
|   |-- FieldResult.php         # 📝 Form field result wrapper
|   |-- FileSystemUtils.php     # 📁 File system utilities
|   |-- Validator.php           # ✔️ Dot syntax validation
|-- templates/
|   |-- gv-builder.php          # 🏗️ Composes final dot syntax from theme & usage (stdin → dot)
|-- i18n/
|   |-- en.json                 # 🇬🇧 English translations
|   |-- zh-hant.json            # 🇹🇼 Traditional Chinese translations
|   |-- ja.json                 # 🇯🇵 Japanese translations
|-- tests/
|   |-- bootstrap.php           # 🚀 Test bootstrap (stubs MW globals)
|   |-- FieldResultTest.php     # 🧪 FieldResult unit tests
|   |-- FileSystemUtilsTest.php # 🧪 FileSystemUtils unit tests
|   |-- ValidatorTest.php       # 🧪 Validator unit tests
|-- debug/                      # 🐛 Debug files (not in VCS)
```

## Development

### Run Tests

```bash
composer test
```

### Code Style

```bash
# Check
composer phpcs

# Auto-fix
composer phpcbf
```

### Static Analysis

```bash
composer phan
```

## License

[MIT](LICENSE)
