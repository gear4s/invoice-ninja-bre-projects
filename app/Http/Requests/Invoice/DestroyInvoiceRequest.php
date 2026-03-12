<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\Invoice;

use App\Exceptions\DuplicatePaymentException;
use App\Http\Requests\Request;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DestroyInvoiceRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('edit', $this->invoice);
    }

    public function rules()
    {
        return [];
    }

    public function prepareForValidation()
    {

        /** @var User $user */
        $user = auth()->user();

        if (Cache::has($this->ip() . '|' . $this->invoice->id . '|' . $user->company()->company_key)) {
            throw new DuplicatePaymentException('Duplicate request.', 429);
        }

        Cache::put(($this->ip() . '|' . $this->invoice->id . '|' . $user->company()->company_key), true, 1);

    }
}
