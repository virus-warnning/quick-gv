# QuickGV Blueprint

Parser hook extension for MediaWiki that renders [Graphviz](https://www.graphviz.org/) diagrams as SVG.

## System Overview

QuickGV registers a `<quickgv>` parser hook via MediaWiki's `ParserFirstCallInit` event. When a page containing the hook is parsed, the extension validates input attributes, composes a complete Graphviz dot diagram with theme configuration, and renders it to SVG using the `dot` command-line tool. Generated SVGs are cached on disk keyed by an MD5 checksum to avoid redundant rendering on subsequent page views. The SVG is post-processed to add link targets, color link text, and a "powered by QuickGV" footer.

## Subdirectories & System Boundaries
- `src`: [./src/README.md](./src/README.md) — Core PHP classes (Hook, Command, Validator, FieldResult, FileSystemUtils, ExtensionConstants)
- `templates`: [./templates/README.md](./templates/README.md) — CLI template script that composes dot syntax with theme configuration

## Global Invariants
- All shell command execution is sandboxed via MediaWiki ShellBox with `disableNetwork()` and `firejailDefaultSeccomp()`
- SVG and summary files are stored under `MW_INSTALL_PATH/images/quickgv/`
- The extension requires MediaWiki >= 1.31.0 (per `extension.json`)
- Theme selection is one of: `cold`, `warm`, `sakura`, `default`
- Usage type aliases: `er`/`ram` → `record`, `mindmap` → `neato`

## Global Side Effects
- External process execution (`php`, `dot`) via ShellBox sandbox
- File writes to `MW_INSTALL_PATH/images/quickgv/` (SVG images + JSON summary files)
- Debug file writes to `debug/` directory (gated by `ExtensionConstants::DUMP_DEBUG_FILES`)

## System Verification
- Run: `composer test`
