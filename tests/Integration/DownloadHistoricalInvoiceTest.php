<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Integration;

use App\Models\Activity;
use App\Repositories\ActivityRepository;
use App\Utils\Ninja;
use App\Utils\Traits\MakesHash;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MockAccountData;
use Tests\TestCase;

/**
 *  App\Http\Controllers\ActivityController
 */
class DownloadHistoricalInvoiceTest extends TestCase
{
    use DatabaseTransactions;
    use MakesHash;
    use MockAccountData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTestData();

        if (config('ninja.testvars.travis') !== false) {
            $this->markTestSkipped('Skip test for GH Actions');
        }
    }

    public function test_download_invoice_route()
    {

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get("/api/v1/invoices/{$this->invoice->hashed_id}/download");

        $response->assertStatus(200);
        $response->assertDownload();

    }

    public function test_download_delivery_route()
    {

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get("/api/v1/invoices/{$this->invoice->hashed_id}/delivery_note");

        $response->assertStatus(200);
        $response->assertDownload();

    }

    public function test_download_invoice_bulk_action_route()
    {
        $data = [
            'action' => 'download',
            'ids' => [$this->invoice->hashed_id],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/invoices/bulk', $data);

        $response->assertStatus(200);
        $response->assertDownload();

    }

    public function test_download_quote_route()
    {

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->get("/api/v1/quotes/{$this->quote->hashed_id}/download");

        $response->assertStatus(200);
        $response->assertDownload();

    }

    public function test_download_quote_bulk_action_route()
    {
        $data = [
            'action' => 'download',
            'ids' => [$this->quote->hashed_id],
        ];

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $this->token,
        ])->post('/api/v1/quotes/bulk', $data);

        $response->assertStatus(200);

    }

    private function mockActivity()
    {
        $activity_repo = new ActivityRepository;

        $obj = new \stdClass;
        $obj->invoice_id = $this->invoice->id;
        $obj->user_id = $this->invoice->user_id;
        $obj->company_id = $this->company->id;
        $obj->activity_type_id = Activity::EMAIL_INVOICE;
        $activity_repo->save($obj, $this->invoice, Ninja::eventVars());
    }

    public function test_activity_accessible()
    {
        $this->mockActivity();

        $this->assertNotNull($this->invoice->activities);
    }
}
