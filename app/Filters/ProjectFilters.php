<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Filters;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * ProjectFilters.
 */
class ProjectFilters extends QueryFilters
{
    /**
     * Filter based on search text.
     *
     * @deprecated
     */
    public function filter(string $filter = ''): Builder
    {
        if (strlen($filter) == 0) {
            return $this->builder;
        }

        return $this->builder->where(function ($query) use ($filter) {
            $query->where('name', 'like', '%' . $filter . '%')
                ->orWhereHas('client', function ($q) use ($filter) {
                    $q->where('name', 'like', '%' . $filter . '%');
                })
                ->orWhere('public_notes', 'like', '%' . $filter . '%')
                ->orWhere('private_notes', 'like', '%' . $filter . '%');
        });
    }

    public function number(string $number = ''): Builder
    {
        if (strlen($number) == 0) {
            return $this->builder;
        }

        return $this->builder->where('number', $number);
    }

    /**
     * Sorts the list based on $sort.
     *
     * @param  string  $sort  formatted as column|asc
     */
    public function sort(string $sort = ''): Builder
    {
        $sort_col = explode('|', $sort);

        if (!is_array($sort_col) || count($sort_col) != 2 || !in_array($sort_col[0], Schema::getColumnListing('projects'))) {
            return $this->builder;
        }

        $dir = ($sort_col[1] == 'asc') ? 'asc' : 'desc';

        if ($sort_col[0] == 'client_id') {
            return $this->builder->orderByRaw('ISNULL(client_id), client_id ' . $dir)
                ->orderBy(Client::select('name')
                    ->whereColumn('clients.id', 'projects.client_id'), $dir);
        }

        if ($sort_col[0] == 'number') {
            return $this->builder->orderByRaw("REGEXP_REPLACE(number,'[^0-9]+','')+0 " . $dir);
        }

        return $this->builder->orderBy($sort_col[0], $dir);

    }

    /**
     * date_range
     *
     * only filters on date
     *
     * @param  string  $date_range  in format column,start_date,end_date
     */
    public function date_range(string $date_range = ''): Builder
    {
        $parts = explode(',', $date_range);

        if (count($parts) != 3 || !in_array($parts[0], Schema::getColumnListing($this->builder->getModel()->getTable()))) {
            return $this->builder;
        }

        try {

            $start_date = Carbon::parse($parts[1]);
            $end_date = Carbon::parse($parts[2]);

            return $this->builder->whereBetween($parts[0], [$start_date, $end_date]);
        } catch (\Exception $e) {
            return $this->builder;
        }

    }

    /**
     * Filters the query by the users company ID.
     */
    public function entityFilter(): Builder
    {
        return $this->builder->company();
    }
}
