<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2025. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\Quickbooks;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;

class ConfigQuickbooksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'invoices' => 'required|in:push,pull,bidirectional,none|bail',
            'quotes' => 'required|in:push,pull,bidirectional,none|bail',
            'payments' => 'required|in:push,pull,bidirectional,none|bail',
            'products' => 'required|in:push,pull,bidirectional,none|bail',
            'vendors' => 'required|in:push,pull,bidirectional,none|bail',
            'clients' => 'required|in:push,pull,bidirectional,none|bail',
            'expenses' => 'required|in:push,pull,bidirectional,none|bail',
            'expense_categories' => 'required|in:push,pull,bidirectional,none|bail',
            'qb_income_account_id' => 'required|string|bail',
        ];
    }

}
