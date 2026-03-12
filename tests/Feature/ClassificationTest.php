<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Feature;

use App\Utils\Traits\MakesHash;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MockUnitData;
use Tests\TestCase;

class ClassificationTest extends TestCase
{
    use DatabaseTransactions;
    use MakesHash;
    use MockUnitData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->makeTestData();

    }

    public function test_client_classification()
    {
        $data = [
            'name' => 'Personal Company',
            'classification' => 'individual',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/clients', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('individual', $arr['data']['classification']);
    }

    public function test_validation_classification()
    {
        $data = [
            'name' => 'Personal Company',
            'classification' => 'this_is_not_validated',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/clients', $data);

        $response->assertStatus(422);

    }

    public function test_validation2_classification()
    {
        $this->client->classification = 'business';

        nlog($this->client->toArray());
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson('/api/v1/clients/' . $this->client->hashed_id, $this->client->toArray());

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('business', $arr['data']['classification']);
    }

    public function test_validation3_classification()
    {
        $this->client->classification = 'this_is_not_validated';

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson('/api/v1/clients/' . $this->client->hashed_id, $this->client->toArray());

        $response->assertStatus(422);

    }

    public function test_vendor_classification()
    {
        $data = [
            'name' => 'Personal Company',
            'classification' => 'individual',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/vendors', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('individual', $arr['data']['classification']);
    }

    public function test_vendor_validation_classification()
    {
        $data = [
            'name' => 'Personal Company',
            'classification' => 'this_is_not_validated',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/vendors', $data);

        $response->assertStatus(422);

    }

    public function test_vendor_validation2_classification()
    {
        $this->vendor->classification = 'company';

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson('/api/v1/vendors/' . $this->vendor->hashed_id, $this->vendor->toArray());

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('company', $arr['data']['classification']);
    }

    public function test_vendor_validation3_classification()
    {
        $this->vendor->classification = 'this_is_not_validated';

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson('/api/v1/vendors/' . $this->vendor->hashed_id, $this->vendor->toArray());

        $response->assertStatus(422);

    }

    public function test_company_classification()
    {
        $settings = $this->company->settings;
        $settings->classification = 'company';

        $this->company->settings = $settings;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson('/api/v1/companies/' . $this->company->hashed_id, $this->company->toArray());

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('company', $arr['data']['settings']['classification']);
    }

    public function test_company_validation_classification()
    {
        $settings = $this->company->settings;
        $settings->classification = 545454;

        $this->company->settings = $settings;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson('/api/v1/companies/' . $this->company->hashed_id, $this->company->toArray());

        $response->assertStatus(422);

    }

    public function test_company_validation2_classification()
    {
        $settings = $this->company->settings;
        $settings->classification = null;

        $this->company->settings = $settings;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson('/api/v1/companies/' . $this->company->hashed_id, $this->company->toArray());

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('', $arr['data']['settings']['classification']);
    }
}
