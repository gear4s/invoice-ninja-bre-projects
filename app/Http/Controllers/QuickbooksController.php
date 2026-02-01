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

namespace App\Http\Controllers;

use App\Enum\SyncDirection;
use App\Services\Quickbooks\QuickbooksService;
use App\Http\Requests\Quickbooks\SyncTaxRatesRequest;
use App\Http\Requests\Quickbooks\DisconnectQuickbooksRequest;
use App\Http\Requests\Quickbooks\SyncQuickbooksRequest;
use App\Http\Requests\Quickbooks\ConfigQuickbooksRequest;

class QuickbooksController extends BaseController
{

    public function sync(SyncQuickbooksRequest $request)
    {
        
        return response()->noContent();
    }

    public function configuration(ConfigQuickbooksRequest $request)
    {
        
        $user = auth()->user();
        $company = $user->company();
        
        $quickbooks = $company->quickbooks;
        $quickbooks->settings->client->direction = $request->clients ? SyncDirection::PUSH : SyncDirection::NONE;
        $quickbooks->settings->vendor->direction = $request->vendors ? SyncDirection::PUSH : SyncDirection::NONE;
        $quickbooks->settings->product->direction = $request->products ? SyncDirection::PUSH : SyncDirection::NONE;
        $quickbooks->settings->invoice->direction = $request->invoices ? SyncDirection::PUSH : SyncDirection::NONE;
        $quickbooks->settings->quote->direction = $request->quotes ? SyncDirection::PUSH : SyncDirection::NONE;
        $quickbooks->settings->payment->direction = $request->payments ? SyncDirection::PUSH : SyncDirection::NONE;
        $quickbooks->settings->expense->direction = $request->expenses ? SyncDirection::PUSH : SyncDirection::NONE;
        $quickbooks->settings->expense_category->direction = $request->expense_categories ? SyncDirection::PUSH : SyncDirection::NONE;
        $company->quickbooks = $quickbooks;
        $company->save();

        return response()->noContent();
    }

    
    /**
     * syncTaxRates
     * 
     * Syncs tax rates from Quickbooks to Invoice Ninja
     *
     * @param  SyncTaxRatesRequest $request
     * @return \Illuminate\Http\Response
     */
    public function syncTaxRates(SyncTaxRatesRequest $request)
    {
        $user = auth()->user();
        $company = $user->company();

        $qb = new QuickbooksService($company);
        $qb->syncTaxRates();
        
        return response()->noContent();
    }
    
    /**
     * disconnect
     * 
     * Disconnects the Quickbooks Account From the Invoice Ninja Company
     * 
     * @param  DisconnectQuickbooksRequest $request
     * @return \Illuminate\Http\Response
     */
    public function disconnect(DisconnectQuickbooksRequest $request)
    {
        
        $user = auth()->user();
        $company = $user->company();

        $qb = new QuickbooksService($company);
        $qb->disconnect();
        
        return response()->noContent();
    }
}