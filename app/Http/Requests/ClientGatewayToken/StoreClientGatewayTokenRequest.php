<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\ClientGatewayToken;

use App\Http\Requests\Request;
use App\Models\Client;
use App\Models\User;
use App\Utils\Traits\MakesHash;
use Illuminate\Validation\Rule;

class StoreClientGatewayTokenRequest extends Request
{
    use MakesHash;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules()
    {

        /** @var User $user */
        $user = auth()->user();

        // ensure client is present
        $rules = [
            'client_id' => ['required', 'bail', Rule::exists('clients', 'id')->where('company_id', $user->company()->id)->where('is_deleted', 0)],
            'company_gateway_id' => ['required', 'bail', Rule::exists('company_gateways', 'id')->where('company_id', $user->company()->id)->where('is_deleted', 0)],
            'gateway_type_id' => 'required|integer',
            'meta' => 'required',
            'is_default' => 'sometimes|bail|boolean',
        ];

        return $this->globalRules($rules);
    }

    public function prepareForValidation()
    {
        $input = $this->all();

        $input = $this->decodePrimaryKeys($input);

        $this->replace($input);
    }

    public function messages()
    {
        return [
        ];
    }
}
