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

use App\Factory\PaymentTermFactory;
use App\Models\PaymentTerm;
use App\Utils\Traits\MakesHash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Session;
use Tests\MockAccountData;
use Tests\TestCase;

/**
 *  App\Http\Controllers\PaymentTermController
 */
class PaymentTermsApiTest extends TestCase
{
    use DatabaseTransactions;
    use MakesHash;
    use MockAccountData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTestData();

        Session::start();
        Model::reguard();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );
    }

    public function test_payment_terms_get_with_filter()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/payment_terms?filter=hey');

        $response->assertStatus(200);
    }

    public function test_payment_terms_get()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/payment_terms');

        $response->assertStatus(200);
    }

    public function test_payment_terms_get_status_active()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/payment_terms?status=active');

        $response->assertStatus(200);
    }

    public function test_payment_terms_get_status_archived()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/payment_terms?status=archived');

        $response->assertStatus(200);
    }

    public function test_payment_terms_get_status_deleted()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/payment_terms?status=deleted');

        $response->assertStatus(200);
    }

    public function test_post_payment_term()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/payment_terms', ['num_days' => 50]);

        $response->assertStatus(200);

        $data = $response->json();

        $this->hashed_id = $data['data']['id'];
    }

    public function test_put_payment_terms()
    {
        $payment_term = PaymentTermFactory::create($this->company->id, $this->user->id);
        $payment_term->num_days = 500;
        $payment_term->save();

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->put('/api/v1/payment_terms/' . $this->encodePrimaryKey($payment_term->id), ['num_days' => 5000]);

        $response->assertStatus(200);
    }

    public function test_delete_payment_term()
    {
        $payment_term = PaymentTermFactory::create($this->company->id, $this->user->id);
        $payment_term->num_days = 500;
        $payment_term->save();

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->delete('/api/v1/payment_terms/' . $this->encodePrimaryKey($payment_term->id));

        $response->assertStatus(200);

        $payment_term = PaymentTerm::find($payment_term->id);

        $this->assertNull($payment_term);
    }
}
