<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Models;

use App\Utils\Traits\MakesHash;
use Illuminate\Database\Eloquent\Collection;

/**
 * App\Models\Proposal
 *
 * @property-read User|null $assigned_user
 * @property-read Collection<int, Document> $documents
 * @property-read int|null $documents_count
 * @property-read mixed $hashed_id
 * @property-read mixed $proposal_id
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel company()
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel exclude($columns)
 * @method static \Illuminate\Database\Eloquent\Builder|Proposal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Proposal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Proposal query()
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel scope()
 *
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Document> $documents
 *
 * @mixin \Eloquent
 */
class Proposal extends BaseModel
{
    use MakesHash;

    protected $guarded = [
        'id',
    ];

    protected $touches = [];

    public function getEntityType()
    {
        return self::class;
    }

    protected $appends = ['proposal_id'];

    public function getRouteKeyName()
    {
        return 'proposal_id';
    }

    public function getProposalIdAttribute()
    {
        return $this->encodePrimaryKey($this->id);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function assigned_user()
    {
        return $this->belongsTo(User::class, 'assigned_user_id', 'id');
    }
}
