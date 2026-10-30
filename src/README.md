# QuickGV — Source

## Overview
Core source files for the QuickGV MediaWiki extension. Implements the `<quickgv>` parser hook, shell command execution, input validation, SVG generation via Graphviz `dot`, and file system utilities. All classes live in the `MediaWiki\Extension\QuickGV` namespace.

## Interface
- `Command` — `execute(...)`, `pipeExec(array $arguments, $stdin, &$stdout, &$stderr, $encoding)`, `findExecutable($exec_name)` — ShellBox sandboxed command execution
- `ExtensionConstants` — `DUMP_DEBUG_FILES`, `ENABLE_SVG_CACHE`, `IMG_STYLES`, `LOGGING_STYLES` — extension-wide configuration constants
- `FieldResult` — `createOkay($field, $value)`, `createWarning($field, $value, $msg)`, `createError($field, $msg)` — validation result value object
- `FileSystemUtils` — `dumpDebugFile($name, $content, $mode)`, `getFriendlySize($path)`, `getSafeName($name)`, `loadSummary($path)`, `saveSummary($path, array $summary)` — file I/O helpers
- `Hook` — `init(&$parser)`, `render($syntax, $props, $parser, $frame)`, `genFullHtml()` — MediaWiki `ParserFirstCallInit` entry point, `<quickgv>` parser hook handler, and HTML output generator
- `Validator` — `getDescriptor()`, `buildValidationMessage($field, $spec)`, `validate(array $rawArgs)` — input validation for `syntax`, `name`, `theme`, `usage`, `dump-env`, `dump-gv`

## Invariants
- `Hook::init()` must execute before any `Hook::render()` call (registered via `ParserFirstCallInit` in `extension.json`)
- `Hook::$sys_errors` must be empty for the `Hook` constructor to proceed; if non-empty, SVG generation is skipped
- `Validator::MAX_SYNTAX_SIZE` (1 048 576 bytes) caps dot syntax input length
- `Validator::NAME_LBOUND` (1) and `Validator::NAME_UBOUND` (25) constrain the `name` attribute
- `Command::execute()` and `Command::pipeExec()` always invoke ShellBox with `disableNetwork()` and `firejailDefaultSeccomp()`
- SVG cache hit is determined by MD5 match between `md5_incoming` (JSON-encoded props + syntax) and the stored summary (`Hook::$md5_existed`)

## Side Effects
- `Command::execute()` / `Command::pipeExec()` spawn external processes (`php`, `dot`) inside a ShellBox sandbox
- `FileSystemUtils::dumpDebugFile()` writes files to `../debug/` (gated by `ExtensionConstants::DUMP_DEBUG_FILES`)
- `FileSystemUtils::saveSummary()` / `loadSummary()` read/write JSON files under `MW_INSTALL_PATH/images/quickgv/`
- `Hook::genSvg()` writes SVG output to `MW_INSTALL_PATH/images/quickgv/`

## Verification
- Run: `composer test`
