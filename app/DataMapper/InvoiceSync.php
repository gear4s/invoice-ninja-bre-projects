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

namespace App\DataMapper;

use App\Casts\InvoiceSyncCast;
use Illuminate\Contracts\Database\Eloquent\Castable;

/**
 * InvoiceSync.
 */
class InvoiceSync implements Castable
{
    
    public function __construct(
        public string $qb_id = '',
        public ?string $dn_id = null,
        public ?string $dn_invitation_id = null,
        public ?string $dn_sig = null,
        public bool $dn_completed = false,
        public string $dn_contacts = '',
    ){}
     /**
     * Get the name of the caster class to use when casting from / to this cast target.
     *
     * @param  array<string, mixed>  $arguments
     */
    public static function castUsing(array $arguments): string
    {
        return InvoiceSyncCast::class;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            qb_id: $data['qb_id'] ?? '',
            dn_id: $data['dn_id'] ?? null,
            dn_completed: $data['dn_completed'] ?? false,
            dn_invitation_id: $data['dn_invitation_id'] ?? null,
            dn_sig: $data['dn_sig'] ?? null,
            dn_contacts: $data['dn_contacts'] ?? '',
        );
    }
}
