<?php

namespace Dancycodes\Hyper\Html\Concerns\Attributes\Table;

use Closure;

/**
 * Table cell attributes
 *
 * All methods accept closures for dynamic attribute values with dependency injection.
 */
trait HasTableAttributes
{
    /**
     * Set the colspan attribute
     *
     * Specifies the number of columns a table cell should span.
     *
     * @param int|Closure $span Column span value or closure
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#attr-tdth-colspan
     */
    public function colspan(int|Closure $span): static
    {
        return $this->attr('colspan', $span);
    }

    /**
     * Set the rowspan attribute
     *
     * Specifies the number of rows a table cell should span.
     *
     * @param int|Closure $span Row span value or closure
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#attr-tdth-rowspan
     */
    public function rowspan(int|Closure $span): static
    {
        return $this->attr('rowspan', $span);
    }

    /**
     * Set the headers attribute
     *
     * Specifies which header cells (by ID) provide header information for the current cell.
     * Used for accessibility to associate data cells with their headers.
     *
     * @param string|Closure $headerIds Space-separated header IDs or closure
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#attr-tdth-headers
     */
    public function headers(string|Closure $headerIds): static
    {
        return $this->attr('headers', $headerIds);
    }

    /**
     * Set the scope attribute (col, row, colgroup, rowgroup)
     *
     * Specifies whether a header cell provides header information for a column, row,
     * group of columns, or group of rows. Used for accessibility.
     *
     * Valid values:
     * - row: Header for a row
     * - col: Header for a column
     * - rowgroup: Header for a group of rows
     * - colgroup: Header for a group of columns
     *
     * @param string|Closure $scope Scope value or closure
     *
     * @throws \InvalidArgumentException
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#attr-th-scope
     */
    public function scope(string|Closure $scope): static
    {
        $scope = $this->evaluate($scope);

        if (!in_array($scope, ['row', 'col', 'rowgroup', 'colgroup'], true)) {
            throw new \InvalidArgumentException(
                'Invalid value for scope() attribute: ' . htmlspecialchars($scope, ENT_QUOTES, 'UTF-8')
            );
        }

        return $this->attr('scope', $scope);
    }

    /**
     * Set the abbr attribute (th elements)
     *
     * Provides an abbreviated version of the header cell's content. Used for accessibility
     * when screen readers need a shorter description.
     *
     * @param string|Closure $abbreviation Abbreviation text or closure
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#attr-th-abbr
     */
    public function abbr(string|Closure $abbreviation): static
    {
        return $this->attr('abbr', $abbreviation);
    }
}
