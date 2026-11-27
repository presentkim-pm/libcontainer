<!-- PROJECT BADGES -->
<div align="center">

[![CI][ci-badge]][ci-url]
[![License][license-badge]][license-url]

</div>


<!-- PROJECT LOGO -->
<br />
<div align="center">
  <img src="https://raw.githubusercontent.com/presentkim-pm/pmmp-library-template/main/assets/icon.png" alt="Logo" width="80" height="80"/>
  <h3>pmmp-library-template</h3>
  <p align="center">
    A template for Composer libraries for axolotl-pm/PocketMine-MP and PHP 8.4.
  </p>
</div>

> The Poggit virion template for the original PocketMine-MP 5.44 and older is kept on the
> [`legacy/pmmp-5.44`][legacy-url] branch (no longer maintained).

-----

## 🚀 Create a library

1. Click **Use this template** and create a repository. Name it in kebab-case (e.g. `session-utils`) and fill in
   the repository description.
2. The **Set up repository from template** workflow runs on the first push and commits the renamed project.
   Wait about a minute, then pull.
3. Adjust the namespace if you want a different one (e.g. `kim\present\utils\session`), write your code,
   [publish it on Packagist](#-releasing) and tag a release.

### What gets renamed

Example for a repository named `session-utils`:

| Placeholder                              | Becomes                                      | Example                                  |
|------------------------------------------|----------------------------------------------|------------------------------------------|
| Package `presentkim/library-name`        | `presentkim/<name>`                          | `presentkim/session-utils`               |
| Namespace `kim\present\libraryname`      | `kim\present\<compact name>`                 | `kim\present\sessionutils`               |
| Source directory `src/kim/present/libraryname/` | `src/kim/present/<compact name>/`     | `src/kim/present/sessionutils/`          |
| Main class `LibraryName`                 | `<PascalName>`                               | `SessionUtils`                           |
| Test class `LibraryNameTest`             | `<PascalName>Test`                           | `SessionUtilsTest`                       |
| Description `Description of Library`     | The repository description                   | Falls back to `A library for axolotl-pm/PocketMine-MP` when empty |
| GitHub URLs                              | `<owner>/<repo>`                             | `presentkim-pm/session-utils`            |
| `LICENSE` year                           | The current year                             | `2026`                                   |
| `README.md` and `CHANGELOG.md`           | Replaced by the files in `.github/template/` | -                                        |

The workflow runs PHPUnit on the renamed code before committing, then deletes itself
(`setup.yml`, `.github/scripts` and `.github/template`).

-----

## 🗂️ Layout

```text
.
├── .github/
│   └── workflows/
│       └── ci.yml                    # PHPUnit on PHP 8.4 / 8.5
├── assets/
│   └── icon.png                      # Logo used by the README
├── src/kim/present/<compact>/
│   └── <PascalName>.php              # Example class, replace it
├── tests/
│   ├── <PascalName>Test.php
│   └── bootstrap.php
├── CHANGELOG.md
├── composer.json
├── LICENSE
├── phpunit.xml.dist
└── README.md
```

-----

## 📦 Releasing

1. Register the library on [Packagist](https://packagist.org/packages/submit) once by submitting its GitHub URL.
   The GitHub hook keeps it up to date afterwards.
2. Versions are git tags (e.g. `1.0.0`). Update `CHANGELOG.md` before tagging.

Plugins built from [presentkim-pm/plugin-template](https://github.com/presentkim-pm/plugin-template) bundle the
library with shaded namespaces when they release.

-----

## 🔧 Troubleshooting

If the setup workflow failed (for example the repository name is not a valid namespace, such as one starting
with a digit), either:

- run it locally, then commit the result:

  ```sh
  python3 .github/scripts/setup-template.py <owner>/<repo> "<description>"
  ```

- or rename the repository and re-run the workflow from the **Actions** tab (`workflow_dispatch`).

The workflow is skipped once `composer.json` no longer contains the placeholder.

-----

## 📄 License

Distributed under the **MIT** License. See [LICENSE][license-url] for more information.


[ci-badge]: https://img.shields.io/github/actions/workflow/status/presentkim-pm/pmmp-library-template/ci.yml?branch=main&style=for-the-badge

[ci-url]: https://github.com/presentkim-pm/pmmp-library-template/actions/workflows/ci.yml

[license-badge]: https://img.shields.io/github/license/presentkim-pm/pmmp-library-template.svg?style=for-the-badge

[license-url]: https://github.com/presentkim-pm/pmmp-library-template/blob/main/LICENSE

[legacy-url]: https://github.com/presentkim-pm/pmmp-library-template/tree/legacy/pmmp-5.44
