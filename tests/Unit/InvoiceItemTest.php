<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Unit;

use App\DataMapper\InvoiceItem;
use App\Factory\InvoiceFactory;
use App\Factory\InvoiceItemFactory;
use App\Helpers\Invoice\InvoiceItemSum;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MockAccountData;
use Tests\TestCase;

/**
 *   App\Helpers\Invoice\InvoiceItemSum
 */
class InvoiceItemTest extends TestCase
{
    use DatabaseTransactions;
    use MockAccountData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTestData();
    }

    public function test_net_cost()
    {

        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = true;
        $invoice->is_amount_discount = false;
        $invoice->discount = 0;
        $invoice->tax_rate1 = 0;
        $invoice->tax_rate2 = 0;
        $invoice->tax_rate3 = 0;
        $invoice->tax_name1 = '';
        $invoice->tax_name2 = '';
        $invoice->tax_name3 = '';

        $line_items = [];

        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 22;
        $line_item->tax_name1 = 'Km';
        $line_item->product_key = 'Test';
        $line_item->notes = 'Test';
        $line_item->is_amount_discount = false;
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $this->assertEquals(100, $invoice->amount);
        $this->assertEquals(18.03, $invoice->total_taxes);

    }

    public function test_edge_casewith_discounts_percentage_and_tax_calculations()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = false;
        $invoice->is_amount_discount = false;
        $invoice->discount = 0;
        $invoice->tax_rate1 = 0;
        $invoice->tax_rate2 = 0;
        $invoice->tax_rate3 = 0;
        $invoice->tax_name1 = '';
        $invoice->tax_name2 = '';
        $invoice->tax_name3 = '';

        $line_items = [];

        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 22;
        $line_item->tax_name1 = 'Km';
        $line_item->product_key = 'Test';
        $line_item->notes = 'Test';
        $line_item->is_amount_discount = false;
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $this->assertEquals(122, $invoice->amount);
        $this->assertEquals(22, $invoice->total_taxes);
    }

    public function test_discounts_with_inclusive_taxes()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = true;
        $invoice->is_amount_discount = true;
        $invoice->discount = 10;

        $line_items = [];

        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 10;
        $line_item->tax_name1 = 'GST';
        $line_item->product_key = 'Test';
        $line_item->notes = 'Test';
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $this->assertEquals(90, $invoice->amount);
        $this->assertEquals(8.18, $invoice->total_taxes);
    }

    public function test_discounts_with_inclusive_taxes_negative_invoice()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = true;
        $invoice->is_amount_discount = true;
        $invoice->discount = -10;

        $line_items = [];

        $line_item = new InvoiceItem;
        $line_item->quantity = -1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 10;
        $line_item->tax_name1 = 'GST';
        $line_item->product_key = 'Test';
        $line_item->notes = 'Test';
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $this->assertEquals(-90, $invoice->amount);
        $this->assertEquals(-8.18, $invoice->total_taxes);
    }

    public function test_dicounts_with_taxes()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = false;
        $invoice->is_amount_discount = true;
        $invoice->discount = 10;

        $line_items = [];

        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 10;
        $line_item->tax_name1 = 'GST';
        $line_item->product_key = 'Test';
        $line_item->notes = 'Test';
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $this->assertEquals(99, $invoice->amount);
        $this->assertEquals(9, $invoice->total_taxes);
    }

    public function test_dicounts_with_taxes_negative_invoice()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = false;
        $invoice->is_amount_discount = true;
        $invoice->discount = -10;

        $line_items = [];

        $line_item = new InvoiceItem;
        $line_item->quantity = -1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 10;
        $line_item->tax_name1 = 'GST';
        $line_item->product_key = 'Test';
        $line_item->notes = 'Test';
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $this->assertEquals(-99, $invoice->amount);
        $this->assertEquals(-9, $invoice->total_taxes);
    }

    public function test_dicounts_with_taxes_percentage()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = false;
        $invoice->is_amount_discount = false;
        $invoice->discount = 10;

        $line_items = [];

        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 10;
        $line_item->tax_name1 = 'GST';
        $line_item->product_key = 'Test';
        $line_item->notes = 'Test';
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $this->assertEquals(99, $invoice->amount);
        $this->assertEquals(9, $invoice->total_taxes);
    }

    public function test_dicounts_with_taxes_percentage_on_line()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = true;
        $invoice->is_amount_discount = false;
        $invoice->discount = 10;

        $line_items = [];

        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->is_amount_discount = false;
        $line_item->discount = 10;
        $line_item->tax_rate1 = 10;
        $line_item->tax_name1 = 'GST';
        $line_item->product_key = 'Test';
        $line_item->notes = 'Test';
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $this->assertEquals(81, $invoice->amount);
        $this->assertEquals(7.36, $invoice->total_taxes);
    }

    public function test_dicounts_with_exclusive_taxes_percentage_on_line()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = false;
        $invoice->is_amount_discount = false;
        $invoice->discount = -10;

        $line_items = [];

        $line_item = new InvoiceItem;
        $line_item->quantity = -1;
        $line_item->cost = 100;
        $line_item->is_amount_discount = false;
        $line_item->discount = -10;
        $line_item->tax_rate1 = 10;
        $line_item->tax_name1 = 'GST';
        $line_item->product_key = 'Test';
        $line_item->notes = 'Test';
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $this->assertEquals(-133.1, $invoice->amount);
        $this->assertEquals(-12.1, $invoice->total_taxes);
    }

    public function test_dicounts_with_taxes_negative_invoice_percentage()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = false;
        $invoice->is_amount_discount = false;
        $invoice->discount = -10;

        $line_items = [];

        $line_item = new InvoiceItem;
        $line_item->quantity = -1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 10;
        $line_item->tax_name1 = 'GST';
        $line_item->product_key = 'Test';
        $line_item->notes = 'Test';
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $this->assertEquals(-121, $invoice->amount);
        $this->assertEquals(-10, $invoice->discount);
        $this->assertEquals(-11, $invoice->total_taxes);
    }

    public function test_dicount_percentage_with_taxes()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = false;
        $invoice->is_amount_discount = true;
        $invoice->discount = 10;

        $line_items = [];

        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 10;
        $line_item->tax_name1 = 'GST';
        $line_item->product_key = 'Test';
        $line_item->notes = 'Test';
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $this->assertEquals(99, $invoice->amount);
        $this->assertEquals(9, $invoice->total_taxes);
    }

    public function test_invoice_item_total_simple()
    {
        $item = InvoiceItemFactory::create();
        $item->quantity = 1;
        $item->cost = 10;
        $item->is_amount_discount = true;

        $settings = new \stdClass;
        $settings->inclusive_taxes = true;
        $settings->precision = 2;

        $this->invoice->line_items = [$item];

        $item_calc = new InvoiceItemSum($this->invoice, $settings);
        $item_calc->process();

        $this->assertEquals($item_calc->getLineTotal(), 10);
    }

    public function test_invoice_item_total_simple_with_gross_taxes()
    {
        $item = InvoiceItemFactory::create();
        $item->quantity = 1;
        $item->cost = 10;
        $item->is_amount_discount = true;
        $item->tax_rate1 = 10;

        $settings = new \stdClass;
        $settings->inclusive_taxes = false;
        $settings->precision = 2;

        $this->invoice->line_items = [$item];

        $item_calc = new InvoiceItemSum($this->invoice, $settings);
        $item_calc->process();

        $this->assertEquals($item_calc->getLineTotal(), 10);
        $this->assertEquals($item_calc->getGrossLineTotal(), 11);
    }

    public function test_invoice_item_total_simple_with_discount()
    {
        $item = InvoiceItemFactory::create();
        $item->quantity = 1;
        $item->cost = 10;
        $item->is_amount_discount = true;
        $item->discount = 2;

        $this->invoice->line_items = [$item];

        $settings = new \stdClass;
        $settings->inclusive_taxes = true;
        $settings->precision = 2;

        $item_calc = new InvoiceItemSum($this->invoice, $settings);
        $item_calc->process();

        $this->assertEquals($item_calc->getLineTotal(), 8);
    }

    public function test_invoice_item_total_simple_with_discount_and_gross_line_total()
    {
        $item = InvoiceItemFactory::create();
        $item->quantity = 1;
        $item->cost = 10;
        $item->is_amount_discount = true;
        $item->discount = 2;
        $item->tax_rate1 = 10;

        $this->invoice->line_items = [$item];

        $settings = new \stdClass;
        $settings->inclusive_taxes = false;
        $settings->precision = 2;

        $item_calc = new InvoiceItemSum($this->invoice, $settings);
        $item_calc->process();

        $this->assertEquals($item_calc->getLineTotal(), 8);
        $this->assertEquals($item_calc->getGrossLineTotal(), 8.8);
    }

    public function test_invoice_item_total_simple_with_discount_with_precision()
    {
        $item = InvoiceItemFactory::create();
        $item->quantity = 1;
        $item->cost = 10;
        $item->is_amount_discount = true;
        $item->discount = 2.521254522145214511;

        $this->invoice->line_items = [$item];

        $settings = new \stdClass;
        $settings->inclusive_taxes = true;
        $settings->precision = 2;

        $item_calc = new InvoiceItemSum($this->invoice, $settings);
        $item_calc->process();

        $this->assertEquals($item_calc->getLineTotal(), 7.48);
    }

    public function test_invoice_item_total_simple_with_discount_with_precision_with_single_inclusive_tax()
    {
        $item = InvoiceItemFactory::create();
        $item->quantity = 1;
        $item->cost = 10;
        $item->is_amount_discount = true;
        $item->discount = 2;
        $item->tax_rate1 = 10;

        $settings = new \stdClass;
        $settings->inclusive_taxes = false;
        $settings->precision = 2;

        $this->invoice->line_items = [$item];

        $item_calc = new InvoiceItemSum($this->invoice, $settings);
        $item_calc->process();

        $this->assertEquals($item_calc->getTotalTaxes(), 0.80);
    }

    public function test_invoice_item_total_simple_with_discount_with_precision_with_single_exclusive_tax()
    {
        $item = InvoiceItemFactory::create();
        $item->quantity = 1;
        $item->cost = 10;
        $item->is_amount_discount = true;
        $item->discount = 2.521254522145214511;
        $item->tax_rate1 = 10;

        $this->invoice->line_items = [$item];

        $settings = new \stdClass;
        $settings->inclusive_taxes = false;
        $settings->precision = 2;

        $item_calc = new InvoiceItemSum($this->invoice, $settings);
        $item_calc->process();

        $this->assertEquals($item_calc->getTotalTaxes(), 0.75);
    }

    public function test_invoice_item_total_simple_with_discount_with_precision_with_double_inclusive_tax()
    {
        $item = InvoiceItemFactory::create();
        $item->quantity = 1;
        $item->cost = 10;
        $item->is_amount_discount = true;
        $item->discount = 2.521254522145214511;
        $item->tax_rate1 = 10;
        $item->tax_rate2 = 17.5;

        $this->invoice->line_items = [$item];

        $settings = new \stdClass;
        $settings->inclusive_taxes = true;
        $settings->precision = 2;

        $item_calc = new InvoiceItemSum($this->invoice, $settings);
        $item_calc->process();

        $this->assertEquals($item_calc->getTotalTaxes(), 2.06);
    }

    public function test_invoice_item_total_simple_with_discount_with_precision_with_double_exclusive_tax()
    {
        $item = InvoiceItemFactory::create();
        $item->quantity = 1;
        $item->cost = 10;
        $item->is_amount_discount = true;
        $item->discount = 2.521254522145214511;
        $item->tax_name1 = 'GST';
        $item->tax_rate1 = 10;
        $item->tax_name2 = 'VAT';
        $item->tax_rate2 = 17.5;

        $this->invoice->line_items = [$item];

        $settings = new \stdClass;
        $settings->inclusive_taxes = false;
        $settings->precision = 2;

        $item_calc = new InvoiceItemSum($this->invoice, $settings);
        $item_calc->process();

        nlog($item_calc->getGroupedTaxes());

        $this->assertEquals($item_calc->getTotalTaxes(), 2.06);
        $this->assertEquals($item_calc->getGroupedTaxes()->count(), 2);
    }

    public function test_net_cost_with_double_tax_inclusive()
    {

        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = true;
        $invoice->is_amount_discount = false;

        $line_items = [];
        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 10;
        $line_item->tax_rate2 = 5;
        $line_item->tax_name1 = 'GST';
        $line_item->tax_name2 = 'VAT';
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $this->assertEquals(100, $invoice->amount);
        $this->assertEquals(13.85, $invoice->total_taxes);

    }

    public function test_net_cost_with_high_tax_rates_inclusive()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = true;

        $line_items = [];
        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 25;
        $line_item->tax_rate2 = 20;
        $line_item->tax_name1 = 'Tax1';
        $line_item->tax_name2 = 'Tax2';
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $item = $invoice->line_items[0];

        $this->assertEquals(100, $invoice->amount);
        $this->assertEquals(36.67, $invoice->total_taxes);
        $this->assertEquals(68.97, $item->net_cost);
    }

    public function test_net_cost_with_triple_tax_inclusive()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = true;

        $line_items = [];
        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 7;
        $line_item->tax_rate2 = 5;
        $line_item->tax_rate3 = 3;
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $item = $invoice->line_items[0];

        $this->assertEquals(100, $invoice->amount);
        $this->assertEquals(14.21, $invoice->total_taxes);
        $this->assertEquals(86.96, $item->net_cost);
    }

    public function test_net_cost_with_fractional_tax_rates_inclusive()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = true;

        $line_items = [];
        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 7.5;
        $line_item->tax_rate2 = 2.75;
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $item = $invoice->line_items[0];

        $this->assertEquals(100, $invoice->amount);
        $this->assertEquals(9.66, $invoice->total_taxes);
        $this->assertEquals(90.7, $item->net_cost);
    }

    public function test_net_cost_with_high_value_and_multiple_taxes_inclusive()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = true;

        $line_items = [];
        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 1000;
        $line_item->tax_rate1 = 15;
        $line_item->tax_rate2 = 10;
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $item = $invoice->line_items[0];

        $this->assertEquals(1000, $invoice->amount);
        $this->assertEquals(221.34, $invoice->total_taxes);
        $this->assertEquals(800, $item->net_cost);
    }

    public function test_net_cost_with_low_tax_rates_inclusive()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = true;

        $line_items = [];
        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 1.5;
        $line_item->tax_rate2 = 0.5;
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $item = $invoice->line_items[0];

        $this->assertEquals(100, $invoice->amount);
        $this->assertEquals(1.98, $invoice->total_taxes);
        $this->assertEquals(98.04, $item->net_cost);
    }

    public function test_net_cost_with_equal_tax_rates_inclusive()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = true;

        $line_items = [];
        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_rate1 = 10;
        $line_item->tax_rate2 = 10;
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $item = $invoice->line_items[0];

        $this->assertEquals(100, $invoice->amount);
        $this->assertEquals(18.18, $invoice->total_taxes);
        $this->assertEquals(83.33, $item->net_cost);
    }

    public function test_net_cost_with_zero_and_non_zero_taxes_inclusive()
    {
        $invoice = InvoiceFactory::create($this->company->id, $this->user->id);
        $invoice->client_id = $this->client->id;
        $invoice->uses_inclusive_taxes = true;

        $line_items = [];
        $line_item = new InvoiceItem;
        $line_item->quantity = 1;
        $line_item->cost = 100;
        $line_item->tax_name1 = 'Tax1';
        $line_item->tax_rate1 = 0;
        $line_item->tax_name2 = 'Tax2';
        $line_item->tax_rate2 = 15;
        $line_items[] = $line_item;

        $invoice->line_items = $line_items;
        $invoice->save();

        $invoice = $invoice->calc()->getInvoice();

        $item = $invoice->line_items[0];

        $this->assertEquals(100, $invoice->amount);
        $this->assertEquals(13.04, $invoice->total_taxes);
        $this->assertEquals(86.96, $item->net_cost);
    }
}
