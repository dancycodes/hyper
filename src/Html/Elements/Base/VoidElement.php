<?php

namespace Dancycodes\Hyper\Html\Elements\Base;

use Dancycodes\Hyper\Html\Concerns\Actions\HasActionMethods;
use LogicException;

/**
 * Void Element Class - Self-Closing HTML Elements
 *
 * Represents HTML5 void elements (also known as self-closing or empty elements) that cannot
 * have any content or closing tags. These elements are defined by the HTML5 specification
 * and include elements like <br>, <hr>, <img>, <input>, <link>, <meta>, etc.
 *
 * Void Elements (HTML5 Specification):
 * - area, base, br, col, embed, hr, img, input, link, meta, param, source, track, wbr
 *
 * Key Characteristics:
 * - No closing tag (renders as <tag /> in XHTML style)
 * - Cannot contain child elements
 * - Cannot contain text content
 * - Can have attributes (id, class, data-*, etc.)
 *
 * Rendering Format:
 * VoidElement renders using XHTML-style self-closing syntax: <tag attributes />
 * This format is compatible with both HTML5 and XHTML parsers.
 *
 * Basic Usage:
 * ```php
 * use Dancycodes\Hyper\Html\Html;
 *
 * // Line breaks
 * echo Html::br();
 * // Output: <br />
 *
 * // Horizontal rule
 * echo Html::hr()->class('my-4');
 * // Output: <hr class="my-4" />
 *
 * // Images
 * echo Html::img()->src('/logo.png')->alt('Logo');
 * // Output: <img src="/logo.png" alt="Logo" />
 *
 * // Form inputs
 * echo Html::input()
 *     ->type('email')
 *     ->name('email')
 *     ->required()
 *     ->dataBind('email');
 * // Output: <input type="email" name="email" required data-bind="email" />
 * ```
 *
 * Common Use Cases:
 * ```php
 * // Text inputs with Datastar binding
 * Html::input()
 *     ->type('text')
 *     ->name('username')
 *     ->dataBind('username')
 *     ->placeholder('Enter username');
 *
 * // Checkboxes
 * Html::input()
 *     ->type('checkbox')
 *     ->name('agree')
 *     ->value('yes')
 *     ->checked(true);
 *
 * // Hidden inputs
 * Html::input()
 *     ->type('hidden')
 *     ->name('csrf_token')
 *     ->value(csrf_token());
 *
 * // Responsive images
 * Html::img()
 *     ->src('/images/hero.jpg')
 *     ->srcset('/images/hero-2x.jpg 2x')
 *     ->alt('Hero image')
 *     ->class('w-full h-auto');
 *
 * // Meta tags
 * Html::meta()
 *     ->name('description')
 *     ->content('Page description');
 *
 * // Link tags (CSS, icons)
 * Html::link()
 *     ->rel('stylesheet')
 *     ->href('/css/app.css');
 * ```
 *
 * Security Features:
 * - All attributes are HTML-escaped (ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5)
 * - XSS protection for attribute values
 * - Throws LogicException if child() or text() methods are called
 *
 * Error Prevention:
 * Attempting to add content to void elements throws LogicException:
 * ```php
 * Html::br()->text('content'); // LogicException: Void element cannot have text content
 * Html::img()->child($element); // LogicException: Void element cannot have children
 * ```
 *
 * Special Form Input Types:
 * ```php
 * // Text-like inputs
 * Html::input()->type('text');
 * Html::input()->type('email');
 * Html::input()->type('password');
 * Html::input()->type('url');
 * Html::input()->type('tel');
 * Html::input()->type('search');
 * Html::input()->type('number');
 * Html::input()->type('date');
 * Html::input()->type('time');
 *
 * // Choice inputs
 * Html::input()->type('checkbox');
 * Html::input()->type('radio');
 *
 * // Special inputs
 * Html::input()->type('file');
 * Html::input()->type('hidden');
 * Html::input()->type('range');
 * Html::input()->type('color');
 * ```
 *
 * Performance Notes:
 * - Void elements have minimal rendering overhead (no child processing)
 * - Attribute rendering is optimized with early returns
 * - Static $isVoid property prevents unnecessary checks
 *
 * HTML5 Specification Compliance:
 * - Follows WHATWG HTML5 void element specification
 * - Self-closing syntax is valid in both HTML5 and XHTML
 * - No end tag is required or allowed per spec
 *
 * @see \Dancycodes\Hyper\Html\Elements\Base\Element
 * @see \Dancycodes\Hyper\Html\Html
 * @see https://html.spec.whatwg.org/multipage/syntax.html#void-elements
 */
abstract class VoidElement extends Element
{
    use HasActionMethods;

    protected bool $isVoid = true;

    /**
     * Render void element to HTML (no closing tag)
     */
    public function toHtml(): string
    {
        // Apply HTML5 validation attributes if enabled
        if (method_exists($this, 'applyHtml5ValidationAttributes')) {
            $this->applyHtml5ValidationAttributes();
        }

        // Apply live validation if enabled
        if (method_exists($this, 'applyLiveValidation')) {
            $this->applyLiveValidation();
        }

        $attributes = $this->renderAttributes();

        // Generate error div if needed
        $errorDiv = '';
        if (method_exists($this, 'generateErrorDiv')) {
            $errorDivElement = $this->generateErrorDiv();
            if ($errorDivElement) {
                $errorDiv = $errorDivElement->render();
            }
        }

        // Void elements are self-closing (no content, no closing tag)
        // Format: <tag attributes /> or <tag attributes> (both valid in HTML5)
        return "<{$this->tag}{$attributes} />".$errorDiv;
    }

    /**
     * Prevent children on void elements
     *
     * @throws LogicException
     */
    public function child(mixed $element): static
    {
        throw new LogicException(
            "Void element <{$this->tag}> cannot have children"
        );
    }

    /**
     * Prevent text content on void elements
     *
     * @throws LogicException
     */
    public function text(string $content): static
    {
        throw new LogicException(
            "Void element <{$this->tag}> cannot have text content"
        );
    }
}
