<?php

namespace Dancycodes\Hyper\Html;

use Dancycodes\Hyper\Html\Elements\Base\Element;
use InvalidArgumentException;

/**
 * Element Registry - Custom HTML Element Registration System
 *
 * Provides a centralized registry for third-party packages to register custom HTML elements
 * that seamlessly integrate with the Html facade. This enables a plugin ecosystem where
 * packages can extend the HTML Builder with custom components, web components, or
 * specialized element types.
 *
 * Key Features:
 * - Dynamic element registration at runtime
 * - Automatic name normalization (kebab-case ↔ camelCase)
 * - Html facade integration via __callStatic magic method
 * - Type safety with class validation
 * - Element lifecycle management (register, unregister, clear)
 * - Support for constructor arguments during instantiation
 *
 * Use Cases:
 * - Third-party component libraries (UI kits, design systems)
 * - Web components integration (custom elements)
 * - Specialized element types (charts, maps, widgets)
 * - Framework-specific components (Livewire, Alpine.js wrappers)
 * - Organization-specific reusable components
 *
 * Basic Registration:
 * ```php
 * use Dancycodes\Hyper\Html\ElementRegistry;
 * use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;
 *
 * // Define custom element class
 * class Card extends ContainerElement
 * {
 *     public function __construct()
 *     {
 *         parent::__construct('div');
 *         $this->class('card');
 *     }
 *
 *     public function premium(): static
 *     {
 *         return $this->class('card-premium');
 *     }
 * }
 *
 * // Register in service provider boot() method
 * ElementRegistry::register('card', Card::class);
 *
 * // Use via Html facade
 * echo Html::card()->premium()->content(
 *     Html::h2()->text('Premium Card')
 * );
 * // Output: <div class="card card-premium"><h2>Premium Card</h2></div>
 * ```
 *
 * Name Normalization:
 * Both kebab-case and camelCase naming are supported and automatically normalized:
 * ```php
 * ElementRegistry::register('custom-card', CustomCard::class);
 * ElementRegistry::register('customCard', CustomCard::class);
 *
 * // Both work identically:
 * Html::customCard()
 * Html::custom_card() // Also works via __callStatic normalization
 * ```
 *
 * Service Provider Integration:
 * ```php
 * namespace MyPackage\Providers;
 *
 * use Illuminate\Support\ServiceProvider;
 * use Dancycodes\Hyper\Html\ElementRegistry;
 * use MyPackage\Html\Elements\Alert;
 * use MyPackage\Html\Elements\Badge;
 * use MyPackage\Html\Elements\Modal;
 *
 * class HtmlComponentServiceProvider extends ServiceProvider
 * {
 *     public function boot(): void
 *     {
 *         // Register multiple custom elements
 *         ElementRegistry::register('alert', Alert::class);
 *         ElementRegistry::register('badge', Badge::class);
 *         ElementRegistry::register('modal', Modal::class);
 *     }
 * }
 * ```
 *
 * Advanced Custom Element Example:
 * ```php
 * use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;
 *
 * class DataTable extends ContainerElement
 * {
 *     public function __construct(?array $data = null)
 *     {
 *         parent::__construct('table');
 *         $this->class('data-table');
 *
 *         if ($data) {
 *             $this->withData($data);
 *         }
 *     }
 *
 *     public function withData(array $data): static
 *     {
 *         $rows = collect($data)->map(fn($row) =>
 *             Html::tr()->children(
 *                 collect($row)->map(fn($cell) =>
 *                     Html::td()->text($cell)
 *                 )->all()
 *             )
 *         );
 *
 *         return $this->content(
 *             Html::tbody()->children($rows->all())
 *         );
 *     }
 *
 *     public function sortable(): static
 *     {
 *         return $this->attr('data-sortable', 'true');
 *     }
 * }
 *
 * // Register and use
 * ElementRegistry::register('data-table', DataTable::class);
 *
 * echo Html::dataTable([
 *     ['Name', 'Email'],
 *     ['John', 'john@example.com'],
 * ])->sortable();
 * ```
 *
 * Web Components Integration:
 * ```php
 * use Dancycodes\Hyper\Html\Elements\Base\ContainerElement;
 *
 * class WebComponent extends ContainerElement
 * {
 *     public function __construct(string $tagName)
 *     {
 *         parent::__construct($tagName);
 *     }
 * }
 *
 * // Register web components
 * ElementRegistry::register('my-button', fn() => new WebComponent('my-button'));
 * ElementRegistry::register('my-dialog', fn() => new WebComponent('my-dialog'));
 *
 * // Use custom web components
 * Html::myButton()->attr('primary', true)->text('Click Me');
 * Html::myDialog()->attr('open', true)->content(...);
 * ```
 *
 * Runtime Element Management:
 * ```php
 * // Check if element is registered
 * if (ElementRegistry::has('custom-card')) {
 *     echo Html::customCard();
 * }
 *
 * // Get element class name
 * $class = ElementRegistry::get('custom-card'); // Returns: CustomCard::class
 *
 * // Get all registered elements
 * $elements = ElementRegistry::all();
 * // Returns: ['customCard' => CustomCard::class, ...]
 *
 * // Unregister an element
 * ElementRegistry::unregister('custom-card');
 *
 * // Clear all custom elements (useful for testing)
 * ElementRegistry::clear();
 * ```
 *
 * Testing Custom Elements:
 * ```php
 * use Tests\TestCase;
 * use Dancycodes\Hyper\Html\ElementRegistry;
 *
 * class CustomElementTest extends TestCase
 * {
 *     protected function setUp(): void
 *     {
 *         parent::setUp();
 *         ElementRegistry::register('test-card', TestCard::class);
 *     }
 *
 *     protected function tearDown(): void
 *     {
 *         ElementRegistry::clear(); // Clean up after tests
 *         parent::tearDown();
 *     }
 *
 *     public function test_custom_element_renders(): void
 *     {
 *         $html = Html::testCard()->render();
 *         $this->assertStringContainsString('<div class="test-card">', $html);
 *     }
 * }
 * ```
 *
 * Security Considerations:
 * - Element classes MUST extend Element base class (enforced)
 * - All custom elements inherit XSS protection from Element
 * - Custom elements use same escaping rules as built-in elements
 * - Validation ensures only valid Element subclasses are registered
 *
 * Performance Notes:
 * - Element lookup is O(1) via associative array
 * - Name normalization happens once during registration
 * - No performance impact on built-in elements
 * - Elements are instantiated on-demand, not at registration
 *
 * Integration with Html Facade:
 * The Html facade's __callStatic method checks ElementRegistry for custom elements:
 * 1. First checks for built-in element (div, p, span, etc.)
 * 2. Then checks ElementRegistry for custom element
 * 3. Finally checks ElementRegistry with normalized name
 * 4. Throws exception if element not found
 *
 * @see \Dancycodes\Hyper\Html\Html
 * @see \Dancycodes\Hyper\Html\Elements\Base\Element
 * @see \Dancycodes\Hyper\Html\Elements\Base\ContainerElement
 * @see \Dancycodes\Hyper\Html\Elements\Base\TextElement
 */
class ElementRegistry
{
    /**
     * Registered custom elements
     *
     * @var array<string, class-string<Element>>
     */
    protected static array $customElements = [];

    /**
     * Register a custom element
     *
     * @param string $name Element name (kebab-case or camelCase)
     * @param class-string<Element> $class Element class that extends Element
     *
     * @throws InvalidArgumentException
     */
    public static function register(string $name, string $class): void
    {
        // Validate that class exists
        if (!class_exists($class)) {
            throw new InvalidArgumentException(
                "Cannot register element '{$name}': class '{$class}' does not exist."
            );
        }

        // Validate that class extends Element
        if (!is_subclass_of($class, Element::class)) {
            throw new InvalidArgumentException(
                "Cannot register element '{$name}': class '{$class}' must extend " . Element::class
            );
        }

        // Normalize name to camelCase
        $normalizedName = static::normalizeName($name);

        // Store in registry
        static::$customElements[$normalizedName] = $class;
    }

    /**
     * Check if an element is registered
     */
    public static function has(string $name): bool
    {
        $normalizedName = static::normalizeName($name);

        return isset(static::$customElements[$normalizedName]);
    }

    /**
     * Get registered element class
     *
     * @return class-string<Element>|null
     */
    public static function get(string $name): ?string
    {
        $normalizedName = static::normalizeName($name);

        return static::$customElements[$normalizedName] ?? null;
    }

    /**
     * Create an instance of a registered element
     *
     * @param array $args Constructor arguments
     *
     * @throws InvalidArgumentException
     */
    public static function make(string $name, array $args = []): Element
    {
        $normalizedName = static::normalizeName($name);

        // Check if element is registered
        if (!isset(static::$customElements[$normalizedName])) {
            throw new InvalidArgumentException(
                "Element '{$name}' is not registered. Use ElementRegistry::register() to register custom elements."
            );
        }

        $class = static::$customElements[$normalizedName];

        // Instantiate with constructor arguments
        return new $class(...$args);
    }

    /**
     * Unregister a custom element
     */
    public static function unregister(string $name): void
    {
        $normalizedName = static::normalizeName($name);
        unset(static::$customElements[$normalizedName]);
    }

    /**
     * Get all registered custom elements
     *
     * @return array<string, class-string<Element>>
     */
    public static function all(): array
    {
        return static::$customElements;
    }

    /**
     * Clear all registered elements (useful for testing)
     */
    public static function clear(): void
    {
        static::$customElements = [];
    }

    /**
     * Normalize element name (kebab-case to camelCase)
     */
    protected static function normalizeName(string $name): string
    {
        // If already camelCase, return as-is
        if (!str_contains($name, '-')) {
            return $name;
        }

        // Convert kebab-case to camelCase
        // Examples:
        // 'custom-card' => 'customCard'
        // 'my-custom-element' => 'myCustomElement'
        return lcfirst(str_replace('-', '', ucwords($name, '-')));
    }
}
