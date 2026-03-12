<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\Quickbooks;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SyncQuickbooksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client' => 'required|boolean',
            'product' => 'required|boolean',
            'invoice' => 'required|boolean',
            // 'quotes' => 'sometimes|in:number,always_create',
            // 'payments' => 'sometimes|in:always_create',
            // 'vendors' => 'sometimes|in:email,name,always_create',
        ];
    }
}
