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

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/**
 * Class EntityPolicy.
 */
class EntityPolicy
{
    /**
     * Fires before any of the custom policy methods.
     *
     * Only fires if true, if false we continue.....
     *
     * Do not use this function!!!! We MUST also check company_id,
     *
     * @param User $user
     * @param  $ability
     * @return void /void
     */
    public function before($user, $ability) {}

    /**
     * Checks if the user has edit permissions.
     *
     * For Client entities we check that the entity belongs to the same
     * *account* as the user (cross-company global clients).  For every other
     * entity type we still enforce the stricter same-company check.
     *
     * @param  User $user
     * @param  $entity
     * @return bool
     */
    public function edit(User $user, $entity): bool
    {
        if ($entity instanceof Client) {
            return $this->entityBelongsToAccount($user, $entity) &&
                ($user->isAdmin() ||
                    $user->hasPermission(
                        'edit_' .
                            \Illuminate\Support\Str::snake(
                                class_basename($entity),
                            ),
                    ) ||
                    $user->owns($entity) ||
                    $user->assigned($entity));
        }

        return ($user->isAdmin() &&
            $entity->company_id == $user->companyId()) ||
            ($user->hasPermission(
                'edit_' .
                    \Illuminate\Support\Str::snake(class_basename($entity)),
            ) &&
                $entity->company_id == $user->companyId()) ||
            ($user->owns($entity) &&
                $entity->company_id == $user->companyId()) ||
            ($user->assigned($entity) &&
                $entity->company_id == $user->companyId());
    }

    /**
     *  Checks if the user has view permissions.
     *
     * For Client entities we check that the entity belongs to the same
     * *account* as the user (cross-company global clients).  For every other
     * entity type we still enforce the stricter same-company check.
     *
     * @param  User $user
     * @param  $entity
     * @return bool
     */
    public function view(User $user, $entity): bool
    {
        if ($entity instanceof Client) {
            return $this->entityBelongsToAccount($user, $entity) &&
                ($user->isAdmin() ||
                    $user->hasPermission(
                        'view_' .
                            \Illuminate\Support\Str::snake(
                                class_basename($entity),
                            ),
                    ) ||
                    $user->owns($entity) ||
                    $user->assigned($entity));
        }

        return ($user->isAdmin() &&
            $entity->company_id == $user->companyId()) ||
            ($user->hasPermission(
                'view_' .
                    \Illuminate\Support\Str::snake(class_basename($entity)),
            ) &&
                $entity->company_id == $user->companyId()) ||
            ($user->owns($entity) &&
                $entity->company_id == $user->companyId()) ||
            ($user->assigned($entity) &&
                $entity->company_id == $user->companyId());
    }

    /**
     * Determines whether the given entity belongs to the same account as the
     * authenticated user.  Used for global (cross-company) client access.
     *
     * @param  User  $user
     * @param  mixed $entity
     * @return bool
     */
    protected function entityBelongsToAccount(User $user, $entity): bool
    {
        // account_id is denormalised onto Client rows; fall back to looking it
        // up through the company relationship if it isn't set for any reason.
        $entity_account_id =
            $entity->account_id ?? optional($entity->company)->account_id;

        return $entity_account_id == $user->account_id;
    }
}
