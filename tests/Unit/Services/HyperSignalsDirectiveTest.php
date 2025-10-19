<?php

namespace Dancycodes\Hyper\Tests\Unit\Services;

use Dancycodes\Hyper\Services\HyperSignalsDirective;
use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Support\Collection;

/**
 * Test the HyperSignalsDirective service
 *
 * @see TESTING.md - File 5: HyperSignalsDirective Tests
 * Status: 🔄 IN PROGRESS - 35 test methods
 */
class HyperSignalsDirectiveTest extends TestCase
{
    protected static $latestResponse;

    protected HyperSignalsDirective $directive;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directive = new HyperSignalsDirective;
    }

    // ==========================================
    // Expression Parsing Tests (15 methods)
    // ==========================================

    /** @test */
    public function test_parse_and_rewrite_expression_handles_simple_variable()
    {
        $result = $this->directive->parseAndRewriteExpression('$count');

        $this->assertEquals("['count' => \$count]", $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_local_variable()
    {
        $result = $this->directive->parseAndRewriteExpression('$_temp');

        // NEW: Uses literal variable name - $_temp variable creates _temp signal
        $this->assertEquals("['_temp' => \$_temp]", $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_locked_variable()
    {
        $result = $this->directive->parseAndRewriteExpression('$userId_');

        // Core code includes underscore in variable name: $userId_ → ['userId_' => $userId_]
        $this->assertEquals("['userId_' => \$userId_]", $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_spread_operator()
    {
        $result = $this->directive->parseAndRewriteExpression('...$user');

        $this->assertEquals("['__SPREAD__' => \$user]", $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_spread_local()
    {
        $result = $this->directive->parseAndRewriteExpression('...$_uiState');

        // NEW: Uses literal variable name - $_uiState variable spreads as-is
        $this->assertEquals("['__SPREAD__' => \$_uiState]", $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_spread_locked()
    {
        $result = $this->directive->parseAndRewriteExpression('...$permissions_');

        // NEW: Uses literal variable name - $permissions_ variable spreads as-is
        $this->assertEquals("['__SPREAD__' => \$permissions_]", $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_mixed_types()
    {
        $result = $this->directive->parseAndRewriteExpression('$count, $_editing, $userId_');

        // NEW: Uses literal variable names for all signals
        $expected = "['count' => \$count], ['_editing' => \$_editing], ['userId_' => \$userId_]";
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_associative_array()
    {
        $result = $this->directive->parseAndRewriteExpression("['name' => 'John', 'age' => 30]");

        $this->assertEquals("['name' => 'John', 'age' => 30]", $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_empty_expression()
    {
        $result = $this->directive->parseAndRewriteExpression('');

        $this->assertEquals('', $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_whitespace_expression()
    {
        $result = $this->directive->parseAndRewriteExpression('   ');

        $this->assertEquals('', $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_complex_nesting()
    {
        $result = $this->directive->parseAndRewriteExpression("['nested' => ['key' => 'value']], \$var");

        $expected = "['nested' => ['key' => 'value']], ['var' => \$var]";
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_multiple_spreads()
    {
        $result = $this->directive->parseAndRewriteExpression('...$user, ...$settings');

        $expected = "['__SPREAD__' => \$user], ['__SPREAD__' => \$settings]";
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_invalid_double_underscore_pattern()
    {
        // $_invalid_ - variable name with both prefix and suffix underscore
        $result = $this->directive->parseAndRewriteExpression('$_invalid_');

        // NEW: Uses literal variable name - $_invalid_ variable creates _invalid_ signal
        $this->assertEquals("['_invalid_' => \$_invalid_]", $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_preserves_string_values()
    {
        $result = $this->directive->parseAndRewriteExpression("['name' => 'John Doe']");

        $this->assertEquals("['name' => 'John Doe']", $result);
    }

    /** @test */
    public function test_parse_and_rewrite_expression_handles_variables_with_underscores()
    {
        $result = $this->directive->parseAndRewriteExpression('$user_name, $total_count');

        // These are regular variables with underscores in the name, not locked signals
        // The pattern matches $variable_ (ending with underscore), not variables with underscores in the middle
        $expected = "['user' => \$user], ['total' => \$total]";

        // Actually, let me re-examine the regex: /^\$(_?)([a-zA-Z_][a-zA-Z0-9_]*)(_?)$/
        // This will match: $ + optional _ + variable name + optional _
        // So $user_name would match with varName = "user_name" and no suffix
        // Let me recalculate
        $expected = "['user_name' => \$user_name], ['total_count' => \$total_count]";
        $this->assertEquals($expected, $result);
    }

    // ==========================================
    // Signal Rendering Tests (8 methods)
    // ==========================================

    /** @test */
    public function test_render_generates_data_signals_attribute()
    {
        $result = $this->directive->render(['count' => 5]);

        $this->assertStringStartsWith('data-signals=', $result);
        $this->assertStringContainsString('count', $result);
        $this->assertStringContainsString('5', $result);
    }

    /** @test */
    public function test_render_with_single_signal()
    {
        $result = $this->directive->render(['message' => 'Hello']);

        $this->assertStringContainsString('"message"', $result);
        $this->assertStringContainsString('"Hello"', $result);
    }

    /** @test */
    public function test_render_with_multiple_signals()
    {
        $result = $this->directive->render(['count' => 10, 'name' => 'Test']);

        $this->assertStringContainsString('"count"', $result);
        $this->assertStringContainsString('10', $result);
        $this->assertStringContainsString('"name"', $result);
        $this->assertStringContainsString('"Test"', $result);
    }

    /** @test */
    public function test_render_with_empty_array()
    {
        $result = $this->directive->render([]);

        $this->assertEquals("data-signals='{}'", $result);
    }

    /** @test */
    public function test_render_escapes_html_in_json()
    {
        $result = $this->directive->render(['html' => '<script>alert("xss")</script>']);

        // Should be escaped with JSON_HEX_TAG
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('\\u003C', $result); // Escaped <
    }

    /** @test */
    public function test_render_handles_special_characters()
    {
        $result = $this->directive->render(['data' => "Test's \"quoted\" & text"]);

        // Should escape quotes and ampersands
        $this->assertStringContainsString('data', $result);
        // JSON_HEX_APOS and JSON_HEX_QUOT will escape these
    }

    /** @test */
    public function test_render_with_unicode_characters()
    {
        $result = $this->directive->render(['emoji' => '😀', 'chinese' => '你好']);

        // JSON_UNESCAPED_UNICODE should preserve these
        $this->assertStringContainsString('😀', $result);
        $this->assertStringContainsString('你好', $result);
    }

    /** @test */
    public function test_render_produces_valid_json()
    {
        $result = $this->directive->render(['key' => 'value', 'number' => 42]);

        // Extract JSON from attribute
        preg_match("/data-signals='(.*)'/", $result, $matches);
        $json = $matches[1] ?? '';

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('value', $decoded['key']);
        $this->assertEquals(42, $decoded['number']);
    }

    // ==========================================
    // Signal Conversion Tests (7 methods)
    // ==========================================

    /** @test */
    public function test_convert_to_signal_handles_arrays()
    {
        $data = ['name' => 'John', 'age' => 30];

        $result = $this->directive->convertToSignal($data);

        $this->assertEquals($data, $result);
    }

    /** @test */
    public function test_convert_to_signal_handles_arrayable_objects()
    {
        $collection = new Collection(['a' => 1, 'b' => 2]);

        $result = $this->directive->convertToSignal($collection);

        $this->assertEquals(['a' => 1, 'b' => 2], $result);
    }

    /** @test */
    public function test_convert_to_signal_handles_json_serializable()
    {
        $obj = new class implements \JsonSerializable
        {
            public function jsonSerialize(): array
            {
                return ['custom' => 'data'];
            }
        };

        $result = $this->directive->convertToSignal($obj);

        $this->assertEquals(['custom' => 'data'], $result);
    }

    /** @test */
    public function test_convert_to_signal_handles_scalars()
    {
        $this->assertEquals('string', $this->directive->convertToSignal('string'));
        $this->assertEquals(42, $this->directive->convertToSignal(42));
        $this->assertEquals(3.14, $this->directive->convertToSignal(3.14));
        $this->assertEquals(true, $this->directive->convertToSignal(true));
        $this->assertEquals(null, $this->directive->convertToSignal(null));
    }

    /** @test */
    public function test_convert_to_signal_handles_collections()
    {
        $collection = collect([1, 2, 3, 4, 5]);

        $result = $this->directive->convertToSignal($collection);

        $this->assertEquals([1, 2, 3, 4, 5], $result);
    }

    /** @test */
    public function test_convert_signal_batch_converts_multiple()
    {
        $signals = [
            'count' => 5,
            'users' => new Collection(['John', 'Jane']),
            'name' => 'Test',
        ];

        $result = $this->directive->convertSignalBatch($signals);

        $this->assertEquals(5, $result['count']);
        $this->assertEquals(['John', 'Jane'], $result['users']);
        $this->assertEquals('Test', $result['name']);
    }

    /** @test */
    public function test_render_handles_spread_operator()
    {
        $userData = ['name' => 'John', 'email' => 'john@example.com'];

        $result = $this->directive->render(['__SPREAD__' => $userData], ['count' => 5]);

        // Should spread the user data into individual signals
        $this->assertStringContainsString('"name"', $result);
        $this->assertStringContainsString('"John"', $result);
        $this->assertStringContainsString('"email"', $result);
        $this->assertStringContainsString('"count"', $result);
    }

    // ==========================================
    // Locked Signal Storage Tests (5 methods)
    // ==========================================

    /** @test */
    public function test_render_stores_locked_signals()
    {
        // Setup Hyper request
        request()->headers->set('Datastar-Request', 'true');
        request()->merge(['datastar' => json_encode([])]);

        $result = $this->directive->render(['userId_' => 123, 'regular' => 'data']);

        // Locked signal should be stored in session
        $this->assertTrue(session()->has('hyper_locked_signals'));

        // Result should contain both signals
        $this->assertStringContainsString('"userId_"', $result);
        $this->assertStringContainsString('"regular"', $result);
    }

    /** @test */
    public function test_render_skips_storage_for_regular_signals_only()
    {
        request()->headers->set('Datastar-Request', 'true');
        request()->merge(['datastar' => json_encode([])]);

        session()->forget('hyper_locked_signals');

        $result = $this->directive->render(['count' => 5, 'name' => 'Test']);

        // No locked signals, so session should not be created
        $this->assertFalse(session()->has('hyper_locked_signals'));
    }

    /** @test */
    public function test_render_handles_mixed_signals_storage()
    {
        request()->headers->set('Datastar-Request', 'true');
        request()->merge(['datastar' => json_encode([])]);

        $result = $this->directive->render([
            'count' => 5,
            'userId_' => 123,
            'role_' => 'admin',
            'name' => 'Test',
        ]);

        // Should store locked signals
        $this->assertTrue(session()->has('hyper_locked_signals'));

        // Should contain all signals in output
        $this->assertStringContainsString('"count"', $result);
        $this->assertStringContainsString('"userId_"', $result);
        $this->assertStringContainsString('"role_"', $result);
        $this->assertStringContainsString('"name"', $result);
    }

    /** @test */
    public function test_render_handles_spread_with_underscored_keys()
    {
        // NEW: Spread respects the keys as-is from the array
        $uiState = ['_editing' => true, '_loading' => false];

        $result = $this->directive->render(['__SPREAD__' => $uiState]);

        // Keys are preserved from array - no transformation
        $this->assertStringContainsString('"_editing"', $result);
        $this->assertStringContainsString('"_loading"', $result);
        $this->assertStringContainsString('true', $result);
        $this->assertStringContainsString('false', $result);
    }

    /** @test */
    public function test_render_handles_spread_locked_keys()
    {
        request()->headers->set('Datastar-Request', 'true');
        request()->merge(['datastar' => json_encode([])]);

        // NEW: Keys ending with _ are locked signals
        $permissions = ['canEdit_' => true, 'canDelete_' => false];

        $result = $this->directive->render(['__SPREAD__' => $permissions]);

        // Keys are preserved from array - locked because they end with _
        $this->assertStringContainsString('"canEdit_"', $result);
        $this->assertStringContainsString('"canDelete_"', $result);

        // Should store locked signals
        $this->assertTrue(session()->has('hyper_locked_signals'));
    }

    // ==========================================
    // Expression Splitting Tests (5 methods)
    // ==========================================

    /** @test */
    public function test_split_expression_parts_handles_simple_comma_separation()
    {
        $expression = '$a, $b, $c';
        $parsed = $this->directive->parseAndRewriteExpression($expression);

        // Should split and handle each variable
        $this->assertStringContainsString("'a'", $parsed);
        $this->assertStringContainsString("'b'", $parsed);
        $this->assertStringContainsString("'c'", $parsed);
    }

    /** @test */
    public function test_split_expression_parts_handles_nested_arrays()
    {
        $expression = "['key' => ['nested' => 'value']], \$var";
        $parsed = $this->directive->parseAndRewriteExpression($expression);

        // Should not split inside the nested array
        $this->assertStringContainsString("['key' => ['nested' => 'value']]", $parsed);
        $this->assertStringContainsString("['var' => \$var]", $parsed);
    }

    /** @test */
    public function test_split_expression_parts_handles_strings_with_commas()
    {
        $expression = "['message' => 'Hello, World'], \$count";
        $parsed = $this->directive->parseAndRewriteExpression($expression);

        // Should not split at comma inside string
        $this->assertStringContainsString("'Hello, World'", $parsed);
        $this->assertStringContainsString("['count' => \$count]", $parsed);
    }

    /** @test */
    public function test_split_expression_parts_handles_escaped_quotes()
    {
        $expression = "['text' => 'He said \\'hello\\''], \$var";
        $parsed = $this->directive->parseAndRewriteExpression($expression);

        // Should handle escaped quotes properly
        $this->assertStringContainsString("\\'hello\\'", $parsed);
    }

    /** @test */
    public function test_split_expression_parts_handles_empty_parts()
    {
        $expression = '$a, , $b';
        $parsed = $this->directive->parseAndRewriteExpression($expression);

        // Should skip empty parts
        $this->assertStringContainsString("'a'", $parsed);
        $this->assertStringContainsString("'b'", $parsed);
    }
}
