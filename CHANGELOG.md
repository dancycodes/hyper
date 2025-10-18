# Changelog

All notable changes to `dancycodes/hyper` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-10-18

### Added
- Initial release of Laravel Hyper
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

[Unreleased]: https://github.com/dancycodes/hyper/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/dancycodes/hyper/releases/tag/v1.0.0
