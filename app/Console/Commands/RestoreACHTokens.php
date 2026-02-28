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

namespace App\Console\Commands;

use App\Libraries\MultiDB;
use App\Models\ClientGatewayToken;
use App\Models\GatewayType;
use Illuminate\Console\Command;

class RestoreACHTokens extends Command
{
    protected $signature = 'ninja:restore-ach-tokens {--restore} {--client_id=}';

    protected $description = 'Check and restore soft-deleted Stripe ACH payment methods that are still valid and chargeable in Stripe';

    private int $found = 0;
    private int $valid = 0;
    private int $restored = 0;
    private int $invalid = 0;
    private int $errors = 0;

    private const STRIPE_KEYS = [
        'd14dd26a37cecc30fdd65700bfb55b23',
        'd14dd26a47cecc30fdd65700bfb67b34',
    ];

    public function handle()
    {
        $isRestore = $this->option('restore');

        $this->info('=== Restore ACH Tokens ===');
        $this->info($isRestore ? 'Mode: RESTORE (changes will be applied)' : 'Mode: DRY RUN (no changes will be made)');
        $this->info('');

        if (config('ninja.db.multi_db_enabled')) {
            foreach (MultiDB::$dbs as $db) {
                MultiDB::setDB($db);
                $this->info("Processing database: {$db}");
                $this->processTokens();
            }
        } else {
            $this->processTokens();
        }

        $this->info('');
        $this->info('=== Summary ===');
        $this->info("Soft-deleted ACH tokens found: {$this->found}");
        $this->info("Valid & chargeable in Stripe: {$this->valid}");
        $this->info("Restored: {$this->restored}");
        $this->info("Invalid/not chargeable in Stripe: {$this->invalid}");
        $this->info("Errors (skipped): {$this->errors}");

        if (! $isRestore && $this->valid > 0) {
            $this->info('');
            $this->info('Run with --restore flag to apply changes: php artisan ninja:restore-ach-tokens --restore');
        }
    }

    private function processTokens(): void
    {
        $query = ClientGatewayToken::onlyTrashed()
            ->where('gateway_type_id', GatewayType::BANK_TRANSFER)
            ->whereHas('gateway', function ($q) {
                $q->whereIn('gateway_key', self::STRIPE_KEYS);
            });

        if ($this->option('client_id')) {
            $query->where('client_id', $this->option('client_id'));
        }

        $query->cursor()->each(function ($token) {
            $this->found++;
            $this->processToken($token);
        });
    }

    private function processToken(ClientGatewayToken $token): void
    {
        $this->info("Checking token #{$token->id} (client: {$token->client_id}, pm: {$token->token})");

        if (! $token->client) {
            $this->info("  -> SKIP: Client not found");
            $this->errors++;
            return;
        }

        if (! $token->gateway) {
            $this->info("  -> SKIP: Gateway not found");
            $this->errors++;
            return;
        }

        try {
            $stripe = $token->gateway->driver($token->client)->init();

            if (str_starts_with($token->token, 'ba_')) {
                $this->processLegacyBankAccount($token, $stripe);
            } else {
                $this->processPaymentMethod($token, $stripe);
            }
        } catch (\Exception $e) {
            $this->errors++;
            $this->info("  -> ERROR: {$e->getMessage()}");
        }
    }

    private function processPaymentMethod(ClientGatewayToken $token, $stripe): void
    {
        $pm = $stripe->getStripePaymentMethod($token->token);

        if (! $pm) {
            $this->invalid++;
            $this->info("  -> INVALID: Payment method not found in Stripe");
            return;
        }

        $status = $pm->us_bank_account->status ?? 'unknown';

        if (! in_array($status, ['verified', 'validated'])) {
            $this->invalid++;
            $this->info("  -> NOT CHARGEABLE: us_bank_account status is '{$status}'");
            return;
        }

        $this->valid++;
        $this->info("  -> VALID in Stripe (type: {$pm->type}, status: {$status})");

        if ($this->option('restore')) {
            $this->restoreToken($token);
        } else {
            $this->info("  -> WOULD RESTORE (dry run)");
        }
    }

    private function processLegacyBankAccount(ClientGatewayToken $token, $stripe): void
    {
        $customer = \Stripe\Customer::retrieve(
            $token->gateway_customer_reference,
            $stripe->stripe_connect_auth
        );

        $source = $customer->retrieveSource(
            $token->gateway_customer_reference,
            $token->token,
            $stripe->stripe_connect_auth
        );

        if (! $source) {
            $this->invalid++;
            $this->info("  -> INVALID: Bank account source not found in Stripe");
            return;
        }

        $status = $source->status ?? 'unknown';

        if ($status !== 'verified') {
            $this->invalid++;
            $this->info("  -> NOT CHARGEABLE: source status is '{$status}'");
            return;
        }

        $this->valid++;
        $this->info("  -> VALID in Stripe (legacy source, status: {$status})");

        if ($this->option('restore')) {
            $this->restoreToken($token);
        } else {
            $this->info("  -> WOULD RESTORE (dry run)");
        }
    }

    private function restoreToken(ClientGatewayToken $token): void
    {
        $token->is_deleted = false;
        $token->save();
        $token->restore();

        $meta = $token->meta;
        if ($meta) {
            $meta->state = 'authorized';
            $token->meta = $meta;
            $token->save();
        }

        $this->restored++;
        $this->info("  -> RESTORED token #{$token->id}");
    }
}
