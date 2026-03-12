<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Feature\Export;

use App\Utils\Traits\MakesHash;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\MockAccountData;
use Tests\TestCase;

class ClientCsvTest extends TestCase
{
    use MakesHash;
    use MockAccountData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );

        $this->makeTestData();

        $this->withoutExceptionHandling();
    }

    public function test_recurring_invoice_export_csv()
    {
        $data = [
            'date_range' => 'this_year',
            'report_keys' => [],
            'send_email' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/reports/recurring_invoices', $data);

        $response->assertStatus(200);
    }

    public function test_vendor_export_csv()
    {
        $data = [
            'date_range' => 'this_year',
            'report_keys' => [],
            'send_email' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/reports/vendors', $data);

        $response->assertStatus(200);
    }

    public function test_purchase_order_export_csv()
    {
        $data = [
            'date_range' => 'this_year',
            'report_keys' => [],
            'send_email' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/reports/purchase_orders', $data);

        $response->assertStatus(200);
    }

    public function test_client_export_csv()
    {
        $data = [
            'date_range' => 'this_year',
            'report_keys' => [],
            'send_email' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/reports/clients', $data);

        $response->assertStatus(200);
    }

    public function test_contact_export_csv()
    {
        $data = [
            'date_range' => 'this_year',
            'report_keys' => [],
            'send_email' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/reports/contacts', $data);

        $response->assertStatus(200);
    }

    public function test_activity_export_csv()
    {
        $data = [
            'date_range' => 'this_year',
            'report_keys' => [],
            'send_email' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/reports/activities', $data);

        $response->assertStatus(200);
    }
}
