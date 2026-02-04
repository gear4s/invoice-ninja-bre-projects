<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2025. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Services\Quickbooks\Models;

use Carbon\Carbon;
use App\Models\Expense;
use App\Models\Invoice;
use App\DataMapper\ExpenseSync;
use App\DataMapper\InvoiceSync;
use App\Factory\ExpenseFactory;
use App\Factory\InvoiceFactory;
use App\Interfaces\SyncInterface;
use App\Repositories\ExpenseRepository;
use App\Services\Quickbooks\QuickbooksService;
use App\Services\Quickbooks\Transformers\ExpenseTransformer;
use App\Services\Quickbooks\Transformers\PaymentTransformer;

class QbExpense implements SyncInterface
{
    protected ExpenseTransformer $expense_transformer;

    protected ExpenseRepository $expense_repository;

    public function __construct(public QuickbooksService $service)
    {
        $this->expense_transformer = new ExpenseTransformer($this->service->company);
        $this->expense_repository = new ExpenseRepository();
    }

    public function find(string $id): mixed
    {
        return $this->service->sdk->FindById('Expense', $id);
    }

    public function syncToNinja(array $records): void
    {

        foreach ($records as $record) {

            $this->syncNinjaExpense($record);

        }

    }

    public function importToNinja(array $records): void
    {

        foreach ($records as $record) {

            $ninja_expense_data = $this->expense_transformer->qbToNinja($record);

            if ($expense = $this->findExpense($ninja_expense_data['id'])) {

                if ($expense->id) {
                    $this->qbExpenseUpdate($ninja_expense_data, $expense);
                }

                if (Expense::where('company_id', $this->service->company->id)
                    ->whereNotNull('number')
                    ->where('number', $ninja_expense_data['number'])
                    ->exists()) {
                    $ninja_expense_data['number'] = 'qb_' . $ninja_expense_data['number'] . '_' . rand(1000, 99999);
                }

                $expense->fill($ninja_expense_data);
                $expense->saveQuietly();

            }

            $ninja_invoice_data = false;


        }

    }

    public function syncToForeign(array $records): void
    {
        foreach ($records as $invoice) {
            if (!$invoice instanceof Invoice) {
                continue;
            }

            // Check if sync direction allows push
            if (!$this->service->syncable('invoice', \App\Enum\SyncDirection::PUSH)) {
                continue;
            }

            try {
                // Transform invoice to QuickBooks format
                $qb_invoice_data = $this->invoice_transformer->ninjaToQb($invoice, $this->service);

                // If updating, fetch SyncToken using existing find() method
                if (isset($invoice->sync->qb_id) && !empty($invoice->sync->qb_id)) {
                    $existing_qb_invoice = $this->find($invoice->sync->qb_id);
                    if ($existing_qb_invoice) {
                        $qb_invoice_data['SyncToken'] = $existing_qb_invoice->SyncToken ?? '0';
                    }
                }

                // Create or update invoice in QuickBooks
                $qb_invoice = \QuickBooksOnline\API\Facades\Invoice::create($qb_invoice_data);

                if (isset($invoice->sync->qb_id) && !empty($invoice->sync->qb_id)) {
                    // Update existing invoice
                    $result = $this->service->sdk->Update($qb_invoice);
                    nlog("QuickBooks: Updated invoice {$invoice->id} (QB ID: {$invoice->sync->qb_id})");
                } else {
                    // Create new invoice
                    $result = $this->service->sdk->Add($qb_invoice);

                    // Store QB ID in invoice sync
                    $sync = new InvoiceSync();
                    $sync->qb_id = data_get($result, 'Id') ?? data_get($result, 'Id.value');
                    $invoice->sync = $sync;
                    $invoice->saveQuietly();

                    nlog("QuickBooks: Created expense {$invoice->id} (QB ID: {$sync->qb_id})");
                }
            } catch (\Exception $e) {
                nlog("QuickBooks: Error pushing expense {$invoice->id} to QuickBooks: {$e->getMessage()}");
                // Continue with next invoice instead of failing completely
                continue;
            }
        }
    }

    // private function qbInvoiceUpdate(array $ninja_invoice_data, Invoice $invoice): void
    // {
    //     $current_ninja_invoice_balance = $invoice->balance;
    //     $qb_invoice_balance = $ninja_invoice_data['balance'];

    //     if (floatval($current_ninja_invoice_balance) == floatval($qb_invoice_balance)) {
    //         nlog('Invoice balance is the same, skipping update of line items');
    //         unset($ninja_invoice_data['line_items']);
    //         $invoice->fill($ninja_invoice_data);
    //         $invoice->saveQuietly();
    //     } else {
    //         $this->expense_repository->save($ninja_expense_data, $expense);
    //     }
    // }

    private function findExpense(string $id): ?Expense
    {
        $search = Expense::query()
                            ->withTrashed()
                            ->where('company_id', $this->service->company->id)
                            ->where('sync->qb_id', $id);

        if ($search->count() == 0) {
            $expense = ExpenseFactory::create($this->service->company->id, $this->service->company->owner()->id);

            $sync = new ExpenseSync();
            $sync->qb_id = $id;
            $expense->sync = $sync;

            return $expense;
        } elseif ($search->count() == 1) {
            return $this->service->syncable('expense', \App\Enum\SyncDirection::PULL) ? $search->first() : null;
        }

        return null;

    }

    public function sync($id, string $last_updated): void
    {

        $qb_record = $this->find($id);


        if ($this->service->syncable('expense', \App\Enum\SyncDirection::PULL)) {

            $expense = $this->findExpense($id);

            nlog("Comparing QB last updated: " . $last_updated);
            nlog("Comparing Ninja last updated: " . $expense->updated_at);

            if (data_get($qb_record, 'TxnStatus') === 'Voided') {
                $this->delete($id);
                return;
            }

            if (!$expense->id) {
                $this->syncNinjaExpense($qb_record);
            } elseif (Carbon::parse($last_updated)->gt(Carbon::parse($expense->updated_at)) || $qb_record->SyncToken == '0') {
                $ninja_expense_data = $this->expense_transformer->qbToNinja($qb_record);

                $this->expense_repository->save($ninja_expense_data, $expense);

            }

        }
    }

    /**
     * syncNinjaInvoice
     *
     * @param  $record
     * @return void
     */
    public function syncNinjaInvoice($record): void
    {

        $ninja_invoice_data = $this->invoice_transformer->qbToNinja($record);

        $payment_ids = $ninja_invoice_data['payment_ids'] ?? [];

        $client_id = $ninja_invoice_data['client_id'] ?? null;

        if (is_null($client_id)) {
            return;
        }

        unset($ninja_invoice_data['payment_ids']);

        if ($invoice = $this->findInvoice($ninja_invoice_data['id'], $ninja_invoice_data['client_id'])) {

            if ($invoice->id) {
                $this->qbInvoiceUpdate($ninja_invoice_data, $invoice);
            }
            //new invoice scaffold
            $invoice->fill($ninja_invoice_data);
            $invoice->saveQuietly();

            $invoice = $invoice->calc()->getInvoice()->service()->markSent()->applyNumber()->createInvitations()->save();

            foreach ($payment_ids as $payment_id) {

                $payment = $this->service->sdk->FindById('Payment', $payment_id);

                $payment_transformer = new PaymentTransformer($this->service->company);

                $transformed = $payment_transformer->qbToNinja($payment);

                $ninja_payment = $payment_transformer->buildPayment($payment);
                $ninja_payment->service()->applyNumber()->save();

                $paymentable = new \App\Models\Paymentable();
                $paymentable->payment_id = $ninja_payment->id;
                $paymentable->paymentable_id = $invoice->id;
                $paymentable->paymentable_type = 'invoices';
                $paymentable->amount = $transformed['applied'] + $ninja_payment->credits->sum('amount');
                $paymentable->created_at = $ninja_payment->date; //@phpstan-ignore-line
                $paymentable->save();

                $invoice->service()->applyPayment($ninja_payment, $paymentable->amount);

            }

            if ($record instanceof \QuickBooksOnline\API\Data\IPPSalesReceipt) {
                $invoice->service()->markPaid()->save();
            }

        }

    }

    /**
     * Deletes the invoice from Ninja and sets the sync to null
     *
     * @param string $id
     * @return void
     */
    public function delete($id): void
    {
        $qb_record = $this->find($id);

        if ($this->service->syncable('expense', \App\Enum\SyncDirection::PULL) && $expense = $this->findExpense($id)) {
            $expense->sync = null;
            $expense->saveQuietly();
            $this->expense_repository->delete($expense);
        }
    }
}
