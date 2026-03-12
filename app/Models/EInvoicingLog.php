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

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\EInvoicingLog
 *
 * @property int $id
 * @property string $tenant_id (sent|received)
 * @property string $direction
 * @property int $legal_entity_id
 * @property string|null $license_key The license key string
 * @property string|null $notes
 * @property int $counter
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property-read License $license
 *
 * @mixin \Eloquent
 */
class EInvoicingLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'direction',
        'legal_entity_id',
        'license_key',
        'notes',
        'counter',
    ];

    protected $casts = [
        'created_at' => 'date',
        'updated_at' => 'date',
        'deleted_at' => 'date',
    ];

    /**
     * license
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_key', 'license_key');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'tenant_id', 'id');
    }
}
