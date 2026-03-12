<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Events\Client;

use App\Models\Company;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

/**
 * Class ClientWasMerged.
 */
class ClientWasPurged
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public string $purged_client, public User $user, public Company $company, public array $event_vars) {}
}
