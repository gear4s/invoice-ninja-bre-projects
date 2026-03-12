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

class ReportApiTest extends TestCase
{
    use MakesHash;
    use MockAccountData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(
            ThrottleRequests::class
        );

        // $this->withoutExceptionHandling();
        $this->makeTestData();

    }

    public function test_activity_csv_export()
    {
        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/activities', $data)
            ->assertStatus(200);

    }

    public function test_user_sales_report_api_route()
    {
        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/user_sales_report', $data)
            ->assertStatus(200);

    }

    public function test_tax_summary_report_api_route()
    {
        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/tax_summary_report', $data)
            ->assertStatus(200);

    }

    public function test_client_sales_report_api_route()
    {
        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/client_sales_report', $data)
            ->assertStatus(200);

    }

    public function test_ar_detail_report_api_route()
    {
        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/ar_detail_report', $data)
            ->assertStatus(200);

    }

    public function test_ar_summary_report_api_route()
    {
        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/ar_summary_report', $data)
            ->assertStatus(200);

    }

    public function test_client_balance_report_api_route()
    {
        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/client_balance_report', $data)
            ->assertStatus(200);

    }
}
