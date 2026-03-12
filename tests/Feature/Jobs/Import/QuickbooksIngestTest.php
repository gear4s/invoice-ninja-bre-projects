<?php

namespace Tests\Feature\Jobs\Import;

use App\Jobs\Import\QuickbooksIngest;
use App\Models\Client;
use App\Utils\Traits\MakesHash;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\MockAccountData;
use Tests\TestCase;

class QuickbooksIngestTest extends TestCase
{
    use DatabaseTransactions;
    use MakesHash;
    use MockAccountData;

    protected $quickbooks;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => config('ninja.db.default')]);
        $this->markTestSkipped('no bueno');
        $this->makeTestData();
        $this->withoutExceptionHandling();
        Auth::setUser($this->user);

    }

    /**
     * A basic feature test example.
     */
    public function test_can_quickbooks_ingest(): void
    {
        $data = (json_decode(file_get_contents(base_path('tests/Feature/Import/customers.json')), true))['Customer'];
        $hash = Str::random(32);
        Cache::put($hash . '-client', base64_encode(json_encode($data)), 360);
        QuickbooksIngest::dispatch([
            'hash' => $hash,
            'column_map' => ['client' => ['mapping' => []]],
            'skip_header' => true,
            'import_types' => ['client'],
        ], $this->company)->handle();
        $this->assertTrue(Client::withTrashed()->where(['company_id' => $this->company->id, 'name' => 'Freeman Sporting Goods'])->exists());
    }
}
