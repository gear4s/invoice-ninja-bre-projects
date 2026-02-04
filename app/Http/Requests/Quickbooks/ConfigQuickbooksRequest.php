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
            'invoice' => 'required|in:push,pull,bidirectional,none|bail',
            'quote' => 'required|in:push,pull,bidirectional,none|bail',
            'payment' => 'required|in:push,pull,bidirectional,none|bail',
            'product' => 'required|in:push,pull,bidirectional,none|bail',
            'vendor' => 'required|in:push,pull,bidirectional,none|bail',
            'client' => 'required|in:push,pull,bidirectional,none|bail',
            'expense' => 'required|in:push,pull,bidirectional,none|bail',
            'expense_category' => 'required|in:push,pull,bidirectional,none|bail',
            'qb_income_account_id' => [
                'required',
                'string',
                'bail',
                function ($attribute, $value, $fail) {
                    /** @var \App\Models\User $user */
                    $user = auth()->user();
                    $company = $user->company();
                    
                    if (!$company || !$company->quickbooks || !$company->quickbooks->settings) {
                        $fail('QuickBooks settings not found.');
                        return;
                    }
                    
                    $income_account_map = $company->quickbooks->settings->income_account_map ?? [];
                    
                    if (empty($income_account_map)) {
                        $fail('No income accounts are available. Please sync income accounts first.');
                        return;
                    }
                    
                    // Check if the value exists in any entry of the income_account_map under the 'id' key
                    $exists = false;
                    foreach ($income_account_map as $account) {
                        if (isset($account['id']) && (string)$account['id'] === (string)$value) {
                            $exists = true;
                            break;
                        }
                    }
                    
                    if (!$exists) {
                        $fail('The selected income account ID does not exist in the available income accounts.');
                    }
                },
            ],
        ];
    }

}
