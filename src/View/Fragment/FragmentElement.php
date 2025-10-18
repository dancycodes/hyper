<?php

namespace Dancycodes\Hyper\View\Fragment;

/**
 * Fragment Directive Element Base Class
 *
 * Abstract base class for fragment directive location data. Stores multibyte character
 * offsets marking directive start and end positions within Blade template content.
 * Subclasses represent specific directive types (opening vs closing tags).
 *
 * Offsets are zero-indexed character positions computed using multibyte string functions
 * to support Unicode template content. Start offset points to @ character, end offset
 * points to character following directive closing parenthesis or directive name.
 *
 * @see \Dancycodes\Hyper\View\Fragment\OpenFragmentElement
 * @see \Dancycodes\Hyper\View\Fragment\CloseFragmentElement
 * @see \Dancycodes\Hyper\View\Fragment\BladeFragmentParser
 */
abstract class FragmentElement
{
    /** Character offset of directive start position in template content */
    public int $startOffset = 0;

    /** Character offset of directive end position in template content */
    public int $endOffset = 0;
}
