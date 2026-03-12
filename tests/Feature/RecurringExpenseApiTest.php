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

use App\Jobs\Cron\RecurringExpensesCron;
use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\RecurringInvoice;
use App\Utils\Traits\MakesHash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\MockAccountData;
use Tests\TestCase;

/**
 *  App\Http\Controllers\RecurringExpenseController
 */
class RecurringExpenseApiTest extends TestCase
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
    }

    public function test_recurring_expense_generation_with_currency_conversion()
    {
        $r = RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'amount' => 100,
            'number' => Str::random(10),
            'frequency_id' => 5,
            'remaining_cycles' => -1,
            'status_id' => RecurringInvoice::STATUS_ACTIVE,
            'date' => now()->format('Y-m-d'),
            'currency_id' => 1,
            'next_send_date' => now(),
            'next_send_date_client' => now(),
            'invoice_currency_id' => 2,
            'foreign_amount' => 50,
        ]);

        (new RecurringExpensesCron)->handle();

        $expense = Expense::where('recurring_expense_id', $r->id)->orderBy('id', 'desc')->first();

        $this->assertEquals($r->amount, $expense->amount);
        $this->assertEquals($r->currency_id, $expense->currency_id);
        $this->assertEquals($r->invoice_currency_id, $expense->invoice_currency_id);
        $this->assertEquals($r->foreign_amount, $expense->foreign_amount);

    }

    public function test_recurring_expense_generation_null_foreign_currency()
    {
        $r = RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'amount' => 100,
            'number' => Str::random(10),
            'frequency_id' => 5,
            'remaining_cycles' => -1,
            'status_id' => RecurringInvoice::STATUS_ACTIVE,
            'date' => now()->format('Y-m-d'),
            'currency_id' => 1,
            'next_send_date' => now(),
            'next_send_date_client' => now(),
            'invoice_currency_id' => null,
        ]);

        (new RecurringExpensesCron)->handle();

        $expense = Expense::where('recurring_expense_id', $r->id)->orderBy('id', 'desc')->first();

        $this->assertEquals($r->amount, $expense->amount);
        $this->assertEquals($r->currency_id, $expense->currency_id);
        $this->assertEquals($r->invoice_currency_id, $expense->invoice_currency_id);

    }

    public function test_recurring_expense_generation()
    {
        $r = RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'amount' => 100,
            'number' => Str::random(10),
            'frequency_id' => 5,
            'remaining_cycles' => -1,
            'status_id' => RecurringInvoice::STATUS_ACTIVE,
            'date' => now()->format('Y-m-d'),
            'currency_id' => 1,
            'next_send_date' => now(),
            'next_send_date_client' => now(),
        ]);

        (new RecurringExpensesCron)->handle();

        $expense = Expense::where('recurring_expense_id', $r->id)->orderBy('id', 'desc')->first();

        $this->assertEquals($r->amount, $expense->amount);
        $this->assertEquals($r->currency_id, $expense->currency_id);

    }

    public function test_recurring_expense_validation()
    {
        $data = [
            'amount' => 10,
            'client_id' => $this->client->hashed_id,
            'number' => '123321',
            'frequency_id' => 5,
            'remaining_cycles' => 5,
            'currency_id' => 34545435425,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/recurring_expenses?start=true', $data);

        $response->assertStatus(422);

    }

    public function test_recurring_expense_validation2()
    {
        $data = [
            'amount' => 10,
            'client_id' => $this->client->hashed_id,
            'number' => '123321',
            'frequency_id' => 5,
            'remaining_cycles' => 5,
            'currency_id' => 1,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/recurring_expenses?start=true', $data);

        $response->assertStatus(200);

    }

    public function test_recurring_expense_validation3()
    {
        $data = [
            'amount' => 10,
            'client_id' => $this->client->hashed_id,
            'number' => '123321',
            'frequency_id' => 5,
            'remaining_cycles' => 5,
            'currency_id' => null,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/recurring_expenses?start=true', $data);

        $data = $response->json();

        $response->assertStatus(200);

        $this->assertEquals(1, $data['data']['currency_id']);

    }

    public function test_recurring_expense_validation4()
    {
        $data = [
            'amount' => 10,
            'client_id' => $this->client->hashed_id,
            'number' => '123321',
            'frequency_id' => 5,
            'remaining_cycles' => 5,
            'currency_id' => '',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/recurring_expenses?start=true', $data);

        $data = $response->json();

        $response->assertStatus(200);

        $this->assertEquals(1, $data['data']['currency_id']);

    }

    public function test_recurring_expense_get_filtered()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/recurring_expenses?filter=xx');

        $response->assertStatus(200);
    }

    public function test_recurring_expense_get()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/recurring_expenses/');

        $response->assertStatus(200);
    }

    public function test_recurring_expense_get_single_expense()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/recurring_expenses/' . $this->recurring_expense->hashed_id);

        $response->assertStatus(200);
    }

    public function test_recurring_expense_post()
    {
        $data = [
            'amount' => 10,
            'client_id' => $this->client->hashed_id,
            'number' => '123321',
            'frequency_id' => 5,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/recurring_expenses', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->put('/api/v1/recurring_expenses/' . $arr['data']['id'], $data)->assertStatus(200);

        try {
            $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $this->token,
            ])->post('/api/v1/recurring_expenses', $data);
        } catch (ValidationException $e) {
            $response->assertStatus(302);
        }
    }

    public function test_recurring_expense_put()
    {
        $data = [
            'amount' => 20,
            'public_notes' => 'Coolio',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->put('/api/v1/recurring_expenses/' . $this->encodePrimaryKey($this->recurring_expense->id), $data);

        $response->assertStatus(200);
    }

    public function test_recurring_expense_not_archived()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/recurring_expenses/' . $this->encodePrimaryKey($this->recurring_expense->id));

        $arr = $response->json();

        $this->assertEquals(0, $arr['data']['archived_at']);
    }

    public function test_recurring_expense_archived()
    {
        $data = [
            'ids' => [$this->encodePrimaryKey($this->recurring_expense->id)],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/recurring_expenses/bulk?action=archive', $data);

        $arr = $response->json();

        $this->assertNotNull($arr['data'][0]['archived_at']);
    }

    public function test_recurring_expense_restored()
    {
        $data = [
            'ids' => [$this->encodePrimaryKey($this->recurring_expense->id)],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/recurring_expenses/bulk?action=restore', $data);

        $arr = $response->json();

        $this->assertEquals(0, $arr['data'][0]['archived_at']);
    }

    public function test_recurring_expense_deleted()
    {
        $data = [
            'ids' => [$this->encodePrimaryKey($this->recurring_expense->id)],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/recurring_expenses/bulk?action=delete', $data);

        $arr = $response->json();

        $this->assertTrue($arr['data'][0]['is_deleted']);
    }

    public function test_recurring_expense_start()
    {
        $data = [
            'ids' => [$this->encodePrimaryKey($this->recurring_expense->id)],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/recurring_expenses/bulk?action=start', $data);

        $arr = $response->json();

        $this->assertEquals(RecurringInvoice::STATUS_ACTIVE, $arr['data'][0]['status_id']);
    }

    public function test_recurring_expense_paused()
    {
        $data = [
            'ids' => [$this->encodePrimaryKey($this->recurring_expense->id)],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/recurring_expenses/bulk?action=start', $data);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/recurring_expenses/bulk?action=stop', $data);

        $arr = $response->json();

        $this->assertEquals(RecurringInvoice::STATUS_PAUSED, $arr['data'][0]['status_id']);
    }

    public function test_recurring_expense_started_with_triggered_action()
    {
        $data = [
            'ids' => [$this->encodePrimaryKey($this->recurring_expense->id)],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->put('/api/v1/recurring_expenses/' . $this->recurring_expense->hashed_id . '?start=true', []);

        $arr = $response->json();

        $this->assertEquals(RecurringInvoice::STATUS_ACTIVE, $arr['data']['status_id']);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->put('/api/v1/recurring_expenses/' . $this->recurring_expense->hashed_id . '?stop=true', []);

        $arr = $response->json();

        $this->assertEquals(RecurringInvoice::STATUS_PAUSED, $arr['data']['status_id']);
    }

    public function test_recurring_expense_post_with_start_action()
    {
        $data = [
            'amount' => 10,
            'client_id' => $this->client->hashed_id,
            'number' => '123321',
            'frequency_id' => 5,
            'remaining_cycles' => 5,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/recurring_expenses?start=true', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals(RecurringInvoice::STATUS_ACTIVE, $arr['data']['status_id']);
    }

    public function test_recurring_expense_post_with_stop_action()
    {
        $data = [
            'amount' => 10,
            'client_id' => $this->client->hashed_id,
            'number' => '1233x21',
            'frequency_id' => 5,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/recurring_expenses?stop=true', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals(RecurringInvoice::STATUS_PAUSED, $arr['data']['status_id']);
    }
}
