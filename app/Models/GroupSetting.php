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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\GroupSetting
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $user_id
 * @property string|null $name
 * @property object|null $settings
 * @property int $is_default
 * @property int|null $deleted_at
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property bool $is_deleted
 * @property-read Collection<int, Client> $clients
 * @property-read int|null $clients_count
 * @property-read Company $company
 * @property-read Collection<int, Document> $documents
 * @property-read int|null $documents_count
 * @property-read mixed $hashed_id
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|StaticModel company()
 * @method static \Illuminate\Database\Eloquent\Builder|StaticModel exclude($columns)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting whereIsDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|GroupSetting withoutTrashed()
 *
 * @property-read Collection<int, Client> $clients
 *
 * @mixin \Eloquent
 */
class GroupSetting extends StaticModel
{
    use Filterable;
    use MakesHash;
    use SoftDeletes;

    protected $casts = [
        'settings' => 'object',
        'updated_at' => 'timestamp',
        'created_at' => 'timestamp',
        'deleted_at' => 'timestamp',
    ];

    protected $fillable = [
        'name',
        'settings',
    ];

    protected $appends = [
        'hashed_id',
    ];

    public function getHashedIdAttribute()
    {
        return $this->encodePrimaryKey($this->id);
    }

    protected $touches = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'id', 'group_settings_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  null  $field
     * @return Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (is_numeric($value)) {
            throw new ModelNotFoundException("Record with value {$value} not found");
        }

        return $this
            ->withTrashed()
            ->company()
            ->where('id', $this->decodePrimaryKey($value))->firstOrFail();
    }
}
