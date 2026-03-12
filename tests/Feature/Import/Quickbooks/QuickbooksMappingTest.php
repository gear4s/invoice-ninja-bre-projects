<?php

namespace Tests\Feature\Import\Quickbooks;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Services\Quickbooks\QuickbooksService;
use App\Services\Quickbooks\Transformers\ClientTransformer;
use Tests\MockAccountData;
use Tests\TestCase;

class QuickbooksMappingTest extends TestCase
{
    use MockAccountData;

    private string $backup_file = 'tests/Feature/Import/Quickbooks/backup.json';

    private array $qb_data = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (config('ninja.testvars.travis') !== false || !config('services.quickbooks.client_id')) {
            $this->markTestSkipped('Skip test for GH Actions');
        }

        $this->qb_data = json_decode(file_get_contents($this->backup_file), true);

        $this->makeTestData();

        if (!$this->company->quickbooks->accessTokenKey) {
            $this->markTestSkipped('Company does not have Quickbooks connected');
        }
    }

    public function test_backup_import()
    {

        $qb = new QuickbooksService($this->company);

        $pre_count = Client::where('company_id', $this->company->id)->count();
        $qb->client->syncToNinja($this->qb_data['clients']);
        $post_count = Client::where('company_id', $this->company->id)->count();

        $this->assertGreaterThan($pre_count, $post_count);

        $pre_count = Product::where('company_id', $this->company->id)->count();
        $qb->product->syncToNinja($this->qb_data['products']);
        $post_count = Product::where('company_id', $this->company->id)->count();

        $this->assertGreaterThan($pre_count, $post_count);

        Product::where('company_id', $this->company->id)->cursor()->each(function ($product) {
            $product->forceDelete();
        });

        Invoice::where('company_id', $this->company->id)->cursor()->each(function ($invoice) {
            $invoice->forceDelete();
        });

        $pre_count = Invoice::where('company_id', $this->company->id)->count();

        $this->assertGreaterThan(0, count($this->qb_data['invoices']));

        $qb->invoice->importToNinja($this->qb_data['invoices']);
        $post_count = Invoice::where('company_id', $this->company->id)->count();

        $this->assertGreaterThan($pre_count, $post_count);

        $pre_count = Payment::where('company_id', $this->company->id)->count();

        $this->assertGreaterThan(0, count($this->qb_data['payments']));

        $qb->payment->importToNinja($this->qb_data['payments']);
        $post_count = Payment::where('company_id', $this->company->id)->count();

        $this->assertGreaterThan($pre_count, $post_count);

        // loop and check every single invoice amount/balance
        $qb_invoices = collect($this->qb_data['invoices']);

        Invoice::where('company_id', $this->company->id)->cursor()->each(function ($invoice) use ($qb_invoices) {
            $qb_invoice = $qb_invoices->where('Id', $invoice->sync->qb_id)->first();

            // nlog($qb_invoice);
            // nlog($invoice->toArray());
            if (!$qb_invoice) {
                nlog("Borked trying to find invoice {$invoice->sync->qb_id} in qb_invoices");
            }

            $this->assertNotNull($qb_invoice);

            $total_amount = $qb_invoice['TotalAmt'];
            $total_balance = $qb_invoice['Balance'];

            // nlog($total_amount);
            // nlog($invoice->amount);
            // nlog($total_balance);
            // nlog($invoice->balance);

            $delta_amount = abs(round($total_amount - $invoice->amount, 2));
            $delta_balance = abs(round($total_balance - $invoice->balance, 2));

            $this->assertLessThanOrEqual(0.01, $delta_amount);
            $this->assertLessThanOrEqual(0.01, $delta_balance);

        });

        Client::where('company_id', $this->company->id)->cursor()->each(function ($client) {
            $client->forceDelete();
        });

    }

    public function test_file_hydrated()
    {
        $this->assertGreaterThan(1, count($this->qb_data));
    }

    public function test_client_mapping()
    {
        $this->assertTrue(isset($this->qb_data['clients']));
    }

    public function test_client_transformation()
    {

        $ct = new ClientTransformer($this->company);

        $client_array = $ct->transform($this->qb_data['clients'][0]);

        $this->assertNotNull($client_array);

    }
}
