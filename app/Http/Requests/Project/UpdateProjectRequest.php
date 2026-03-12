<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\Project;

use App\Http\Requests\Request;
use App\Models\User;
use App\Utils\Traits\ChecksEntityStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends Request
{
    use ChecksEntityStatus;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        /** @var User $user */
        $user = auth()->user();

        return $user->can('edit', $this->project);
    }

    public function rules()
    {

        /** @var User $user */
        $user = auth()->user();

        $rules = [];

        if (isset($this->number)) {
            $rules['number'] = Rule::unique('projects')->where('company_id', $user->company()->id)->ignore($this->project->id);
        }

        $rules['budgeted_hours'] = 'sometimes|bail|numeric';
        $rules['task_rate'] = 'sometimes|bail|numeric';
        $rules['file'] = 'bail|sometimes|array';
        $rules['file.*'] = $this->fileValidation();

        return $this->globalRules($rules);
    }

    public function prepareForValidation()
    {
        $input = $this->decodePrimaryKeys($this->all());

        if ($this->file('file') instanceof UploadedFile) {
            $this->files->set('file', [$this->file('file')]);
        }

        if (isset($input['client_id'])) {
            unset($input['client_id']);
        }

        if (array_key_exists('color', $input) && is_null($input['color'])) {
            $input['color'] = '';
        }

        if (array_key_exists('budgeted_hours', $input) && empty($input['budgeted_hours'])) {
            $input['budgeted_hours'] = 0;
        }

        if (isset($input['documents'])) {
            unset($input['documents']);
        }

        $this->replace($input);
    }
}
