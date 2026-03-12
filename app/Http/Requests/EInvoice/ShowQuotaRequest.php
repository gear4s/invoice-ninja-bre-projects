<?php

namespace App\Http\Requests\EInvoice;

use App\Models\User;
use App\Utils\Ninja;
use Illuminate\Foundation\Http\FormRequest;

class ShowQuotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (config('ninja.app_env') == 'local') {
            return true;
        }

        /** @var User $user */
        $user = auth()->user();

        return Ninja::isSelfHost() && $user->account->isPaid();
    }

    public function rules(): array
    {
        return [
            //
        ];
    }
}
