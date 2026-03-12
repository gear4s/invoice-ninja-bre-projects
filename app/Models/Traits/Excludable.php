<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Models\Traits;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * @method static Builder scopeExclude(array $columns)
 * @method static Builder exclude(array $columns)
 *
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 *
 * @extends Builder<TModelClass>
 *
 * @mixin Builder
 */
trait Excludable
{
    /**
     * Get the array of columns
     *
     * @return mixed
     */
    private function getTableColumns()
    {
        /** @var Schema|BaseModel $this */
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }

    /**
     * Exclude an array of elements from the result.
     *
     * @method static \Illuminate\Database\Eloquent\Builder<static> exclude($columns)
     * @method static \Illuminate\Database\Eloquent\Builder<static> exclude($columns)
     *
     * @param  Builder  $query
     * @param  array  $columns
     */
    public function scopeExclude($query, $columns): Builder
    {
        /** @var Builder|static $query */
        return $query->select(array_diff($this->getTableColumns(), (array) $columns));
    }
}
