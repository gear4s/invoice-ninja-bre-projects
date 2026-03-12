<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Jobs\Invoice;

use App\Jobs\Entity\CreateBatchablePdf;
use App\Libraries\MultiDB;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Throwable;

class PrintEntityBatch implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private mixed $class, private array $entity_ids, private string $db) {}

    public function handle()
    {

        MultiDB::setDb($this->db);

        $batch_key = Str::uuid();

        $invites = $this->class::with('invitations')->withTrashed()
            ->whereIn('id', $this->entity_ids)
            ->get()
            ->map(function ($entity) use ($batch_key) {
                return new CreateBatchablePdf($entity->invitations->first(), "{$batch_key}-{$entity->id}");
            })->toArray();

        $mergedPdf = null;

        $batch = Bus::batch($invites)
            ->before(function (Batch $batch) {
                // The batch has been created but no jobs have been added...
                // nlog("before");
            })->progress(function (Batch $batch) {
                // A single job has completed successfully...
                // nlog("Batch {$batch->id} is {$batch->progress()}% complete");
            })->then(function (Batch $batch) {
                // All jobs completed successfully...
                // nlog("job finished");

            })->catch(function (Batch $batch, Throwable $e) {
                // First batch job failure detected...
                nlog("PrintEntityBatch failed: {$e->getMessage()}");
            })->finally(function (Batch $batch) {
                // The batch has finished executing...
                // nlog("I have finished");
            })->name($batch_key)->dispatch();

        return $batch->id;

    }
}
