<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\Import;

use App\Http\Requests\Request;
use App\Models\User;

class PreImportRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->isAdmin();
    }

    public function rules()
    {
        return [
            'files.*' => 'file|mimetypes:text/csv,text/plain,application/octet-stream',
            'files' => 'required|array|min:1|max:6',
            'import_type' => 'required',
        ];
    }
}
