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
use App\Models\Company;
use App\Models\Credit;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quote;
use App\Models\RecurringExpense;
use App\Models\RecurringInvoice;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * DocumentFilters.
 */
class DocumentFilters extends QueryFilters
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

        return $this->builder->where('name', 'like', '%' . $filter . '%');

    }

    /**
     * Overriding method as client_id does
     * not exist on this model, just pass
     * back the builder
     *
     * @param  string  $client_id  The client hashed id.
     */
    public function client_id(string $client_id = ''): Builder
    {

        return $this->builder->where(function ($query) use ($client_id) {
            $query->whereHasMorph('documentable', [
                Invoice::class,
                Quote::class,
                Credit::class,
                Expense::class,
                Payment::class,
                Task::class,
                RecurringExpense::class,
                RecurringInvoice::class,
                Project::class,
            ], function ($q2) use ($client_id) {
                $q2->where('client_id', $this->decodePrimaryKey($client_id));
            })->orWhereHasMorph('documentable', [Client::class], function ($q3) use ($client_id) {
                $q3->where('id', $this->decodePrimaryKey($client_id));
            });
        });

    }

    public function type(string $types = '')
    {
        $types = explode(',', $types);

        foreach ($types as $type) {
            match ($type) {
                'private' => $this->builder->where('is_public', 0),
                'public' => $this->builder->where('is_public', 1),
                'pdf' => $this->builder->where('type', 'pdf'),
                'image' => $this->builder->whereIn('type', ['png', 'jpeg', 'jpg', 'gif', 'svg']),
                'other' => $this->builder->whereNotIn('type', ['pdf', 'png', 'jpeg', 'jpg', 'gif', 'svg']),
                default => $this->builder,
            };
        }

        return $this->builder;
    }

    /**
     * Sorts the list based on $sort.
     *
     * @param  string  $sort  formatted as column|asc
     */
    public function sort(string $sort = ''): Builder
    {
        $sort_col = explode('|', $sort);

        if (!is_array($sort_col) || count($sort_col) != 2 || !in_array($sort_col[0], Schema::getColumnListing($this->builder->getModel()->getTable()))) {
            return $this->builder;
        }

        $dir = ($sort_col[1] == 'asc') ? 'asc' : 'desc';

        return $this->builder->orderBy($sort_col[0], $dir);
    }

    public function company_documents($value = 'false')
    {
        if ($value == 'true') {
            return $this->builder->where('documentable_type', Company::class);
        }

        return $this->builder;
    }

    /**
     * Filters the query by the users company ID.
     */
    public function entityFilter(): Builder
    {
        return $this->builder->company();
    }
}
