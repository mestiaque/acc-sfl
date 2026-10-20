<?php

namespace ME\AccSfl\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * "Sort By" (date / no) and "Order" (asc / desc) for the Expense, Balance Receive and IOU
 * lists - used by index, print and export alike. Only reorders rows; totals and balances are
 * computed elsewhere and are unaffected. Values come from the query string, so they survive
 * pagination (withQueryString) and are whitelisted here.
 */
trait SortsListing
{
    protected function applyListSort(Builder $query, Request $request, string $dateColumn, string $numberColumn): Builder
    {
        $direction = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';
        $column = $request->input('sort_by') === 'no' ? $numberColumn : $dateColumn;

        // id breaks ties (same date / same number) in entry order, in the same direction.
        return $query->orderBy($column, $direction)->orderBy('id', $direction);
    }
}
