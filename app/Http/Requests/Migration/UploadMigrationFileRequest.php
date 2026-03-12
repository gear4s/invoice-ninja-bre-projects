<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\Migration;

use App\Http\Requests\Request;
use App\Models\User;

class UploadMigrationFileRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'migration' => [],
        ];

        /* We'll skip mime validation while running tests. */
        if (app()->environment() !== 'testing') {
            $rules['migration'] = ['required', 'file', 'mimes:zip'];
        }

        return $rules;
    }
}
