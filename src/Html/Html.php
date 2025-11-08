<?php

namespace Dancycodes\Hyper\Html;

use Dancycodes\Hyper\Html\Elements\Document\Base;
use Dancycodes\Hyper\Html\Elements\Document\Body;
use Dancycodes\Hyper\Html\Elements\Document\Head;
use Dancycodes\Hyper\Html\Elements\Document\Html as HtmlElement;
use Dancycodes\Hyper\Html\Elements\Document\Title;
use Dancycodes\Hyper\Html\Elements\Embedded\Noscript;
use Dancycodes\Hyper\Html\Elements\Embedded\Portal;
use Dancycodes\Hyper\Html\Elements\Embedded\Slot;
use Dancycodes\Hyper\Html\Elements\Embedded\Wbr;
use Dancycodes\Hyper\Html\Elements\Form\Button;
use Dancycodes\Hyper\Html\Elements\Form\Datalist;
use Dancycodes\Hyper\Html\Elements\Form\Fieldset;
use Dancycodes\Hyper\Html\Elements\Form\Form;
use Dancycodes\Hyper\Html\Elements\Form\Input;
use Dancycodes\Hyper\Html\Elements\Form\Label;
use Dancycodes\Hyper\Html\Elements\Form\Legend;
use Dancycodes\Hyper\Html\Elements\Form\Meter;
use Dancycodes\Hyper\Html\Elements\Form\Optgroup;
use Dancycodes\Hyper\Html\Elements\Form\Option;
use Dancycodes\Hyper\Html\Elements\Form\Output;
use Dancycodes\Hyper\Html\Elements\Form\Progress;
use Dancycodes\Hyper\Html\Elements\Form\Select;
use Dancycodes\Hyper\Html\Elements\Form\Textarea;
use Dancycodes\Hyper\Html\Elements\Interactive\Details;
use Dancycodes\Hyper\Html\Elements\Interactive\Dialog;
use Dancycodes\Hyper\Html\Elements\Interactive\Menu;
use Dancycodes\Hyper\Html\Elements\Interactive\Summary;
use Dancycodes\Hyper\Html\Elements\Interactive\Template;
use Dancycodes\Hyper\Html\Elements\Link\A;
use Dancycodes\Hyper\Html\Elements\List\Dd;
use Dancycodes\Hyper\Html\Elements\List\Dl;
use Dancycodes\Hyper\Html\Elements\List\Dt;
use Dancycodes\Hyper\Html\Elements\List\Li;
use Dancycodes\Hyper\Html\Elements\List\Ol;
use Dancycodes\Hyper\Html\Elements\List\Ul;
use Dancycodes\Hyper\Html\Elements\Media\Area;
use Dancycodes\Hyper\Html\Elements\Media\Audio;
use Dancycodes\Hyper\Html\Elements\Media\Canvas;
use Dancycodes\Hyper\Html\Elements\Media\Embed;
use Dancycodes\Hyper\Html\Elements\Media\Figcaption;
use Dancycodes\Hyper\Html\Elements\Media\Figure;
use Dancycodes\Hyper\Html\Elements\Media\Iframe;
use Dancycodes\Hyper\Html\Elements\Media\Img;
use Dancycodes\Hyper\Html\Elements\Media\Map;
use Dancycodes\Hyper\Html\Elements\Media\ObjectElement;
use Dancycodes\Hyper\Html\Elements\Media\Param;
use Dancycodes\Hyper\Html\Elements\Media\Picture;
use Dancycodes\Hyper\Html\Elements\Media\Source;
use Dancycodes\Hyper\Html\Elements\Media\Svg;
use Dancycodes\Hyper\Html\Elements\Media\Track;
use Dancycodes\Hyper\Html\Elements\Media\Video;
use Dancycodes\Hyper\Html\Elements\Meta\Link;
use Dancycodes\Hyper\Html\Elements\Meta\Meta;
use Dancycodes\Hyper\Html\Elements\Meta\Script;
use Dancycodes\Hyper\Html\Elements\Meta\Style;
use Dancycodes\Hyper\Html\Elements\Structure\Article;
use Dancycodes\Hyper\Html\Elements\Structure\Aside;
use Dancycodes\Hyper\Html\Elements\Structure\Div;
use Dancycodes\Hyper\Html\Elements\Structure\Footer;
use Dancycodes\Hyper\Html\Elements\Structure\Header;
use Dancycodes\Hyper\Html\Elements\Structure\Hr;
use Dancycodes\Hyper\Html\Elements\Structure\Main;
use Dancycodes\Hyper\Html\Elements\Structure\Nav;
use Dancycodes\Hyper\Html\Elements\Structure\Search;
use Dancycodes\Hyper\Html\Elements\Structure\Section;
use Dancycodes\Hyper\Html\Elements\Structure\Span;
use Dancycodes\Hyper\Html\Elements\Table\Caption;
use Dancycodes\Hyper\Html\Elements\Table\Col;
use Dancycodes\Hyper\Html\Elements\Table\Colgroup;
use Dancycodes\Hyper\Html\Elements\Table\Table;
use Dancycodes\Hyper\Html\Elements\Table\Tbody;
use Dancycodes\Hyper\Html\Elements\Table\Td;
use Dancycodes\Hyper\Html\Elements\Table\Tfoot;
use Dancycodes\Hyper\Html\Elements\Table\Th;
use Dancycodes\Hyper\Html\Elements\Table\Thead;
use Dancycodes\Hyper\Html\Elements\Table\Tr;
use Dancycodes\Hyper\Html\Elements\Text\Abbr;
use Dancycodes\Hyper\Html\Elements\Text\Address;
use Dancycodes\Hyper\Html\Elements\Text\B;
use Dancycodes\Hyper\Html\Elements\Text\Bdi;
use Dancycodes\Hyper\Html\Elements\Text\Bdo;
use Dancycodes\Hyper\Html\Elements\Text\Blockquote;
use Dancycodes\Hyper\Html\Elements\Text\Br;
use Dancycodes\Hyper\Html\Elements\Text\Cite;
use Dancycodes\Hyper\Html\Elements\Text\Code;
use Dancycodes\Hyper\Html\Elements\Text\Data;
use Dancycodes\Hyper\Html\Elements\Text\Del;
use Dancycodes\Hyper\Html\Elements\Text\Dfn;
use Dancycodes\Hyper\Html\Elements\Text\Em;
use Dancycodes\Hyper\Html\Elements\Text\H1;
use Dancycodes\Hyper\Html\Elements\Text\H2;
use Dancycodes\Hyper\Html\Elements\Text\H3;
use Dancycodes\Hyper\Html\Elements\Text\H4;
use Dancycodes\Hyper\Html\Elements\Text\H5;
use Dancycodes\Hyper\Html\Elements\Text\H6;
use Dancycodes\Hyper\Html\Elements\Text\I;
use Dancycodes\Hyper\Html\Elements\Text\Ins;
use Dancycodes\Hyper\Html\Elements\Text\Kbd;
use Dancycodes\Hyper\Html\Elements\Text\Mark;
use Dancycodes\Hyper\Html\Elements\Text\P;
use Dancycodes\Hyper\Html\Elements\Text\Pre;
use Dancycodes\Hyper\Html\Elements\Text\Q;
use Dancycodes\Hyper\Html\Elements\Text\Rp;
use Dancycodes\Hyper\Html\Elements\Text\Rt;
use Dancycodes\Hyper\Html\Elements\Text\Ruby;
use Dancycodes\Hyper\Html\Elements\Text\S;
use Dancycodes\Hyper\Html\Elements\Text\Samp;
use Dancycodes\Hyper\Html\Elements\Text\Small;
use Dancycodes\Hyper\Html\Elements\Text\Strong;
use Dancycodes\Hyper\Html\Elements\Text\Sub;
use Dancycodes\Hyper\Html\Elements\Text\Sup;
use Dancycodes\Hyper\Html\Elements\Text\Time;
use Dancycodes\Hyper\Html\Elements\Text\U;
use Dancycodes\Hyper\Html\Elements\Text\VarElement;
use Dancycodes\Hyper\Html\Elements\Visual\Icon;
use Dancycodes\Hyper\Html\Contracts\IconProviderContract;
use Dancycodes\Hyper\Html\Services\IconManager;

/**
 * HTML Element Facade - Programmatic HTML Builder with XSS Protection
 *
 * Provides a fluent, type-safe API for constructing HTML elements programmatically with full
 * IDE autocomplete support. All content is automatically escaped to prevent XSS attacks using
 * secure htmlspecialchars() with ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5 flags.
 *
 * Key Features:
 * - Type-safe factory methods for all HTML5 elements (90+ element types)
 * - Automatic XSS protection via context-aware escaping
 * - Fluent method chaining for readable code
 * - Full Datastar integration via data-* attributes
 * - Support for locked/local signals with session encryption
 * - Circular reference detection (prevents infinite loops)
 * - Recursion depth limits (prevents stack overflow)
 * - Laravel dependency injection in closures
 *
 * Basic Usage:
 * ```php
 * use Dancycodes\Hyper\Html\Html;
 *
 * // Simple element
 * echo Html::div()->class('container')->text('Hello World');
 * // Output: <div class="container">Hello World</div>
 *
 * // Nested structure
 * echo Html::div()->content(
 *     Html::h1()->text('Title'),
 *     Html::p()->text('Paragraph')
 * );
 *
 * // With Datastar signals
 * Html::div()
 *     ->dataSignals(['count' => 0, 'userId_' => auth()->id()])
 *     ->content(
 *         Html::button()
 *             ->dataOnClick('@postx("/increment")')
 *             ->text('Increment')
 *     );
 * ```
 *
 * Security:
 * - Text content is escaped: `text('<script>')` → `&lt;script&gt;`
 * - Attributes are escaped: `id('<script>')` → `id="&lt;script&gt;"`
 * - Raw HTML available via `html()` method - use only with trusted input
 * - Locked signals (suffix `_`) are encrypted in session to prevent tampering
 *
 * Element Hierarchy:
 * - Element (base class) - attributes, classes, rendering
 * - TextElement extends Element - adds text() and html() methods
 * - ContainerElement extends TextElement - adds content() for child elements
 * - VoidElement extends Element - self-closing tags (br, hr, img, input, etc.)
 *
 * @see \Dancycodes\Hyper\Html\Elements\Base\Element
 * @see \Dancycodes\Hyper\Html\Elements\Base\TextElement
 * @see \Dancycodes\Hyper\Html\Elements\Base\ContainerElement
 * @see \Dancycodes\Hyper\Html\Elements\Base\VoidElement
 * @see \Dancycodes\Hyper\Html\ElementRegistry
 * @see https://html.spec.whatwg.org/multipage/
 */
class Html
{
    // ========================================================================
    // DOCUMENT STRUCTURE
    // ========================================================================

    /**
     * Create a full HTML5 document with DOCTYPE declaration
     *
     * This is a convenience method that creates an <html> element
     * with the HTML5 DOCTYPE already included.
     *
     * Example:
     * Html::document()->lang('en')->content(...)
     * // Outputs: <!DOCTYPE html>\n<html lang="en">...</html>
     */
    public static function document(): HtmlElement
    {
        return HtmlElement::make()->withDoctype();
    }

    /**
     * Create an <html> element (without DOCTYPE)
     *
     * Use Html::document() to include the DOCTYPE declaration.
     */
    public static function html(): HtmlElement
    {
        return HtmlElement::make();
    }

    /**
     * Create a <head> element
     */
    public static function head(): Head
    {
        return Head::make();
    }

    /**
     * Create a <body> element
     */
    public static function body(): Body
    {
        return Body::make();
    }

    /**
     * Create a <title> element
     */
    public static function title(?string $text = null): Title
    {
        return Title::make($text);
    }

    /**
     * Create a <base> element
     */
    public static function base(): Base
    {
        return Base::make();
    }

    // ========================================================================
    // META ELEMENTS
    // ========================================================================

    /**
     * Create a <meta> element
     */
    public static function meta(): Meta
    {
        return Meta::make();
    }

    /**
     * Create a <link> element
     */
    public static function link(): Link
    {
        return Link::make();
    }

    /**
     * Create a <style> element
     */
    public static function style(?string $css = null): Style
    {
        return Style::make($css);
    }

    /**
     * Create a <script> element
     */
    public static function script(?string $content = null): Script
    {
        return Script::make($content);
    }

    // ========================================================================
    // STRUCTURE ELEMENTS
    // ========================================================================

    /**
     * Create a <div> element
     */
    public static function div(?string $text = null): Div
    {
        return Div::make($text);
    }

    /**
     * Create a <span> element
     */
    public static function span(?string $text = null): Span
    {
        return Span::make($text);
    }

    /**
     * Create a <header> element
     */
    public static function header(): Header
    {
        return Header::make();
    }

    /**
     * Create a <footer> element
     */
    public static function footer(): Footer
    {
        return Footer::make();
    }

    /**
     * Create a <nav> element
     */
    public static function nav(): Nav
    {
        return Nav::make();
    }

    /**
     * Create a <main> element
     */
    public static function main(): Main
    {
        return Main::make();
    }

    /**
     * Create a <section> element
     */
    public static function section(?string $text = null): Section
    {
        return Section::make($text);
    }

    /**
     * Create an <article> element
     */
    public static function article(): Article
    {
        return Article::make();
    }

    /**
     * Create an <aside> element
     */
    public static function aside(): Aside
    {
        return Aside::make();
    }

    /**
     * Create an <hr> element
     */
    public static function hr(): Hr
    {
        return Hr::make();
    }

    /**
     * Create a <search> element (semantic search container)
     */
    public static function search(): Search
    {
        return Search::make();
    }

    // ========================================================================
    // TEXT CONTENT
    // ========================================================================

    /**
     * Create an <h1> element
     */
    public static function h1(?string $text = null): H1
    {
        return H1::make($text);
    }

    /**
     * Create an <h2> element
     */
    public static function h2(?string $text = null): H2
    {
        return H2::make($text);
    }

    /**
     * Create an <h3> element
     */
    public static function h3(?string $text = null): H3
    {
        return H3::make($text);
    }

    /**
     * Create an <h4> element
     */
    public static function h4(?string $text = null): H4
    {
        return H4::make($text);
    }

    /**
     * Create an <h5> element
     */
    public static function h5(?string $text = null): H5
    {
        return H5::make($text);
    }

    /**
     * Create an <h6> element
     */
    public static function h6(?string $text = null): H6
    {
        return H6::make($text);
    }

    /**
     * Create a <p> element
     */
    public static function p(?string $text = null): P
    {
        return P::make($text);
    }

    /**
     * Create a <strong> element
     */
    public static function strong(?string $text = null): Strong
    {
        return Strong::make($text);
    }

    /**
     * Create an <em> element
     */
    public static function em(?string $text = null): Em
    {
        return Em::make($text);
    }

    /**
     * Create a <code> element
     */
    public static function code(?string $text = null): Code
    {
        return Code::make($text);
    }

    /**
     * Create a <pre> element
     */
    public static function pre(?string $text = null): Pre
    {
        return Pre::make($text);
    }

    /**
     * Create a <br> element
     */
    public static function br(): Br
    {
        return Br::make();
    }

    /**
     * Create a <blockquote> element
     */
    public static function blockquote(?string $text = null): Blockquote
    {
        return Blockquote::make($text);
    }

    /**
     * Create a <small> element
     */
    public static function small(?string $text = null): Small
    {
        return Small::make($text);
    }

    /**
     * Create a <mark> element
     */
    public static function mark(?string $text = null): Mark
    {
        return Mark::make($text);
    }

    /**
     * Create a <del> element (deleted text)
     */
    public static function del(?string $text = null): Del
    {
        return Del::make($text);
    }

    /**
     * Create an <ins> element (inserted text)
     */
    public static function ins(?string $text = null): Ins
    {
        return Ins::make($text);
    }

    /**
     * Create a <sub> element (subscript)
     */
    public static function sub(?string $text = null): Sub
    {
        return Sub::make($text);
    }

    /**
     * Create a <sup> element (superscript)
     */
    public static function sup(?string $text = null): Sup
    {
        return Sup::make($text);
    }

    /**
     * Create an <abbr> element (abbreviation)
     */
    public static function abbr(?string $text = null): Abbr
    {
        return Abbr::make($text);
    }

    /**
     * Create a <cite> element
     */
    public static function cite(?string $text = null): Cite
    {
        return Cite::make($text);
    }

    /**
     * Create a <dfn> element (definition)
     */
    public static function dfn(?string $text = null): Dfn
    {
        return Dfn::make($text);
    }

    /**
     * Create a <kbd> element (keyboard input)
     */
    public static function kbd(?string $text = null): Kbd
    {
        return Kbd::make($text);
    }

    /**
     * Create a <samp> element (sample output)
     */
    public static function samp(?string $text = null): Samp
    {
        return Samp::make($text);
    }

    /**
     * Create a <var> element (variable)
     * Note: Returns VarElement class to avoid PHP reserved keyword
     */
    public static function varElement(?string $text = null): VarElement
    {
        return VarElement::make($text);
    }

    /**
     * Create a <time> element
     */
    public static function time(?string $text = null): Time
    {
        return Time::make($text);
    }

    /**
     * Create an <address> element
     */
    public static function address(?string $text = null): Address
    {
        return Address::make($text);
    }

    /**
     * Create a <b> element (bold, stylistic emphasis)
     */
    public static function b(?string $text = null): B
    {
        return B::make($text);
    }

    /**
     * Create an <i> element (italic, alternate voice)
     */
    public static function i(?string $text = null): I
    {
        return I::make($text);
    }

    /**
     * Create a <q> element (inline quotation)
     */
    public static function q(?string $text = null): Q
    {
        return Q::make($text);
    }

    /**
     * Create an <s> element (strikethrough, irrelevant content)
     */
    public static function s(?string $text = null): S
    {
        return S::make($text);
    }

    /**
     * Create a <u> element (unarticulated annotation, underline)
     */
    public static function u(?string $text = null): U
    {
        return U::make($text);
    }

    /**
     * Create a <data> element (machine-readable data)
     */
    public static function data(?string $text = null): Data
    {
        return Data::make($text);
    }

    /**
     * Create a <bdi> element (bidirectional isolate)
     */
    public static function bdi(?string $text = null): Bdi
    {
        return Bdi::make($text);
    }

    /**
     * Create a <bdo> element (bidirectional override)
     */
    public static function bdo(?string $text = null): Bdo
    {
        return Bdo::make($text);
    }

    /**
     * Create a <ruby> element (ruby annotation container)
     */
    public static function ruby(): Ruby
    {
        return Ruby::make();
    }

    /**
     * Create an <rt> element (ruby text)
     */
    public static function rt(?string $text = null): Rt
    {
        return Rt::make($text);
    }

    /**
     * Create an <rp> element (ruby parentheses fallback)
     */
    public static function rp(?string $text = null): Rp
    {
        return Rp::make($text);
    }

    // ========================================================================
    // LISTS
    // ========================================================================

    /**
     * Create a <ul> element
     */
    public static function ul(): Ul
    {
        return Ul::make();
    }

    /**
     * Create an <ol> element
     */
    public static function ol(): Ol
    {
        return Ol::make();
    }

    /**
     * Create a <li> element
     */
    public static function li(?string $text = null): Li
    {
        return Li::make($text);
    }

    /**
     * Create a <dl> element (description list)
     */
    public static function dl(): Dl
    {
        return Dl::make();
    }

    /**
     * Create a <dt> element (description term)
     */
    public static function dt(?string $text = null): Dt
    {
        return Dt::make($text);
    }

    /**
     * Create a <dd> element (description definition)
     */
    public static function dd(?string $text = null): Dd
    {
        return Dd::make($text);
    }

    // ========================================================================
    // LINKS
    // ========================================================================

    /**
     * Create an <a> element
     */
    public static function a(?string $text = null): A
    {
        return A::make($text);
    }

    // ========================================================================
    // FORMS
    // ========================================================================

    /**
     * Create a <form> element
     */
    public static function form(): Form
    {
        return Form::make();
    }

    /**
     * Create an <input> element
     */
    public static function input(): Input
    {
        return Input::make();
    }

    /**
     * Create a <button> element
     */
    public static function button(?string $text = null): Button
    {
        return Button::make($text);
    }

    /**
     * Create a <textarea> element
     */
    public static function textarea(?string $content = null): Textarea
    {
        return Textarea::make($content);
    }

    /**
     * Create a <label> element
     */
    public static function label(?string $text = null): Label
    {
        return Label::make($text);
    }

    /**
     * Create a <select> element
     */
    public static function select(): Select
    {
        return Select::make();
    }

    /**
     * Create an <option> element
     */
    public static function option(?string $text = null): Option
    {
        return Option::make($text);
    }

    /**
     * Create an <optgroup> element
     */
    public static function optgroup(): Optgroup
    {
        return Optgroup::make();
    }

    /**
     * Create a <fieldset> element
     */
    public static function fieldset(): Fieldset
    {
        return Fieldset::make();
    }

    /**
     * Create a <legend> element
     */
    public static function legend(?string $text = null): Legend
    {
        return Legend::make($text);
    }

    /**
     * Create a <datalist> element
     */
    public static function datalist(): Datalist
    {
        return Datalist::make();
    }

    /**
     * Create an <output> element
     */
    public static function output(?string $text = null): Output
    {
        return Output::make($text);
    }

    /**
     * Create a <progress> element
     */
    public static function progress(): Progress
    {
        return Progress::make();
    }

    /**
     * Create a <meter> element
     */
    public static function meter(): Meter
    {
        return Meter::make();
    }

    // ========================================================================
    // MEDIA
    // ========================================================================

    /**
     * Create an <img> element
     */
    public static function img(?string $src = null, ?string $alt = null): Img
    {
        return Img::make($src, $alt);
    }

    /**
     * Create an <audio> element
     */
    public static function audio(): Audio
    {
        return Audio::make();
    }

    /**
     * Create a <video> element
     */
    public static function video(): Video
    {
        return Video::make();
    }

    /**
     * Create a <source> element
     */
    public static function source(): Source
    {
        return Source::make();
    }

    /**
     * Create a <track> element
     */
    public static function track(): Track
    {
        return Track::make();
    }

    /**
     * Create a <picture> element
     */
    public static function picture(): Picture
    {
        return Picture::make();
    }

    /**
     * Create a <figure> element
     */
    public static function figure(): Figure
    {
        return Figure::make();
    }

    /**
     * Create a <figcaption> element
     */
    public static function figcaption(?string $text = null): Figcaption
    {
        return Figcaption::make($text);
    }

    /**
     * Create an <iframe> element
     */
    public static function iframe(): Iframe
    {
        return Iframe::make();
    }

    /**
     * Create an <embed> element
     */
    public static function embed(): Embed
    {
        return Embed::make();
    }

    /**
     * Create an <object> element
     * Note: Returns ObjectElement class to avoid PHP reserved keyword
     */
    public static function object(): ObjectElement
    {
        return ObjectElement::make();
    }

    /**
     * Create a <param> element
     */
    public static function param(): Param
    {
        return Param::make();
    }

    /**
     * Create a <canvas> element
     */
    public static function canvas(): Canvas
    {
        return Canvas::make();
    }

    /**
     * Create an <svg> element
     */
    public static function svg(): Svg
    {
        return Svg::make();
    }

    /**
     * Create a <map> element
     */
    public static function map(): Map
    {
        return Map::make();
    }

    /**
     * Create an <area> element
     */
    public static function area(): Area
    {
        return Area::make();
    }

    // ========================================================================
    // TABLES
    // ========================================================================

    /**
     * Create a <table> element
     */
    public static function table(): Table
    {
        return Table::make();
    }

    /**
     * Create a <caption> element
     */
    public static function caption(?string $text = null): Caption
    {
        return Caption::make($text);
    }

    /**
     * Create a <thead> element
     */
    public static function thead(): Thead
    {
        return Thead::make();
    }

    /**
     * Create a <tbody> element
     */
    public static function tbody(): Tbody
    {
        return Tbody::make();
    }

    /**
     * Create a <tfoot> element
     */
    public static function tfoot(): Tfoot
    {
        return Tfoot::make();
    }

    /**
     * Create a <tr> element
     */
    public static function tr(): Tr
    {
        return Tr::make();
    }

    /**
     * Create a <th> element
     */
    public static function th(?string $text = null): Th
    {
        return Th::make($text);
    }

    /**
     * Create a <td> element
     */
    public static function td(?string $text = null): Td
    {
        return Td::make($text);
    }

    /**
     * Create a <col> element
     */
    public static function col(): Col
    {
        return Col::make();
    }

    /**
     * Create a <colgroup> element
     */
    public static function colgroup(): Colgroup
    {
        return Colgroup::make();
    }

    // ========================================================================
    // INTERACTIVE
    // ========================================================================

    /**
     * Create a <template> element (for Datastar iteration)
     */
    public static function template(): Template
    {
        return Template::make();
    }

    /**
     * Create a <details> element
     */
    public static function details(): Details
    {
        return Details::make();
    }

    /**
     * Create a <summary> element
     */
    public static function summary(?string $text = null): Summary
    {
        return Summary::make($text);
    }

    /**
     * Create a <dialog> element
     */
    public static function dialog(): Dialog
    {
        return Dialog::make();
    }

    /**
     * Create a <menu> element
     */
    public static function menu(): Menu
    {
        return Menu::make();
    }

    // ========================================================================
    // EMBEDDED
    // ========================================================================

    /**
     * Create a <noscript> element
     */
    public static function noscript(?string $text = null): Noscript
    {
        return Noscript::make($text);
    }

    /**
     * Create a <portal> element (experimental)
     */
    public static function portal(): Portal
    {
        return Portal::make();
    }

    /**
     * Create a <slot> element (web components)
     */
    public static function slot(): Slot
    {
        return Slot::make();
    }

    /**
     * Create a <wbr> element (word break opportunity)
     */
    public static function wbr(): Wbr
    {
        return Wbr::make();
    }

    // ========================================================================
    // CUSTOM/PLUGIN ELEMENTS
    // ========================================================================

    /**
     * Create a custom element registered via ElementRegistry
     *
     * @param mixed ...$args
     *
     * @throws \InvalidArgumentException
     *
     * @return \Dancycodes\Hyper\Html\Elements\Base\Element
     */
    public static function __callStatic(string $name, array $args)
    {
        return ElementRegistry::make($name, $args);
    }

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Create raw HTML (unescaped) - use with caution
     */
    public static function raw(string $html): string
    {
        return $html;
    }

    // ========================================================================
    // ICON MANAGEMENT
    // ========================================================================

    /**
     * Create an icon element
     *
     * Convenience method for creating standalone icons.
     *
     * Examples:
     * ```php
     * Html::icon('heroicon-s-home');
     * Html::icon('heroicon-o-user')->lg();
     * Html::icon('home', 'heroicons')->solid();
     * ```
     *
     * @param string $name Icon name
     * @param string|null $provider Provider name (null = use default)
     */
    public static function icon(string $name, ?string $provider = null): Icon
    {
        return Icon::make($name, $provider);
    }

    /**
     * Register an icon provider
     *
     * FilamentPHP-style simple registration. One-liner to add new icon providers.
     *
     * Examples:
     * ```php
     * // In AppServiceProvider boot() method:
     * Html::iconProvider('heroicons', HeroiconsProvider::class);
     * Html::iconProvider('fontawesome', FontAwesomeProvider::class);
     * Html::iconProvider('custom', new MyIconProvider());
     * ```
     *
     * @param string $name Provider name (e.g., 'heroicons', 'fontawesome')
     * @param string|IconProviderContract $provider Provider class name or instance
     */
    public static function iconProvider(string $name, string|IconProviderContract $provider): void
    {
        app(IconManager::class)->register($name, $provider);
    }

    /**
     * Set the default icon provider
     *
     * The default provider is used when no provider is explicitly specified.
     *
     * Example:
     * ```php
     * Html::setDefaultIconProvider('heroicons');
     *
     * // Now icons without provider use Heroicons
     * Html::icon('home');  // Uses Heroicons
     * ```
     *
     * @param string $name Provider name
     */
    public static function setDefaultIconProvider(string $name): void
    {
        app(IconManager::class)->setDefaultProvider($name);
    }
}
