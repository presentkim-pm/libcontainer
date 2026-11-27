<!-- PROJECT BADGES -->
<div align="center">

[![Packagist][packagist-badge]][packagist-url]
[![CI][ci-badge]][ci-url]
[![Stars][stars-badge]][stars-url]
[![License][license-badge]][license-url]

</div>


<!-- PROJECT LOGO -->
<br />
<div align="center">
  <img src="https://raw.githubusercontent.com/presentkim-pm/pmmp-library-template/main/assets/icon.png" alt="Logo" width="80" height="80"/>
  <h3>library-name</h3>
  <p align="center">
    Description of Library

[Packagist][packagist-url] · [Changelog](CHANGELOG.md) · [Report a bug][issues-url] · [Request a feature][issues-url]

  </p>
</div>

```php
echo LibraryName::greet("Steve"); // Hello, Steve!
```

-----

## 📦 Installation

```sh
composer require presentkim/library-name
```

| Requirement | Version                                                          |
|-------------|------------------------------------------------------------------|
| Server      | [axolotl-pm/PocketMine-MP][axolotl-url] (fork of PocketMine-MP 5) |
| PHP         | **8.4** or newer                                                 |

> Using it in a plugin: plugins built from [presentkim-pm/plugin-template][plugin-template-url] bundle Composer
> libraries into the phar with shaded namespaces at release, so `composer require` is all a plugin needs.

-----

## 🚀 Quick start

```php
use kim\present\libraryname\LibraryName;

$message = LibraryName::greet($player->getName()); // "Hello, Steve!"
```

-----

## 📖 API reference

All classes are in `kim\present\libraryname`.

| Method                                  | Description                                    |
|-----------------------------------------|------------------------------------------------|
| `LibraryName::greet(string $name) : string` | Returns a greeting for the given name (example) |

-----

## 🧪 Development

```sh
composer install --ignore-platform-req='ext-*'
composer test
```

The server's native extensions are not needed for the tests, so `ext-*` requirements are ignored on install.

-----

## 📄 License

Distributed under the **MIT** License. See [LICENSE][license-url] for more information.


[packagist-badge]: https://img.shields.io/packagist/v/presentkim/library-name.svg?style=for-the-badge

[packagist-url]: https://packagist.org/packages/presentkim/library-name

[ci-badge]: https://img.shields.io/github/actions/workflow/status/presentkim-pm/pmmp-library-template/ci.yml?branch=main&style=for-the-badge

[ci-url]: https://github.com/presentkim-pm/pmmp-library-template/actions/workflows/ci.yml

[stars-badge]: https://img.shields.io/github/stars/presentkim-pm/pmmp-library-template.svg?style=for-the-badge

[license-badge]: https://img.shields.io/github/license/presentkim-pm/pmmp-library-template.svg?style=for-the-badge

[stars-url]: https://github.com/presentkim-pm/pmmp-library-template/stargazers

[issues-url]: https://github.com/presentkim-pm/pmmp-library-template/issues

[license-url]: https://github.com/presentkim-pm/pmmp-library-template/blob/main/LICENSE

[axolotl-url]: https://github.com/axolotl-pm/PocketMine-MP

[plugin-template-url]: https://github.com/presentkim-pm/plugin-template
