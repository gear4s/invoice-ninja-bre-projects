<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Repositories;

use App\Utils\Ninja;
use App\Models\Company;
use App\Repositories\BaseRepository;

/**
 * CompanyRepository.
 */
class CompanyRepository extends BaseRepository
{
    public function __construct() {}

    /**
     * Saves the client and its contacts.
     *
     * @param array $data The data
     * @param Company $company
     * @return Company|null  Company Object
     */
    public function save(array $data, Company $company): ?Company
    {

        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            $data['custom_fields'] = $this->parseCustomFields($data['custom_fields']);
        }

        $company->fill($data);

        if (array_key_exists('settings', $data)) {
            $company->saveSettings($data['settings'], $company);
        }

        if (isset($data['smtp_username'])) {
            $company->smtp_username = $data['smtp_username'];
        }

        if (isset($data['smtp_password'])) {
            $company->smtp_password = $data['smtp_password'];
        }

        if (isset($data['e_invoice'])) {
            $company->e_invoice = $data['e_invoice'];
        }

        $company->save();

        return $company;
    }

    /**
     * parseCustomFields
     *
     * @param  array $fields
     * @return array
     */
    private function parseCustomFields($fields): array
    {
        foreach ($fields as &$value) {
            $value = (string) $value;
        }

        return $fields;
    }

    /**
     * Update QuickBooks settings by merging updatable fields with existing settings.
     *
     * This method safely merges only the allowed fields from the input
     * with the existing QuickBooks settings, preserving OAuth tokens and
     * other protected fields.
     *
     * @param  Company $company
     * @param  array $quickbooks_data
     * @return void
     */
    private function updateQuickbooksSettings(Company $company, array $quickbooks_data): void
    {
        $existing = $company->quickbooks ?? new QuickbooksSettings();

        // Update top-level fields
        if (isset($quickbooks_data['companyName'])) {
            $existing->companyName = $quickbooks_data['companyName'];
        }

        // Update nested settings if provided
        if (isset($quickbooks_data['settings']) && is_array($quickbooks_data['settings'])) {
            foreach ($quickbooks_data['settings'] as $key => $value) {
                // Handle sync map objects (client, vendor, invoice, etc.)
                if (in_array($key, ['client', 'vendor', 'invoice', 'sales', 'quote', 'purchase_order', 'product', 'payment', 'expense', 'expense_category'])) {
                    if (isset($value['direction'])) {
                        $existing->settings->{$key}->direction = SyncDirection::from($value['direction']);
                    }
                }
                // Handle scalar settings fields
                elseif (property_exists($existing->settings, $key)) {
                    $existing->settings->{$key} = $value;
                }
            }
        }

        $company->quickbooks = $existing;
    }
}
