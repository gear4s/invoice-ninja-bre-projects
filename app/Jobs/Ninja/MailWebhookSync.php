<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Jobs\Ninja;

use App\Jobs\PostMark\ProcessPostmarkWebhook;
use App\Libraries\MultiDB;
use App\Models\CreditInvitation;
use App\Models\InvoiceInvitation;
use App\Models\PurchaseOrderInvitation;
use App\Models\QuoteInvitation;
use App\Models\RecurringInvoiceInvitation;
use App\Utils\Ninja;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Postmark\PostmarkClient;

class MailWebhookSync implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        if (!Ninja::isHosted()) {
            return;
        }

        /** Add to the logs any email deliveries that have not been sync'd */
        foreach (MultiDB::$dbs as $db) {
            MultiDB::setDB($db);

            $this->scanSentEmails();
        }
    }

    private function scanSentEmails()
    {
        $invitationTypes = [
            InvoiceInvitation::class,
            QuoteInvitation::class,
            RecurringInvoiceInvitation::class,
            CreditInvitation::class,
            PurchaseOrderInvitation::class,
        ];

        foreach ($invitationTypes as $model) {

            $query = $model::whereBetween('created_at', [now()->subHours(12), now()->subHour()])
                ->whereNotNull('message_id')
                ->whereNull('email_status')
                ->whereHas('company', function ($q) {
                    $q->where('settings->email_sending_method', 'default');
                });

            $this->runIterator($query);
        }
    }

    // private function scanSentEmails()
    // {

    //     $query = \App\Models\InvoiceInvitation::whereNotNull('message_id')
    //     ->whereNull('email_status')
    //     ->whereHas('company', function ($q) {
    //         $q->where('settings->email_sending_method', 'default');
    //     });

    //     $this->runIterator($query);

    //     $query = \App\Models\QuoteInvitation::whereNotNull('message_id')
    //     ->whereNull('email_status')
    //     ->whereHas('company', function ($q) {
    //         $q->where('settings->email_sending_method', 'default');
    //     });

    //     $this->runIterator($query);

    //     $query = \App\Models\RecurringInvoiceInvitation::whereNotNull('message_id')
    //     ->whereNull('email_status')
    //     ->whereHas('company', function ($q) {
    //         $q->where('settings->email_sending_method', 'default');
    //     });

    //     $this->runIterator($query);

    //     $query = \App\Models\CreditInvitation::whereNotNull('message_id')
    //     ->whereNull('email_status')
    //     ->whereHas('company', function ($q) {
    //         $q->where('settings->email_sending_method', 'default');
    //     });

    //     $this->runIterator($query);

    //     $query = \App\Models\PurchaseOrderInvitation::whereNotNull('message_id')
    //     ->whereNull('email_status')
    //     ->whereHas('company', function ($q) {
    //         $q->where('settings->email_sending_method', 'default');
    //     });

    //     $this->runIterator($query);

    // }

    private function runIterator($query)
    {
        // $query->whereBetween('created_at', [now()->subHours(12), now()->subHour()])
        // ->orderBy('id', 'desc')
        $query->each(function ($invite) {

            $token = config('services.postmark.token');
            $postmark = new PostmarkClient($token);

            $messageDetail = false;

            try {
                $messageDetail = $postmark->getOutboundMessageDetails($invite->message_id);
            } catch (\Throwable $th) {
                $token = config('services.postmark-outlook.token');
                $postmark = new PostmarkClient($token);

                try {
                    $messageDetail = $postmark->getOutboundMessageDetails($invite->message_id);
                } catch (\Throwable $th) {

                }

            }

            try {

                if (!$messageDetail) {
                    return true;
                }

                $data = [
                    'RecordType' => 'Delivery',
                    'ServerID' => 23,
                    'MessageStream' => 'outbound',
                    'MessageID' => $invite->message_id,
                    'Recipient' => collect($messageDetail->recipients)->first(),
                    'Tag' => $invite->company->company_key,
                    'DeliveredAt' => '2025-01-01T16:34:52Z',
                    'Metadata' => [

                    ],
                ];

                (new ProcessPostmarkWebhook($data, $token))->handle();

                $invite->sent_date = now();
                $invite->save();

            } catch (\Throwable $th) {
                nlog("MailWebhookSync:: {$th->getMessage()}");
            }

        });

    }

    public function middleware()
    {
        return [(new WithoutOverlapping('mail-webhook-sync'))->dontRelease()];
    }

    public function failed($exception)
    {
        nlog('MailWebhookSync:: Exception:: => ' . $exception->getMessage());
        config(['queue.failed.driver' => null]);
    }
}
