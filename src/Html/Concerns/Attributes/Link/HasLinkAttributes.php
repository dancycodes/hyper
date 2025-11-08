<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Link;

use Closure;

/**
 * Link/anchor element attributes
 *
 * All methods accept closures for dynamic attribute values with dependency injection.
 */
trait HasLinkAttributes
{
    /**
     * Set the href attribute
     *
     * Specifies the URL of the linked resource (for <a>, <link>, <area> elements).
     *
     * @param string|Closure $url URL or closure returning URL
     *
     * @see https://html.spec.whatwg.org/multipage/links.html#attr-hyperlink-href
     */
    public function href(string|Closure $url): static
    {
        return $this->attr('href', $url);
    }

    /**
     * Set the target attribute
     *
     * Specifies where to display the linked URL. Common values:
     * - _self: Load into the same browsing context (default)
     * - _blank: Load into a new unnamed browsing context
     * - _parent: Load into the parent browsing context
     * - _top: Load into the top-level browsing context
     *
     * @param string|Closure $target Target (_blank, _self, _parent, _top) or closure
     *
     * @see https://html.spec.whatwg.org/multipage/links.html#attr-hyperlink-target
     */
    public function target(string|Closure $target): static
    {
        return $this->attr('target', $target);
    }

    /**
     * Set the rel attribute
     *
     * Specifies the relationship between the current document and the linked resource.
     *
     * Common values:
     * - stylesheet: External stylesheet
     * - icon: Favicon
     * - preload/prefetch: Resource hints
     * - nofollow: Don't follow link for SEO
     * - noopener: Don't give window.opener access
     * - noreferrer: Don't send referrer header
     *
     * @param string|Closure $relationship Relationship value or closure
     *
     * @see https://html.spec.whatwg.org/multipage/links.html#attr-hyperlink-rel
     */
    public function rel(string|Closure $relationship): static
    {
        return $this->attr('rel', $relationship);
    }

    /**
     * Set the download attribute
     *
     * Indicates that the hyperlink is to be used for downloading a resource.
     * If a value is provided, it suggests a filename for the downloaded file.
     *
     * @param string|bool|Closure $filename Filename, true (empty download), or closure
     *
     * @see https://html.spec.whatwg.org/multipage/links.html#attr-hyperlink-download
     */
    public function download(string|bool|Closure $filename = true): static
    {
        $filename = $this->evaluate($filename);

        if (is_bool($filename)) {
            // Boolean true = empty download attribute, false = omit attribute
            return $this->attr('download', $filename ? '' : false);
        }

        return $this->attr('download', $filename);
    }

    /**
     * Set the hreflang attribute
     *
     * Specifies the language of the linked resource. Uses BCP 47 language tags.
     * Example values: "en", "fr", "es-MX", "zh-Hans"
     *
     * @param string|Closure $lang Language code or closure
     *
     * @see https://html.spec.whatwg.org/multipage/links.html#attr-hyperlink-hreflang
     */
    public function hreflang(string|Closure $lang): static
    {
        return $this->attr('hreflang', $lang);
    }

    /**
     * Set the type attribute (MIME type)
     *
     * Specifies the MIME type of the linked resource.
     * Example values: "text/html", "application/pdf", "image/png"
     *
     * Note: This method is available on link elements. Input elements should use
     * the type() method from HasInputAttributes trait for input types.
     *
     * @param string|Closure $mimeType MIME type or closure
     *
     * @see https://html.spec.whatwg.org/multipage/links.html#attr-hyperlink-type
     */
    public function type(string|Closure $mimeType): static
    {
        return $this->attr('type', $mimeType);
    }

    /**
     * Set the ping attribute
     *
     * Specifies a space-separated list of URLs to be notified (via POST request)
     * when the user follows the hyperlink. Used for tracking link clicks.
     *
     * @param string|Closure $urls Space-separated URLs or closure
     *
     * @see https://html.spec.whatwg.org/multipage/links.html#attr-hyperlink-ping
     */
    public function ping(string|Closure $urls): static
    {
        return $this->attr('ping', $urls);
    }

    /**
     * Set the referrerpolicy attribute
     *
     * Specifies which referrer information to send when following the link.
     *
     * Common values:
     * - no-referrer: Never send referrer
     * - origin: Send only the origin
     * - strict-origin: Send origin for HTTPS→HTTPS, nothing for HTTPS→HTTP
     * - no-referrer-when-downgrade: Default behavior
     * - origin-when-cross-origin: Full URL for same-origin, origin for cross-origin
     * - same-origin: Send referrer for same-origin, nothing for cross-origin
     * - strict-origin-when-cross-origin: Similar to strict-origin but sends full URL for same-origin
     * - unsafe-url: Always send full URL (not recommended)
     *
     * @param string|Closure $policy Referrer policy or closure
     *
     * @see https://html.spec.whatwg.org/multipage/links.html#attr-hyperlink-referrerpolicy
     */
    public function referrerpolicy(string|Closure $policy): static
    {
        return $this->attr('referrerpolicy', $policy);
    }
}
