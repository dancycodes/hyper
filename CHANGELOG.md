# Changelog

All notable changes to `dancycodes/hyper` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.4](https://github.com/dancycodes/hyper/compare/v0.1.3...v0.1.4) (2025-11-08)


### Bug Fixes

* Improve fileSource handling for object input ([0ed7de1](https://github.com/dancycodes/hyper/commit/0ed7de1bb8b90d3ce9297dd9af24d9deb6437ffd))
* Merge pull request [#7](https://github.com/dancycodes/hyper/issues/7) from dancycodes/develop ([6ae11bb](https://github.com/dancycodes/hyper/commit/6ae11bba70c4e7adc7608390ad9b7565427267e4))

## [0.1.3](https://github.com/dancycodes/hyper/compare/v0.1.2...v0.1.3) (2025-10-25)


### Bug Fixes

* Add middleware to convert redirects for Datastar requests ([ea548a2](https://github.com/dancycodes/hyper/commit/ea548a239fc76d6ee29a646e506ad368007579a6))
* Add middleware to convert redirects for Datastar requests ([c170c2c](https://github.com/dancycodes/hyper/commit/c170c2c1d45f18de8e6b5449634f9eca8a049c3f))
* Merge pull request [#5](https://github.com/dancycodes/hyper/issues/5) from dancycodes/develop ([ea548a2](https://github.com/dancycodes/hyper/commit/ea548a239fc76d6ee29a646e506ad368007579a6))

## [0.1.1](https://github.com/dancycodes/hyper/compare/v0.1.0...v0.1.1) (2025-10-21)


### Bug Fixes

* add static $latestResponse property to all Feature test classes ([7e97181](https://github.com/dancycodes/hyper/commit/7e97181131d43faa114bee8bca568c99b767baf9))
* add static $latestResponse property to all Unit test classes ([1a9eafe](https://github.com/dancycodes/hyper/commit/1a9eafe217d0cb2f4a5fa616b5d98632bd6eeea4))
* declare missing static property in test classes ([1234bf3](https://github.com/dancycodes/hyper/commit/1234bf3107d99c86e1e64973922b7b8739d3d423))
* remove Laravel 12 from test matrix (version doesn't exist yet) ([87df410](https://github.com/dancycodes/hyper/commit/87df4105a82e649cde3017cd3916a3a89b508498))
* update phpunit.xml to ensure GitHub Actions workflows pass ([0bbdb98](https://github.com/dancycodes/hyper/commit/0bbdb9813ef83d3bb845a49967c5bd20d696b737))


### Miscellaneous Chores

* configure Release Please for automated releases ([cf4009b](https://github.com/dancycodes/hyper/commit/cf4009bcdf2be8627f960015e152de20d910c824))
* **deps-dev:** update orchestra/testbench requirement || ^10.0 ([8dee5b8](https://github.com/dancycodes/hyper/commit/8dee5b84b05f20fc321faed4abbc3c0315318bb7))
* **deps-dev:** update orchestra/testbench requirement from ^9.0 to ^9.0 || ^10.0 ([def27c0](https://github.com/dancycodes/hyper/commit/def27c022048fa1d1e58f402baff070f1c0ea6d8))

## [Unreleased]

## [0.1.0] - 2025-10-19

> **Pre-release Version**: Laravel Hyper is currently in active development (0.x.x). The API may change as we gather feedback and refine features. We're committed to reaching a stable 1.0.0 release once the package is battle-tested with real-world usage. Breaking changes will be clearly documented in the changelog.

### Added
- Initial pre-release of Laravel Hyper
- **Core Features:**
  - Reactive signals with automatic UI synchronization
  - Server-side signal validation using Laravel's validation system
  - Signal types: regular, local (`_prefix`), and locked (`suffix_`)
  - CSRF-protected Datastar actions (`@postx`, `@putx`, `@patchx`, `@deletex`)

- **Blade Integration:**
  - `@hyper` directive for script inclusion and CSRF token
  - `@signals` directive for PHP-to-JavaScript signal initialization
  - `@ifhyper` conditional directive for request type detection
  - `@fragment` / `@endfragment` directives for reusable HTML sections

- **HTTP Response Builder:**
  - Fluent `hyper()` helper for building responses
  - Signal updates with `signals()` method
  - View rendering with `view()` method
  - Fragment rendering with `fragment()` method
  - HTML patching with `html()` method
  - JavaScript execution with `js()` method
  - Conditional logic with `when()` / `unless()` methods
  - Signal forgetting with `forget()` method

- **File Upload Support:**
  - Base64 file encoding for reactive uploads
  - Specialized validation rules: `b64image`, `b64file`, `b64max`, `b64dimensions`, `b64mimes`
  - `signals()->store()` helper for easy file storage
  - `hyperStorage()` helper for file operations
  - Live file preview with `@fileUrl` action

- **Navigation:**
  - SPA-like navigation with `data-navigate` attribute
  - Server-side navigation methods: `navigate()`, `navigateMerge()`, `navigateClean()`, `navigateOnly()`, `navigateExcept()`
  - Query parameter manipulation
  - Navigation key support for targeted updates
  - History management (push/replace modes)

- **Locked Signals (Security):**
  - Tamper-proof signals with encryption
  - Session-based validation
  - `HyperSignalTamperedException` for security violations
  - Automatic tampering detection and logging

- **Route Discovery:**
  - Automatic route registration from controllers
  - PHP 8 attribute-based routing: `#[Route]`, `#[Prefix]`, `#[Where]`, `#[WithTrashed]`
  - View-based route discovery
  - Customizable transformers
  - `@DoNotDiscover` attribute for exclusions

- **Advanced Features:**
  - Server-Sent Events (SSE) streaming support
  - Fragment caching and optimization
  - DOM morphing for efficient updates
  - Custom Datastar actions and plugins
  - Request/Response macros

- **Developer Experience:**
  - Comprehensive test suite (700+ tests)
  - Full PHPDoc documentation
  - Laravel package auto-discovery
  - Zero build step required
  - Type-safe helpers and validation

- **Custom Datastar Extensions:**
  - `data-error` attribute for validation error display
  - `data-for` attribute for iteration (Alpine.js-like syntax)
  - `data-if` attribute for conditional rendering
  - `data-navigate` attribute with modifiers

### Documentation
- Complete documentation in VitePress
- Step-by-step tutorials and examples
- API reference for all methods
- Best practices guide
- Migration guide from other frameworks

### Developer Tools
- `composer test` - Run test suite
- `composer test-coverage` - Generate coverage reports
- `php artisan vendor:publish --tag=hyper-assets` - Publish JavaScript assets
- `php artisan vendor:publish --tag=hyper-config` - Publish configuration

[Unreleased]: https://github.com/dancycodes/hyper/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/dancycodes/hyper/releases/tag/v0.1.0
