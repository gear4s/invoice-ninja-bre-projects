<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Unit\Migration;

use App\Jobs\Util\Import;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MockAccountData;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use DatabaseTransactions;
    use MockAccountData;

    public $migration_array;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTestData();

        $migration_file = base_path() . '/tests/Unit/Migration/migration.json';

        $this->migration_array = json_decode(file_get_contents($migration_file), 1);
    }

    public function test_import_class_exists()
    {
        $status = class_exists(Import::class);

        $this->assertTrue($status);
    }
}
