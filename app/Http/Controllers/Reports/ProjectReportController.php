<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Report\ProjectReportRequest;
use App\Jobs\Report\PreviewReport;
use App\Jobs\Report\SendToAdmin;
use App\Models\User;
use App\Services\Report\ProjectReport;
use App\Utils\Traits\MakesHash;
use Illuminate\Support\Str;

class ProjectReportController extends BaseController
{
    use MakesHash;

    private string $filename = 'project_report.pdf';

    public function __construct()
    {
        parent::__construct();
    }

    public function __invoke(ProjectReportRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();

        if ($request->has('send_email') && $request->get('send_email') && $request->missing('output')) {
            SendToAdmin::dispatch($user->company(), $request->all(), ProjectReport::class, $this->filename);

            return response()->json(['message' => 'working...'], 200);
        }

        $hash = Str::uuid();

        PreviewReport::dispatch($user->company(), $request->all(), ProjectReport::class, $hash);

        return response()->json(['message' => $hash], 200);

    }
}
