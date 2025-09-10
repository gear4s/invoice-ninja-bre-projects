<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2025. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Services\Client;

use App\Models\CompanyGateway;
use App\Models\Client;
use App\Services\AbstractService;
use App\Utils\Traits\MakesHash;

class MapSettings extends AbstractService
{
    use MakesHash;

     private array $default_settings = [
        "company_gateway_ids" => "company_gateways",
        "auto_archive_invoice" => "auto_archive_invoice_help",
        "enable_client_portal_password" => "enable_portal_password",
        "enable_client_portal" => "enable_client_portal_help",
        "signature_on_pdf" => "signature_on_pdf_help",
        "default_task_rate" => "default_task_rate",
        "payment_terms" => "payment_terms",
        "send_reminders" => "send_reminders",
        "auto_email_invoice" => "auto_email_invoice_help",
        "entity_send_time" => "send_time",
        "auto_bill_date" => "auto_bill_on",
        "valid_until" => "quote_valid_until",
        "show_accept_invoice_terms" => "show_accept_invoice_terms",
        "show_accept_quote_terms" => "show_accept_quote_terms",
        "require_invoice_signature" => "require_invoice_signature",
        "require_quote_signature" => "require_quote_signature",
        "client_online_payment_notification" => "online_payment_email",
        "client_manual_payment_notification" => "manual_payment_email",
        "send_email_on_mark_paid" => "mark_paid_payment_email",
        "auto_bill_standard_invoices" => "auto_bill_standard_invoices",
    ];

    public function __construct(private Client $client)
    {
    }

    public function run()
    {
            
        
        // $merged_settings = $this->client->getMergedSettings();

        // // Extract only the settings we want from merged settings
        // $default_settings = [];

        // foreach ($this->default_settings as $key) {
        //     if (isset($merged_settings->{$key})) {
        //         $default_settings[$key] = $merged_settings->{$key};
        //     }
        // }

        $settings_map = [];
        $group_only_settings = [];
        $client_settings = (array)$this->client->settings;

        if ($this->client->group_settings) {
            $group_settings = (array)$this->client->group_settings->settings;
            $group_only_settings = array_diff_key($group_settings, $client_settings);
        }

        $group = collect($group_only_settings)->mapWithKeys(function($value, $key) {
            
            if($key == "company_gateway_ids") {
                $key = "company_gateways";
                $value = $this->handleCompanyGateways($value);
            }

            return [$this->getTranslationFromKey($key) => $value];
        })->toArray();

        unset($client_settings['entity']);
        unset($client_settings['industry_id']);
        unset($client_settings['size_id']);
        unset($client_settings['currency_id']);

        $client = collect($client_settings)->mapWithKeys(function($value, $key) {
        
            if ($key == "company_gateway_ids") {
                $key = "company_gateways";
                $value = $this->handleCompanyGateways($value);
            }

            return [$this->getTranslationFromKey($key) => $value];
        })->toArray();

        return [
            'group_settings' => $group,
            'client_settings' => $client,
        ];

    }

    private function getTranslationFromKey(string $key): string
    {
        if(isset($this->default_settings[$key])) {
            return ctrans("texts.{$this->default_settings[$key]}");
        }

        return ctrans("texts.{$key}");
    }

    private function handleCompanyGateways(string $company_gateway_ids): string
    {
        nlog($company_gateway_ids);
        if($company_gateway_ids == "0") {
            return "Payment Gateways Disabled!";
        }
        
        if ($company_gateway_ids == "") {
            return "No Special Configuration.";
        }

        $company_gateway_ids = explode(',', $company_gateway_ids);

        $company_gateways = CompanyGateway::where('company_id', $this->client->company_id)
                                            ->whereIn('id', $this->transformKeys($company_gateway_ids))
                                            ->pluck('label')
                                            ->toArray();

        return implode(', ', $company_gateways);
    }
}