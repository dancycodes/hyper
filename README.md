# Laravel Hyper

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dancycodes/hyper.svg?style=flat-square)](https://packagist.org/packages/dancycodes/hyper)
[![Total Downloads](https://img.shields.io/packagist/dt/dancycodes/hyper.svg?style=flat-square)](https://packagist.org/packages/dancycodes/hyper)
[![Tests](https://img.shields.io/github/actions/workflow/status/dancycodes/hyper/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/dancycodes/hyper/actions/workflows/tests.yml)
[![Code Style](https://img.shields.io/github/actions/workflow/status/dancycodes/hyper/code-style.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/dancycodes/hyper/actions/workflows/code-style.yml)
[![License](https://img.shields.io/packagist/l/dancycodes/hyper.svg?style=flat-square)](https://packagist.org/packages/dancycodes/hyper)
[![PHP Version](https://img.shields.io/packagist/php-v/dancycodes/hyper.svg?style=flat-square)](https://packagist.org/packages/dancycodes/hyper)

## What is Laravel Hyper?

Laravel Hyper integrates [Datastar](https://data-star.dev)—a reactive hypermedia framework—with Laravel's development patterns, enabling you to build reactive user interfaces using server-side rendering and Blade templates.

Datastar provides the reactive signals system and frontend reactivity engine. Laravel provides the backend framework and development patterns. Hyper bridges these two technologies with Laravel-specific helpers, Blade directives, CSRF protection, validation integration, and file upload handling.

```blade
<!-- Reactive counter with Datastar's signals and Laravel's backend -->
<div @signals(['count' => 0])>
    <button data-on-click="@postx('/increment')">+</button>
    <span data-text="$count"></span>
    <button data-on-click="@postx('/decrement')">-</button>
</div>
```

```php
// Laravel controller with Hyper's helpers
public function increment()
{
    $count = signals('count', 0);
    return hyper()->signals(['count' => $count + 1]);
}
```

## The Problem Hyper Solves

Modern web applications benefit from reactive interfaces where UI updates feel instant—users expect immediate feedback when clicking buttons, typing in search fields, or submitting forms. Achieving this responsiveness traditionally required either building separate JavaScript frontends or accepting slower full-page reloads.

Hyper offers server-driven reactivity: your Laravel application maintains control of business logic, validation, and data access while the interface updates reactively. This approach works well when your application logic belongs on the server and you want reactive UI without managing complex client-side state.

## Core Technologies

### Datastar (Reactive Framework)

[Datastar](https://data-star.dev) is an independent hypermedia framework that powers the reactive behavior in Hyper applications. It provides:

- **Reactive Signals**: Variables that automatically update UI when they change
- **Data Attributes**: HTML attributes like `data-bind`, `data-text`, `data-show` for reactive behavior
- **HTTP Actions**: Trigger server requests with `@get`, `@post` and other verbs
- **Server-Sent Events**: Receive updates from the server via SSE
- **DOM Morphing**: Efficiently update HTML without full page reloads

All reactivity in Hyper applications comes from Datastar. When you use `data-bind`, `data-text`, or any reactive attribute, that's Datastar handling the reactivity.

### Laravel (Backend Framework)

Laravel provides the server-side foundation—routing, controllers, Blade templates, validation, Eloquent, and all the patterns Laravel developers know.

### Hyper (Integration Layer)

Hyper connects Datastar and Laravel with:

**Server-Side Helpers:**

- `hyper()` - Fluent response builder for reactive responses
- `signals()` - Read signals sent from the frontend (similar to `request()`)

**Blade Directives:**

- `@hyper` - Include Datastar JavaScript and CSRF token
- `@signals` - Initialize signals from PHP data
- `@fragment` / `@endfragment` - Define reusable view sections

**Laravel Integration:**

- `@postx`, `@putx`, `@patchx`, `@deletex` - HTTP actions with automatic CSRF tokens
- `data-error` - Display Laravel validation errors
- `data-navigate` - Client-side navigation with Laravel routes
- Validation integration via `signals()->validate()`
- Base64 file upload handling with `signals()->store()`

**Custom Attributes:**

- `data-for` - Optimized loops for rendering collections
- `data-if` - Conditional rendering based on signals

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or higher

## Installation

Install Laravel Hyper via Composer:

```bash
composer require dancycodes/hyper
```

Laravel's package auto-discovery will register the service provider automatically.

Publish the JavaScript assets to your `public` directory:

```bash
php artisan vendor:publish --tag=hyper-assets
```

This copies Datastar and Hyper's JavaScript files to `public/vendor/hyper/js/`.

Add the `@hyper` directive to your layout's `<head>` section:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Application</title>
    @hyper
</head>
<body>
    @yield('content')
</body>
</html>
```

The `@hyper` directive includes the CSRF meta tag and loads the JavaScript module.

## Quick Start

### Reactive State with Signals

Signals are reactive variables. When a signal's value changes, any UI element displaying that signal updates automatically. This concept comes from Datastar.

```blade
<div @signals(['username' => '', 'email' => ''])>
    <input data-bind="username" placeholder="Username" />
    <input data-bind="email" type="email" placeholder="Email" />

    <p>Hello, <span data-text="$username || 'Guest'"></span>!</p>
</div>
```

The `@signals` directive (Hyper) creates signals from PHP data. The `data-bind` and `data-text` attributes (Datastar) make the interface reactive.

### Server Communication

Use CSRF-protected HTTP actions to communicate with your Laravel controllers:

```blade
<form @signals(['name' => '', 'email' => '', 'errors' => []])
      data-on-submit__prevent="@postx('/contacts')">

    <div>
        <input data-bind="name" />
        <div data-error="name"></div>
    </div>

    <div>
        <input data-bind="email" type="email" />
        <div data-error="email"></div>
    </div>

    <button type="submit">Save Contact</button>
</form>
```

The `@postx` action (Hyper) automatically includes Laravel's CSRF token. The `data-error` attribute (Hyper) displays validation errors.

```php
public function store()
{
    $validated = signals()->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email'
    ]);

    Contact::create($validated);

    return hyper()->signals([
        'name' => '',
        'email' => '',
        'errors' => []
    ]);
}
```

The `signals()` helper (Hyper) reads signals from the request. The `hyper()` helper (Hyper) builds the response.

### Updating HTML Content

Render Blade views to update specific sections of your page:

```blade
<!-- Your view -->
<div id="user-list">
    @foreach($users as $user)
        <div class="user-item">
            <span>{{ $user->name }}</span>
            <button data-on-click="@deletex('/users/{{ $user->id }}')">
                Delete
            </button>
        </div>
    @endforeach
</div>
```

```php
public function destroy($id)
{
    User::destroy($id);

    $users = User::all();

    return hyper()->view('users.list', compact('users'));
}
```

Datastar receives the rendered HTML and updates the matching element by ID.

## Working with Signals

### Signal Types

Signals come in three types, each serving different purposes:

**Regular Signals** (sent to server with every request):

```blade
<div @signals(['count' => 0, 'email' => ''])>
    <!-- These signals are sent to the server automatically -->
</div>
```

**Local Signals** (stay in browser, never sent to server):

```blade
<div @signals(['count' => 0, '_showMenu' => false])>
    <!-- _showMenu stays in the browser (underscore prefix) -->
    <button data-on-click="$_showMenu = !$_showMenu">Toggle Menu</button>
    <nav data-show="$_showMenu">Navigation content...</nav>
</div>
```

Local signals are useful for UI state that doesn't need server processing—dropdowns, accordions, modal visibility, etc.

**Locked Signals** (protected from client tampering):

```blade
<div @signals(['userId_' => auth()->id(), 'role_' => auth()->user()->role])>
    <!-- Locked signals (underscore suffix) are validated server-side -->
    <!-- Client can read but cannot modify them -->
</div>
```

Locked signals are stored encrypted in the session and validated on every request. If a locked signal is tampered with, Hyper throws a `HyperSignalTamperedException`. This feature is provided by Hyper for secure state management.

### Creating Signals

The `@signals` directive offers several syntaxes:

**Array syntax:**

```blade
<div @signals(['count' => 0, 'message' => 'Hello'])>
    <!-- Creates count and message signals -->
</div>
```

**Variable syntax:**

```blade
@php
    $username = 'John';
    $_editing = false;
    $userId_ = auth()->id();
@endphp

<div @signals($username, $_editing, $userId_)>
    <!-- Creates: username, _editing, userId_ signals -->
    <!-- Variable names become signal names literally -->
</div>
```

**Spread syntax:**

```blade
<div @signals(...$user, ['errors' => []])>
    <!-- If $user = ['name' => 'John', 'email' => 'john@example.com'] -->
    <!-- Creates signals: name, email, errors -->
</div>
```

**Automatic type conversion:**

The `@signals` directive automatically converts Laravel types:

```blade
<div @signals($user)>
    <!-- Eloquent Model → converted via toArray() -->
</div>

<div @signals($contacts)>
    <!-- Collection → converted via toArray() -->
</div>

<div @signals($results)>
    <!-- Paginator → converted via toArray() -->
</div>
```

### Reading Signals

**In the frontend** (Datastar):

```blade
<div @signals(['price' => 100, 'quantity' => 2])>
    <!-- Display signal value -->
    <p data-text="$price"></p>

    <!-- Use in expressions -->
    <p data-text="'Total: $' + ($price * $quantity)"></p>

    <!-- Use in conditionals -->
    <div data-show="$quantity > 0">Items in cart</div>
</div>
```

The `$` prefix accesses signal values in Datastar expressions.

**In the backend** (Hyper):

```php
public function updateCart()
{
    // Get specific signal with default value
    $quantity = signals('quantity', 1);

    // Check if signal exists
    if (signals()->has('coupon')) {
        $discount = signals('coupon');
    }

    // Get all signals
    $allSignals = signals()->all();

    // Get only specific signals
    $data = signals()->only(['name', 'email']);
}
```

### Updating Signals

**From the frontend** (Datastar):

```blade
<div @signals(['count' => 0])>
    <button data-on-click="$count++">Increment</button>
    <button data-on-click="$count--">Decrement</button>
    <button data-on-click="$count = 0">Reset</button>
</div>
```

**From the backend** (Hyper):

```php
// Update single signal
return hyper()->signals(['count' => 5]);

// Update multiple signals
return hyper()->signals([
    'count' => 5,
    'message' => 'Updated!',
    'errors' => []
]);

// Chain with other operations
return hyper()
    ->signals(['count' => 5])
    ->view('status', $data)
    ->js('console.log("Done")');
```

## Validation

Laravel's validation system integrates seamlessly with signals through Hyper's `signals()->validate()` method:

```php
public function store()
{
    $validated = signals()->validate([
        'title' => 'required|string|max:100',
        'content' => 'required|string',
        'email' => 'required|email|unique:users'
    ]);

    Post::create($validated);

    return hyper()->signals(['errors' => [], 'message' => 'Post created']);
}
```

When validation fails, Hyper automatically:

1. Creates an `errors` signal with Laravel's error messages
2. Sends it to the frontend
3. The `data-error` attribute displays field-specific errors

```blade
<form @signals(['title' => '', 'content' => '', 'errors' => []])
      data-on-submit__prevent="@postx('/posts')">

    <div>
        <label>Title</label>
        <input data-bind="title" />
        <div data-error="title" class="text-red-500"></div>
    </div>

    <div>
        <label>Content</label>
        <textarea data-bind="content"></textarea>
        <div data-error="content" class="text-red-500"></div>
    </div>

    <button type="submit">Create Post</button>
</form>
```

### Custom Validation Messages

All Laravel validation features work with signals:

```php
signals()->validate(
    [
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8'
    ],
    [
        'email.unique' => 'This email is already registered.',
        'password.min' => 'Password must be at least :min characters.'
    ],
    [
        'email' => 'email address',
        'password' => 'password'
    ]
);
```

## File Uploads

File inputs bound with `data-bind` automatically encode files as base64 (Datastar behavior). Hyper provides validation rules and storage helpers for handling these base64-encoded files.

```blade
<form @signals(['avatar' => null, 'errors' => []])
      data-on-submit__prevent="@postx('/profile/avatar')">

    <input type="file" data-bind="avatar" accept="image/*" />

    <!-- Live preview -->
    <img data-show="$avatar !== null"
         data-attr-src="@fileUrl($avatar)"
         class="w-32 h-32 object-cover rounded" />

    <div data-error="avatar" class="text-red-500"></div>

    <button type="submit">Upload Avatar</button>
</form>
```

### Base64 Validation Rules

Hyper provides specialized validation rules for base64-encoded files:

```php
public function uploadAvatar()
{
    signals()->validate([
        'avatar' => 'required|b64image|b64max:2048|b64dimensions:min_width=200,min_height=200'
    ]);

    $path = signals()->store('avatar', 'avatars', 'public');

    auth()->user()->update(['avatar' => $path]);

    return hyper()->signals(['avatar' => null, 'errors' => []]);
}
```

**Available validation rules:**

- `b64file` - Validates any base64-encoded file
- `b64image` - Validates base64-encoded image files
- `b64max:size` - Maximum file size in kilobytes
- `b64min:size` - Minimum file size in kilobytes
- `b64mimes:ext1,ext2` - Allowed file extensions
- `b64dimensions:constraints` - Image dimension validation

**Dimension constraints:**

```php
'avatar' => 'b64dimensions:min_width=100,max_width=1000,ratio=16/9'
```

Supports: `min_width`, `max_width`, `min_height`, `max_height`, `width`, `height`, `ratio`

### Storing Files

```php
// Store with auto-generated filename
$path = signals()->store('avatar', 'avatars', 'public');

// Store and get public URL
$url = signals()->storeAsUrl('avatar', 'avatars', 'public');

// Store multiple files
$paths = signals()->storeMultiple([
    'avatar' => 'avatars',
    'banner' => 'banners'
], 'public');
```

## Fragment Rendering

Fragments let you define reusable sections within Blade views and render them independently.

```blade
<!-- resources/views/todos/index.blade.php -->
<div id="todo-list">
    @fragment('todo-list')
        @forelse($todos as $todo)
            <div class="todo-item">
                <span>{{ $todo->title }}</span>
                <button data-on-click="@deletex('/todos/{{ $todo->id }}')">
                    Delete
                </button>
            </div>
        @empty
            <p>No todos found.</p>
        @endforelse
    @endfragment
</div>

<div id="todo-count">
    @fragment('todo-count')
        <p>{{ $todos->count() }} todos remaining</p>
    @endfragment
</div>
```

From your controller, render specific fragments:

```php
public function destroy($id)
{
    Todo::destroy($id);
    $todos = Todo::all();

    return hyper()
        ->fragment('todos.index', 'todo-list', compact('todos'))
        ->fragment('todos.index', 'todo-count', compact('todos'));
}
```

### Fragment Targeting

By default, fragments target elements by ID. You can specify custom selectors:

```php
return hyper()->fragment('todos.index', 'todo-list', compact('todos'), [
    'selector' => '#todo-list',
    'mode' => 'inner'  // Update only inner HTML
]);
```

**Merge modes** (Datastar):

- `outer` - Replace entire element (default)
- `inner` - Replace inner HTML only
- `prepend` - Add content at the beginning
- `append` - Add content at the end
- `before` - Insert before element
- `after` - Insert after element

## Navigation

Hyper provides client-side navigation features that work with Laravel routes.

### Link Navigation

```blade
<!-- Basic navigation -->
<a href="/dashboard" data-navigate="true">Dashboard</a>

<!-- Merge query parameters -->
<a href="/products?category=electronics" data-navigate__merge="true">Electronics</a>

<!-- Keep only specific parameters -->
<a href="/products" data-navigate__only.search,sort="true">Products</a>

<!-- Keep all except specific parameters -->
<a href="/products" data-navigate__except.page="true">Reset Page</a>
```

The `data-navigate` attribute (Hyper) enables client-side navigation without full page reloads.

### Server-Side Navigation

```php
// Navigate to a URL
return hyper()->navigate('/dashboard');

// Navigate with merged query parameters
return hyper()->navigateMerge('/products', ['category' => 'electronics']);

// Navigate keeping only specific parameters
return hyper()->navigateOnly('/products', ['search', 'sort']);

// Navigate excluding specific parameters
return hyper()->navigateExcept('/products', ['page']);

// Navigate with debounce
return hyper()->navigate('/search', ['debounce' => 300]);
```

### Full Page Redirects

For full page reloads (not AJAX):

```php
// Standard Laravel redirect
return redirect('/dashboard');

// With flash data
return redirect('/dashboard')->with('success', 'User created');
```

## Advanced Features

### Streaming

Send continuous updates to the client using Server-Sent Events:

```php
public function liveMetrics()
{
    return hyper()->stream(function($hyper) {
        while (true) {
            $metrics = $this->getLatestMetrics();

            $hyper->signals(['metrics' => $metrics])
                  ->fragment('dashboard', 'metrics-card', $metrics)
                  ->send();

            sleep(5);
        }
    });
}
```

### Route Discovery

Automatically generate routes from controller methods using PHP attributes:

**Enable in config/hyper.php:**

```php
return [
    'route_discovery' => [
        'enabled' => true,
        'discover_controllers_in_directory' => [
            app_path('Http/Controllers'),
        ],
    ],
];
```

**Use attributes in controllers:**

```php
use Dancycodes\Hyper\Routing\Attributes\Route;

#[Route(middleware: 'web')]
class PostController extends Controller
{
    public function index()
    {
        // Auto-registered as GET /post
    }

    #[Route(method: 'post')]
    public function store()
    {
        // Auto-registered as POST /post/store
    }

    #[Route(method: 'delete', uri: '/posts/{post}')]
    public function destroy(Post $post)
    {
        // Custom URI with route model binding
    }
}
```

### Conditional Logic

Use conditional methods to build responses dynamically:

```php
return hyper()
    ->signals(['count' => $count])
    ->when($count > 10, function($hyper) {
        return $hyper->js('showWarning()');
    })
    ->unless($errors->isEmpty(), function($hyper) use ($errors) {
        return $hyper->signals(['errors' => $errors]);
    });
```

### JavaScript Execution

Execute JavaScript code from the server:

```php
// Execute once
return hyper()->js('console.log("Task complete")');

// Execute with auto-removal
return hyper()->js('showNotification()', ['autoRemove' => true]);

// Dispatch custom DOM events
return hyper()->dispatch('task:complete', ['taskId' => 123]);
```

## Datastar Attributes Reference

Hyper applications use Datastar's reactive attributes. Here are the most commonly used:

### State Management

- `data-signals="{...}"` - Create reactive signals
- `data-computed-name="expression"` - Create computed signal
- `data-effect="expression"` - Run code when signals change

### Display & Binding

- `data-text="$signal"` - Display signal value as text
- `data-bind="signal"` - Two-way bind input to signal
- `data-show="condition"` - Show/hide element
- `data-class-name="condition"` - Toggle CSS class
- `data-attr-name="value"` - Set attribute value

### Events

- `data-on-click="expression"` - Handle click events
- `data-on-input="expression"` - Handle input events
- `data-on-submit="expression"` - Handle form submit
- `data-on-[event]="expression"` - Handle any DOM event

Event modifiers:

- `data-on-click__prevent` - Prevent default
- `data-on-submit__prevent__stop` - Prevent default and stop propagation
- `data-on-input__debounce.300ms` - Debounce input

### HTTP Actions

- `@get('/url')` - GET request
- `@post('/url')` - POST request
- `@put('/url')` - PUT request
- `@patch('/url')` - PATCH request
- `@delete('/url')` - DELETE request

**Hyper's CSRF-protected actions:**

- `@postx('/url')` - POST with CSRF token
- `@putx('/url')` - PUT with CSRF token
- `@patchx('/url')` - PATCH with CSRF token
- `@deletex('/url')` - DELETE with CSRF token

### References

- `data-ref="myRef"` - Create reference to element
- `data-intersect="expression"` - Run when element enters viewport

For complete Datastar documentation, visit [data-star.dev](https://data-star.dev).

## Testing

Run the test suite:

```bash
composer test
```

Hyper includes 746 tests covering:

- Signals and state management
- Validation integration
- File upload handling
- Fragment rendering
- Navigation
- Security (locked signals, CSRF)
- Response building

## What Can You Build?

Hyper works well for server-driven applications with reactive UI requirements:

- **CRUD Interfaces**: Admin panels, data tables, content management
- **Forms**: Multi-step wizards, dynamic forms with live validation
- **Live Features**: Real-time dashboards, notifications, chat interfaces
- **Interactive UIs**: Search, filtering, sorting, pagination
- **File Management**: Upload interfaces with previews and progress

Hyper maintains Laravel's server-side architecture while providing reactive user experiences.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`
4. Ensure all tests pass before submitting pull requests

## Security

Security vulnerabilities should be reported privately. Please review [SECURITY.md](SECURITY.md) for responsible disclosure procedures.

## Credits

**Laravel Hyper** is made possible by:

- **[Laravel](https://laravel.com)** - The PHP framework for web artisans
- **[Datastar](https://data-star.dev)** - The reactive hypermedia framework
- **DancyCodes** - Hyper's integration layer and Laravel-specific features
- The Laravel and Datastar communities

## License

Laravel Hyper is open-source software licensed under the [MIT license](LICENSE).

## Support

- **Documentation**: [laravel-hyper.com](https://laravel-hyper.com)
- **Issues**: [GitHub Issues](https://github.com/dancycodes/hyper/issues)
- **Discussions**: [GitHub Discussions](https://github.com/dancycodes/hyper/discussions)
- **Email**: dancycodes@gmail.com

---

<p align="center">Made with ❤️ for the Laravel community</p>
