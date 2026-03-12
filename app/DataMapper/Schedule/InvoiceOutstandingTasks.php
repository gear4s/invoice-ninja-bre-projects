<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\DataMapper\Schedule;

class InvoiceOutstandingTasks
{
    /**
     * Defines the template name
     */
    public string $template = 'invoice_outstanding_tasks';

    /**
     * The date range the report should include
     */
    public string $date_range = 'this_month';

    /**
     * An array of clients hashed_ids
     *
     * Leave blank if this action should apply to all clients
     */
    public array $clients = [];

    /**
     * If true, the invoice will be auto-sent
     * else it will be generated and kept in a draft state
     */
    public bool $auto_send = false;

    /**
     * If true, the project tasks will be included in the report
     */
    public bool $include_project_tasks = false;
}
