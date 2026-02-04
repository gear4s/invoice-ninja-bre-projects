<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Controllers;

use App\Http\Requests\Quickbooks\AuthorizedQuickbooksRequest;
use App\Libraries\MultiDB;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\Quickbooks\AuthQuickbooksRequest;
use App\Models\TaxRate;
use App\Services\Quickbooks\QuickbooksService;

class ImportQuickbooksController extends BaseController
{
    /**
     * authorizeQuickbooks
     *
     * Starts the Quickbooks authorization process.
     *
     * @param  AuthQuickbooksRequest $request
     * @param  string $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function authorizeQuickbooks(AuthQuickbooksRequest $request, string $token)
    {

        MultiDB::findAndSetDbByCompanyKey($request->getTokenContent()['company_key']);

        $company = $request->getCompany();

        $qb = new QuickbooksService($company);

        $authorizationUrl = $qb->sdk()->getAuthorizationUrl();

        $state = $qb->sdk()->getState();

        Cache::put($state, $token, 190);

        return redirect()->to($authorizationUrl);
    }

    /**
     * onAuthorized
     *
     * Handles the callback from Quickbooks after authorization.
     *
     * @param  AuthorizedQuickbooksRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function onAuthorized(AuthorizedQuickbooksRequest $request)
    {

        nlog($request->all());

        MultiDB::findAndSetDbByCompanyKey($request->getTokenContent()['company_key']);

        $company = $request->getCompany();

        $qb = new QuickbooksService($company);

        $realm = $request->query('realmId');

        nlog($realm);

        $access_token_object = $qb->sdk()->accessTokenFromCode($request->query('code'), $realm);

        nlog($access_token_object);

        $qb->sdk()->saveOAuthToken($access_token_object);

        // Refresh the service to initialize SDK with the new access token
        $qb->refresh();

        $companyInfo = $qb->sdk()->company();

        $income_accounts = $qb->fetchIncomeAccounts();
        $tax_rates = $qb->fetchTaxRates();
        $company_preferences = $qb->sdk()->getPreferences();
        $automatic_taxes = data_get($company_preferences, 'TaxPrefs.PartnerTaxEnabled', false);

        $company->quickbooks->settings->tax_rate_map = $tax_rates;
        $company->quickbooks->settings->income_account_map = $income_accounts;
        $company->quickbooks->settings->qb_income_account_id = $income_accounts[0]['id'] ?? null;
        $company->quickbooks->companyName = $companyInfo->CompanyName ?? '';
        $company->quickbooks->settings->automatic_taxes = $automatic_taxes;
        $company->save();

        nlog($companyInfo);

        // We need to sync and align the tax rates used in Invoice Ninja with the tax rates used in Quickbooks.

        // Get all Invoice Ninja tax rates for this company and archived them
        TaxRate::where('company_id', $company->id)
                ->cursor()
                ->each(function ($tax) {
                    $tax->delete();
                });

        // Iterate through the Quickbooks tax rates and create new Invoice Ninja tax rates
        foreach ($tax_rates as $tax_rate) {
            $tr = new TaxRate();
            $tr->company_id = $company->id;
            $tr->user_id = $company->owner()->id;
            $tr->name = $tax_rate['name'];
            $tr->rate = $tax_rate['rate'];
            $tr->save();
        }

        return redirect(config('ninja.react_url'));

    }


}
