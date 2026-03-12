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

use App\Export\CSV\ActivityExport;
use App\Export\CSV\ClientExport;
use App\Export\CSV\ContactExport;
use App\Export\CSV\CreditExport;
use App\Export\CSV\DocumentExport;
use App\Export\CSV\ExpenseExport;
use App\Export\CSV\InvoiceExport;
use App\Export\CSV\InvoiceItemExport;
use App\Export\CSV\PaymentExport;
use App\Export\CSV\ProductExport;
use App\Export\CSV\PurchaseOrderExport;
use App\Export\CSV\PurchaseOrderItemExport;
use App\Export\CSV\QuoteExport;
use App\Export\CSV\QuoteItemExport;
use App\Jobs\Report\PreviewReport;
use App\Models\Client;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Utils\Traits\MakesHash;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Tests\MockAccountData;
use Tests\TestCase;

class ReportPreviewTest extends TestCase
{
    use MakesHash;
    use MockAccountData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(
            ThrottleRequests::class
        );

        $this->withoutExceptionHandling();

        $this->makeTestData();

    }

    public function test_product_json_export()
    {
        Product::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/products?output=json', $data)
            ->assertStatus(200);

        $p = (new PreviewReport($this->company, $data, ProductExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);

    }

    public function test_payment_json_export()
    {
        Payment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/payments?output=json', $data)
            ->assertStatus(200);

        $p = (new PreviewReport($this->company, $data, PaymentExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);

    }

    public function test_purchase_order_item_json_export()
    {
        PurchaseOrder::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'vendor_id' => $this->vendor->id,
        ]);

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/purchase_order_items?output=json', $data)
            ->assertStatus(200);

        $p = (new PreviewReport($this->company, $data, PurchaseOrderItemExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);

        // nlog($r);

    }

    public function test_quote_item_json_export()
    {
        Quote::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/quote_items?output=json', $data)
            ->assertStatus(200);

        $p = (new PreviewReport($this->company, $data, QuoteItemExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);

        // nlog($r);

    }

    public function test_invoice_item_json_export()
    {
        Invoice::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/invoice_items?output=json', $data)
            ->assertStatus(200);

        $p = (new PreviewReport($this->company, $data, InvoiceItemExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);

        // nlog($r);

    }

    public function test_purchase_order_json_export()
    {
        PurchaseOrder::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'vendor_id' => $this->vendor->id,
        ]);

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/purchase_orders?output=json', $data)
            ->assertStatus(200);

        $p = (new PreviewReport($this->company, $data, PurchaseOrderExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);

    }

    public function test_quote_json_export()
    {
        Quote::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/quotes?output=json', $data)
            ->assertStatus(200);

        $p = (new PreviewReport($this->company, $data, QuoteExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);

    }

    public function test_invoice_json_export()
    {
        Invoice::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/invoices?output=json', $data)
            ->assertStatus(200);

        $p = (new PreviewReport($this->company, $data, InvoiceExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);

    }

    public function test_expense_json_export()
    {
        Expense::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/expenses?output=json', $data)
            ->assertStatus(200);

        $p = (new PreviewReport($this->company, $data, ExpenseExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);
        // nlog($r);
    }

    public function test_document_json_export()
    {
        Document::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'documentable_type' => Client::class,
            'documentable_id' => $this->client->id,
        ]);

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/documents?output=json', $data)
            ->assertStatus(200);

        $p = (new PreviewReport($this->company, $data, DocumentExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);
        // nlog($r);
    }

    public function test_client_export_json()
    {
        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/clients?output=json', $data)
            ->assertStatus(200);

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => ['client.name', 'client.balance'],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $p = (new PreviewReport($this->company, $data, ClientExport::class, 'client_export1'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('client_export1');

        $this->assertNotNull($r);

    }

    public function test_client_contact_export_json_limited_keys()
    {

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/client_contacts?output=json', $data)
            ->assertStatus(200);

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => ['client.name', 'client.balance', 'contact.email'],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $p = (new PreviewReport($this->company, $data, ContactExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);

    }

    public function test_activity_csv_export_json()
    {
        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/activities?output=json', $data)
            ->assertStatus(200);

        $p = (new PreviewReport($this->company, $data, ActivityExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);

    }

    public function test_credit_export_preview()
    {

        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $p = (new PreviewReport($this->company, $data, CreditExport::class, '123'))->handle();

        $this->assertNull($p);

        $r = Cache::pull('123');

        $this->assertNotNull($r);

    }

    public function test_credit_preview()
    {
        $data = [
            'send_email' => false,
            'date_range' => 'all',
            'report_keys' => [],
            'include_deleted' => false,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->postJson('/api/v1/reports/credits?output=json', $data)
            ->assertStatus(200);

    }
}
