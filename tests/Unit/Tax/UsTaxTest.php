<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Unit\Tax;

use App\DataMapper\CompanySettings;
use App\DataMapper\Tax\TaxModel;
use App\DataMapper\Tax\ZipTax\Response;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\MockAccountData;
use Tests\TestCase;

class UsTaxTest extends TestCase
{
    use DatabaseTransactions;
    use MockAccountData;

    private array $mock_response = [
        'geoPostalCode' => '92582',
        'geoCity' => 'SAN JACINTO',
        'geoCounty' => 'RIVERSIDE',
        'geoState' => 'CA',
        'taxSales' => 0.0875,
        'taxUse' => 0.0875,
        'txbService' => 'N',
        'txbFreight' => 'N',
        'stateSalesTax' => 0.06,
        'stateUseTax' => 0.06,
        'citySalesTax' => 0.01,
        'cityUseTax' => 0.01,
        'cityTaxCode' => '874',
        'countySalesTax' => 0.0025,
        'countyUseTax' => 0.0025,
        'countyTaxCode' => '',
        'districtSalesTax' => 0.015,
        'districtUseTax' => 0.015,
        'district1Code' => '26',
        'district1SalesTax' => 0,
        'district1UseTax' => 0,
        'district2Code' => '26',
        'district2SalesTax' => 0.005,
        'district2UseTax' => 0.005,
        'district3Code' => '',
        'district3SalesTax' => 0,
        'district3UseTax' => 0,
        'district4Code' => '33',
        'district4SalesTax' => 0.01,
        'district4UseTax' => 0.01,
        'district5Code' => '',
        'district5SalesTax' => 0,
        'district5UseTax' => 0,
        'originDestination' => 'D',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );

        $this->withoutExceptionHandling();

        $this->makeTestData();
    }

    private function invoiceStub(?string $postal_code = '')
    {

        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = true;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),

        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 840,
            'shipping_country_id' => 840,
            'has_valid_vat_number' => false,
            'postal_code' => $postal_code,
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => 1,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        return $invoice;
    }

    public function test_tax_au_no_exemption()
    {

        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = false;
        $tax_data->regions->AU->has_sales_above_threshold = true;
        $tax_data->regions->AU->tax_all_subregions = true;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 36,
            'postal_code' => '30002',
            'shipping_country_id' => 36,
            'shipping_postal_code' => '30002',
            'shipping_state' => '30002',
            'has_valid_vat_number' => false,
            'is_tax_exempt' => false,
            'state' => 'NSW',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_PHYSICAL,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(110, $invoice->amount);

    }

    public function test_tax_au_client_exemption()
    {

        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = false;
        $tax_data->regions->AU->has_sales_above_threshold = true;
        $tax_data->regions->AU->tax_all_subregions = true;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 36,
            'postal_code' => '30002',
            'shipping_country_id' => 36,
            'shipping_postal_code' => '30002',
            'shipping_state' => '30002',
            'has_valid_vat_number' => false,
            'is_tax_exempt' => true,
            'state' => 'NSW',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_PHYSICAL,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(100, $invoice->amount);

    }

    public function test_tax_au_product_exemption()
    {

        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = false;
        $tax_data->regions->AU->has_sales_above_threshold = true;
        $tax_data->regions->AU->tax_all_subregions = true;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 36,
            'postal_code' => '30002',
            'shipping_country_id' => 36,
            'shipping_postal_code' => '30002',
            'shipping_state' => '30002',
            'has_valid_vat_number' => false,
            'is_tax_exempt' => false,
            'state' => 'NSW',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_EXEMPT,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(100, $invoice->amount);

    }

    public function test_tax_au_product_override()
    {

        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = false;
        $tax_data->regions->AU->has_sales_above_threshold = true;
        $tax_data->regions->AU->tax_all_subregions = true;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 36,
            'postal_code' => '30002',
            'shipping_country_id' => 36,
            'shipping_postal_code' => '30002',
            'shipping_state' => '30002',
            'has_valid_vat_number' => false,
            'is_tax_exempt' => false,
            'state' => 'NSW',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => 'OVERRIDE',
                    'tax_rate1' => 20,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_OVERRIDE_TAX,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(120, $invoice->amount);

    }

    public function test_interstate_freight_no_tax_with_product_tax()
    {

        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = true;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 840,
            'postal_code' => '30002',
            'shipping_country_id' => 840,
            'shipping_postal_code' => '30002',
            'shipping_state' => '30002',
            'has_valid_vat_number' => false,
            'is_tax_exempt' => false,
            'state' => 'GA',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_SHIPPING,
                ],
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_PHYSICAL,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(208.75, $invoice->amount);

    }

    public function test_interstate_freight_product_no_tax()
    {

        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = false;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 840,
            'postal_code' => '30002',
            'shipping_country_id' => 840,
            'shipping_postal_code' => '30002',
            'shipping_state' => '30002',
            'has_valid_vat_number' => false,
            'is_tax_exempt' => false,
            'state' => 'GA',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_SHIPPING,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(100, $invoice->amount);

    }

    public function test_interstate_service_product_no_tax()
    {

        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = false;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 840,
            'postal_code' => '30002',
            'shipping_country_id' => 840,
            'shipping_postal_code' => '30002',
            'shipping_state' => '30002',
            'has_valid_vat_number' => false,
            'is_tax_exempt' => false,
            'state' => 'GA',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '2',
                    'tax_id' => Product::PRODUCT_TYPE_SERVICE,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(100, $invoice->amount);

    }

    public function test_interstate_with_no_tax()
    {

        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = false;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 840,
            'postal_code' => '30002',
            'shipping_country_id' => 840,
            'shipping_postal_code' => '30002',
            'shipping_state' => '30002',
            'has_valid_vat_number' => false,
            'is_tax_exempt' => false,
            'state' => 'GA',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_PHYSICAL,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(100, $invoice->amount);

    }

    public function test_same_subregion_and_exempt_product()
    {

        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = true;
        $tax_data->regions->EU->has_sales_above_threshold = true;
        $tax_data->regions->EU->tax_all_subregions = true;
        $tax_data->regions->EU->subregions->DE->tax_rate = 21;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 840,
            'postal_code' => '90210',
            'shipping_country_id' => 840,
            'shipping_postal_code' => '90210',
            'has_valid_vat_number' => false,
            'postal_code' => 'xx',
            'is_tax_exempt' => false,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_EXEMPT,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(100, $invoice->amount);

    }

    public function test_same_subregion_and_exempt_client()
    {

        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = true;
        $tax_data->regions->EU->has_sales_above_threshold = true;
        $tax_data->regions->EU->tax_all_subregions = true;
        $tax_data->regions->EU->subregions->DE->tax_rate = 21;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 840,
            'postal_code' => '90210',
            'shipping_country_id' => 840,
            'shipping_postal_code' => '90210',
            'has_valid_vat_number' => false,
            'is_tax_exempt' => true,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_PHYSICAL,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(100, $invoice->amount);

    }

    public function test_foreign_taxes_enabled_with_exempt_product()
    {
        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = true;
        $tax_data->regions->EU->has_sales_above_threshold = true;
        $tax_data->regions->EU->tax_all_subregions = true;
        $tax_data->regions->EU->subregions->DE->tax_rate = 21;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 276,
            'shipping_country_id' => 276,
            'has_valid_vat_number' => false,
            'postal_code' => 'xx',
            'is_tax_exempt' => false,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_EXEMPT,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(100, $invoice->amount);

    }

    public function test_foreign_taxes_disabled()
    {
        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = true;
        $tax_data->regions->EU->has_sales_above_threshold = true;
        $tax_data->regions->EU->tax_all_subregions = false;
        $tax_data->regions->EU->subregions->DE->tax_rate = 21;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 276,
            'shipping_country_id' => 276,
            'has_valid_vat_number' => false,
            'postal_code' => 'xx',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_PHYSICAL,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(100, $invoice->amount);

    }

    public function test_foreign_taxes_enabled()
    {
        $settings = CompanySettings::defaults();
        $settings->country_id = '840'; // germany

        $tax_data = new TaxModel;
        $tax_data->seller_subregion = 'CA';
        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = true;

        $tax_data->regions->US->subregions->CA->tax_rate = 6;
        $tax_data->regions->US->subregions->CA->tax_name = 'Sales Tax';

        $tax_data->regions->EU->has_sales_above_threshold = true;
        $tax_data->regions->EU->tax_all_subregions = true;
        $tax_data->regions->EU->subregions->DE->tax_rate = 21;

        $company = Company::factory()->create([
            'account_id' => $this->account->id,
            'settings' => $settings,
            'tax_data' => $tax_data,
            'calculate_taxes' => true,
            'origin_tax_data' => new Response($this->mock_response),
        ]);

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'country_id' => 276,
            'shipping_country_id' => 276,
            'has_valid_vat_number' => false,
            'postal_code' => 'xx',
            'tax_data' => new Response($this->mock_response),
            'is_tax_exempt' => false,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'status_id' => 1,
            'user_id' => $this->user->id,
            'uses_inclusive_taxes' => false,
            'discount' => 0,
            'line_items' => [
                [
                    'product_key' => 'Test',
                    'notes' => 'Test',
                    'cost' => 100,
                    'quantity' => 1,
                    'tax_name1' => '',
                    'tax_rate1' => 0,
                    'tax_name2' => '',
                    'tax_rate2' => 0,
                    'tax_name3' => '',
                    'tax_rate3' => 0,
                    'type_id' => '1',
                    'tax_id' => Product::PRODUCT_TYPE_PHYSICAL,
                ],
            ],
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_data' => new Response($this->mock_response),
        ]);

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(121, $invoice->amount);

    }

    public function test_company_tax_all_off_but_tax_us_region()
    {

        $invoice = $this->invoiceStub('92582');
        $invoice->client->is_tax_exempt = false;
        $invoice->client->tax_data = new Response($this->mock_response);

        $invoice->client->push();

        $tax_data = $invoice->company->tax_data;

        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = true;

        $invoice->company->tax_data = $tax_data;
        $invoice->company->push();

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(8.75, $invoice->line_items[0]->tax_rate1);
        $this->assertEquals(108.75, $invoice->amount);

    }

    public function test_company_tax_all_off()
    {

        $invoice = $this->invoiceStub('92582');
        $client = $invoice->client;
        $client->is_tax_exempt = false;
        $client->save();

        $company = $invoice->company;
        $tax_data = $company->tax_data;

        $tax_data->regions->US->has_sales_above_threshold = true;
        $tax_data->regions->US->tax_all_subregions = false;

        $company->tax_data = $tax_data;
        $company->save();

        // Reload the company relationship on the invoice to ensure fresh tax_data is used
        $invoice->load('company');
        $invoice->client->load('company');

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(0, $invoice->line_items[0]->tax_rate1);
        $this->assertEquals(100, $invoice->amount);

    }

    public function test_threshold_levels_are_met()
    {

        $invoice = $this->invoiceStub('92582');
        $client = $invoice->client;
        $client->is_tax_exempt = true;
        $client->save();

        $company = $invoice->company;
        $tax_data = $company->tax_data;

        $tax_data->regions->US->has_sales_above_threshold = false;
        $tax_data->regions->US->tax_all_subregions = true;

        $company->tax_data = $tax_data;
        $company->save();

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(0, $invoice->line_items[0]->tax_rate1);
        $this->assertEquals(100, $invoice->amount);

    }

    public function test_has_valid_vat_makes_no_difference_to_tax_calc()
    {

        $invoice = $this->invoiceStub('92582');
        $client = $invoice->client;
        $client->has_valid_vat_number = true;
        $client->save();

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(8.75, $invoice->line_items[0]->tax_rate1);
        $this->assertEquals(108.75, $invoice->amount);
    }

    public function test_tax_exemption()
    {
        $invoice = $this->invoiceStub('92582');
        $client = $invoice->client;
        $client->is_tax_exempt = true;
        $client->save();

        $invoice = $invoice->calc()->getInvoice()->service()->markSent()->save();

        $this->assertEquals(0, $invoice->line_items[0]->tax_rate1);
        $this->assertEquals(100, $invoice->amount);
    }

    public function test_basic_tax_calculation()
    {

        $invoice = $this->invoiceStub();

        $this->assertEquals(8.75, $invoice->line_items[0]->tax_rate1);
        $this->assertEquals(108.75, $invoice->amount);

    }
}
