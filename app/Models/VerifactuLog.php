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

/**
 * @property int $id
 * @property int $company_id
 * @property int $invoice_id
 * @property string $nif
 * @property Carbon $date
 * @property string $invoice_number
 * @property string $hash
 * @property string $previous_hash
 * @property string $status
 * @property object|null $response
 * @property string $state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Company $company
 * @property-read Invoice $invoice
 */
class VerifactuLog extends Model
{
    public $timestamps = true;

    protected $casts = [
        'date' => 'date',
        'response' => 'object',
    ];

    protected $guarded = ['id'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function deserialize()
    {
        return \App\Services\EDocument\Standards\Verifactu\Models\Invoice::unserialize($this->state);
    }
}
