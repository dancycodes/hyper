<?php

namespace Dancycodes\Hyper\Tests\Unit\Html;

use Dancycodes\Hyper\Html\Contracts\IconProviderContract;
use Dancycodes\Hyper\Html\Elements\Visual\Icon;
use Dancycodes\Hyper\Html\Html;
use Dancycodes\Hyper\Html\Services\IconManager;
use Dancycodes\Hyper\Tests\TestCase;

/**
 * Icon Integration Tests
 *
 * Tests the complete icon system integration including:
 * - Provider registration
 * - Icon resolution
 * - HasIcons trait functionality
 * - Multiple icon positioning
 * - Size and accessibility
 */
class IconIntegrationTest extends TestCase
{
    protected IconProviderContract $mockProvider;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock icon provider for testing
        $this->mockProvider = new class implements IconProviderContract
        {
            public function resolve(string $name, ?string $variant = null): string
            {
                return '<svg><path d="test-' . $name . '"></path></svg>';
            }

            public function available(): array
            {
                return ['home', 'user', 'star'];
            }

            public function has(string $name, ?string $variant = null): bool
            {
                return in_array($name, ['home', 'user', 'star']);
            }
        };
    }

    /** @test */
    public function test_registers_and_resolves_icon_providers_via_Html_facade()
    {
    // Register test provider
    Html::iconProvider('test', $this->mockProvider);

    // Create icon using the test provider
        $icon = Html::icon('home', 'test');

        $this->assertInstanceOf(Icon::class, $icon);

        $html = $icon->render();
        $this->assertStringContainsString('test-home', $html);
    }

    /** @test */
    public function test_creates_icons_with_HasIcons_trait_on_buttons()
    {
        // Register test provider
        Html::iconProvider('test', $this->mockProvider);

        // Create button with left icon
        $button = Html::button()
            ->text('Click Me')
            ->leftIcon('home');

        $html = $button->render();

        // Should contain both icon and text
        $this->assertStringContainsString('test-home', $html);
        $this->assertStringContainsString('Click Me', $html);
        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('</button>', $html);
    }

    /** @test */
    public function test_supports_multiple_icons_on_same_element()
    {
        // Register test provider
        Html::iconProvider('test', $this->mockProvider);

        // Create button with multiple icons
        $button = Html::button()
            ->text('Action')
            ->leftIcon('home')
            ->rightIcon('user')
            ->rightIcon('star');

        $html = $button->render();

        // Should contain all icons and text
        $this->assertStringContainsString('test-home', $html);
        $this->assertStringContainsString('test-user', $html);
        $this->assertStringContainsString('test-star', $html);
        $this->assertStringContainsString('Action', $html);
    }

    /** @test */
    public function test_supports_prefix_and_suffix_icon_aliases()
    {
        Html::iconProvider('test', $this->mockProvider);

        $button = Html::button()
            ->text('Save')
            ->prefixIcon('home')  // Alias for leftIcon
            ->suffixIcon('star'); // Alias for rightIcon

        $html = $button->render();

        $this->assertStringContainsString('test-home', $html);
        $this->assertStringContainsString('test-star', $html);
        $this->assertStringContainsString('Save', $html);
    }

    /** @test */
    public function test_supports_all_four_icon_positions()
    {
        Html::iconProvider('test', $this->mockProvider);

        $div = Html::div()
            ->text('Content')
            ->topIcon('home')
            ->leftIcon('user')
            ->rightIcon('star')
            ->bottomIcon('home');

        $html = $div->render();

        // All icons should be present
        $this->assertStringContainsString('test-home', $html);
        $this->assertStringContainsString('test-user', $html);
        $this->assertStringContainsString('test-star', $html);
        $this->assertStringContainsString('Content', $html);
    }

    /** @test */
    public function test_supports_array_based_batch_icon_adding()
    {
        Html::iconProvider('test', $this->mockProvider);

        $button = Html::button()
            ->icons([
                ['name' => 'home', 'position' => 'left'],
                ['name' => 'user', 'position' => 'right'],
                ['name' => 'star', 'position' => 'right'],
            ])
            ->text('Multi Icon');

        $html = $button->render();

        $this->assertStringContainsString('test-home', $html);
        $this->assertStringContainsString('test-user', $html);
        $this->assertStringContainsString('test-star', $html);
        $this->assertStringContainsString('Multi Icon', $html);
    }

    /** @test */
    public function test_applies_size_classes_to_icons()
    {
        Html::iconProvider('test', $this->mockProvider);

        $icon = Html::icon('home', 'test')->lg();
        $html = $icon->render();

        $this->assertStringContainsString('h-6 w-6', $html);
    }

    /** @test */
    public function test_applies_accessibility_attributes_to_icons()
    {
        Html::iconProvider('test', $this->mockProvider);

        // Decorative icon (default)
        $decorative = Html::icon('home', 'test');
        $html = $decorative->render();
        $this->assertStringContainsString('aria-hidden="true"', $html);

        // Semantic icon with string shorthand (sets semantic + aria-label)
        $semantic = Html::icon('home', 'test')->semantic('Home Icon');
        $html = $semantic->render();
        $this->assertStringContainsString('role="img"', $html);
        $this->assertStringContainsString('aria-label="Home Icon"', $html);

        // Semantic icon using parent's ariaLabel() method
        $semantic2 = Html::icon('home', 'test')->semantic()->ariaLabel('Go Home');
        $html2 = $semantic2->render();
        $this->assertStringContainsString('aria-label="Go Home"', $html2);
    }

    /** @test */
    public function test_sets_default_provider_correctly()
    {
        Html::iconProvider('test', $this->mockProvider);
        Html::setDefaultIconProvider('test');

        $manager = app(IconManager::class);

        $this->assertEquals('test', $manager->getDefaultProvider());

        // Icon without provider should use default
        $icon = Html::icon('home');
        $html = $icon->render();

        $this->assertStringContainsString('test-home', $html);
    }
}
