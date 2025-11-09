# Action Methods API Reference

Simplified action methods for Datastar HTTP actions with smart event detection. These methods reduce verbosity while maintaining full access to Datastar's capabilities.

## Table of Contents

- [Quick Start](#quick-start)
- [Smart Event Detection](#smart-event-detection)
- [CSRF-Protected Actions](#csrf-protected-actions)
- [Standard Datastar Actions](#standard-datastar-actions)
- [Navigate Action](#navigate-action)
- [Dispatch Action](#dispatch-action)
- [Event Modifiers](#event-modifiers)
- [Options Reference](#options-reference)
- [Common Patterns](#common-patterns)

## Quick Start

```php
use Dancycodes\Hyper\Html\Html;

// Before: verbose dataOn() syntax
Html::button()->dataOn('click', '@postx("/save")');

// After: simplified action method with smart event detection
Html::button()->postx('/save');

// Forms automatically use 'submit__prevent'
Html::form()->postx('/submit'); // Uses submit__prevent event

// Inputs automatically use 'input' event
Html::input()->get('/search'); // Uses input event
```

## Smart Event Detection

Action methods automatically select the appropriate event based on element type and attributes:

| Element Type | Default Event | Reasoning |
|-------------|---------------|-----------|
| `form` | `submit__prevent` | Prevents default form submission |
| `button`, `a` | `click` | Standard click interaction |
| `input[type=checkbox]` | `change` | Fires when checked/unchecked |
| `input[type=radio]` | `change` | Fires when selection changes |
| `input[type=file]` | `change` | Fires when file selected |
| `input[type=color]` | `change` | Fires when color selected |
| `input` (text-like) | `input` | Fires on every keystroke/change |
| `select` | `change` | Fires when option selected |
| `textarea` | `input` | Fires on every keystroke |
| All others | `click` | Safe default for interactions |

### Input Type Detection

For `<input>` elements, the smart detection checks the `type` attribute:

**Change Event Types** (state-based interactions):
- `checkbox` - Toggle checked state
- `radio` - Select from radio group
- `file` - Select file from file system
- `color` - Pick color from color picker

**Input Event Types** (continuous feedback):
- `text`, `email`, `password` - Text entry
- `number`, `date`, `time` - Numeric/date entry
- `search`, `url`, `tel` - Specialized text entry
- `range` - Slider movement
- All other input types default to `input`

### Smart Detection Examples

```php
// Text input - automatically uses 'input' event
Html::input()->type('text')->get('/autosave');
// Generates: data-on:input="@get('/autosave')"

// Checkbox - automatically uses 'change' event
Html::input()->type('checkbox')->patchx('/toggle-active');
// Generates: data-on:change="@patchx('/toggle-active')"

// Radio button - automatically uses 'change' event
Html::input()->type('radio')->patchx('/select-option');
// Generates: data-on:change="@patchx('/select-option')"

// File input - automatically uses 'change' event
Html::input()->type('file')->postx('/upload');
// Generates: data-on:change="@postx('/upload')"

// Select dropdown - automatically uses 'change' event
Html::select()->get('/filter');
// Generates: data-on:change="@get('/filter')"

// Form - automatically uses 'submit__prevent' event
Html::form()->postx('/submit');
// Generates: data-on:submit__prevent="@postx('/submit')"
```

### Override Smart Defaults

Explicitly pass an event to override the smart default:

```php
// Override form's default submit__prevent
Html::form()->postx('/save', 'change');

// Override checkbox's default change event
Html::input()->type('checkbox')->patchx('/toggle', 'click');

// Add event modifiers to text input
Html::input()->type('text')->get('/search', 'input__debounce.300ms');

// Use window event modifier
Html::div()->get('/refresh', 'click__window');
```

## CSRF-Protected Actions

Laravel Hyper extends Datastar with CSRF-protected variants of mutating actions. **Always use these for Laravel applications.**

### postx()

POST request with automatic CSRF token inclusion.

```php
public function postx(string $url, ?string $event = null, array $options = []): static
```

**Usage:**

```php
// Simple POST (uses smart event: 'click' for button)
Html::button()->postx('/create');

// POST on form (uses smart event: 'submit__prevent')
Html::form()->postx('/submit');

// Explicit event with modifiers
Html::button()->postx('/save', 'dblclick__once');

// With options
Html::button()->postx('/save', null, [
    'headers' => ['X-Custom' => 'value'],
    'retry' => 3
]);
```

### putx()

PUT request with automatic CSRF token inclusion.

```php
public function putx(string $url, ?string $event = null, array $options = []): static
```

**Usage:**

```php
// Full resource update
Html::button()->putx('/users/123');

// With explicit event
Html::form()->putx('/update', 'submit__prevent');
```

### patchx()

PATCH request with automatic CSRF token inclusion.

```php
public function patchx(string $url, ?string $event = null, array $options = []): static
```

**Usage:**

```php
// Partial resource update
Html::button()->patchx('/users/123');

// Toggle status on checkbox change
Html::input()
    ->type('checkbox')
    ->patchx('/toggle-status', 'change');
```

### deletex()

DELETE request with automatic CSRF token inclusion.

```php
public function deletex(string $url, ?string $event = null, array $options = []): static
```

**Usage:**

```php
// Simple delete
Html::button()->deletex('/items/123');

// With confirmation (use window modifier for global event)
Html::button()->deletex('/items/123', 'click__window');
```

## Standard Datastar Actions

These are the standard Datastar actions **without CSRF protection**. Use these only when:
- Interfacing with external APIs
- Working with read-only GET operations
- You've handled CSRF another way

### get()

GET request to fetch data or HTML.

```php
public function get(string $url, ?string $event = null, array $options = []): static
```

**Usage:**

```php
// Simple GET
Html::button()->get('/fetch-data');

// Search on input with debounce
Html::input()->get('/search', 'input__debounce.300ms');

// Infinite scroll on intersection
Html::div()->get('/load-more', 'intersect__once');

// With options
Html::button()->get('/api/data', null, [
    'headers' => ['Accept' => 'application/json']
]);
```

### post(), put(), patch(), delete()

Standard Datastar actions **without CSRF protection**.

```php
public function post(string $url, ?string $event = null, array $options = []): static
public function put(string $url, ?string $event = null, array $options = []): static
public function patch(string $url, ?string $event = null, array $options = []): static
public function delete(string $url, ?string $event = null, array $options = []): static
```

**⚠️ Warning:** Use the `*x` variants (postx, putx, patchx, deletex) for Laravel applications instead.

## Navigate Action

Client-side navigation with optional targeted updates. Updates browser URL and fetches new content without full page reload.

```php
public function navigate(
    string $url,
    ?string $event = null,
    ?string $key = null,
    array $options = []
): static
```

**Parameters:**
- `$url`: Target URL to navigate to
- `$event`: Event trigger (null = smart default)
- `$key`: Optional navigation key for targeted updates
- `$options`: Datastar fetch options

**Usage:**

```php
// Simple navigation
Html::a()
    ->href('/dashboard')
    ->navigate('/dashboard');

// Targeted navigation (only updates specific fragment)
Html::a()->navigate('/sidebar', null, 'sidebar-content');

// With explicit event
Html::div()->navigate('/page', 'dblclick');

// With options
Html::a()->navigate('/page', null, 'main', [
    'headers' => ['X-Partial' => 'true']
]);

// Navigation with event modifiers
Html::a()->navigate('/content', 'click__prevent', 'main');
```

## Dispatch Action

Dispatches custom JavaScript events with optional detail data. Useful for component communication and decoupled architectures.

```php
public function dispatch(
    string $eventName,
    ?string $event = null,
    array $detail = []
): static
```

**Parameters:**
- `$eventName`: Custom event name to dispatch
- `$event`: DOM event that triggers dispatch (null = smart default)
- `$detail`: Optional event detail data (sent as `event.detail`)

**Usage:**

```php
// Simple event dispatch
Html::button()->dispatch('modal-open');

// With event detail
Html::button()->dispatch('user-selected', null, [
    'userId' => 123,
    'username' => 'john'
]);

// Explicit trigger event
Html::div()->dispatch('item-clicked', 'dblclick');

// Complex detail data
Html::button()->dispatch('form-ready', null, [
    'formId' => 'user-form',
    'fields' => ['name', 'email'],
    'timestamp' => time()
]);
```

## Event Modifiers

Event modifiers control event behavior. Use Datastar's double underscore (`__`) syntax:

| Modifier | Description | Example |
|---------|-------------|---------|
| `prevent` | Calls `preventDefault()` | `click__prevent` |
| `stop` | Calls `stopPropagation()` | `click__stop` |
| `once` | Fire only once | `click__once` |
| `passive` | Passive event listener | `scroll__passive` |
| `capture` | Use capture phase | `click__capture` |
| `debounce.{time}ms` | Debounce execution | `input__debounce.300ms` |
| `throttle.{time}ms` | Throttle execution | `scroll__throttle.250ms` |
| `delay.{time}ms` | Simple delay | `click__delay.500ms` |
| `window` | Attach to window | `resize__window` |
| `outside` | Trigger when click outside | `click__outside` |
| `document` | Attach to document | `keydown__document` |
| `viewtransition` | Use View Transition API | `click__viewtransition` |

### Modifier Examples

```php
// Prevent default form submission
Html::form()->postx('/save', 'submit__prevent');

// Debounced search input
Html::input()->get('/search', 'input__debounce.300ms');

// Fire only once
Html::button()->postx('/init', 'click__once');

// Window resize event
Html::div()->get('/layout', 'resize__window');

// Combine multiple modifiers
Html::button()->postx('/save', 'click__prevent__stop__once');

// Throttle scroll event
Html::div()->get('/load-items', 'scroll__throttle.250ms');
```

## Options Reference

All action methods accept an optional `$options` array for Datastar fetch configuration:

```php
Html::button()->postx('/save', null, [
    // Custom headers
    'headers' => [
        'X-Custom-Header' => 'value',
        'Accept' => 'application/json'
    ],

    // Retry configuration
    'retry' => 3,                    // Number of retries
    'retryDelay' => 1000,           // Delay between retries (ms)

    // Credentials handling
    'credentials' => 'include',      // 'omit', 'same-origin', 'include'

    // Filter signals sent to server
    'filterSignals' => ['userId', 'formData'],

    // Content-Type override
    'contentType' => 'application/json',

    // Timeout
    'timeout' => 5000,              // Request timeout (ms)
]);
```

### Common Options Patterns

```php
// API endpoint with JSON
Html::button()->postx('/api/save', null, [
    'headers' => ['Accept' => 'application/json'],
    'contentType' => 'application/json'
]);

// Retry on failure
Html::button()->postx('/unreliable-endpoint', null, [
    'retry' => 5,
    'retryDelay' => 2000
]);

// Filter sensitive signals
Html::form()->postx('/submit', null, [
    'filterSignals' => ['email', 'formData']
]);
```

## Common Patterns

### Search with Debounce

```php
Html::input()
    ->type('search')
    ->name('q')
    ->placeholder('Search...')
    ->dataBind('searchQuery')
    ->get('/search', 'input__debounce.300ms')
    ->dataIndicator('searching');
```

### Save Button with Loading State

```php
Html::button()
    ->type('submit')
    ->class('btn btn-primary')
    ->dataIndicator('saving')
    ->postx('/save')
    ->dataAttr('disabled', '$saving')
    ->content(
        Html::span()->dataShow('!$saving')->text('Save'),
        Html::span()->dataShow('$saving')->text('Saving...')
    );
```

### Infinite Scroll

```php
Html::div()
    ->id('scroll-trigger')
    ->get('/load-more', 'intersect__once')
    ->dataIndicator('loading')
    ->content(
        Html::div()->dataShow('$loading')->text('Loading...')
    );
```

### Delete with Confirmation

```php
Html::button()
    ->class('btn btn-danger')
    ->dataIndicator('deleting')
    ->deletex('/items/123', 'click__window')  // window modifier for global confirmation
    ->dataAttr('disabled', '$deleting')
    ->text('Delete');
```

### Form with Multiple Submit Actions

```php
Html::form()
    ->dataSignals(['status' => 'draft'])
    ->dataIndicator('submitting')
    ->content(
        Html::input()->type('text')->name('title')->dataBind('title'),

        // Save as draft
        Html::button()
            ->type('submit')
            ->postx('/save?status=draft')
            ->dataAttr('disabled', '$submitting')
            ->text('Save Draft'),

        // Publish
        Html::button()
            ->type('submit')
            ->postx('/save?status=published')
            ->dataAttr('disabled', '$submitting')
            ->text('Publish')
    );
```

### Navigation with Targeted Updates

```php
// Main navigation link
Html::a()
    ->href('/dashboard')
    ->class('nav-link')
    ->navigate('/dashboard', null, 'main-content', [
        'headers' => ['X-Partial' => 'main-content']
    ])
    ->text('Dashboard');

// Sidebar navigation (updates sidebar only)
Html::a()
    ->href('/sidebar/users')
    ->navigate('/sidebar/users', null, 'sidebar', [
        'headers' => ['X-Partial' => 'sidebar']
    ])
    ->text('Users');
```

### Auto-save on Input

```php
Html::input()
    ->type('text')
    ->name('title')
    ->dataBind('title')
    ->patchx('/autosave', 'input__debounce.500ms')
    ->dataIndicator('autoSaving')
    ->dataAttr('title', "$autoSaving ? 'Saving...' : 'Auto-save enabled'");
```

### Toggle on Checkbox

```php
// Smart detection automatically uses 'change' event
Html::input()
    ->type('checkbox')
    ->name('active')
    ->dataBind('isActive')
    ->patchx('/toggle-active')  // No need to specify 'change'!
    ->dataIndicator('toggling')
    ->dataAttr('disabled', '$toggling');
```

### File Upload Preview

```php
// Smart detection automatically uses 'change' event for file inputs
Html::input()
    ->type('file')
    ->name('avatar')
    ->dataBind('avatarFile')
    ->postx('/upload')  // No need to specify 'change'!
    ->dataIndicator('uploading');
```

## Migration Guide

Migrating from verbose `dataOn()` syntax to simplified action methods:

```php
// Before
Html::button()->dataOn('click', '@postx("/save")');

// After
Html::button()->postx('/save');

// Before (with event modifiers)
Html::input()->dataOn('input__debounce.300ms', '@get("/search")');

// After
Html::input()->get('/search', 'input__debounce.300ms');

// Before (with options)
Html::button()->dataOn('click', '@postx("/save", {retry: 3})');

// After
Html::button()->postx('/save', null, ['retry' => 3]);
```

## See Also

- [Loading States Documentation](./LOADING_STATES.md)
- [Datastar Backend Actions](https://data-star.dev/reference/plugins/backend)
- [Datastar Event Modifiers](https://data-star.dev/reference/plugins/backend#event-modifiers)
