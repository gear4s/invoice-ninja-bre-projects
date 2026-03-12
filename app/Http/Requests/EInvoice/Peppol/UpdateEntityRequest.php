<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\EInvoice\Peppol;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        /**
         * @var User
         */
        $user = auth()->user();

        if (config('ninja.app_env') == 'local') {
            return true;
        }

        return $user->account->isPaid() && $user->isAdmin()
            && $user->company()->legal_entity_id != null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'acts_as_receiver' => ['required', 'bool'],
            'acts_as_sender' => ['required', 'bool'],
        ];
    }

    public function prepareForValidation()
    {
        $input = $this->all();

        $this->replace($input);
    }
}
