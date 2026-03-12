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

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\MockAccountData;
use Tests\TestCase;

class ActivityApiTest extends TestCase
{
    use DatabaseTransactions;
    use MockAccountData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTestData();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );

        $this->withoutExceptionHandling();

    }

    public function test_activity_invoice_notes()
    {
        $data = [
            'entity' => 'invoices',
            'entity_id' => $this->invoice->hashed_id,
            'notes' => 'These are notes',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/notes', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('These are notes', $arr['data']['notes']);
    }

    public function test_activity_credit_notes()
    {
        $data = [
            'entity' => 'credits',
            'entity_id' => $this->credit->hashed_id,
            'notes' => 'These are notes',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/notes', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('These are notes', $arr['data']['notes']);
    }

    public function test_activity_quote_notes()
    {
        $data = [
            'entity' => 'quotes',
            'entity_id' => $this->quote->hashed_id,
            'notes' => 'These are notes',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/notes', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('These are notes', $arr['data']['notes']);
    }

    public function test_activity_client_notes()
    {
        $data = [
            'entity' => 'clients',
            'entity_id' => $this->client->hashed_id,
            'notes' => 'These are notes',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/notes', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('These are notes', $arr['data']['notes']);
    }

    public function test_activity_recurring_invoice_notes()
    {
        $data = [
            'entity' => 'recurring_invoices',
            'entity_id' => $this->recurring_invoice->hashed_id,
            'notes' => 'These are notes',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/notes', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('These are notes', $arr['data']['notes']);
    }

    public function test_activity_expense_notes()
    {
        $data = [
            'entity' => 'expenses',
            'entity_id' => $this->expense->hashed_id,
            'notes' => 'These are notes',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/notes', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('These are notes', $arr['data']['notes']);
    }

    public function test_activity_recurring_expense_notes()
    {
        $data = [
            'entity' => 'recurring_expenses',
            'entity_id' => $this->recurring_expense->hashed_id,
            'notes' => 'These are notes',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/notes', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('These are notes', $arr['data']['notes']);
    }

    public function test_activity_vendor_notes()
    {
        $data = [
            'entity' => 'vendors',
            'entity_id' => $this->vendor->hashed_id,
            'notes' => 'These are notes',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/notes', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('These are notes', $arr['data']['notes']);
    }

    public function test_activity_purchase_order_notes()
    {
        $data = [
            'entity' => 'purchase_orders',
            'entity_id' => $this->purchase_order->hashed_id,
            'notes' => 'These are notes',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/notes', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('These are notes', $arr['data']['notes']);
    }

    public function test_activity_task_notes()
    {
        $data = [
            'entity' => 'tasks',
            'entity_id' => $this->task->hashed_id,
            'notes' => 'These are notes',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/notes', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('These are notes', $arr['data']['notes']);
    }

    public function test_activity_project_notes()
    {
        $data = [
            'entity' => 'projects',
            'entity_id' => $this->project->hashed_id,
            'notes' => 'These are notes',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/notes', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('These are notes', $arr['data']['notes']);
    }

    public function test_activity_payment_notes()
    {
        $data = [
            'entity' => 'payments',
            'entity_id' => $this->payment->hashed_id,
            'notes' => 'These are notes',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/notes', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('These are notes', $arr['data']['notes']);
    }

    public function test_activity_entity()
    {

        $invoice = $this->company->invoices()->first();

        $invoice->service()->markSent()->markPaid()->markDeleted()->handleRestore()->save();

        $data = [
            'entity' => 'invoice',
            'entity_id' => $invoice->hashed_id,
        ];

        $response = false;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/activities/entity', $data);

        $response->assertStatus(200);

    }

    public function test_activity_get()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/activities/');

        $response->assertStatus(200);
    }

    public function test_activity_get_with_react()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/activities?react=true');

        $response->assertStatus(200);
    }
}
