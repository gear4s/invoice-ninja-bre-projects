<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Events\Invoice;

use App\Models\BaseModel;
use App\Models\Company;
use App\Models\InvoiceInvitation;
use App\Utils\Traits\Invoice\Broadcasting\DefaultResourceBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use League\Fractal\Manager;

/**
 * Class InvoiceWasViewed.
 */
class InvoiceWasViewed implements ShouldBroadcast
{
    use DefaultResourceBroadcast;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public InvoiceInvitation $invitation, public Company $company, public array $event_vars)
    {
        //
    }

    public function broadcastModel(): BaseModel
    {
        return $this->invitation->invoice;
    }

    public function broadcastManager(Manager $manager): Manager
    {
        $manager->parseIncludes('client');

        return $manager;
    }

    public function broadcastIncludes(): array
    {
        return [
            'client',
        ];
    }
}
