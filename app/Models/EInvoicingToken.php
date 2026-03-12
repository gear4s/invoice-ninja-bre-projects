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

/**
 * App\Models\EInvoicingToken
 *
 * @property string|null $license_key The license key string
 * @property string|null $token
 * @property string|null $account_key
 * @property License $license_relation
 *
 * @mixin \Eloquent
 */
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EInvoicingToken extends Model
{
    protected $fillable = [
        'license',
        'token',
        'account_key',
    ];

    /**
     * license_relation
     */
    public function license_relation(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license', 'license_key');
    }
}
