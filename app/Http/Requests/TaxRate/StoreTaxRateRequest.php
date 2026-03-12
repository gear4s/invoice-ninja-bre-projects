<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\TaxRate;

use App\Http\Requests\Request;
use App\Models\User;

class StoreTaxRateRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->isAdmin();
    }

    public function rules()
    {

        /** @var User $user */
        $user = auth()->user();

        return [
            'name' => 'required|unique:tax_rates,name,null,null,company_id,' . $user->companyId() . ',deleted_at,NULL',
            'rate' => 'required|numeric',
        ];
    }
}
