<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Observers;

use App\Jobs\User\VerifyPhone;
use App\Models\User;
use App\Utils\Ninja;
use Modules\Admin\Jobs\Account\FieldQuality;
use Modules\Admin\Jobs\Account\UpdateOwnerUser;

class UserObserver
{
    /**
     * Handle the app models user "created" event.
     *
     * @return void
     */
    public function created(User $user)
    {
        if (Ninja::isHosted() && isset($user->phone)) {
            VerifyPhone::dispatch($user);
        }
    }

    /**
     * Handle the app models user "updated" event.
     *
     * @return void
     */
    public function updated(User $user)
    {
        if (Ninja::isHosted() && $user->isDirty('email') && $user->company_users()->where('is_owner', true)->exists()) {
            // ensure they are owner user and update email on file.
            if (class_exists(UpdateOwnerUser::class)) {
                UpdateOwnerUser::dispatch($user->account->key, $user, $user->getOriginal('email'));
            }
        }

        if (Ninja::isHosted() && $user->isDirty('first_name') || $user->isDirty('last_name')) {

            try {
                (new FieldQuality)->checkUserName($user, $user->account->companies->first());
            } catch (\Throwable $e) {
                nlog(['user_name_check', $e->getMessage()]);
            }

        }

    }

    /**
     * Handle the app models user "deleted" event.
     *
     * @return void
     */
    public function deleted(User $user)
    {
        //
    }

    /**
     * Handle the app models user "restored" event.
     *
     * @return void
     */
    public function restored(User $user)
    {
        //
    }

    /**
     * Handle the app models user "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(User $user)
    {
        //
    }
}
