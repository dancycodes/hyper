# HTML Builder Validation System

Laravel-compatible validation for the Hyper HTML builder with progressive enhancement via HTML5 attributes and real-time validation.

## Table of Contents

- [Quick Start](#quick-start)
- [Core Concepts](#core-concepts)
- [Laravel Compatibility](#laravel-compatibility)
- [Element-Level Validation](#element-level-validation)
- [Form-Level Validation](#form-level-validation)
- [HTML5 Validation (clientSide)](#html5-validation-clientside)
- [Real-Time Validation (live)](#real-time-validation-live)
- [Complete Examples](#complete-examples)

## Quick Start

```php
use Dancycodes\Hyper\Html\Html;

// Simple input with validation
Html::input()
    ->name('email')
    ->validate('required|email')
    ->withError();

// Auto-everything form
Html::form()
    ->withSignals()   // Auto @signals
    ->withErrors()    // Auto error divs
    ->postx('/submit')
    ->content(
        Html::input()->name('email')->validate('required|email'),
        Html::input()->name('password')->validate('required|min:8'),
        Html::button()->type('submit')->text('Submit')
    );
```

## Core Concepts

### Three Validation Levels

1. **Element-Level**: Individual input validation
2. **Form-Level**: Orchestrated multi-field validation
3. **Server-Side**: Using `signals()->validate()`

### Progressive Enhancement

- **Server-Side**: Always validates via Laravel (secure)
- **HTML5**: Optional client-side validation (immediate feedback)
- **Live**: Optional real-time validation (as you type)

## Laravel Compatibility

The `validate()` method signature is **100% compatible** with Laravel's validation:

```php
// Laravel Request validation
$request->validate($rules, $messages, $attributes);

// Laravel Signals validation
signals()->validate($rules, $messages, $attributes);

// HTML Builder validation (SAME SIGNATURE!)
Html::input()->validate($rules, $messages, $attributes, clientSide: false, live: false);
```

### Signature

```php
public function validate(
    string|array|Closure $rules,        // Laravel compat
    array|Closure $messages = [],       // Laravel compat
    array|Closure $attributes = [],     // Laravel compat
    bool $clientSide = false,           // NEW: HTML5 attributes
    bool $live = false                  // NEW: Real-time validation
): static
```

## Element-Level Validation

### Basic Usage

```php
// String rules for single field
Html::input()
    ->name('email')
    ->validate('required|email|unique:users');

// With custom messages
Html::input()
    ->name('email')
    ->validate(
        'required|email|unique:users',
        ['email.unique' => 'This email is already taken']
    );

// With custom attribute names
Html::input()
    ->name('user_email')
    ->validate(
        'required|email',
        ['user_email.required' => 'Email is required'],
        ['user_email' => 'email address']  // Used in error messages
    );
```

### Auto-Generate Error Display

```php
// Default error styling
Html::input()
    ->name('email')
    ->validate('required|email')
    ->withError();  // Generates: <div data-error="email" class="text-red-500 text-sm mt-1"></div>

// Custom error styling
Html::input()
    ->name('email')
    ->validate('required|email')
    ->withError('text-red-600 text-xs font-semibold mt-0.5');
```

## Form-Level Validation

### Auto-Generate Signals

```php
Html::form()
    ->withSignals()  // Injects @signals with errors array + field signals
    ->postx('/submit')
    ->content(
        Html::input()->name('email')->validate('required|email'),
        Html::input()->name('name')->validate('required')
    );
```

**Generates:**
```html
<form data-signals='{"email":"","name":"","errors":[]}'
      data-on:submit__prevent="@postx('/submit')">
    ...
</form>
```

### Auto-Generate Error Divs

```php
Html::form()
    ->withErrors()  // Auto-adds withError() to all validated inputs
    ->content(
        Html::input()->name('email')->validate('required|email'),
        Html::input()->name('password')->validate('required|min:8')
    );
```

**Result**: Every validated input automatically gets an error div after it.

### Validation Groups (Multi-Step Forms)

```php
Html::form()
    ->withSignals()
    ->validationGroup('step1', ['name', 'email'])
    ->validationGroup('step2', ['password', 'password_confirmation'])
    ->content(
        // Step 1
        Html::div()->dataShow('$currentStep === 1')->content(
            Html::input()->name('name')->validate('required'),
            Html::input()->name('email')->validate('required|email'),
            Html::button()->text('Next')->patchx('/validate-step/step1')
        ),

        // Step 2
        Html::div()->dataShow('$currentStep === 2')->content(
            Html::input()->name('password')->validate('required|min:8'),
            Html::input()->name('password_confirmation')->validate('required'),
            Html::button()->type('submit')->text('Submit')
        )
    );
```

**Controller:**
```php
Route::patchx('/validate-step/{step}', function (string $step, RegisterForm $form) {
    // Validate only this step's fields!
    $rules = $form->getValidationRules($step);

    signals()->validate($rules);

    return hyper()->signals(['currentStep' => (int)$step + 1]);
});
```

### Export Validation Rules

```php
// In View/Page Class
class RegisterPage
{
    public Form $form;

    public function __construct()
    {
        $this->form = Html::form()->content(
            Html::input()->name('email')->validate('required|email|unique:users'),
            Html::input()->name('password')->validate('required|min:8')
        );
    }
}

// In Controller
class RegisterController
{
    public function store(RegisterPage $page)
    {
        // Export rules from form!
        $rules = $page->form->getValidationRules();
        // Returns: ['email' => 'required|email|unique:users', 'password' => 'required|min:8']

        $validated = signals()->validate($rules);

        User::create($validated);

        return hyper()->signals(['message' => 'Account created!']);
    }
}
```

## HTML5 Validation (clientSide)

Enable `clientSide: true` to automatically generate HTML5 validation attributes:

```php
Html::input()
    ->name('email')
    ->validate('required|email|max:255', clientSide: true);
```

**Renders:**
```html
<input name="email" required type="email" maxlength="255" />
```

### Laravel → HTML5 Transformation

| Laravel Rule | HTML5 Attribute | Example |
|--------------|-----------------|---------|
| `required` | `required` | `<input required>` |
| `email` | `type="email"` | `<input type="email">` |
| `url` | `type="url"` | `<input type="url">` |
| `numeric` | `type="number"` | `<input type="number">` |
| `min:8` | `minlength="8"`, `min="8"` | `<input minlength="8">` |
| `max:255` | `maxlength="255"`, `max="255"` | `<input maxlength="255">` |
| `between:5,10` | `minlength="5"`, `maxlength="10"` | `<input minlength="5" maxlength="10">` |
| `regex:/pattern/` | `pattern="pattern"` | `<input pattern="[A-Z]+">` |

### Example

```php
Html::input()
    ->name('username')
    ->validate('required|alpha_dash|min:3|max:20', clientSide: true)
    ->withError();
```

**Renders:**
```html
<input name="username"
       required
       minlength="3"
       maxlength="20"
       data-bind="username" />
<div data-error="username" class="text-red-500 text-sm mt-1"></div>
```

**Benefits:**
- ✅ Immediate feedback before server request
- ✅ Reduces unnecessary API calls
- ✅ Progressive enhancement (works without JavaScript)
- ✅ Still validates on server (secure)

## Real-Time Validation (live)

Enable `live: true` for real-time field validation as users type:

```php
Html::input()
    ->name('username')
    ->validate('required|alpha_dash|unique:users', live: true)
    ->withError();
```

**What Happens:**

1. **Auto-attaches debounced action** (300ms delay):
   ```html
   <input data-on:input__debounce.300ms="@patchx('/validate/username')" />
   ```

2. **Auto-registers validation route**:
   ```php
   Route::patchx('/validate/username', function () {
       signals()->validate(['username' => 'required|alpha_dash|unique:users']);
       return hyper()->signals(['errors' => ['username' => []]]);
   });
   ```

3. **Updates errors in real-time**:
   - User types → Waits 300ms → Validates field → Shows errors immediately
   - Field becomes valid → Errors cleared automatically

### Example: Live Username Availability

```php
Html::input()
    ->name('username')
    ->validate('required|alpha_dash|unique:users|min:3', live: true, clientSide: true)
    ->withError('text-red-600 text-xs mt-1');
```

**User Experience:**
1. User types "jo" → HTML5: Too short (minlength=3)
2. User types "joh" → Live: Checks database → ✅ Available
3. User types "john" → Live: Checks database → ❌ "Username already taken"

## Complete Examples

### Simple Registration Form

```php
Html::form()
    ->withSignals()
    ->withErrors()
    ->postx('/register')
    ->content(
        Html::input()
            ->name('email')
            ->type('email')
            ->validate('required|email|unique:users', clientSide: true),

        Html::input()
            ->name('password')
            ->type('password')
            ->validate('required|min:8', clientSide: true),

        Html::button()
            ->type('submit')
            ->dataIndicator('submitting')
            ->dataAttr('disabled', '$submitting')
            ->text('Register')
    );
```

### Advanced Form with Live Validation

```php
Html::form()
    ->withSignals()
    ->withErrors('text-red-600 text-xs font-semibold mt-0.5')
    ->dataIndicator('submitting')
    ->postx('/register')
    ->content(
        // Live username check
        Html::input()
            ->name('username')
            ->placeholder('Username')
            ->validate(
                'required|alpha_dash|min:3|max:20|unique:users',
                ['username.unique' => 'Username already taken'],
                [],
                clientSide: true,
                live: true
            ),

        // Email with HTML5 + Live
        Html::input()
            ->name('email')
            ->type('email')
            ->placeholder('Email')
            ->validate('required|email|unique:users', clientSide: true, live: true),

        // Password with HTML5 only
        Html::input()
            ->name('password')
            ->type('password')
            ->placeholder('Password')
            ->validate('required|min:8|confirmed', clientSide: true),

        // Password confirmation
        Html::input()
            ->name('password_confirmation')
            ->type('password')
            ->placeholder('Confirm Password')
            ->validate('required', clientSide: true),

        // Submit with loading state
        Html::button()
            ->type('submit')
            ->dataAttr('disabled', '$submitting')
            ->dataClass(['opacity-50' => '$submitting'])
            ->content(
                Html::span()->dataShow('!$submitting')->text('Create Account'),
                Html::span()->dataShow('$submitting')->text('Creating Account...')
            )
    );
```

### Multi-Step Wizard

```php
Html::form()
    ->withSignals()
    ->withErrors()
    ->dataSignals(['currentStep' => 1])
    ->validationGroup('personalInfo', ['name', 'email', 'phone'])
    ->validationGroup('accountInfo', ['username', 'password', 'password_confirmation'])
    ->validationGroup('preferences', ['newsletter', 'notifications'])
    ->content(
        // Step 1: Personal Info
        Html::div()->dataShow('$currentStep === 1')->content(
            Html::h2('Personal Information')->class('text-xl font-bold mb-4'),
            Html::input()->name('name')->validate('required|string|max:255'),
            Html::input()->name('email')->validate('required|email|unique:users', live: true),
            Html::input()->name('phone')->validate('required|phone'),
            Html::button()
                ->text('Next')
                ->dataOn('click', '$currentStep = 2')
                ->patchx('/validate-step/personalInfo')
        ),

        // Step 2: Account Info
        Html::div()->dataShow('$currentStep === 2')->content(
            Html::h2('Account Information')->class('text-xl font-bold mb-4'),
            Html::input()->name('username')->validate('required|alpha_dash|unique:users', live: true),
            Html::input()->name('password')->type('password')->validate('required|min:8|confirmed'),
            Html::input()->name('password_confirmation')->type('password')->validate('required'),
            Html::button()->text('Previous')->dataOn('click', '$currentStep = 1'),
            Html::button()
                ->text('Next')
                ->dataOn('click', '$currentStep = 3')
                ->patchx('/validate-step/accountInfo')
        ),

        // Step 3: Preferences
        Html::div()->dataShow('$currentStep === 3')->content(
            Html::h2('Preferences')->class('text-xl font-bold mb-4'),
            Html::input()->type('checkbox')->name('newsletter')->dataBind('newsletter'),
            Html::input()->type('checkbox')->name('notifications')->dataBind('notifications'),
            Html::button()->text('Previous')->dataOn('click', '$currentStep = 2'),
            Html::button()->type('submit')->text('Create Account')
        )
    );
```

## Best Practices

### ✅ DO

- Use `withSignals()` and `withErrors()` for convenience
- Enable `clientSide: true` for better UX
- Use `live: true` sparingly (database-intensive checks only)
- Export rules for controller validation
- Combine HTML5 + server validation (progressive enhancement)

### ❌ DON'T

- Don't skip server-side validation (never trust client-side)
- Don't use `live: true` on every field (performance)
- Don't forget to add `->withError()` when not using `->withErrors()`
- Don't override HTML5 attributes set by `clientSide: true`

## See Also

- [Action Methods API](./ACTIONS_API.md)
- [Loading States](./LOADING_STATES.md)
- [Laravel Validation Documentation](https://laravel.com/docs/validation)
