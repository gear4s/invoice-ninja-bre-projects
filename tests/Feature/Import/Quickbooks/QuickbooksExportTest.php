<?php

namespace Tests\Feature\Import\Quickbooks;

use App\Models\Company;
use App\Services\Quickbooks\QuickbooksService;
use App\Utils\Traits\MakesHash;
use Tests\MockAccountData;
use Tests\TestCase;

class QuickbooksExportTest extends TestCase
{
    use MakesHash;
    use MockAccountData;

    protected QuickbooksService $qb;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('ninja.testvars.travis') || !config('services.quickbooks.client_id')) {
            $this->markTestSkipped('No Quickbooks Client ID found');
        }

        $company = Company::find(1);

        if (!$company) {
            $this->markTestSkipped('No company found');
        }

        $this->qb = new QuickbooksService($company);
    }

    public function test_import_products()
    {
        $entity = 'Product';

        $entities = [
            'client' => 'Customer',
            'product' => 'Item',
            'invoice' => 'Invoice',
            // 'sales' => 'SalesReceipt',
        ];

        foreach ($entities as $key => $entity) {
            $records = $this->qb->sdk()->fetchRecords($entity);

            $this->assertNotNull($records);

            switch ($key) {
                case 'product':
                    $this->qb->product->syncToNinja($records);
                    break;
                case 'client':
                    $this->qb->client->syncToNinja($records);
                    break;
                case 'invoice':
                    $this->qb->invoice->syncToNinja($records);
                    break;
                case 'sales':
                    $this->qb->invoice->syncToNinja($records);
                    break;
            }

        }

    }
}
