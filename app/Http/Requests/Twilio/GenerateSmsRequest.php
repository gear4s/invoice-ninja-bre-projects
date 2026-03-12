<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\Twilio;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Validator;

class GenerateSmsRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'phone' => 'required|regex:^\+[1-9]\d{1,14}$^',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $user = auth()->user();

        $key = "phone_verification_code_{$user->id}_{$user->account_id}";
        $count = Cache::get($key);

        if ($count && $count > 1) {

            Cache::put($key, $count + 1, 300);
            $validator->after(function ($validator) {
                $validator->errors()->add('phone', 'You requested a verification code recently. Please retry again in a few minutes.');
            });

        } else {
            Cache::put($key, 1, 300);
        }

    }
}
