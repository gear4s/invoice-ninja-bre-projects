<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPortal\Tasks\ShowTasksRequest;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Show the tasks in the client portal.
     *
     * @return Factory|View
     */
    public function index(ShowTasksRequest $request)
    {
        Carbon::setLocale(
            auth()->guard('contact')->user()->preferredLocale()
        );

        return render('tasks.index');
    }
}
