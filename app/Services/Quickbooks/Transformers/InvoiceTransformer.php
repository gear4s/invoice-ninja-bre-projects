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

use App\Models\Invoice;
use App\Models\Product;
use App\DataMapper\InvoiceItem;
use App\Services\Quickbooks\Helpers\Helper;

/**
 * Class InvoiceTransformer.
 * 
 */
class InvoiceTransformer extends BaseTransformer
{    
    /**
     * qbToNinja
     *
     * Transforms a QB invoice to a Invoice Ninja Invoice
     * 
     * @param  mixed $qb_data
     * @param  \App\Services\Quickbooks\QuickbooksService|null $qb_service
     * @return array
     */
    public function qbToNinja(mixed $qb_data, ?\App\Services\Quickbooks\QuickbooksService $qb_service = null)
    {
        return $this->transform($qb_data, $qb_service);
    }
    
    /**
     * ninjaToQb
     *
     * Transforms a Invoice Ninja Invoice to a QB invoice
     * 
     * @param  \App\Models\Invoice $invoice
     * @param  \App\Services\Quickbooks\QuickbooksService $qb_service
     * @return array
     */
    public function ninjaToQb(Invoice $invoice, \App\Services\Quickbooks\QuickbooksService $qb_service): array
    {
        // Get client's QuickBooks ID (business logic handled by caller - QbInvoice)
        $client_qb_id = $invoice->client->sync->qb_id ?? null;

        // Build line items
        $line_items = [];
        $line_num = 1;

        $ast = $qb_service->company->quickbooks->settings->automatic_taxes;

        foreach ($invoice->line_items as $line_item) {
            // Get product's QuickBooks ID (business logic handled by QbProduct)
            $product_qb_id = $qb_service->product->findOrCreateProduct($line_item);

            $tax_code_id = 'NON'; // Default to non-taxable
            
            // Check if tax_id is set and is exempt/zero rate (5 = exempt, 8 = zero rate)
            if(isset($line_item->tax_id) && in_array($line_item->tax_id, ['5', '8'])){
                $tax_code_id = 'NON';
            }
            elseif($ast){ // Automatic taxes are enabled    
                $tax_code_id = 'TAX';
            }
            elseif (isset($line_item->tax_id) && !in_array($line_item->tax_id, ['5', '8'])) {
                // Only use 'TAX' if there are actual tax rates applied to this line item
                $has_tax_rate = (
                    (isset($line_item->tax_rate1) && $line_item->tax_rate1 > 0) ||
                    (isset($line_item->tax_rate2) && $line_item->tax_rate2 > 0) ||
                    (isset($line_item->tax_rate3) && $line_item->tax_rate3 > 0)
                );
                
                if ($has_tax_rate) {
                    $tax_code_id = 'TAX';
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
                        'value' => $tax_code_id,
                    ],
                ],
                'Description' => $line_item->notes ?? '',
                'Amount' => $line_item->line_total ?? ($line_item->cost * ($line_item->quantity ?? 1)),
            ];

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

        // Calculate invoice to get accurate tax information
        $invoice_calc = $invoice->calc();
        $total_taxes = $invoice_calc->getTotalTaxes();
        $subtotal = $invoice_calc->getSubTotal();
        $discount = $invoice_calc->getTotalDiscount();
        $surcharges = $invoice_calc->getTotalSurcharges();
        
        // Calculate taxable amount (subtotal - discount + surcharges, before taxes)
        $taxable_amount = $subtotal - $discount + $surcharges;
        
        // Add discount as a line item if discount exists
        if ($discount > 0) {
            // QuickBooks expects positive Amount for DiscountLineDetail (it handles the negative internally)
            $discount_amount = (float)round($discount, 2);
            
            // Get discount account ID using helper
            $discount_account_id = $qb_service->helper->getDiscountAccountId();
            
            $discount_line = [
                'LineNum' => $line_num,
                'DetailType' => 'DiscountLineDetail',
                'Amount' => $discount_amount, // Positive amount - QuickBooks handles the negative
                'DiscountLineDetail' => [
                    'PercentBased' => !$invoice->is_amount_discount, // true for percentage, false for amount
                ],
            ];
            
            // Add DiscountAccountRef if available (may be required by QuickBooks)
            if ($discount_account_id) {
                $discount_line['DiscountLineDetail']['DiscountAccountRef'] = [
                    'value' => $discount_account_id,
                ];
            }
            
            if (!$invoice->is_amount_discount && $invoice->discount > 0) {
                // For percentage-based discounts, set DiscountPercent
                $discount_line['DiscountLineDetail']['DiscountPercent'] = round($invoice->discount, 2);
            } else {
                // For amount-based discounts, set DiscountPercent to 0.0 (as suggested)
                $discount_line['DiscountLineDetail']['DiscountPercent'] = 0.0;
            }
            
            $line_items[] = $discount_line;
            $line_num++;
        }
        
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
            // 'GlobalTaxCalculation' => 'TaxExcluded',
            'GlobalTaxCalculation' => $qb_service->company->quickbooks->settings->automatic_taxes ? 'TaxExcluded' : 'NotApplicable',
        ];
        
        // Add TxnTaxDetail if invoice has taxes and AST is not enabled.
        if(!$qb_service->company->quickbooks->settings->automatic_taxes) {
            $tax_detail = $this->buildTxnTaxDetail($invoice, $total_taxes, $taxable_amount, $qb_service);
            if ($tax_detail) {
                $invoice_data['TxnTaxDetail'] = $tax_detail;
            }
        }

        // Add optional fields
        if ($invoice->public_notes || $invoice->terms) {
            $public_notes = $invoice->public_notes ?? '';
            $terms = $invoice->terms ?? '';
            
            // Clean HTML: replace <br> tags with newlines and strip all HTML tags
            $public_notes = $qb_service->helper->cleanHtmlText($public_notes);
            $terms = $qb_service->helper->cleanHtmlText($terms);
            
            // Combine public notes and terms
            $memo_value = trim($public_notes . ($public_notes && $terms ? "\n\n" : '') . $terms);
            
            if ($memo_value) {
                $invoice_data['CustomerMemo'] = [
                    'value' => $memo_value,
                ];
            }
        }

        if ($invoice->private_notes) {
            $invoice_data['PrivateNote'] = $qb_service->helper->cleanHtmlText($invoice->private_notes);
        }

        if ($invoice->po_number) {
            $invoice_data['PONumber'] = $invoice->po_number;
        }

        // Add partial deposit if invoice has a partial payment amount
        // QuickBooks uses 'Deposit' field for partial payments/deposits
        if ($invoice->partial && $invoice->partial > 0) {
            $invoice_data['Deposit'] = $invoice->partial;
            
            // Note: QuickBooks doesn't have a separate 'DepositDueDate' field
        }

        // If invoice already has a QB ID, include it for updates
        // Note: SyncToken will be fetched in QbInvoice::syncToForeign using the existing find() method
        if (isset($invoice->sync->qb_id) && !empty($invoice->sync->qb_id)) {
            $invoice_data['Id'] = $invoice->sync->qb_id;
        }

        // nlog($invoice_data);
        
        return $invoice_data;
    }


    /**
     * Build TxnTaxDetail for invoice-level tax calculation.
     * This handles total taxes applied to the invoice.
     * 
     * @param \App\Models\Invoice $invoice
     * @param float $total_taxes The total tax amount
     * @param float $taxable_amount The taxable amount (subtotal - discount + surcharges)
     * @param \App\Services\Quickbooks\QuickbooksService $qb_service
     * @return array|null TxnTaxDetail array or null if no taxes
     */
    private function buildTxnTaxDetail(\App\Models\Invoice $invoice, float $total_taxes, float $taxable_amount, \App\Services\Quickbooks\QuickbooksService $qb_service): ?array
    {
        // Collect invoice-level taxes (tax_name1/rate1, tax_name2/rate2, tax_name3/rate3)
        $tax_lines = [];
        $calculated_total_tax = 0;
        
        $tax_rate_map = $qb_service->company->quickbooks->settings->tax_rate_map ?? [];
              
        foreach($invoice->calc()->getTaxMap() ?? [] as $tax)
        {
            $tax_components = $qb_service->helper->splitTaxName($tax['name']);

            $tax_rate_id = null;

            foreach($tax_rate_map as $rate_map)
            {
    
                if(floatval($rate_map['rate']) == floatval($tax_components['percentage']) && $rate_map['name'] == $tax_components['name'])
                {
                    $tax_rate_id = $rate_map['id'];
                    break;
                }
            }
    

          $tax_lines[] = [
            'Amount' => round($tax['total'], 2),
            'DetailType' => 'TaxLineDetail',
            'TaxLineDetail' => [
                'TaxRateRef' => [
                    'value' => $tax_rate_id,
                ],
                'PercentBased' => false,
                'NetAmountTaxable' => round($tax['base_amount'], 2),
                'TaxInclusiveAmount' => 0.00,
            ],
          ];

          $calculated_total_tax += round($tax['total'], 2);
        }


        foreach($invoice->calc()->getTotalTaxMap() ?? [] as $tax)
        {
            $tax_components = $qb_service->helper->splitTaxName($tax['name']);

            $tax_rate_id = null;

            foreach($tax_rate_map as $rate_map)
            {
    
                if(floatval($rate_map['rate']) == floatval($tax_components['percentage']) && $rate_map['name'] == $tax_components['name'])
                {
                    $tax_rate_id = $rate_map['id'];
                    break;
                }
            }
    
            $tax_lines[] = [
                'Amount' => round($tax['total'], 2),
                'DetailType' => 'TaxLineDetail',
                'TaxLineDetail' => [
                    'TaxRateRef' => [
                        'value' => $tax_rate_id,
                    ],
                    'PercentBased' => false,
                    'NetAmountTaxable' => round($tax['base_amount'], 2),
                    'TaxInclusiveAmount' => 0.00,
                ],
            ];

            $calculated_total_tax += round($tax['total'], 2);
        }
       
        // If no tax lines, return null
        if (empty($tax_lines)) {
            return null;
        }
        
        // Use the actual total_taxes from invoice if available, otherwise use calculated
        $final_total_tax = $total_taxes > 0 ? round($total_taxes, 2) : round($calculated_total_tax, 2);
        
        return [
            'TotalTax' => $final_total_tax,
            'TaxLine' => $tax_lines,
        ];
    }

    
    /**
     * transform
     *
     * @param  mixed $qb_data
     * @param  \App\Services\Quickbooks\QuickbooksService|null $qb_service
     * @return array|bool
     */
    public function transform(mixed $qb_data, ?\App\Services\Quickbooks\QuickbooksService $qb_service = null): array|bool
    {
        $client_id = $this->getClientId(data_get($qb_data, 'CustomerRef', null));
        
        // Use helper for business logic if available, otherwise return basic transformation
        $tax_array = $qb_service ? $qb_service->helper->calculateTotalTax($qb_data) : [0, ''];
        $custom_surcharge1 = $qb_service ? $qb_service->helper->checkIfDiscountAfterTax($qb_data) : 0;

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
            'line_items' => $qb_service ? $qb_service->helper->getLineItems($qb_data, $tax_array) : [],
            'payment_ids' => $qb_service ? $qb_service->helper->getPayments($qb_data) : [],
            'status_id' => Invoice::STATUS_SENT,
            'custom_surcharge1' => $custom_surcharge1,
            'balance' => data_get($qb_data, 'Balance', 0),

        ] : false;
    }

}
