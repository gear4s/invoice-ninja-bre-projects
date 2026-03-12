<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Listeners\User;

use App\Libraries\MultiDB;
use App\Models\Company;
use App\Notifications\Ninja\GenericNinjaAdminNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateUserLoginFailed implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {

        $user = MultiDB::hasUser(['email' => $event->email]);

        if (!$user) {
            return;
        }

        $user->increment('failed_logins', 1);

        if ($user->failed_logins > 3) {
            $content = [
                "Multiple Logins failed for user: {$user->email}",
                "IP address: {$event->ip}",
            ];

            $company = Company::first();
            $company->notification(new GenericNinjaAdminNotification($content))->ninja();

        }

    }
}
