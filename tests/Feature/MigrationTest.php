<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Session;
use Tests\MockAccountData;
use Tests\TestCase;

/**
 *  App\Http\Controllers\MigrationController
 */
class MigrationTest extends TestCase
{
    use DatabaseTransactions;
    use MockAccountData;

    protected function setUp(): void
    {
        parent::setUp();

        Session::start();
        Model::reguard();

        $this->makeTestData();
    }

    public function test_company_exists()
    {
        $co = Company::find($this->company->id);

        // $this->assertNull($this->company);
        $this->assertNotNull($co);
    }

    public function test_that_company_deletes_completely()
    {
        $company_id = $this->company->id;

        $this->company->delete();
        $this->company->fresh();

        $co = Company::find($company_id);

        // $this->assertNull($this->company);
        $this->assertNull($co);
    }

    public function test_company_child_deletes()
    {
        $this->makeTestData();

        $this->assertNotNull($this->company);

        $co = Client::whereCompanyId($this->company->id)->get();
        $inv = Invoice::whereCompanyId($this->company->id)->get();

        $this->assertEquals($co->count(), 1);
        $this->assertEquals($inv->count(), 1);
    }
}
