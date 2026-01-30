<?php

/**
 * Invoice Ninja (https://clientninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2022. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Services\Quickbooks\Transformers;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Product;
use App\DataMapper\InvoiceItem;
use App\Models\TaxRate;

/**
 * Class InvoiceTransformer.
 */
class InvoiceTransformer extends BaseTransformer
{
    public function qbToNinja(mixed $qb_data)
    {
        return $this->transform($qb_data);
    }

    public function ninjaToQb(Invoice $invoice, \App\Services\Quickbooks\QuickbooksService $qb_service): array
    {
        // Get client's QuickBooks ID
        $client_qb_id = $invoice->client->sync->qb_id ?? null;
        
        // If client doesn't have QB ID, create it first
        if (!$client_qb_id) {
            $client_qb_id = $qb_service->client->createQbClient($invoice->client);
        }

        // Build line items
        $line_items = [];
        $line_num = 1;

        foreach ($invoice->line_items as $line_item) {
            // Get product's QuickBooks ID if it exists
            $product = \App\Models\Product::where('company_id', $this->company->id)
                                          ->where('product_key', $line_item->product_key)
                                          ->first();

            $product_qb_id = null;
            
            if ($product && isset($product->sync->qb_id)) {
                // Product already has QuickBooks ID
                $product_qb_id = $product->sync->qb_id;
            } else {
                // Product doesn't exist in QuickBooks - create it on-the-fly
                $product_qb_id = $this->createProductInQuickbooks($line_item, $qb_service);
                
                // If creation failed, skip this line item
                if (!$product_qb_id) {
                    nlog("QuickBooks: Failed to create product for line item: {$line_item->product_key}");
                    continue;
                }
            }

            $tax_code = 'TAX';
            if (isset($line_item->tax_id)) {
                // Check if tax exempt (similar to test pattern)
                if (in_array($line_item->tax_id, [5, 8])) {
                    $tax_code = 'NON';
                }
            }

            $line_payload = [
                'LineNum' => $line_num,
                'DetailType' => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef' => [
                        'value' => $product_qb_id,
                    ],
                    'Qty' => $line_item->quantity ?? 1,
                    'UnitPrice' => $line_item->cost ?? 0,
                    'TaxCodeRef' => [
                        'value' => $tax_code,
                    ],
                ],
                'Description' => $line_item->notes ?? '',
                'Amount' => $line_item->line_total ?? ($line_item->cost * ($line_item->quantity ?? 1)),
            ];


            //check here if we need to inject the income account reference
            // $line_payload['AccountRef'] = ['value' => $income_account_qb_id];

            $line_items[] = $line_payload;

            $line_num++;
        }

        // QuickBooks requires at least one line item
        if (empty($line_items)) {
            $error_msg = "QuickBooks: Invoice {$invoice->id} cannot be created - no valid line items could be processed.";
            nlog($error_msg);
            throw new \Exception($error_msg);
        }

        // Get primary contact email
        $primary_contact = $invoice->client->contacts()->orderBy('is_primary', 'desc')->first();
        $email = $primary_contact?->email ?? $invoice->client->contacts()->first()?->email ?? '';

        // Build invoice data
        $invoice_data = [
            'Line' => $line_items,
            'CustomerRef' => [
                'value' => $client_qb_id,
            ],
            'BillEmail' => [
                'Address' => $email,
            ],
            'TxnDate' => $invoice->date,
            'DueDate' => $invoice->due_date,
            'TotalAmt' => $invoice->amount,
            'DocNumber' => $invoice->number,
            'ApplyTaxAfterDiscount' => true,
            'PrintStatus' => 'NeedToPrint',
            'EmailStatus' => 'NotSet',
            'GlobalTaxCalculation' => 'TaxExcluded',
        ];

        // Add optional fields
        if ($invoice->public_notes) {
            $invoice_data['CustomerMemo'] = [
                'value' => $invoice->public_notes,
            ];
        }

        if ($invoice->private_notes) {
            $invoice_data['PrivateNote'] = $invoice->private_notes;
        }

        if ($invoice->po_number) {
            $invoice_data['PONumber'] = $invoice->po_number;
        }

        // If invoice already has a QB ID, include it for updates
        // Note: SyncToken will be fetched in QbInvoice::syncToForeign using the existing find() method
        if (isset($invoice->sync->qb_id) && !empty($invoice->sync->qb_id)) {
            $invoice_data['Id'] = $invoice->sync->qb_id;
        }

        return $invoice_data;
    }

    /**
     * Create a product/item in QuickBooks on-the-fly if it doesn't exist.
     * 
     * @param \App\DataMapper\InvoiceItem $line_item
     * @param \App\Services\Quickbooks\QuickbooksService $qb_service
     * @return string|null The QuickBooks item ID, or null if creation failed
     */
    private function createProductInQuickbooks($line_item, \App\Services\Quickbooks\QuickbooksService $qb_service): ?string
    {
        try {
            // Build item data for QuickBooks
            $item_name = $line_item->product_key ?? $line_item->notes ?? 'Product ' . uniqid();
            $item_description = $line_item->notes ?? '';
            
            // Determine item type - default to Service
            $item_type = 'Service'; // Service items are simplest and don't require inventory tracking
            
            // Get an income account (required for Service items)
            $income_account_id = $this->getIncomeAccountId($qb_service);
            if (!$income_account_id) {
                nlog("QuickBooks: No income account found - cannot create product/item");
                return null;
            }
            
            $item_data = [
                'Name' => $item_name,
                'Type' => $item_type,
                'Active' => true,
                'IncomeAccountRef' => [
                    'value' => $income_account_id,
                ],
            ];
            
            if ($item_description) {
                $item_data['Description'] = $item_description;
            }
            
            // Set unit price if available
            if (isset($line_item->cost) && $line_item->cost > 0) {
                $item_data['UnitPrice'] = $line_item->cost;
            }
            
            // Create the item in QuickBooks
            $qb_item = \QuickBooksOnline\API\Facades\Item::create($item_data);
            $result = $qb_service->sdk->Add($qb_item);
            
            $qb_id = data_get($result, 'Id') ?? data_get($result, 'Id.value');
            
            if ($qb_id) {
                // Optionally, save the QB ID back to the product if it exists
                $product = \App\Models\Product::where('company_id', $this->company->id)
                                              ->where('product_key', $line_item->product_key)
                                              ->first();
                
                if ($product) {
                    $sync = new \App\DataMapper\ProductSync();
                    $sync->qb_id = $qb_id;
                    $product->sync = $sync;
                    $product->saveQuietly();
                }
                
                nlog("QuickBooks: Auto-created product/item '{$item_name}' in QuickBooks (QB ID: {$qb_id})");
                return $qb_id;
            }
            
            return null;
        } catch (\Exception $e) {
            nlog("QuickBooks: Error creating product/item in QuickBooks: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get an income account ID from QuickBooks.
     * 
     * @param \App\Services\Quickbooks\QuickbooksService $qb_service
     * @return string|null The income account ID, or null if not found
     */
    private function getIncomeAccountId(\App\Services\Quickbooks\QuickbooksService $qb_service): ?string
    {
        try {
            // Query for an income account
            $query = "SELECT * FROM Account WHERE AccountType = 'Income' AND Active = true MAXRESULTS 1";
            $accounts = $qb_service->sdk->Query($query);
            
            if (!empty($accounts) && isset($accounts[0])) {
                $account = $accounts[0];
                $account_id = data_get($account, 'Id') ?? data_get($account, 'Id.value');
                return $account_id ? (string) $account_id : null;
            }
            
            return null;
        } catch (\Exception $e) {
            nlog("QuickBooks: Error fetching income account: {$e->getMessage()}");
            return null;
        }
    }

    public function transform($qb_data)
    {
        $client_id = $this->getClientId(data_get($qb_data, 'CustomerRef', null));
        $tax_array = $this->calculateTotalTax($qb_data);

        return $client_id ? [
            'id' => data_get($qb_data, 'Id', false),
            'client_id' => $client_id,
            'number' => data_get($qb_data, 'DocNumber', false),
            'date' => data_get($qb_data, 'TxnDate', now()->format('Y-m-d')),
            'private_notes' => data_get($qb_data, 'PrivateNote', ''),
            'public_notes' => data_get($qb_data, 'CustomerMemo', false),
            'due_date' => data_get($qb_data, 'DueDate', null),
            'po_number' => data_get($qb_data, 'PONumber', ""),
            'partial' => (float)data_get($qb_data, 'Deposit', 0),
            'line_items' => $this->getLineItems($qb_data, $tax_array),
            'payment_ids' => $this->getPayments($qb_data),
            'status_id' => Invoice::STATUS_SENT,
            // 'tax_rate1' => $rate = $this->calculateTotalTax($qb_data),
            // 'tax_name1' => $rate > 0 ? "Sales Tax" : "",
            'custom_surcharge1' => $this->checkIfDiscountAfterTax($qb_data),
            'balance' => data_get($qb_data, 'Balance', 0),

        ] : false;
    }

    private function checkIfDiscountAfterTax($qb_data)
    {

        if (data_get($qb_data, 'ApplyTaxAfterDiscount') == 'true') {
            return 0;
        }

        foreach (data_get($qb_data, 'Line', []) as $line) {

            if (data_get($line, 'DetailType') == 'DiscountLineDetail') {

                if (!isset($this->company->custom_fields->surcharge1)) {
                    $this->company->custom_fields->surcharge1 = ctrans('texts.discount');
                    $this->company->save();
                }

                return (float)data_get($line, 'Amount', 0) * -1;
            }
        }

        return 0;
    }

    private function calculateTotalTax($qb_data)
    {
        $total_tax = data_get($qb_data, 'TxnTaxDetail.TotalTax', false);

        $tax_rate = 0;
        $tax_name = '';

        if ($total_tax == "0") {
            return [$tax_rate, $tax_name];
        }

        $taxLines = data_get($qb_data, 'TxnTaxDetail.TaxLine', []) ?? [];

        if (!empty($taxLines) && !isset($taxLines[0])) {
            $taxLines = [$taxLines];
        }

        $totalTaxRate = 0;

        foreach ($taxLines as $taxLine) {
            $taxRate = data_get($taxLine, 'TaxLineDetail.TaxPercent', 0);
            $totalTaxRate += $taxRate;
        }


        if ($totalTaxRate > 0) {
            $formattedTaxRate = rtrim(rtrim(number_format($totalTaxRate, 6), '0'), '.');
            $formattedTaxRate = trim($formattedTaxRate);

            $tr = \App\Models\TaxRate::firstOrNew(
                [
                'company_id' => $this->company->id,
                'rate' => $formattedTaxRate,
                ],
                [
                'name' => "Sales Tax [{$formattedTaxRate}]",
                'rate' => $formattedTaxRate,
                ]
            );
            $tr->company_id = $this->company->id;
            $tr->user_id = $this->company->owner()->id;
            $tr->save();

            $tax_rate = $tr->rate;
            $tax_name = $tr->name;
        }

        return [$tax_rate, $tax_name];

    }


    private function getPayments(mixed $qb_data)
    {
        $payments = [];

        $qb_payments = data_get($qb_data, 'LinkedTxn', false) ?? [];

        if (!empty($qb_payments) && !isset($qb_payments[0])) {
            $qb_payments = [$qb_payments];
        }

        foreach ($qb_payments as $payment) {
            if (data_get($payment, 'TxnType', false) == 'Payment') {
                $payments[] = data_get($payment, 'TxnId', false);
            }
        }

        return $payments;

    }

    private function getLineItems(mixed $qb_data, array $tax_array)
    {
        $qb_items = data_get($qb_data, 'Line', []);

        $include_discount = data_get($qb_data, 'ApplyTaxAfterDiscount', 'true');

        $items = [];

        if (!empty($qb_items) && !isset($qb_items[0])) {

            //handle weird statement charges
            $tax_rate = (float)data_get($qb_data, 'TxnTaxDetail.TaxLine.TaxLineDetail.TaxPercent', 0);
            $tax_name = $tax_rate > 0 ? "Sales Tax [{$tax_rate}]" : '';

            $item = new InvoiceItem();
            $item->product_key = '';
            $item->notes = 'Recurring Charge';
            $item->quantity = 1;
            $item->cost = (float)data_get($qb_items, 'Amount', 0);
            $item->discount = 0;
            $item->is_amount_discount = false;
            $item->type_id = '1';
            $item->tax_id = '1';
            $item->tax_rate1 = (float)$tax_rate;
            $item->tax_name1 = $tax_name;

            $items[] = (object)$item;

            return $items;
        }

        foreach ($qb_items as $qb_item) {

            $taxCodeRef = data_get($qb_item, 'TaxCodeRef', data_get($qb_item, 'SalesItemLineDetail.TaxCodeRef', 'TAX'));

            if (data_get($qb_item, 'DetailType') == 'SalesItemLineDetail') {
                $item = new InvoiceItem();
                $item->product_key = data_get($qb_item, 'SalesItemLineDetail.ItemRef.name', '');
                $item->notes = data_get($qb_item, 'Description', '');
                $item->quantity = (float)(data_get($qb_item, 'SalesItemLineDetail.Qty') ?? 1);
                $item->cost = (float)(data_get($qb_item, 'SalesItemLineDetail.UnitPrice') ?? data_get($qb_item, 'SalesItemLineDetail.MarkupInfo.Value', 0));
                $item->discount = (float)data_get($item, 'DiscountRate', data_get($qb_item, 'DiscountAmount', 0));
                $item->is_amount_discount = data_get($qb_item, 'DiscountAmount', 0) > 0 ? true : false;
                $item->type_id = stripos(data_get($qb_item, 'ItemAccountRef.name') ?? '', 'Service') !== false ? '2' : '1';
                $item->tax_id = $taxCodeRef == 'NON' ? (string)Product::PRODUCT_TYPE_EXEMPT : $item->type_id;
                $item->tax_rate1 = $taxCodeRef == 'NON' ? 0 : (float)$tax_array[0];
                $item->tax_name1 = $taxCodeRef == 'NON' ? '' : $tax_array[1];

                $items[] = (object)$item;
            }

            if (data_get($qb_item, 'DetailType') == 'DiscountLineDetail' && $include_discount == 'true') {

                $item = new InvoiceItem();
                $item->product_key = ctrans('texts.discount');
                $item->notes = ctrans('texts.discount');
                $item->quantity = 1;
                $item->cost = (float)data_get($qb_item, 'Amount', 0) * -1;
                $item->discount = 0;
                $item->is_amount_discount = true;

                $item->tax_rate1 = $include_discount == 'true' ? (float)$tax_array[0] : 0;
                $item->tax_name1 = $include_discount == 'true' ? $tax_array[1] : '';

                $item->type_id = '1';
                $item->tax_id = (string)Product::PRODUCT_TYPE_PHYSICAL;
                $items[] = (object)$item;

            }
        }

        return $items;

    }

}
