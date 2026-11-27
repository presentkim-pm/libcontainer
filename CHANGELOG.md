# Changelog

## 2026-11-27

Rewritten from a Poggit virion template into a Composer library template for
[axolotl-pm/PocketMine-MP](https://github.com/axolotl-pm/PocketMine-MP) and PHP 8.4
(the repository was renamed from `pmmp-virion-template`).

The Poggit virion template for the original PocketMine-MP 5.44 and older is kept on the
[`legacy/pmmp-5.44`](https://github.com/presentkim-pm/pmmp-library-template/tree/legacy/pmmp-5.44) branch.

### ⚠️ Breaking changes
- Removed `virion.yml` and the `authorname\virionname` placeholder.

### ✨ Features
- Added `composer.json`, PHPUnit 12 tests and CI on PHP 8.4 / 8.5.
- Added the "Set up repository from template" workflow, which renames the placeholders on the first push.
