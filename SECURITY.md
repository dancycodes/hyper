# Security Policy

## Supported Versions

We release patches for security vulnerabilities. Which versions are eligible for receiving such patches depends on the CVSS v3.0 Rating:

| Version | Supported          |
| ------- | ------------------ |
| 0.x     | :white_check_mark: |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

We take the security of Laravel Hyper seriously. If you discover a security vulnerability, please follow these steps:

### 1. **DO NOT** Disclose Publicly

Please do not create a public GitHub issue for the vulnerability. Public disclosure makes it easier for malicious actors to exploit the vulnerability.

### 2. Email Us Directly

Send a detailed report to: **dancycodes@gmail.com**

Include as much information as possible:
- Description of the vulnerability
- Steps to reproduce the issue
- Potential impact
- Suggested fix (if you have one)
- Your name/handle for acknowledgment (optional)

### 3. Wait for Confirmation

You should receive an acknowledgment within 48 hours. We will:
1. Confirm receipt of your vulnerability report
2. Assign a severity level
3. Develop and test a fix
4. Prepare a security advisory
5. Release a patch
6. Publicly disclose the vulnerability (with your permission)

### 4. Coordinated Disclosure

We practice coordinated disclosure:
- We will keep you informed about our progress
- We will not disclose the vulnerability until a fix is ready
- We will credit you in the security advisory (if you wish)
- We request that you keep the vulnerability confidential until we release the fix

## Security Best Practices

When using Laravel Hyper, please follow these security best practices:

### 1. Locked Signals

Use locked signals for sensitive data that should not be modified by the client:

```php
// In your Blade view
<div @signals([
    'userId_' => auth()->id(),
    'role_' => auth()->user()->role,
    'price_' => $product->price
])>
```

**Why:** Locked signals are encrypted and validated on every request, preventing tampering.

### 2. Server-Side Validation

Always validate signals on the server:

```php
public function update() {
    $validated = signals()->validate([
        'email' => 'required|email|unique:users',
        'role' => 'required|in:user,admin'
    ]);

    // Use validated data...
}
```

**Why:** Client-side data can be manipulated. Never trust data from the frontend.

### 3. Authorization Checks

Always verify permissions before performing sensitive operations:

```php
public function delete($id) {
    $post = Post::findOrFail($id);

    // Authorization check
    if (!auth()->user()->can('delete', $post)) {
        abort(403);
    }

    $post->delete();

    return hyper()->signals(['deleted' => true]);
}
```

**Why:** Signal validation doesn't replace authorization checks.

### 4. CSRF Protection

Always use CSRF-protected actions for mutating operations:

```blade
<!-- ✅ Correct: Uses @postx with automatic CSRF -->
<button data-on:click="@postx('/delete')">Delete</button>

<!-- ❌ Wrong: Uses @post without CSRF -->
<button data-on:click="@post('/delete')">Delete</button>
```

**Why:** The `x` suffix (`@postx`, `@putx`, etc.) automatically includes CSRF tokens.

### 5. File Upload Validation

Always validate uploaded files with appropriate rules:

```php
signals()->validate([
    'avatar' => 'required|b64image|b64max:2048|b64dimensions:min_width=100,max_width=2000',
    'document' => 'required|b64file|b64mimes:pdf,docx|b64max:5120'
]);
```

**Why:** Prevents malicious file uploads and resource exhaustion.

### 6. XSS Prevention

Hyper automatically escapes output, but be careful with raw HTML:

```php
// ✅ Safe: Blade escaping
return hyper()->view('partial', ['username' => $username]);

// ⚠️ Be careful: Raw HTML
return hyper()->html("<div>$username</div>"); // Could be vulnerable

// ✅ Better: Use views for dynamic content
return hyper()->view('partial', compact('username'));
```

### 7. Rate Limiting

Implement rate limiting for Hyper endpoints:

```php
Route::post('/search', [SearchController::class, 'search'])
    ->middleware('throttle:60,1'); // 60 requests per minute
```

**Why:** Prevents abuse and DoS attacks.

### 8. Input Sanitization

Sanitize user input, especially for database queries and HTML output:

```php
$query = signals('search');

// ✅ Use Eloquent query builder (auto-escapes)
User::where('name', 'like', "%{$query}%")->get();

// ❌ Don't use raw queries without bindings
DB::select("SELECT * FROM users WHERE name LIKE '%{$query}%'");
```

### 9. Secure Session Configuration

Ensure your session configuration is secure:

```php
// config/session.php
'secure' => env('SESSION_SECURE_COOKIE', true),
'http_only' => true,
'same_site' => 'strict',
```

**Why:** Locked signals rely on session security.

### 10. Regular Updates

Keep Laravel Hyper and all dependencies up to date:

```bash
composer update dancycodes/hyper
```

**Why:** Security patches are released regularly.

## Known Security Features

### Automatic CSRF Protection

Hyper's `x` actions (`@postx`, `@putx`, `@patchx`, `@deletex`) automatically include CSRF tokens from the meta tag generated by the `@hyper` directive.

### Locked Signal Encryption

Locked signals (with `_` suffix) are:
- Encrypted using Laravel's encryption service
- Stored in the session (server-side)
- Validated on every request
- Protected from tampering

If tampering is detected, a `HyperSignalTamperedException` is thrown and logged.

### Validation Integration

Laravel's validation system is fully integrated, providing:
- Type checking
- Format validation
- Custom rules
- Automatic error message generation

### Base64 File Validation

Specialized validation rules prevent:
- Oversized file uploads
- Invalid file types
- Malicious file content
- Resource exhaustion

## Security Advisories

Security advisories will be published:
1. In this repository's [Security Advisories](https://github.com/dancycodes/hyper/security/advisories)
2. On our website
3. Via email to registered users (if available)

## Acknowledgments

We thank all security researchers who responsibly disclose vulnerabilities to us. Recognized contributors:

- (None yet - you could be the first!)

## Contact

For security concerns: **dancycodes@gmail.com**

For general support: **dancycodes@gmail.com**

---

Thank you for helping keep Laravel Hyper and its users safe!
