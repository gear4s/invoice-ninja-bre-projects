<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('company-{company_key}', function (User $user, string $company_key) {
    return $user->company()->company_key === $company_key;
});

Broadcast::channel('user-{account_key}-{user_id}', function (User $user, string $account_key, string $user_id) {
    return $user->account->key === $account_key && $user->id === (int) $user_id;
});
