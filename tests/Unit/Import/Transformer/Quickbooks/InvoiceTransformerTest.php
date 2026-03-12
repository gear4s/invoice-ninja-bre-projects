<?php

namespace Tests\Unit\Import\Transformer\Quickbooks;

use App\Import\Transformer\Quickbooks\InvoiceTransformer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\MockAccountData;
use Tests\TestCase;

class InvoiceTransformerTest extends TestCase
{
    use DatabaseTransactions;
    use MockAccountData;

    private $invoiceData;

    private $tranformedData;

    private $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('NO BUENO');

        $this->makeTestData();
        $this->withoutExceptionHandling();
        Auth::setUser($this->user);
        // Read the JSON string from a file and decode into an associative array
        $this->invoiceData = json_decode(file_get_contents(app_path('/../tests/Mock/Quickbooks/Data/invoice.json')), true);
        $this->transformer = new InvoiceTransformer($this->company);
        $this->transformedData = $this->transformer->transform($this->invoiceData['Invoice']);
    }

    public function test_is_instance_of()
    {
        $this->assertInstanceOf(InvoiceTransformer::class, $this->transformer);
    }

    public function test_transform_returns_array()
    {
        $this->assertIsArray($this->transformedData);
    }

    public function test_transform_contains_number()
    {
        $this->assertArrayHasKey('number', $this->transformedData);
        $this->assertEquals($this->invoiceData['Invoice']['DocNumber'], $this->transformedData['number']);
    }

    public function test_transform_contains_due_date()
    {
        $this->assertArrayHasKey('due_date', $this->transformedData);
        $this->assertEquals(strtotime($this->invoiceData['Invoice']['DueDate']), strtotime($this->transformedData['due_date']));
    }

    public function test_transform_contains_amount()
    {
        $this->assertArrayHasKey('amount', $this->transformedData);
        $this->assertIsFloat($this->transformedData['amount']);
        $this->assertEquals($this->invoiceData['Invoice']['TotalAmt'], $this->transformedData['amount']);
    }

    public function test_transform_contains_line_items()
    {
        $this->assertArrayHasKey('line_items', $this->transformedData);
        $this->assertNotNull($this->transformedData['line_items']);
        $this->assertEquals(count($this->invoiceData['Invoice']['Line']) - 1, count($this->transformedData['line_items']));
    }

    public function test_transform_has_client()
    {
        $this->assertArrayHasKey('client', $this->transformedData);
        $this->assertArrayHasKey('contacts', $this->transformedData['client']);
        $this->assertEquals($this->invoiceData['Invoice']['BillEmail']['Address'], $this->transformedData['client']['contacts'][0]['email']);
    }
}
