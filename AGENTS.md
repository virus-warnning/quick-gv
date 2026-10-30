# AGENTS.md

## EGM Blueprint

The root directory's EGM blueprint file is `EGM.md`. Always use `EGM.md` (not `README.md`) when referring to or working with the project's EGM blueprint.

## Post-Change Requirements

After every code change (adding, modifying, or deleting source files), you MUST run the following quality checks before considering the task complete:

1. **PHPCS** — Run `vendor/bin/phpcs` to verify code style compliance.
2. **PHAN** — Run `vendor/bin/phan` to check for static analysis issues.

Fix any errors or warnings reported by either tool before proceeding.

## Dependency Version Changes

When the PHP dependency version is changed (e.g., in `composer.json`), you MUST update the following files simultaneously:

1. **README.md** — Update any referenced PHP version requirements or dependency version mentions.
2. **extension.json** — Update the corresponding engine/dependency version constraint.

Failure to keep these files in sync will cause inconsistent version declarations across the project.
