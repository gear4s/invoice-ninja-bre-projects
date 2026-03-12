<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Events\Socket;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

/**
 * Class DownloadAvailable.
 */
class DownloadAvailable implements ShouldBroadcast
{
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public string $url, public string $message, public User $user) {}

    public function broadcastOn()
    {
        return [
            new PrivateChannel("user-{$this->user->account->key}-{$this->user->id}"),
        ];
    }

    public function broadcastWith(): array
    {

        // ctrans('texts.document_download_subject');

        return [
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
