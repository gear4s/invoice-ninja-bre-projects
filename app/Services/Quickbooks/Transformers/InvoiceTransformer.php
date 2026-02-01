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

        $ast = $qb_service->company->quickbooks->settings->automatic_taxes;

        foreach ($invoice->line_items as $line_item) {
            // Get product's QuickBooks ID if it exists
            $product_qb_id = $qb_service->product->findOrCreateProduct($line_item);

            // Determine if line item is taxable (for TaxCodeRef)
            // TaxCodeRef indicates taxable status, but actual tax calculation is done at invoice level via TxnTaxDetail
            // NEVER assign a default tax - if there's no line item tax, it must be NON
            $tax_code_id = 'NON'; // Default to non-taxable
            
            // Check if tax_id is set and is exempt/zero rate (5 = exempt, 8 = zero rate)
            if(isset($line_item->tax_id) && in_array($line_item->tax_id, ['5', '8'])){
                $tax_code_id = 'NON';
            }
            elseif($ast){
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
            
            // Try to get discount account ID from QuickBooks settings or fetch it
            $discount_account_id = $this->getDiscountAccountId($qb_service);
            
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
            'GlobalTaxCalculation' => 'TaxExcluded',
        ];
        
        // Add TxnTaxDetail if invoice has taxes
        if(!$qb_service->company->quickbooks->settings->automatic_taxes) {
        // if ($total_taxes > 0 && !$invoice->client->is_tax_exempt) {
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
            $public_notes = $this->cleanHtmlText($public_notes);
            $terms = $this->cleanHtmlText($terms);
            
            // Combine public notes and terms
            $memo_value = trim($public_notes . ($public_notes && $terms ? "\n\n" : '') . $terms);
            
            if ($memo_value) {
                $invoice_data['CustomerMemo'] = [
                    'value' => $memo_value,
                ];
            }
        }

        if ($invoice->private_notes) {
            $invoice_data['PrivateNote'] = $this->cleanHtmlText($invoice->private_notes);
        }

        if ($invoice->po_number) {
            $invoice_data['PONumber'] = $invoice->po_number;
        }

        // Add partial deposit if invoice has a partial payment amount
        // QuickBooks uses 'Deposit' field for partial payments/deposits
        if ($invoice->partial && $invoice->partial > 0) {
            $invoice_data['Deposit'] = $invoice->partial;
            
            // Note: QuickBooks doesn't have a separate 'DepositDueDate' field
            // The deposit due date would typically be handled via payment terms or custom fields
            // For now, we'll set the deposit amount and the main DueDate will reflect the final payment due date
        }

        // If invoice already has a QB ID, include it for updates
        // Note: SyncToken will be fetched in QbInvoice::syncToForeign using the existing find() method
        if (isset($invoice->sync->qb_id) && !empty($invoice->sync->qb_id)) {
            $invoice_data['Id'] = $invoice->sync->qb_id;
        }

        nlog($invoice_data);
        
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
        
        // Process tax_name1/rate1
        if (!empty($invoice->tax_name1) && !empty($invoice->tax_rate1) && $invoice->tax_rate1 > 0) {
            $tax_amount = ($taxable_amount * $invoice->tax_rate1) / 100;
            $calculated_total_tax += $tax_amount;
            
            $tax_rate_id = $this->findTaxRate($invoice->tax_rate1, $invoice->tax_name1, $qb_service);
            
            if ($tax_rate_id) {
                $tax_lines[] = [
                    'Amount' => round($tax_amount, 2),
                    'DetailType' => 'TaxLineDetail',
                    'TaxLineDetail' => [
                        'TaxRateRef' => [
                            'value' => $tax_rate_id,
                        ],
                        'PercentBased' => true,
                        'TaxPercent' => round($invoice->tax_rate1, 2),
                        'NetAmountTaxable' => round($taxable_amount, 2),
                    ],
                ];
            }
        }
        
        // Process tax_name2/rate2
        if (!empty($invoice->tax_name2) && !empty($invoice->tax_rate2) && $invoice->tax_rate2 > 0) {
            $tax_amount = ($taxable_amount * $invoice->tax_rate2) / 100;
            $calculated_total_tax += $tax_amount;
            
            $tax_rate_id = $this->findTaxRate($invoice->tax_rate2, $invoice->tax_name2, $qb_service);
            
            if ($tax_rate_id) {
                $tax_lines[] = [
                    'Amount' => round($tax_amount, 2),
                    'DetailType' => 'TaxLineDetail',
                    'TaxLineDetail' => [
                        'TaxRateRef' => [
                            'value' => $tax_rate_id,
                        ],
                        'PercentBased' => true,
                        'TaxPercent' => round($invoice->tax_rate2, 2),
                        'NetAmountTaxable' => round($taxable_amount, 2),
                    ],
                ];
            }
        }
        
        // Process tax_name3/rate3
        if (!empty($invoice->tax_name3) && !empty($invoice->tax_rate3) && $invoice->tax_rate3 > 0) {
            $tax_amount = ($taxable_amount * $invoice->tax_rate3) / 100;
            $calculated_total_tax += $tax_amount;
            
            $tax_rate_id = $this->findTaxRate($invoice->tax_rate3, $invoice->tax_name3, $qb_service);
            
            if ($tax_rate_id) {
                $tax_lines[] = [
                    'Amount' => round($tax_amount, 2),
                    'DetailType' => 'TaxLineDetail',
                    'TaxLineDetail' => [
                        'TaxRateRef' => [
                            'value' => $tax_rate_id,
                        ],
                        'PercentBased' => true,
                        'TaxPercent' => round($invoice->tax_rate3, 2),
                        'NetAmountTaxable' => round($taxable_amount, 2),
                    ],
                ];
            }
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
     * Find a TaxRate in QuickBooks by name or rate.
     * TaxRates are read-only in QuickBooks and cannot be created via API.
     * 
     * @param float $tax_rate The tax rate percentage
     * @param string $tax_name The tax name
     * @param \App\Services\Quickbooks\QuickbooksService $qb_service
     * @return string|null The QuickBooks TaxRate ID, or null if not found
     */
    private function findTaxRate(float $tax_rate, string $tax_name, \App\Services\Quickbooks\QuickbooksService $qb_service): ?string
    {
        try {
            $rounded_rate = round($tax_rate, 2);
            $rate_name = $tax_name ?: "Tax {$rounded_rate}%";
            
            // Fetch all TaxRates from QuickBooks
            $tax_rates = $qb_service->fetchTaxRates();
            
            if (empty($tax_rates)) {
                nlog("QuickBooks: No TaxRates found in QuickBooks. TaxRates must be created manually in QuickBooks.");
                return null;
            }
            
            // First, try to find by exact name match
            foreach ($tax_rates as $tax_rate_obj) {
                $qb_name = data_get($tax_rate_obj, 'Name');
                if ($qb_name === $rate_name) {
                    $tax_rate_id = data_get($tax_rate_obj, 'Id') ?? data_get($tax_rate_obj, 'Id.value');
                    if ($tax_rate_id) {
                        nlog("QuickBooks: Found TaxRate '{$rate_name}' in QuickBooks (QB ID: {$tax_rate_id})");
                        return (string) $tax_rate_id;
                    }
                }
            }
            
            // If not found by name, try to find by rate value
            // Check TaxRateDetails for matching rate
            foreach ($tax_rates as $tax_rate_obj) {
                $tax_rate_details = data_get($tax_rate_obj, 'TaxRateDetails');
                if (is_array($tax_rate_details)) {
                    foreach ($tax_rate_details as $detail) {
                        $rate_value = data_get($detail, 'RateValue');
                        if ($rate_value && abs((float)$rate_value - $rounded_rate) < 0.01) {
                            $tax_rate_id = data_get($tax_rate_obj, 'Id') ?? data_get($tax_rate_obj, 'Id.value');
                            if ($tax_rate_id) {
                                $qb_name = data_get($tax_rate_obj, 'Name', 'Unknown');
                                nlog("QuickBooks: Found TaxRate by rate ({$rounded_rate}%) - '{$qb_name}' (QB ID: {$tax_rate_id})");
                                return (string) $tax_rate_id;
                            }
                        }
                    }
                }
            }
            
            // If still not found, use the first available TaxRate as fallback
            $first_tax_rate = $tax_rates[0];
            $fallback_id = data_get($first_tax_rate, 'Id') ?? data_get($first_tax_rate, 'Id.value');
            if ($fallback_id) {
                $fallback_name = data_get($first_tax_rate, 'Name', 'Unknown');
                nlog("QuickBooks: TaxRate '{$rate_name}' ({$rounded_rate}%) not found. Using fallback TaxRate '{$fallback_name}' (QB ID: {$fallback_id}). Please create matching TaxRates in QuickBooks for accurate tax tracking.");
                return (string) $fallback_id;
            }
            
            nlog("QuickBooks: Warning - TaxRate '{$rate_name}' ({$rounded_rate}%) not found and no fallback available.");
            return null;
        } catch (\Exception $e) {
            $rate_name = $tax_name ?: "Tax " . round($tax_rate, 2) . "%";
            nlog("QuickBooks: Error finding TaxRate '{$rate_name}': {$e->getMessage()}");
            return null;
        }
    }


    /**
     * Clean HTML text by replacing <br> tags with newlines and stripping all HTML tags.
     * 
     * @param string $text The text to clean
     * @return string The cleaned text
     */
    private function cleanHtmlText(string $text): string
    {
        if (empty($text)) {
            return '';
        }
        
        // Replace <br> and <br/> tags (case insensitive) with newlines
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        
        // Strip all remaining HTML tags
        $text = strip_tags($text);
        
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Clean up multiple consecutive newlines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }

    /**
     * Get a discount account ID from QuickBooks.
     * Looks for an account with AccountType 'Income' and name containing 'Discount'.
     * 
     * @param \App\Services\Quickbooks\QuickbooksService $qb_service
     * @return string|null The discount account ID, or null if not found
     */
    private function getDiscountAccountId(\App\Services\Quickbooks\QuickbooksService $qb_service): ?string
    {
        try {
            // Query for discount accounts (typically named "Discounts Given" or similar)
            $query = "SELECT * FROM Account WHERE AccountType = 'Income' AND Active = true";
            $accounts = $qb_service->sdk->Query($query);
            
            if (!empty($accounts)) {
                // Look for account with "Discount" in the name
                foreach ($accounts as $account) {
                    $name = is_object($account) ? ($account->Name ?? '') : ($account['Name'] ?? '');
                    if (stripos($name, 'discount') !== false) {
                        $id = is_object($account) ? ($account->Id ?? null) : ($account['Id'] ?? null);
                        if ($id) {
                            return (string) $id;
                        }
                    }
                }
                
                // If no discount account found, use first income account as fallback
                $first_account = $accounts[0];
                $id = is_object($first_account) ? ($first_account->Id ?? null) : ($first_account['Id'] ?? null);
                if ($id) {
                    return (string) $id;
                }
            }
        } catch (\Exception $e) {
            nlog("QuickBooks: Error fetching discount account: {$e->getMessage()}");
        }
        
        return null;
    }

    /**
     * Get an income account ID from stored QuickBooks settings or fallback to querying.
     * 
     * Priority:
     * 1. Use company->quickbooks->settings->qb_income_account_id if set
     * 2. Use first account from company->quickbooks->settings->income_account_map
     * 3. Fallback to querying QuickBooks API
     * 
     * @param \App\Services\Quickbooks\QuickbooksService $qb_service
     * @return string|null The income account ID, or null if not found
     */
    private function getIncomeAccountId(\App\Services\Quickbooks\QuickbooksService $qb_service): ?string
    {
        // First, check if default income account ID is set in settings
        $default_account_id = $this->company->quickbooks->settings->qb_income_account_id ?? null;
        if (!empty($default_account_id)) {
            return (string) $default_account_id;
        }
        
        // Second, check income_account_map and use the first account
        $income_account_map = $this->company->quickbooks->settings->income_account_map ?? [];
        if (!empty($income_account_map) && isset($income_account_map[0]['id'])) {
            return (string) $income_account_map[0]['id'];
        }
        
        // Fallback: Query QuickBooks API if no stored settings available
        try {
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
