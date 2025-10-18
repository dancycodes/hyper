<?php

namespace Dancycodes\Hyper\View\Fragment;

/**
 * Closing Fragment Directive Element
 *
 * Represents closing fragment directive location within Blade template. Extends base
 * FragmentElement without additional properties as closing directives contain no
 * parameters or metadata. Marks end of fragment section in template content.
 *
 * Paired with corresponding OpenFragmentElement based on nesting depth during fragment
 * extraction. Parser tracks nesting level to match closing directive with correct opening
 * directive when fragments are nested within other fragments.
 *
 * @see \Dancycodes\Hyper\View\Fragment\FragmentElement
 * @see \Dancycodes\Hyper\View\Fragment\BladeFragmentParser
 */
class CloseFragmentElement extends FragmentElement {}
