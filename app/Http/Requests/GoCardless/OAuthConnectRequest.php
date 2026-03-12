<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\GoCardless;

use App\Libraries\MultiDB;
use App\Models\BaseModel;
use App\Models\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;

class OAuthConnectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Cache::has($this->token);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    public function getCompany(): Model|Builder|BaseModel
    {
        $data = Cache::get(
            key: $this->token,
        );

        MultiDB::findAndSetDbByCompanyKey(
            company_key: $data['company_key'],
        );

        return Company::query()
            ->where('company_key', $data['company_key'])
            ->firstOrFail();
    }
}
