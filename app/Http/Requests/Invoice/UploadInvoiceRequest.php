<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\Invoice;

use App\Http\Requests\Request;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class UploadInvoiceRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->can('edit', $this->invoice);
    }

    public function rules()
    {
        $rules = [];
        $rules['file'] = 'bail|sometimes|array';
        $rules['file.*'] = $this->fileValidation();
        $rules['documents'] = 'bail|sometimes|array';
        $rules['documents.*'] = $this->fileValidation();
        $rules['is_public'] = 'sometimes|boolean';

        return $rules;
    }

    public function prepareForValidation()
    {
        $input = $this->all();

        if ($this->file('documents') instanceof UploadedFile) {
            $this->files->set('documents', [$this->file('documents')]);
        }

        if ($this->file('file') instanceof UploadedFile) {
            $this->files->set('file', [$this->file('file')]);
        }

        if (isset($input['is_public'])) {
            $input['is_public'] = $this->toBoolean($input['is_public']);
        }

        $this->replace($input);

    }
}
