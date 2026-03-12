<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Integration\Einvoice;

use App\Services\EDocument\Imports\UblEDocument;
use App\Utils\TempFile;
use InvoiceNinja\EInvoice\EInvoice;
use InvoiceNinja\EInvoice\Models\Peppol\Invoice;
use Tests\MockAccountData;
use Tests\TestCase;

class ImportEInvoiceTest extends TestCase
{
    use MockAccountData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTestData();

        $this->markTestSkipped('testing skipper');
    }

    public function test_import_expense_einvoice()
    {
        $file = file_get_contents(base_path('tests/Integration/Einvoice/samples/peppol.xml'));

        $file = TempFile::UploadedFileFromRaw($file, 'peppol.xml', 'xml');

        $expense = (new UblEDocument($file, $this->company))->run();

        $this->assertNotNull($expense);

    }

    public function test_parsing_document()
    {
        $peppol_doc = file_get_contents(base_path('tests/Integration/Einvoice/samples/peppol.xml'));

        // file present
        $this->assertNotNull($peppol_doc);

        $e = new EInvoice;
        $invoice = $e->decode('Peppol', $peppol_doc, 'xml');

        // decodes as expected
        $this->assertNotNull($invoice);

        // has prop we expect
        $this->assertObjectHasProperty('UBLVersionID', $invoice);

        // has hydrated correctly
        $this->assertInstanceOf(Invoice::class, $invoice);

    }
}
