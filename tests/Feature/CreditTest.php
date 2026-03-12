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

use App\DataMapper\InvoiceItem;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Credit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Repositories\CreditRepository;
use App\Repositories\InvoiceRepository;
use App\Utils\Traits\MakesHash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Session;
use Tests\MockAccountData;
use Tests\TestCase;

class CreditTest extends TestCase
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
    }

    public function test_credit_e_invoice_validation()
    {

        $credit_update = [
            'e_invoice' => [
                'CreditNote' => [
                    'BillingReference' => [
                        [
                            'InvoiceDocumentReference' => [
                                'ID' => '',
                                'IssueDate' => '',
                            ],
                        ],
                    ],
                ],
            ],

        ];

        $data = array_merge($this->credit->toArray(), $credit_update);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson('/api/v1/credits/' . $this->encodePrimaryKey($this->credit->id), $data);

        $response->assertStatus(422);
    }

    public function test_credit_e_invoice_validation_with_proper_number()
    {

        $credit_update = [
            'e_invoice' => [
                'CreditNote' => [
                    'BillingReference' => [
                        [
                            'InvoiceDocumentReference' => [
                                'ID' => 'INV-123456S',
                                'IssueDate' => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $data = array_merge($this->credit->toArray(), $credit_update);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson('/api/v1/credits/' . $this->encodePrimaryKey($this->credit->id), $data);

        $response->assertStatus(200);
    }

    public function test_credit_e_invoice_validation_with_proper_date()
    {

        $credit_update = [
            'e_invoice' => [
                'CreditNote' => [
                    'BillingReference' => [
                        [
                            'InvoiceDocumentReference' => [
                                'ID' => 'INV-123456S',
                                'IssueDate' => '2026-01-18',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $data = array_merge($this->credit->toArray(), $credit_update);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson('/api/v1/credits/' . $this->encodePrimaryKey($this->credit->id), $data);

        $response->assertStatus(200);
    }

    public function test_credit_e_invoice_validation_with_incorrect_date()
    {

        $credit_update = [
            'e_invoice' => [
                'CreditNote' => [
                    'BillingReference' => [
                        [
                            'InvoiceDocumentReference' => [
                                'ID' => 'INV-123456S',
                                'IssueDate' => '203326-01-118',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $data = array_merge($this->credit->toArray(), $credit_update);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson('/api/v1/credits/' . $this->encodePrimaryKey($this->credit->id), $data);

        $response->assertStatus(422);
    }

    public function test_credit_e_invoice_validation_with_incorrect_date_but_passes_validation()
    {

        $credit_update = [
            'e_invoice' => [
                'CreditNote' => [
                    'BillingReference' => [
                        [
                            'InvoiceDocumentReference' => [
                                'ID' => 'INV-123456S',
                                'IssueDate' => '3000-01-11',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $data = array_merge($this->credit->toArray(), $credit_update);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->putJson('/api/v1/credits/' . $this->encodePrimaryKey($this->credit->id), $data);

        $response->assertStatus(200);
    }

    public function test_credit_deletion_after_invoice_reversal_and_payment_refund()
    {
        $c = Client::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'balance' => 0,
            'paid_to_date' => 0,
        ]);

        $ii = new InvoiceItem;
        $ii->cost = 100;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $i = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $c->id,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'discount' => 0,
            'line_items' => [
                $ii,
            ],
            'status_id' => 1,
        ]);

        $repo = new InvoiceRepository;
        $repo->save([], $i);

        $i = $i->calc()->getInvoice();
        $i = $i->service()->markPaid()->save(); // paid

        $payment = $i->payments()->first();

        $this->assertNotNull($payment);

        $this->assertEquals(0, $i->balance);
        $this->assertEquals(100, $i->amount);

        $credit_array = $i->withoutRelations()->toArray();
        $credit_array['invoice_id'] = $i->hashed_id;
        $credit_array['client_id'] = $c->hashed_id;
        unset($credit_array['backup']);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/credits', $credit_array);

        $response->assertStatus(200); // reversal - credit created.

        $arr = $response->json();
        $credit = Credit::find($this->decodePrimaryKey($arr['data']['id']));

        $this->assertNotNull($credit);
        $payment = $payment->fresh();

        $i = $i->fresh();

        $this->assertEquals(Invoice::STATUS_REVERSED, $i->status_id);

        $client = $i->client;

        $this->assertEquals(100, $client->credit_balance);
        $this->assertEquals(0, $client->paid_to_date);
        $this->assertEquals(0, $client->balance);

        // delete the credit!!

        $data = [
            'ids' => [$credit->hashed_id],
            'action' => 'delete',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits/bulk', $data);

        $response->assertStatus(200);

        $payment = $payment->fresh();

        $this->assertEquals($payment->amount, $payment->refunded);
        $this->assertEquals(Payment::STATUS_REFUNDED, $payment->status_id);
        $this->assertTrue($payment->paymentables()->where('paymentable_type', Credit::class)->where('paymentable_id', $credit->id)->exists());

        // lets restore the credit!!
        $data = [
            'ids' => [$credit->hashed_id],
            'action' => 'restore',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits/bulk', $data);

        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertEquals(0, $payment->refunded);
        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->status_id);
        $this->assertFalse($payment->paymentables()->where('paymentable_type', Credit::class)->where('paymentable_id', $credit->id)->exists());

    }

    public function test_invoice_with_multiple_payments_and_single_credit_deletion_post_invoice_reversal()
    {
        $c = Client::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'balance' => 0,
            'paid_to_date' => 0,
        ]);

        $ii = new InvoiceItem;
        $ii->cost = 100;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $i = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $c->id,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'discount' => 0,
            'line_items' => [
                $ii,
            ],
            'status_id' => 1,
        ]);

        $repo = new InvoiceRepository;
        $repo->save([], $i);

        $i = $i->calc()->getInvoice();

        $data = [
            'date' => now()->format('Y-m-d'),
            'client_id' => $c->hashed_id,
            'invoices' => [
                [
                    'invoice_id' => $i->hashed_id,
                    'amount' => 10,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments', $data);

        $response->assertStatus(200);

        sleep(1);
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments', $data);
        sleep(1);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments', $data);
        sleep(1);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments', $data);
        sleep(1);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments', $data);
        sleep(1);

        $response->assertStatus(200);

        // At this stage we have 5 payments for half of the invoice amount.
        // create the reversal invoice for half of the invoice.

        $credit_array = $i->withoutRelations()->toArray();
        $credit_array['invoice_id'] = $i->hashed_id;
        $credit_array['client_id'] = $c->hashed_id;

        $ii = new InvoiceItem;
        $ii->cost = 50;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $credit_array['line_items'] = [$ii];
        unset($credit_array['backup']);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/credits', $credit_array);

        $response->assertStatus(200); // reversal - credit created.

        $arr = $response->json();

        $this->assertEquals(50, $arr['data']['balance']);
        $this->assertEquals(50, $arr['data']['amount']);
        $this->assertEquals($i->hashed_id, $arr['data']['invoice_id']);
        $this->assertEquals(Credit::STATUS_SENT, $arr['data']['status_id']);

        $credit = Credit::withTrashed()->find($this->decodePrimaryKey($arr['data']['id']));

        // delete the credit!!
        $data = [
            'ids' => [$arr['data']['id']],
            'action' => 'delete',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits/bulk', $data);

        $response->assertStatus(200);

        $invoice = $i->fresh();

        $invoice->payments()->each(function ($payment) use ($credit) {

            $this->assertEquals($payment->amount, $payment->refunded);
            $this->assertEquals(Payment::STATUS_REFUNDED, $payment->status_id);
            $this->assertTrue($payment->paymentables()->where('paymentable_type', Credit::class)->where('paymentable_id', $credit->id)->exists());

        });

        $client = $invoice->fresh()->client->fresh();

        $this->assertEquals(0, $client->credit_balance);
        $this->assertEquals(0, $client->paid_to_date);
        $this->assertEquals(0, $client->balance);

        // restore the credit!!

        $data = [
            'ids' => [$arr['data']['id']],
            'action' => 'restore',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits/bulk', $data);

        $response->assertStatus(200);

        $invoice = $i->fresh();

        $invoice->payments()->each(function ($payment) use ($credit) {

            $this->assertEquals(0, $payment->refunded);
            $this->assertEquals(Payment::STATUS_COMPLETED, $payment->status_id);
            $this->assertFalse($payment->paymentables()->where('paymentable_type', Credit::class)->where('paymentable_id', $credit->id)->exists());

        });

        $client = $invoice->fresh()->client->fresh();

        $this->assertEquals(50, $client->credit_balance);
        $this->assertEquals(0, $client->paid_to_date);
        $this->assertEquals(0, $client->balance);

    }

    public function test_client_paid_to_date_state_after_credit_created_for_paid_invoice()
    {
        $c = Client::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'balance' => 0,
            'paid_to_date' => 0,
        ]);

        $ii = new InvoiceItem;
        $ii->cost = 100;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $i = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $c->id,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'discount' => 0,
            'line_items' => [
                $ii,
            ],
            'status_id' => 1,
        ]);

        $repo = new InvoiceRepository;
        $repo->save([], $i);

        $i = $i->calc()->getInvoice();
        $i = $i->service()->markPaid()->save(); // paid

        $payment = $i->payments()->first();

        $this->assertNotNull($payment);

        $this->assertEquals(0, $i->balance);
        $this->assertEquals(100, $i->amount);

        $credit_array = $i->withoutRelations()->toArray();
        $credit_array['invoice_id'] = $i->hashed_id;
        $credit_array['client_id'] = $c->hashed_id;
        unset($credit_array['backup']);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/credits', $credit_array);

        $response->assertStatus(200); // reversal - credit created.

        $arr = $response->json();
        $credit = Credit::find($this->decodePrimaryKey($arr['data']['id']));

        $this->assertNotNull($credit);
        $payment = $payment->fresh();

        $i = $i->fresh();

        $this->assertEquals(Invoice::STATUS_REVERSED, $i->status_id);
        // $this->assertTrue($payment->credits()->exists());

        $client = $i->client;

        $this->assertEquals(100, $client->credit_balance);
        $this->assertEquals(0, $client->paid_to_date);
        $this->assertEquals(0, $client->balance);

    }

    public function test_new_credit_deletion_after_invoice_reversal_and_payment_refund()
    {
        $c = Client::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'balance' => 0,
            'paid_to_date' => 0,
        ]);

        $ii = new InvoiceItem;
        $ii->cost = 100;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $i = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $c->id,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'discount' => 0,
            'line_items' => [
                $ii,
            ],
            'status_id' => 1,
        ]);

        $repo = new InvoiceRepository;
        $repo->save([], $i);

        $i = $i->calc()->getInvoice();
        $i = $i->service()->markPaid()->save(); // paid

        $payment = $i->payments()->first();

        $this->assertNotNull($payment);

        $this->assertEquals(0, $i->balance);
        $this->assertEquals(100, $i->amount);

        $credit_array = $i->withoutRelations()->toArray();
        $credit_array['invoice_id'] = $i->hashed_id;
        $credit_array['client_id'] = $c->hashed_id;
        unset($credit_array['backup']);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/credits', $credit_array);

        $response->assertStatus(200); // reversal - credit created.

        $arr = $response->json();
        $credit = Credit::find($this->decodePrimaryKey($arr['data']['id']));

        $this->assertNotNull($credit);
        $payment = $payment->fresh();

        $i = $i->fresh();

        $this->assertEquals(Invoice::STATUS_REVERSED, $i->status_id);
        // $this->assertTrue($payment->credits()->exists());

        $client = $i->client;

        $this->assertEquals(100, $client->credit_balance);

        $refund_payload = [
            'id' => $payment->hashed_id,
            'amount' => 100,
            'date' => '2020/12/12',

            'invoices' => [
                [
                    'invoice_id' => $i->hashed_id,
                    'amount' => 100,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments/refund', $refund_payload);

        $response->assertStatus(200);

        $credit = $credit->fresh();
        $payment = $payment->fresh();
        $client = $client->fresh();

        $this->assertEquals(100, $payment->refunded);
        $this->assertEquals(Payment::STATUS_REFUNDED, $payment->status_id);
        $this->assertEquals(0, $credit->balance);
        $this->assertEquals(Credit::STATUS_APPLIED, $credit->status_id);

        $this->assertEquals(0, $client->paid_to_date);
        $this->assertEquals(0, $client->balance);
        $this->assertEquals(0, $client->credit_balance);

        $payment->service()->deletePayment()->save();

        $payment = $payment->fresh();
        $client = $client->fresh();
        $credit = $credit->fresh();

        $this->assertEquals(1, $payment->is_deleted);
        $this->assertEquals(0, $client->credit_balance);
        $this->assertEquals(0, $client->paid_to_date);
        $this->assertEquals(0, $client->balance);
        $this->assertEquals(0, $credit->balance);

        $this->assertEquals(Credit::STATUS_APPLIED, $credit->status_id);
    }

    public function test_new_credit_deletion_after_invoice_reversal()
    {
        $c = Client::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'balance' => 0,
            'paid_to_date' => 0,
        ]);

        $ii = new InvoiceItem;
        $ii->cost = 100;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $i = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $c->id,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'discount' => 0,
            'line_items' => [
                $ii,
            ],
            'status_id' => 1,
        ]);

        $repo = new InvoiceRepository;
        $repo->save([], $i);

        $i = $i->calc()->getInvoice();
        $i = $i->service()->markPaid()->save();

        $payment = $i->payments()->first();

        $this->assertNotNull($payment);

        $this->assertEquals(0, $i->balance);
        $this->assertEquals(100, $i->amount);

        $credit_array = $i->withoutRelations()->toArray();
        $credit_array['invoice_id'] = $i->hashed_id;
        $credit_array['client_id'] = $c->hashed_id;
        unset($credit_array['backup']);
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/credits', $credit_array);

        $response->assertStatus(200);

        $arr = $response->json();
        $credit = Credit::find($this->decodePrimaryKey($arr['data']['id']));

        $this->assertNotNull($credit);
        $payment = $payment->fresh();

        $i = $i->fresh();

        $this->assertEquals(Invoice::STATUS_REVERSED, $i->status_id);

        $client = $i->client;

        $this->assertEquals(100, $client->credit_balance);

        $payment->service()->deletePayment()->save();

        $credit = $credit->fresh();
        $client = $client->fresh();

        $this->assertEquals(Credit::STATUS_SENT, $credit->status_id);
        $this->assertEquals(100, $client->credit_balance);
        $this->assertEquals(0, $client->balance);
        $this->assertEquals(0, $client->paid_to_date);
        $this->assertEquals(0, $i->balance);
        $this->assertEquals(Invoice::STATUS_REVERSED, $i->status_id);
    }

    public function test_partial_amount_with_partial_credit_and_payment_deleted_balance()
    {

        $c = Client::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'balance' => 0,
            'paid_to_date' => 0,
        ]);

        $ii = new InvoiceItem;
        $ii->cost = 100;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $i = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $c->id,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'discount' => 0,
            'line_items' => [
                $ii,
            ],
            'status_id' => 1,
        ]);

        $repo = new InvoiceRepository;
        $repo->save([], $i);

        $i = $i->calc()->getInvoice();
        $i = $i->service()->markSent()->save();

        $this->assertEquals(100, $i->balance);
        $this->assertEquals(100, $i->amount);

        $cr = Credit::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $c->id,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'discount' => 0,
            'line_items' => [
                $ii,
            ],
            'status_id' => 1,
        ]);

        $repo = new CreditRepository;
        $repo->save([], $cr);

        $cr = $cr->calc()->getInvoice();
        $cr = $cr->service()->markSent()->save();

        $this->assertEquals(100, $cr->balance);
        $this->assertEquals(100, $cr->amount);

        $data = [
            'date' => '2020/12/12',
            'client_id' => $c->hashed_id,
            'amount' => 10,
            'invoices' => [
                [
                    'invoice_id' => $i->hashed_id,
                    'amount' => 10,
                ],
            ],
            'credits' => [
                [
                    'credit_id' => $cr->hashed_id,
                    'amount' => 10,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments', $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals(10, $arr['data']['amount']);

        $this->assertEquals(20, $c->fresh()->paid_to_date);
        $this->assertEquals(90, $i->fresh()->balance);
        $this->assertEquals(90, $cr->fresh()->balance);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->deleteJson('/api/v1/payments/' . $arr['data']['id']);

        $response->assertStatus(200);

        $this->assertEquals(100, $i->fresh()->balance);
        $this->assertEquals(100, $cr->fresh()->balance);
        $this->assertEquals(100, $c->fresh()->balance);
        $this->assertEquals(0, $c->fresh()->paid_to_date);

    }

    public function test_credit_reversal_scenario_invoice_partially_paid()
    {

        $c = Client::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'balance' => 0,
            'paid_to_date' => 0,
        ]);

        $ii = new InvoiceItem;
        $ii->cost = 100;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $i = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $c->id,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'discount' => 0,
            'line_items' => [
                $ii,
            ],
            'status_id' => 1,
        ]);

        $i = $i->calc()->getInvoice();
        $i = $i->service()->markSent()->save();

        $this->assertEquals(100, $i->balance);
        $this->assertEquals(100, $i->amount);

        $i->service()->applyPaymentAmount(50, 'test');
        $i->refresh();

        $this->assertEquals(50, $i->balance);
        $this->assertEquals(100, $i->amount);

        $credit_array = $i->withoutRelations()->toArray();
        $credit_array['invoice_id'] = $i->hashed_id;
        $credit_array['client_id'] = $c->hashed_id;
        unset($credit_array['backup']);

        $ii = new InvoiceItem;
        $ii->cost = 50;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $credit_array['line_items'] = [];
        $credit_array['line_items'][] = (array) $ii;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits', $credit_array);

        $response->assertStatus(200);
        $arr = $response->json();

        $this->assertEquals(50, $arr['data']['balance']);
        $this->assertEquals(50, $arr['data']['amount']);
        $this->assertEquals(2, $arr['data']['status_id']);

        $i->refresh();
        $c->refresh();

        $this->assertEquals(0, $c->balance);
        $this->assertEquals(6, $i->status_id);

    }

    public function test_credit_reversal_scenario_invoice_paid_in_full()
    {

        $c = Client::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'balance' => 0,
            'paid_to_date' => 0,
        ]);

        $ii = new InvoiceItem;
        $ii->cost = 100;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $i = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $c->id,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'discount' => 0,
            'line_items' => [
                $ii,
            ],
            'status_id' => 1,
        ]);

        $i = $i->calc()->getInvoice();
        $i = $i->service()->markSent()->save();

        $this->assertEquals(100, $i->balance);
        $this->assertEquals(100, $i->amount);

        $i->service()->applyPaymentAmount(100, 'test');
        $i->refresh();

        $this->assertEquals(0, $i->balance);
        $this->assertEquals(100, $i->amount);
        $this->assertEquals(4, $i->status_id);

        $credit_array = $i->withoutRelations()->toArray();
        $credit_array['invoice_id'] = $i->hashed_id;
        $credit_array['client_id'] = $c->hashed_id;

        unset($credit_array['backup']);

        $ii = new InvoiceItem;
        $ii->cost = 100;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $credit_array['line_items'] = [];
        $credit_array['line_items'][] = (array) $ii;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits', $credit_array);

        $response->assertStatus(200);
        $arr = $response->json();

        $this->assertEquals(100, $arr['data']['balance']);
        $this->assertEquals(100, $arr['data']['amount']);
        $this->assertEquals(2, $arr['data']['status_id']);

        $i->refresh();
        $c->refresh();

        $this->assertEquals(0, $c->balance);
        $this->assertEquals(6, $i->status_id);
    }

    public function test_paid_to_date_adjustments()
    {

        $c = Client::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'balance' => 0,
        ]);

        $ii = new InvoiceItem;
        $ii->cost = 100;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $i = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $c->id,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'discount' => 0,
            'line_items' => [
                $ii,
            ],
            'status_id' => 1,
        ]);

        $i = $i->calc()->getInvoice();

        $this->assertEquals(0, $i->balance);
        $this->assertEquals(100, $i->amount);

        $i->service()->markSent()->save();

        $this->assertEquals(100, $i->balance);

        $i->service()->markPaid()->save();
        $i = $i->fresh();
        $c = $c->fresh();

        $this->assertEquals(0, $i->balance);
        $this->assertEquals(0, $c->balance);

        $this->assertEquals(100, $c->paid_to_date);

        // $i->service()->handleReversal()->save();

        $data = $i->toArray();
        $data['invoice_id'] = $i->hashed_id;
        $data['user_id'] = $this->encodePrimaryKey($i->user_id);
        $data['client_id'] = $this->encodePrimaryKey($i->client_id);
        $data['status_id'] = 1;

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits?mark_sent=true', $data);

        $response->assertStatus(200);
        $arr = $response->json();

        $cr_id = $arr['data']['id'];

        $i = $i->fresh();
        $c = $c->fresh();

        $credit = $i->credits()->first();

        $this->assertNotNull($credit);

        $this->assertEquals(0, $i->balance);
        $this->assertEquals(100, $c->credit_balance);
        $this->assertEquals(0, $c->paid_to_date);

        $credit->service()->deleteCredit()->save();

        $c = $c->fresh();

        $this->assertEquals(0, $c->paid_to_date);
        $this->assertEquals(0, $c->credit_balance);

        $credit->service()->restoreCredit()->save();

        $c = $c->fresh();

        $this->assertEquals(0, $c->paid_to_date);
        $this->assertEquals(100, $c->credit_balance);

    }

    public function test_credit_payments_paid_to_dates()
    {
        $c = Client::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'balance' => 0,
        ]);

        $ii = new InvoiceItem;
        $ii->cost = 100;
        $ii->quantity = 1;
        $ii->product_key = 'xx';
        $ii->notes = 'yy';

        $i = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $c->id,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'discount' => 0,
            'line_items' => [
                $ii,
            ],
            'status_id' => 1,
        ]);

        $i->save();

        $i->calc()->getInvoice();

        $i->service()->markSent()->save();

        $cr = Credit::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $c->id,
            'tax_name1' => '',
            'tax_name2' => '',
            'tax_name3' => '',
            'tax_rate1' => 0,
            'tax_rate2' => 0,
            'tax_rate3' => 0,
            'discount' => 0,
            'line_items' => [
                $ii,
            ],
            'status_id' => 1,
        ]);

        $cr->calc()->getCredit();
        $cr->service()->markSent()->save();

        $c->refresh();

        $this->assertEquals(100, $i->balance);
        $this->assertEquals(100, $i->amount);
        $this->assertEquals(0, $i->paid_to_date);
        $this->assertEquals(2, $i->status_id);

        $this->assertEquals(100, $cr->balance);
        $this->assertEquals(100, $cr->amount);
        $this->assertEquals(0, $cr->paid_to_date);
        $this->assertEquals(2, $cr->status_id);

        $this->assertEquals(100, $c->balance);
        $this->assertEquals(0, $c->paid_to_date);

        $data = [
            'date' => '2020/12/12',
            'client_id' => $c->hashed_id,
            'invoices' => [
                [
                    'invoice_id' => $i->hashed_id,
                    'amount' => 100,
                ],
            ],
            'credits' => [
                [
                    'credit_id' => $cr->hashed_id,
                    'amount' => 100,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/payments', $data);

        $response->assertStatus(200);
        $arr = $response->json();

        $p_id = $arr['data']['id'];
        $i = $i->fresh();
        $cr = $cr->fresh();
        $c = $c->fresh();

        $this->assertEquals(0, $i->balance);
        $this->assertEquals(100, $i->paid_to_date);
        $this->assertEquals(4, $i->status_id);

        $this->assertEquals(0, $cr->balance);
        $this->assertEquals(100, $cr->paid_to_date);
        $this->assertEquals(4, $i->status_id);

        $this->assertEquals(100, $c->paid_to_date);
        $this->assertEquals(0, $c->balance);

        $p = Payment::find($this->decodePrimaryKey($p_id));

        $this->assertEquals(0, $p->amount);
        $this->assertEquals(0, $p->applied);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->deleteJson("/api/v1/payments/{$p_id}");

        $response->assertStatus(200);

        $i = $i->fresh();
        $cr = $cr->fresh();
        $c = $c->fresh();

        $this->assertEquals(100, $i->balance);
        $this->assertEquals(100, $i->amount);
        $this->assertEquals(0, $i->paid_to_date);
        $this->assertEquals(2, $i->status_id);

        $this->assertEquals(100, $cr->balance);
        $this->assertEquals(100, $cr->amount);
        $this->assertEquals(2, $cr->status_id);
        $this->assertEquals(0, $cr->paid_to_date);

        $this->assertEquals(100, $c->balance);
        $this->assertEquals(0, $c->paid_to_date);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->deleteJson("/api/v1/credits/{$cr->hashed_id}");

        $response->assertStatus(200);

        $cr = $cr->fresh();

        $this->assertEquals(true, $cr->is_deleted);

        $this->assertEquals(100, $c->balance);
        $this->assertEquals(0, $c->paid_to_date);

    }

    public function test_applicable_filters()
    {
        Credit::where('company_id', $this->company->id)->cursor()->each(function ($c) {
            $c->forceDelete();
        });

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/credits');

        $response->assertStatus(200);
        $arr = $response->json();
        $this->assertCount(0, $arr['data']);

        $c = Credit::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status_id' => Credit::STATUS_DRAFT,
            'due_date' => null,
            'date' => now(),
        ]);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/credits');

        $response->assertStatus(200);
        $arr = $response->json();

        $this->assertCount(1, $arr['data']);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/credits?applicable=true');

        $response->assertStatus(200);
        $arr = $response->json();

        $this->assertCount(0, $arr['data']);

        $c->status_id = Credit::STATUS_SENT;
        $c->amount = 20;
        $c->balance = 20;
        $c->save();

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/credits?applicable=true');

        $response->assertStatus(200);
        $arr = $response->json();
        $this->assertCount(1, $arr['data']);

        $c->status_id = Credit::STATUS_SENT;
        $c->amount = 20;
        $c->balance = 20;
        $c->due_date = now()->subYear();
        $c->save();

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/credits?applicable=true');

        $response->assertStatus(200);
        $arr = $response->json();
        $this->assertCount(0, $arr['data']);

        $c->status_id = Credit::STATUS_SENT;
        $c->amount = 20;
        $c->balance = 20;
        $c->due_date = now()->addYear();
        $c->save();

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/credits?applicable=true');

        $response->assertStatus(200);
        $arr = $response->json();
        $this->assertCount(1, $arr['data']);

        $c->status_id = Credit::STATUS_APPLIED;
        $c->amount = 20;
        $c->balance = 20;
        $c->due_date = now()->addYear();
        $c->save();

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/credits?applicable=true');

        $response->assertStatus(200);
        $arr = $response->json();
        $this->assertCount(0, $arr['data']);

    }

    public function test_quote_download_pdf()
    {
        $i = $this->credit->invitations->first();

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get("/api/v1/credit/{$i->key}/download");

        $response->assertStatus(200);
        $this->assertTrue($response->headers->get('content-type') == 'application/pdf');
    }

    public function test_bulk_actions()
    {
        $data = [
            'action' => 'archive',
            'ids' => [$this->credit->hashed_id],
        ];

        $response = $this->withHeaders([
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits/bulk', $data)
            ->assertStatus(200);

        $data = [
            'ids' => [$this->credit->hashed_id],
            'action' => 'restore',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits/bulk', $data)
            ->assertStatus(200);

        $data = [
            'ids' => [$this->credit->hashed_id],
            'action' => 'delete',
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits/bulk', $data)
            ->assertStatus(200);
    }

    public function test_credit_get_client_status()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/credits?client_status=draft' . $this->encodePrimaryKey($this->bank_transaction->id));

        $response->assertStatus(200);
    }

    public function test_credits_list()
    {
        Client::factory()->count(3)->create(['user_id' => $this->user->id, 'company_id' => $this->company->id])->each(function ($c) {
            ClientContact::factory()->create([
                'user_id' => $this->user->id,
                'client_id' => $c->id,
                'company_id' => $this->company->id,
                'is_primary' => 1,
            ]);

            ClientContact::factory()->create([
                'user_id' => $this->user->id,
                'client_id' => $c->id,
                'company_id' => $this->company->id,
            ]);
        });

        $client = Client::all()->first();

        Credit::factory()->create(['user_id' => $this->user->id, 'company_id' => $this->company->id, 'client_id' => $this->client->id]);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/credits');

        $response->assertStatus(200);
    }

    public function test_credit_rest_end_points()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/credits/' . $this->encodePrimaryKey($this->credit->id));

        $response->assertStatus(200);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get('/api/v1/credits/' . $this->encodePrimaryKey($this->credit->id) . '/edit');

        $response->assertStatus(200);

        $credit_update = [
            'tax_name1' => 'dippy',
        ];

        $this->assertNotNull($this->credit);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->put('/api/v1/credits/' . $this->encodePrimaryKey($this->credit->id), $credit_update)
            ->assertStatus(200);
    }

    public function test_post_new_credit()
    {
        $credit = [
            'status_id' => 1,
            'number' => 'dfdfd',
            'discount' => 0,
            'is_amount_discount' => 1,
            'number' => '34343xx43',
            'public_notes' => 'notes',
            'is_deleted' => 0,
            'custom_value1' => 0,
            'custom_value2' => 0,
            'custom_value3' => 0,
            'custom_value4' => 0,
            'status' => 1,
            'client_id' => $this->encodePrimaryKey($this->client->id),
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits/', $credit)
            ->assertStatus(200);
    }

    public function test_delete_credit()
    {
        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->delete('/api/v1/credits/' . $this->encodePrimaryKey($this->credit->id));

        $response->assertStatus(200);
    }

    public function test_duplicate_number_catch()
    {
        $data = [
            'status_id' => 1,
            'number' => 'dfdfd',
            'discount' => 0,
            'is_amount_discount' => 1,
            'number' => '3434343',
            'public_notes' => 'notes',
            'is_deleted' => 0,
            'custom_value1' => 0,
            'custom_value2' => 0,
            'custom_value3' => 0,
            'custom_value4' => 0,
            'status' => 1,
            'client_id' => $this->encodePrimaryKey($this->client->id),
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits', $data);

        $response->assertStatus(200);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits', $data);

        $response->assertStatus(422);
    }

    public function test_credit_put()
    {
        $data = [
            'status_id' => 1,
            'number' => 'dfdfd',
            'discount' => 0,
            'is_amount_discount' => 1,
            'number' => '3434343',
            'public_notes' => 'notes',
            'is_deleted' => 0,
            'custom_value1' => 0,
            'custom_value2' => 0,
            'custom_value3' => 0,
            'custom_value4' => 0,
            'status' => 1,
            'client_id' => $this->encodePrimaryKey($this->client->id),
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->put('/api/v1/credits/' . $this->encodePrimaryKey($this->credit->id), $data);

        $response->assertStatus(200);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->put('/api/v1/credits/' . $this->encodePrimaryKey($this->credit->id), $data);

        $response->assertStatus(200);

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/credits/', $data);

        $response->assertStatus(422);
    }
}
