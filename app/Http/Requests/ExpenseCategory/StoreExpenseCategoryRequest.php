<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\ExpenseCategory;

use App\Http\Requests\Request;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

class StoreExpenseCategoryRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->can('create', ExpenseCategory::class) || $user->can('create', Expense::class);
    }

    public function rules()
    {

        /** @var User $user */
        $user = auth()->user();

        $rules = [];

        $rules['name'] = 'required|unique:expense_categories,name,null,null,company_id,' . $user->companyId();

        return $this->globalRules($rules);
    }

    public function prepareForValidation()
    {
        $input = $this->all();

        $input = $this->decodePrimaryKeys($input);

        if (array_key_exists('color', $input) && is_null($input['color'])) {
            $input['color'] = '';
        }

        $this->replace($input);
    }
}
