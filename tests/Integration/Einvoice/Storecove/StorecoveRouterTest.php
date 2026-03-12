<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Integration\Einvoice\Storecove;

use App\Models\Account;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use App\Services\EDocument\Gateway\Storecove\Storecove;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorecoveRouterTest extends TestCase
{
    use DatabaseTransactions;

    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );

        $this->faker = Factory::create();

    }

    private function buildData()
    {

        $account = Account::factory()->create();
        $company = Company::factory()->create([
            'account_id' => $account->id,
        ]);

        $user = User::factory()->create([
            'account_id' => $account->id,
            'confirmation_code' => 'xyz123',
            'email' => Str::random(32) . '@example.com',
            'password' => Hash::make('ALongAndBriliantPassword'),
        ]);

        $client = Client::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $invoice->service()->markSent()->save();

        return $invoice;

    }

    public function test_is_business_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 352;
        $client->vat_number = 'IS1234567890';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('IS:VAT', $storecove->router->resolveTaxScheme('IS', 'business'));

    }

    // Luxembourg Tests
    public function test_lu_business_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 442;
        $client->vat_number = 'LU12345678';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('LU:VAT', $storecove->router->resolveRouting('LU', 'business'));
    }

    public function test_lu_gov_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 442;
        $client->vat_number = 'LU12345678';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('LU:VAT', $storecove->router->resolveRouting('LU', 'government'));
    }

    public function test_lu_business_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 442;
        $client->vat_number = 'LU12345678';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('LU:VAT', $storecove->router->resolveTaxScheme('LU', 'business'));
    }

    public function test_lu_gov_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 442;
        $client->vat_number = 'LU12345678';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('LU:VAT', $storecove->router->resolveTaxScheme('LU', 'government'));
    }

    // Norway Tests
    public function test_no_business_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 578;
        $client->vat_number = 'NO123456789';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('NO:ORG', $storecove->router->resolveRouting('NO', 'business'));
    }

    public function test_no_gov_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 578;
        $client->vat_number = 'NO123456789';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('NO:ORG', $storecove->router->resolveRouting('NO', 'government'));
    }

    public function test_no_business_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 578;
        $client->vat_number = 'NO123456789';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('NO:VAT', $storecove->router->resolveTaxScheme('NO', 'business'));
    }

    public function test_no_gov_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 578;
        $client->vat_number = 'NO123456789';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('NO:VAT', $storecove->router->resolveTaxScheme('NO', 'government'));
    }

    // Netherlands Tests
    public function test_nl_business_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 528;
        $client->vat_number = 'NL123456789B01';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('NL:VAT', $storecove->router->resolveRouting('NL', 'business'));
    }

    public function test_nl_gov_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 528;
        $client->vat_number = 'NL123456789B01';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('NL:OINO', $storecove->router->resolveRouting('NL', 'government'));
    }

    public function test_nl_business_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 528;
        $client->vat_number = 'NL123456789B01';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('NL:VAT', $storecove->router->resolveTaxScheme('NL', 'business'));
    }

    public function test_nl_gov_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 528;
        $client->vat_number = 'NL123456789B01';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals(false, $storecove->router->resolveTaxScheme('NL', 'government'));
    }

    // Sweden Tests
    public function test_se_business_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 752;
        $client->vat_number = 'SE123456789101';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('SE:ORGNR', $storecove->router->resolveRouting('SE', 'business'));
    }

    public function test_se_gov_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 752;
        $client->vat_number = 'SE123456789101';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('SE:ORGNR', $storecove->router->resolveRouting('SE', 'government'));
    }

    public function test_se_business_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 752;
        $client->vat_number = 'SE123456789101';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('SE:VAT', $storecove->router->resolveTaxScheme('SE', 'business'));
    }

    public function test_se_gov_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 752;
        $client->vat_number = 'SE123456789101';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('SE:VAT', $storecove->router->resolveTaxScheme('SE', 'government'));
    }

    // Iceland Tests
    public function test_is_business_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 352;
        $client->vat_number = 'IS123456';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('IS:KTNR', $storecove->router->resolveRouting('IS', 'business'));
    }

    public function test_is_gov_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 352;
        $client->vat_number = 'IS123456';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('IS:KTNR', $storecove->router->resolveRouting('IS', 'government'));
    }

    public function test_is_business_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 352;
        $client->vat_number = 'IS123456';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('IS:VAT', $storecove->router->resolveTaxScheme('IS', 'business'));
    }

    public function test_is_gov_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 352;
        $client->vat_number = 'IS123456';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('IS:VAT', $storecove->router->resolveTaxScheme('IS', 'government'));
    }

    // Ireland Tests
    public function test_ie_business_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 372;
        $client->vat_number = 'IE1234567T';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('IE:VAT', $storecove->router->resolveRouting('IE', 'business'));
    }

    public function test_ie_gov_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 372;
        $client->vat_number = 'IE1234567T';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('IE:VAT', $storecove->router->resolveRouting('IE', 'government'));
    }

    public function test_ie_business_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 372;
        $client->vat_number = 'IE1234567T';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('IE:VAT', $storecove->router->resolveTaxScheme('IE', 'business'));
    }

    public function test_ie_gov_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 372;
        $client->vat_number = 'IE1234567T';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('IE:VAT', $storecove->router->resolveTaxScheme('IE', 'government'));
    }

    // Denmark Tests
    public function test_dk_business_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 208;
        $client->vat_number = 'DK12345678';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('DK:DIGST', $storecove->router->resolveRouting('DK', 'business'));
    }

    public function test_dk_gov_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 208;
        $client->vat_number = 'DK12345678';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('DK:DIGST', $storecove->router->resolveRouting('DK', 'government'));
    }

    public function test_dk_business_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 208;
        $client->vat_number = 'DK12345678';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('DK:ERST', $storecove->router->resolveTaxScheme('DK', 'business'));
    }

    public function test_dk_gov_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 208;
        $client->vat_number = 'DK12345678';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('DK:ERST', $storecove->router->resolveTaxScheme('DK', 'government'));
    }

    // UK/England Tests
    public function test_gb_business_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 826;
        $client->vat_number = 'GB123456789';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('GB:VAT', $storecove->router->resolveRouting('GB', 'business'));
    }

    public function test_gb_gov_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 826;
        $client->vat_number = 'GB123456789';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('GB:VAT', $storecove->router->resolveRouting('GB', 'government'));
    }

    public function test_gb_business_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 826;
        $client->vat_number = 'GB123456789';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('GB:VAT', $storecove->router->resolveTaxScheme('GB', 'business'));
    }

    public function test_gb_gov_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 826;
        $client->vat_number = 'GB123456789';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('GB:VAT', $storecove->router->resolveTaxScheme('GB', 'government'));
    }

    public function test_be_business_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 56; // Belgium
        $client->vat_number = 'BE0123456789';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('BE:EN', $storecove->router->resolveRouting('BE', 'business'));
    }

    public function test_be_gov_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 56;
        $client->vat_number = 'BE0123456789';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('BE:EN', $storecove->router->resolveRouting('BE', 'government'));
    }

    public function test_be_business_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 56;
        $client->vat_number = 'BE0123456789';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('BE:VAT', $storecove->router->resolveTaxScheme('BE', 'business'));
    }

    public function test_be_gov_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 56;
        $client->vat_number = 'BE0123456789';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('BE:VAT', $storecove->router->resolveTaxScheme('BE', 'government'));
    }

    public function test_at_business_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 40;
        $client->vat_number = 'ATU123456789';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('AT:VAT', $storecove->router->resolveRouting('AT', 'business'));

    }

    public function test_at_gov_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 40;
        $client->vat_number = 'ATU123456789';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('9915:b', $storecove->router->resolveRouting('AT', 'government'));

    }

    public function test_at_business_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 40;
        $client->vat_number = 'ATU123456789';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('AT:VAT', $storecove->router->resolveTaxScheme('AT', 'business'));

    }

    public function test_at_gov_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 40;
        $client->vat_number = 'ATU123456789';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals(false, $storecove->router->resolveTaxScheme('AT', 'government'));

    }

    public function test_de_steur_nummer_registration()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 276;
        // $client->vat_number = 'DE123456789';
        $client->id_number = '12/345/67890';
        $client->classification = 'individual';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('DE:STNR', $storecove->router->resolveRouting('DE', 'individual'));

    }

    public function test_de_business_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 276;
        $client->vat_number = 'DE123456789';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('DE:VAT', $storecove->router->resolveRouting('DE', 'business'));

    }

    public function test_de_gov_client_routing_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 276;
        $client->vat_number = 'DE123456789';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('DE:LWID', $storecove->router->resolveRouting('DE', 'government'));

    }

    public function test_de_business_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 276;
        $client->vat_number = 'DE123456789';
        $client->classification = 'business';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals('DE:VAT', $storecove->router->resolveTaxScheme('DE', 'business'));

    }

    public function test_de_gov_client_tax_identifier()
    {
        $invoice = $this->buildData();

        $client = $invoice->client;
        $client->country_id = 276;
        $client->vat_number = 'DE123456789';
        $client->classification = 'government';
        $client->save();

        $storecove = new Storecove;
        $storecove->router->setInvoice($invoice->fresh());

        $this->assertEquals(false, $storecove->router->resolveTaxScheme('DE', 'government'));

    }
}
