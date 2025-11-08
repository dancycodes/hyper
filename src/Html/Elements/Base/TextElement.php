<?php

namespace Dancycodes\Hyper\Html\Elements\Base;

use Closure;

/**
 * Text Element Class - HTML Elements with Simple Text/HTML Content
 *
 * Extends Element to add support for text and raw HTML content. TextElement is used for
 * HTML elements that contain simple text or HTML but don't need nested child elements
 * (like <p>, <span>, <h1>-<h6>, <strong>, <em>, etc.).
 *
 * Key Distinction:
 * - TextElement: Simple content elements (paragraphs, headings, inline text)
 * - ContainerElement: Complex elements with nested children (divs, sections, lists)
 * - VoidElement: Self-closing elements with no content (br, hr, img, input)
 *
 * Content Methods:
 * - text() - Escaped text content (safe for user input, prevents XSS)
 * - html() - Raw HTML content (dangerous, use only with trusted input)
 *
 * The text() and html() methods are mutually exclusive - calling one clears the other.
 * This prevents mixing escaped and unescaped content which could lead to security issues.
 *
 * Basic Usage:
 * ```php
 * use Dancycodes\Hyper\Html\Html;
 *
 * // Safe text content (escaped)
 * echo Html::p()->text('User input: <script>alert("XSS")</script>');
 * // Output: <p>User input: &lt;script&gt;alert("XSS")&lt;/script&gt;</p>
 *
 * // Raw HTML content (dangerous - use only with trusted input)
 * echo Html::div()->html('<strong>Bold</strong> text');
 * // Output: <div><strong>Bold</strong> text</div>
 *
 * // Dynamic content via closures
 * echo Html::h1()->text(fn() => auth()->user()->name);
 * ```
 *
 * Common Use Cases:
 * ```php
 * // Headings
 * Html::h1()->class('text-2xl font-bold')->text('Page Title');
 *
 * // Paragraphs with user-generated content
 * Html::p()->text($comment->body); // Safe - automatically escaped
 *
 * // Inline elements
 * Html::strong()->text('Important');
 * Html::em()->text('Emphasis');
 * Html::span()->class('badge')->text('New');
 *
 * // Links with text
 * Html::a()->href('/dashboard')->text('Go to Dashboard');
 *
 * // Raw HTML from trusted markdown processor
 * Html::div()->class('prose')->html($markdown->toHtml());
 * ```
 *
 * Security Considerations:
 *
 * - text() method: ALWAYS safe for user input, XSS-protected via htmlspecialchars()
 * - html() method: NEVER safe for user input, bypasses escaping entirely
 *
 * When to use html():
 * - Output from trusted Markdown/BBCode processors
 * - Server-side rendered fragments from your own templates
 * - HTML from your own codebase (not user input)
 *
 * When NOT to use html():
 * - User-generated content (comments, posts, profiles)
 * - Data from external APIs
 * - Database content that users can modify
 * - URL parameters or query strings
 *
 * Performance Notes:
 * - text() and html() have O(1) complexity (just property assignment)
 * - Escaping happens during rendering, not during assignment
 * - Closures are evaluated lazily during render time
 *
 * @see \Dancycodes\Hyper\Html\Elements\Base\Element
 * @see \Dancycodes\Hyper\Html\Elements\Base\ContainerElement
 * @see \Dancycodes\Hyper\Html\Html
 */
abstract class TextElement extends Element
{
    protected ?string $textContent = null;

    /**
     * Set text content (will be escaped for XSS protection)
     *
     * Accepts closures for dynamic text content. Closures are evaluated
     * with dependency injection before setting the content.
     *
     * @param string|Closure $content Text content or closure returning text
     */
    public function text(string|Closure $content): static
    {
        // 1. Evaluate the content if it's a closure
        $content = $this->evaluate($content);

        // 2. Store the text content (will be escaped during rendering)
        $this->textContent = $content;

        // 3. Clear any raw HTML (text and html are mutually exclusive)
        $this->rawHtml = null;

        // 4. Return $this for method chaining
        return $this;
    }

    /**
     * Set raw HTML content (not escaped - use with caution)
     *
     * Accepts closures for dynamic HTML content. Closures are evaluated
     * with dependency injection before setting the content.
     *
     * WARNING: Content is NOT escaped. Only use with trusted input.
     *
     * @param string|Closure $html Raw HTML content or closure returning HTML
     */
    public function html(string|Closure $html): static
    {
        // 1. Evaluate the HTML if it's a closure
        $html = $this->evaluate($html);

        // 2. Store the raw HTML (will NOT be escaped during rendering)
        $this->rawHtml = $html;

        // 3. Clear any text content (text and html are mutually exclusive)
        $this->textContent = null;

        // 4. Return $this for method chaining
        return $this;
    }

    /**
     * Render text element to HTML
     */
    public function toHtml(): string
    {
        $attributes = $this->renderAttributes();

        // Determine content (raw HTML takes precedence over text)
        $content = '';
        if ($this->rawHtml !== null) {
            // Raw HTML - not escaped (dangerous but sometimes necessary)
            $content = $this->rawHtml;
        } elseif ($this->textContent !== null) {
            // Text content - escaped for XSS protection
            $content = htmlspecialchars($this->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return "<{$this->tag}{$attributes}>{$content}</{$this->tag}>";
    }
}
