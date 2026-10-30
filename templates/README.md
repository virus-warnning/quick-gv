# QuickGV — Templates

## Overview
CLI template scripts consumed by the `dot` SVG engine. `gv-builder.php` composes a complete Graphviz dot diagram (theme attributes + graph body) from name, theme, and usage arguments and stdin syntax, which is then piped to the `dot` rendering engine.

## Interface
- `gv-builder.php` — CLI script; accepts `$argv[1]` as graph name (default `G`), `$argv[2]` as theme name (default `default`), `$argv[3]` as usage type (default empty), and reads dot syntax from `php://stdin`; outputs a themed dot diagram to stdout

## Invariants
- Theme argument is optional; defaults to `'default'` when `$argv[2]` is empty or unset
- Usage aliases: `er`/`ram` → `record`, `mindmap` → `neato`
- If stdin is empty, a default flowchart diagram is emitted and theme defaults to `warm`
- Unknown themes fall back to `default`

## Side Effects
- Reads from `php://stdin`
- Writes composed dot syntax to `php://stdout` (piped to the `dot` process)

## Verification
- Run: `composer test`
