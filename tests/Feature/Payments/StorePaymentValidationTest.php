<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Feature\Payments;

use App\Utils\Traits\MakesHash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Session;
use Tests\MockAccountData;
use Tests\TestCase;

class StorePaymentValidationTest extends TestCase
{
    use DatabaseTransactions;
    use MakesHash;
    use MockAccountData;

    protected function setUp(): void
    {
        parent::setUp();

        Session::start();
        Model::reguard();

        $this->makeTestData();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );
    }

    public function test_numeric_parse()
    {
        $this->assertFalse(is_numeric('2760.0,139.14'));
    }

    public function test_no_amount_given()
    {
        $data = [
            // 'amount' => 0,
            'client_id' => $this->client->hashed_id,
            'invoices' => [
                [
                    'invoice_id' => $this->invoice->hashed_id,
                    'amount' => 10,
                ],
            ],
            'credits' => [
                [
                    'credit_id' => $this->credit->hashed_id,
                    'amount' => 5,
                ],
            ],
            'date' => '2019/12/12',
        ];

        $response = false;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments/', $data);

        $response->assertStatus(200);
    }

    public function test_in_valid_payment_amount()
    {
        $data = [
            'amount' => '10,33',
            'client_id' => $this->client->hashed_id,
            'invoices' => [
            ],
            'date' => '2019/12/12',
        ];

        $response = false;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments/', $data);

        $response->assertStatus(422);
    }

    public function test_valid_payment()
    {
        $data = [
            'amount' => 0,
            'client_id' => $this->client->hashed_id,
            'invoices' => [
            ],
            'date' => '2019/12/12',
        ];

        $response = false;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments/', $data);

        $response->assertStatus(200);
    }

    public function test_valid_payment_with_amount()
    {
        $data = [
            'amount' => 0,
            'client_id' => $this->client->hashed_id,
            'invoices' => [
                [
                    'invoice_id' => $this->invoice->hashed_id,
                    'amount' => 10,
                ],
            ],
            'credits' => [
                [
                    'credit_id' => $this->credit->hashed_id,
                    'amount' => 5,
                ],
            ],
            'date' => '2019/12/12',
        ];

        $response = false;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments/', $data);

        $response->assertStatus(200);
    }

    public function test_valid_payment_with_invalid_data()
    {
        $data = [
            'amount' => 0,
            'client_id' => $this->client->hashed_id,
            'invoices' => [
                [
                    'invoice_id' => $this->invoice->hashed_id,
                ],
            ],
            'credits' => [
                [
                    'credit_id' => $this->credit->hashed_id,
                    'amount' => 5,
                ],
            ],
            'date' => '2019/12/12',
        ];

        $response = false;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments/', $data);

        $response->assertStatus(422);
    }
}
