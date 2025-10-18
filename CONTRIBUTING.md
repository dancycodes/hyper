# Contributing to Laravel Hyper

Thank you for considering contributing to Laravel Hyper! We welcome contributions from the community and are grateful for your support.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Documentation](#documentation)
- [Reporting Bugs](#reporting-bugs)
- [Suggesting Enhancements](#suggesting-enhancements)

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to [dancycodes@gmail.com](mailto:dancycodes@gmail.com).

### Our Standards

- **Be respectful**: Treat everyone with respect and kindness
- **Be collaborative**: Work together and help each other
- **Be professional**: Keep discussions focused and constructive
- **Be inclusive**: Welcome newcomers and diverse perspectives

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the [issue tracker](https://github.com/dancycodes/hyper/issues) as you might find that the issue has already been reported. When creating a bug report, include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples** to demonstrate the steps
- **Describe the behavior you observed** after following the steps
- **Explain what behavior you expected** to see instead and why
- **Include screenshots or code samples** if relevant
- **Include your environment details** (PHP version, Laravel version, OS)

### Suggesting Enhancements

Enhancement suggestions are tracked as [GitHub issues](https://github.com/dancycodes/hyper/issues). When creating an enhancement suggestion, include:

- **Use a clear and descriptive title**
- **Provide a detailed description** of the suggested enhancement
- **Explain why this enhancement would be useful** to most Hyper users
- **List examples** of how the enhancement would be used
- **Describe the current behavior** and **explain the expected behavior**

### Code Contributions

1. Fork the repository
2. Create a new branch for your feature (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Write or update tests as needed
5. Ensure all tests pass
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to your branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## Development Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git

### Local Development Environment

1. **Clone your fork:**
   ```bash
   git clone https://github.com/YOUR_USERNAME/hyper.git
   cd hyper/packages/dancycodes/hyper
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Run tests to verify setup:**
   ```bash
   composer test
   ```

4. **Make your changes** in the `src/` directory

5. **Write tests** for your changes in the `tests/` directory

6. **Run tests** to ensure everything works:
   ```bash
   composer test
   ```

### Running Tests

```bash
# Run all tests
composer test

# Run only unit tests
composer test-unit

# Run only feature tests
composer test-feature

# Generate coverage report
composer test-coverage
```

### Coding Standards

We follow the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard and use PHP CS Fixer to enforce it.

```bash
# Check code style
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix code style automatically
./vendor/bin/php-cs-fixer fix
```

**Key Guidelines:**

- Use type hints for all method parameters and return types
- Add PHPDoc blocks for all public methods
- Keep methods focused and single-purpose
- Use descriptive variable and method names
- Follow Laravel naming conventions
- Write self-documenting code with clear variable names

**Example:**

```php
/**
 * Update signals and return Hyper response.
 *
 * @param  array<string, mixed>  $signals
 * @return $this
 */
public function signals(array $signals): self
{
    foreach ($signals as $key => $value) {
        $this->updateSignal($key, $value);
    }

    return $this;
}
```

## Testing Guidelines

### Test Structure

- **Unit Tests** (`tests/Unit/`): Test individual classes and methods in isolation
- **Feature Tests** (`tests/Feature/`): Test complete workflows and integrations

### Writing Tests

1. **Use descriptive test names:**
   ```php
   public function test_signals_method_updates_multiple_signals_correctly()
   ```

2. **Follow the Arrange-Act-Assert pattern:**
   ```php
   public function test_example()
   {
       // Arrange
       $user = User::factory()->create();

       // Act
       $response = hyper()->signals(['user' => $user]);

       // Assert
       $this->assertInstanceOf(HyperResponse::class, $response);
   }
   ```

3. **Test edge cases and error conditions:**
   - Invalid input
   - Empty data
   - Null values
   - Boundary conditions

4. **Mock external dependencies:**
   ```php
   $mock = Mockery::mock(HyperFileStorage::class);
   $mock->shouldReceive('store')->once()->andReturn('path/to/file');
   ```

### Test Coverage

- Aim for **95%+ code coverage**
- All new features must include tests
- Bug fixes should include regression tests

## Pull Request Process

### Before Submitting

1. ✅ **All tests pass** (`composer test`)
2. ✅ **Code follows PSR-12** (run PHP CS Fixer)
3. ✅ **New features have tests**
4. ✅ **Documentation is updated** if needed
5. ✅ **Commit messages are clear**
6. ✅ **No merge conflicts** with main branch

### PR Guidelines

1. **Title**: Use a clear, descriptive title
   - Good: "Add support for streaming responses"
   - Bad: "Fix bug"

2. **Description**: Explain what and why
   ```markdown
   ## Changes
   - Added streaming support for real-time updates
   - Implemented new `stream()` method on HyperResponse

   ## Motivation
   Enables real-time data updates without polling

   ## Testing
   - Added unit tests for stream() method
   - Added feature test for complete streaming workflow
   ```

3. **Link related issues**: Reference any related issues
   ```markdown
   Fixes #123
   Related to #456
   ```

4. **Screenshots**: Include before/after screenshots for UI changes

5. **Breaking Changes**: Clearly mark any breaking changes

### Review Process

1. A maintainer will review your PR
2. Address any requested changes
3. Once approved, your PR will be merged
4. Your contribution will be credited in the changelog

## Documentation

### Code Documentation

- Add PHPDoc blocks to all public methods
- Include `@param`, `@return`, and `@throws` tags
- Provide usage examples in complex methods

### User Documentation

When adding new features:

1. Update relevant documentation files
2. Add examples to README if appropriate
3. Include usage examples in PHPDoc
4. Update CHANGELOG.md

### Blade Directive Documentation

```php
/**
 * Compile the @signals Blade directive.
 *
 * Converts PHP variables into reactive Datastar signals.
 *
 * @example
 * ```blade
 * <div @signals(['count' => 0, 'name' => 'John'])>
 *     <span data-text="$count"></span>
 * </div>
 * ```
 *
 * @param  string  $expression
 * @return string
 */
public function compileSignals(string $expression): string
```

## Branch Naming

Use descriptive branch names:

- `feature/add-streaming-support`
- `fix/validation-error-display`
- `docs/update-readme`
- `refactor/simplify-signal-manager`

## Commit Messages

Write clear, descriptive commit messages:

```
Add streaming support for real-time updates

- Implement stream() method on HyperResponse
- Add SSE header management
- Include comprehensive tests

Closes #123
```

**Format:**
```
<type>: <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

## Getting Help

- **Questions**: Open a [GitHub Discussion](https://github.com/dancycodes/hyper/discussions)
- **Bugs**: Create an [Issue](https://github.com/dancycodes/hyper/issues)
- **Chat**: Join our community (link TBD)

## Recognition

All contributors will be recognized in:
- The CHANGELOG.md file
- The GitHub contributors page
- Release notes for significant contributions

## License

By contributing to Laravel Hyper, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing to Laravel Hyper! 🎉
