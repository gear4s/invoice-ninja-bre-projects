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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\PaymentHash
 *
 * @property int $id
 * @property string $hash 32 char length AlphaNum
 * @property float $fee_total
 * @property int|null $fee_invoice_id
 * @property \stdClass|array $data
 * @property int|null $payment_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Invoice|null $fee_invoice
 * @property-read Payment|null $payment
 *
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentHash newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentHash query()
 *
 * @mixin \Eloquent
 */
class PaymentHash extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'object',
    ];

    /**
     * @class \App\Models\PaymentHash $this
     *
     * @property PaymentHash $data
     * @property \App\Modes\PaymentHash $hash 32 char length AlphaNum
     *
     * @class \stdClass $data
     *
     * @property string $raw_value
     */

    /**
     * @return mixed
     */
    public function invoices()
    {
        return $this->data->invoices;
    }

    /**
     * @return float|null
     */
    public function amount_with_fee()
    {
        return $this->data->amount_with_fee;
    }

    /**
     * @return float
     */
    public function credits_total()
    {
        return $this->data->credits ?? 0;
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class)->withTrashed();
    }

    public function fee_invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'fee_invoice_id', 'id')->withTrashed();
    }

    public function withData(string $property, $value): self
    {
        $this->data = array_merge((array) $this->data, [$property => $value]); // @phpstan-ignore-line
        $this->save(); // @phpstan-ignore-line

        return $this; // @phpstan-ignore-line
    }
}
