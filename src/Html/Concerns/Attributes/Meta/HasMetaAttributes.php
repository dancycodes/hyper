<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Meta;

use Closure;

/**
 * Meta/head element attributes
 *
 * All methods accept closures for dynamic attribute values with dependency injection.
 *
 * Note: The content() method here is specific to meta elements and does not
 * conflict with ContainerElement::content() since meta elements are VoidElements.
 */
trait HasMetaAttributes
{
    /**
     * Set the charset attribute (meta elements)
     *
     * Specifies the character encoding for the HTML document.
     * Common value: "UTF-8"
     *
     * @param string|Closure $charset Character encoding or closure
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#attr-meta-charset
     */
    public function charset(string|Closure $charset): static
    {
        return $this->attr('charset', $charset);
    }

    /**
     * Set the name attribute (meta elements)
     *
     * Specifies the name of the metadata.
     * Common values: "viewport", "description", "keywords", "author", "csrf-token"
     *
     * @param string|Closure $name Meta name or closure
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#attr-meta-name
     */
    public function name(string|Closure $name): static
    {
        return $this->attr('name', $name);
    }

    /**
     * Set the content attribute (meta elements)
     *
     * Specifies the value associated with the http-equiv or name attribute.
     *
     * @param string|Closure $content Meta content value or closure
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#attr-meta-content
     */
    public function content(string|Closure $content): static
    {
        return $this->attr('content', $content);
    }

    /**
     * Set the http-equiv attribute (meta elements)
     *
     * Provides an HTTP header for the information/value of the content attribute.
     * Common values: "content-type", "refresh", "content-security-policy"
     *
     * @param string|Closure $value HTTP header equivalent or closure
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#attr-meta-http-equiv
     */
    public function httpEquiv(string|Closure $value): static
    {
        return $this->attr('http-equiv', $value);
    }

    /**
     * Set the property attribute (meta elements - OpenGraph)
     *
     * Used for Open Graph meta tags and other structured data.
     * Example: "og:title", "og:description", "og:image"
     *
     * @param string|Closure $property OG property name or closure
     *
     * @see https://ogp.me/
     */
    public function property(string|Closure $property): static
    {
        return $this->attr('property', $property);
    }

    /**
     * Set the media attribute (link/style elements)
     *
     * Specifies what media/device the linked document is optimized for.
     * Example: "screen", "print", "(max-width: 600px)"
     *
     * @param string|Closure $query Media query or closure
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#attr-link-media
     */
    public function media(string|Closure $query): static
    {
        return $this->attr('media', $query);
    }

    /**
     * Set the sizes attribute (link elements - icons)
     *
     * Specifies the sizes of icons for visual media.
     * Example: "16x16", "32x32", "any"
     *
     * @param string|Closure $sizes Icon sizes or closure
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#attr-link-sizes
     */
    public function sizes(string|Closure $sizes): static
    {
        return $this->attr('sizes', $sizes);
    }

    /**
     * Set the as attribute (link elements - preload)
     *
     * Specifies the type of content being loaded when using rel="preload".
     * Values: "script", "style", "image", "font", "fetch", "document", etc.
     *
     * @param string|Closure $type Resource type or closure
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#attr-link-as
     */
    public function as(string|Closure $type): static
    {
        return $this->attr('as', $type);
    }

    /**
     * Set the crossorigin attribute
     *
     * Configures CORS requests for the element.
     * - true or "anonymous": CORS request without credentials
     * - "use-credentials": CORS request with credentials
     * - false: No CORS
     *
     * @param string|bool|Closure $value CORS value or closure
     *
     * @throws \InvalidArgumentException
     *
     * @see https://html.spec.whatwg.org/multipage/urls-and-fetching.html#cors-settings-attributes
     */
    public function crossorigin(string|bool|Closure $value = true): static
    {
        $value = $this->evaluate($value);

        if (is_bool($value)) {
            return $this->attr('crossorigin', $value ? 'anonymous' : false);
        }

        if (!in_array($value, ['anonymous', 'use-credentials'], true)) {
            throw new \InvalidArgumentException(
                'Invalid value for crossorigin() attribute: ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
            );
        }

        return $this->attr('crossorigin', $value);
    }

    /**
     * Set the integrity attribute (SRI)
     *
     * Specifies a base64-encoded cryptographic hash for Subresource Integrity verification.
     * Example: "sha384-oqVuAfXRKap7fdgcCY5uykM6+R9GqQ8K/uxy9rx7HNQlGYl1kPzQho1wx4JwY8wC"
     *
     * @param string|Closure $hash SRI hash or closure
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#attr-link-integrity
     */
    public function integrity(string|Closure $hash): static
    {
        return $this->attr('integrity', $hash);
    }
}
