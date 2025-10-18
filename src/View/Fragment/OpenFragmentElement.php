<?php

namespace Dancycodes\Hyper\View\Fragment;

/**
 * Opening Fragment Directive Element
 *
 * Represents opening fragment directive location and metadata within Blade template.
 * Extends base FragmentElement with fragment name property extracted from directive
 * parameters. Marks beginning of named fragment section in template content.
 *
 * Fragment name is extracted from directive parameter during parser regex matching,
 * stripped of surrounding quotes, and stored for fragment identification. Names must
 * be unique within single template file to prevent ambiguous fragment resolution.
 *
 * @see \Dancycodes\Hyper\View\Fragment\FragmentElement
 * @see \Dancycodes\Hyper\View\Fragment\BladeFragmentParser
 */
class OpenFragmentElement extends FragmentElement
{
    /** Fragment name extracted from directive parameter */
    public string $name = '';
}
