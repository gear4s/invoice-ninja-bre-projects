<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Requests\Client;

use App\DataMapper\CompanySettings;
use App\Http\Requests\Request;
use App\Http\ValidationRules\EInvoice\ValidClientScheme;
use App\Http\ValidationRules\ValidClientGroupSettingsRule;
use App\Models\Country;
use App\Models\Language;
use App\Models\User;
use App\Utils\Traits\ChecksEntityStatus;
use App\Utils\Traits\MakesHash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends Request
{
    use ChecksEntityStatus;
    use MakesHash;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->can('edit', $this->client);
    }

    public function rules()
    {
        /* Ensure we have a client name, and that all emails are unique */
        /** @var User $user */
        $user = auth()->user();

        $rules['file'] = 'bail|sometimes|array';
        $rules['file.*'] = $this->fileValidation();
        $rules['documents'] = 'bail|sometimes|array';

        $rules['company_logo'] = 'mimes:jpeg,jpg,png,gif|max:10000';
        $rules['industry_id'] = 'integer|nullable';
        $rules['size_id'] = 'integer|nullable';
        $rules['country_id'] = 'integer|nullable|exists:countries,id';
        $rules['shipping_country_id'] = 'integer|nullable|exists:countries,id';
        $rules['classification'] =
            'bail|sometimes|nullable|in:individual,business,company,partnership,trust,charity,government,other';
        // Uniqueness is enforced across the whole account so that a client
        // number or ID number cannot be duplicated in any company that belongs
        // to the same account (global shared client pool).
        $rules['id_number'] = [
            'sometimes',
            'bail',
            'nullable',
            Rule::unique('clients')
                ->where('account_id', $user->account_id)
                ->ignore($this->client->id),
        ];
        $rules['number'] = [
            'sometimes',
            'bail',
            Rule::unique('clients')
                ->where('account_id', $user->account_id)
                ->ignore($this->client->id),
        ];

        $rules['e_invoice'] = [
            'sometimes',
            'nullable',
            new ValidClientScheme,
        ];

        $rules['settings'] = new ValidClientGroupSettingsRule;
        $rules['contacts'] = 'array';
        $rules['contacts.*.email'] = 'bail|nullable|distinct|sometimes|email';
        $rules['contacts.*.password'] = [
            'nullable',
            'sometimes',
            'string',
            'min:7', // must be at least 10 characters in length
            'regex:/[a-z]/', // must contain at least one lowercase letter
            'regex:/[A-Z]/', // must contain at least one uppercase letter
            'regex:/[0-9]/', // must contain at least one digit
            // 'regex:/[@$!%*#?&.]/', // must contain a special character
        ];

        $rules['custom_value1'] = [
            'bail',
            'nullable',
            'sometimes',
            function ($attribute, $value, $fail) {
                if (is_array($value)) {
                    $fail("The $attribute must not be an array.");
                }
            },
        ];
        $rules['custom_value2'] = [
            'bail',
            'nullable',
            'sometimes',
            function ($attribute, $value, $fail) {
                if (is_array($value)) {
                    $fail("The $attribute must not be an array.");
                }
            },
        ];
        $rules['custom_value3'] = [
            'bail',
            'nullable',
            'sometimes',
            function ($attribute, $value, $fail) {
                if (is_array($value)) {
                    $fail("The $attribute must not be an array.");
                }
            },
        ];
        $rules['custom_value4'] = [
            'bail',
            'nullable',
            'sometimes',
            function ($attribute, $value, $fail) {
                if (is_array($value)) {
                    $fail("The $attribute must not be an array.");
                }
            },
        ];

        $rules['settings.currency_id'] = 'required|exists:currencies,id';

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = auth()->user();
            $company = $user->company();

            if (
                isset($this->settings['lock_invoices']) &&
                $company->verifactuEnabled() &&
                $this->settings['lock_invoices'] != 'when_sent'
            ) {
                $validator
                    ->errors()
                    ->add(
                        'settings.lock_invoices',
                        'Locked Invoices Cannot Be Disabled',
                    );
            }
        });
    }

    public function messages()
    {
        return [
            'email' => ctrans('validation.email', ['attribute' => 'email']),
            'name.required' => ctrans('validation.required', [
                'attribute' => 'name',
            ]),
            'required' => ctrans('validation.required', [
                'attribute' => 'email',
            ]),
            'contacts.*.password.min' => ctrans('texts.password_strength'),
            'contacts.*.password.regex' => ctrans('texts.password_strength'),
            'contacts.*.password.string' => ctrans('texts.password_strength'),
        ];
    }

    public function prepareForValidation()
    {
        $input = $this->all();

        /** @var User $user */
        $user = auth()->user();

        if ($this->file('file') instanceof UploadedFile) {
            $this->files->set('file', [$this->file('file')]);
        }

        if (isset($input['documents'])) {
            unset($input['documents']);
        }

        if (empty($input['settings']['currency_id'])) {
            $input['settings']['currency_id'] = (string) $user->company()
                ->settings->currency_id;
        }

        if (isset($input['language_code'])) {
            $input['settings']['language_id'] = $this->getLanguageId(
                $input['language_code'],
            );
        }

        $input = $this->decodePrimaryKeys($input);

        if (array_key_exists('settings', $input)) {
            $input['settings'] = $this->filterSaveableSettings(
                $input['settings'],
            );
        }

        if (array_key_exists('name', $input)) {
            $input['name'] = strip_tags($input['name'] ?? '');
        }

        // allow setting country_id by iso code
        if (isset($input['country_code'])) {
            $input['country_id'] = $this->getCountryCode(
                $input['country_code'],
            );
        }

        // allow setting country_id by iso code
        if (isset($input['shipping_country_code'])) {
            $input['shipping_country_id'] = $this->getCountryCode(
                $input['shipping_country_code'],
            );
        }

        if (isset($input['e_invoice']) && is_array($input['e_invoice'])) {
            // ensure it is normalized first!
            $input['e_invoice'] = $this->client->filterNullsRecursive(
                $input['e_invoice'],
            );
        }

        if (isset($input['public_notes']) && $this->hasHeader('X-REACT')) {
            $input['public_notes'] = str_replace(
                "\n",
                '',
                $input['public_notes'],
            );
        }
        if (isset($input['private_notes']) && $this->hasHeader('X-REACT')) {
            $input['private_notes'] = str_replace(
                "\n",
                '',
                $input['private_notes'],
            );
        }

        $this->replace($input);
    }

    private function getCountryCode($country_code)
    {
        /** @var Collection<Country> */
        $countries = app('countries');

        $country = $countries->first(function ($item) use ($country_code) {
            return $item->iso_3166_2 == $country_code ||
                $item->iso_3166_3 == $country_code;
        });

        return $country ? (string) $country->id : '';
    }

    private function getLanguageId($language_code)
    {
        /** @var Collection<Language> */
        $languages = app('languages');

        $language = $languages->first(function ($item) use ($language_code) {
            return $item->locale == $language_code;
        });

        return $language ? (string) $language->id : '';
    }

    /**
     * For the hosted platform, we restrict the feature settings.
     *
     * This method will trim the company settings object
     * down to the free plan setting properties which
     * are saveable
     *
     * @param  mixed  $settings
     * @return \stdClass $settings
     */
    private function filterSaveableSettings($settings)
    {
        $account = $this->client->company->account;

        // Do not allow a user to force pdf variables on the client settings.
        unset($settings['pdf_variables']);

        if (!$account->isFreeHostedClient()) {
            return $settings;
        }

        $saveable_casts = CompanySettings::$free_plan_casts;

        foreach ($settings as $key => $value) {
            if (!array_key_exists($key, $saveable_casts)) {
                unset($settings->{$key});
            }

            // 26-04-2022 - In case settings are returned as array instead of object
            if ($key == 'default_task_rate' && is_array($settings)) {
                $settings['default_task_rate'] = floatval($value);
            } elseif ($key == 'default_task_rate' && is_object($settings)) {
                $settings->default_task_rate = floatval($value);
            }
        }

        return $settings;
    }
}
