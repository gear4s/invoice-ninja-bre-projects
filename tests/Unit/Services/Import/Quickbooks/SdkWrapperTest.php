<?php

// tests/Unit/IntuitSDKWrapperTest.php

namespace Tests\Unit\Services\Import\Quickbooks;

use App\Services\Quickbooks\Contracts\SdkInterface;
use App\Services\Quickbooks\SdkWrapper as QuickbookSDK;
use Illuminate\Support\Arr;
use Mockery;
use Tests\TestCase;

class SdkWrapperTest extends TestCase
{
    protected $sdk;

    protected $sdkMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('no bueno');

        $this->sdkMock = Mockery::mock(\stdClass::class);
        $this->sdk = new QuickbookSDK($this->sdkMock);

        $this->markTestSkipped('no resource');
    }

    public function test_is_instance_of()
    {
        $this->assertInstanceOf(SdkInterface::class, $this->sdk);
    }

    public function test_method_fetch_records()
    {
        $data = json_decode(
            file_get_contents(base_path('tests/Mock/Quickbooks/Data/customers.json')),
            true
        );
        $count = count($data);
        $this->sdkMock->shouldReceive('Query')->andReturnUsing(function ($val) use ($count, $data) {
            if (stristr($val, 'count')) {
                return $count;
            }

            return Arr::take($data, 4);
        });

        $this->assertEquals($count, $this->sdk->totalRecords('Customer'));
        $this->assertEquals(4, count($this->sdk->fetchRecords('Customer', 4)));
    }
}
