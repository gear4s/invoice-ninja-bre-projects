<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Controllers;

use App\Http\Requests\Quickbooks\DisconnectQuickbooksRequest;
use App\Http\Requests\Quickbooks\SyncQuickbooksRequest;
use App\Http\Requests\Quickbooks\SyncTaxRatesRequest;
use App\Models\User;
use App\Services\Quickbooks\Jobs\QuickbooksImport;
use App\Services\Quickbooks\QuickbooksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class QuickbooksController extends BaseController
{
    /**
     * sync
     *
     * Syncs the Quickbooks entities to Invoice Ninja
     *
     * @return Response
     */
    public function sync(SyncQuickbooksRequest $request)
    {

        $user = auth()->user();
        $company = $user->company();

        $syncable = [];

        if ($request->client) {
            $syncable[] = 'Customer';
        }
        if ($request->product) {
            $syncable[] = 'Item';
        }
        if ($request->invoice) {
            $syncable[] = 'Invoice';
        }

        QuickbooksImport::dispatch($company->id, $company->db, $syncable);

        return response()->noContent();
    }

    /**
     * syncTaxRates
     *
     * Syncs tax rates from Quickbooks to Invoice Ninja
     *
     * @return Response
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
     * @return Response
     */
    public function disconnect(DisconnectQuickbooksRequest $request)
    {

        $user = auth()->user();
        $company = $user->company();

        try {
            $qb = new QuickbooksService($company);
            $qb->disconnect();
        } catch (\Throwable $e) {
            /** Regardless of what happens, we should always set the quickbooks object to null */
            $company->quickbooks = null;
            $company->save();

        }

        return response()->noContent();
    }

    /**
     * reconnectUrl
     *
     * Returns the URL for the user to reconnect their QuickBooks account.
     *
     * @return JsonResponse
     */
    public function reconnectUrl(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();
        $company = $user->company();

        if (!$company->quickbooks || !$company->quickbooks->isConfigured()) {
            return response()->json(['error' => 'No QuickBooks connection exists'], 400);
        }

        // Generate a one-time token for the reconnect flow
        $token = Str::random(64);

        Cache::put($token, [
            'context' => 'quickbooks.reconnect',
            'company_key' => $company->company_key,
            'user_id' => $user->id,
        ], now()->addMinutes(30));

        return response()->json([
            'reconnect_url' => route('quickbooks.reconnect', ['token' => $token]),
            'requires_reconnect' => $company->quickbooks->requires_reconnect,
            'refresh_token_expires_at' => $company->quickbooks->refreshTokenExpiresAt,
        ]);
    }
}
