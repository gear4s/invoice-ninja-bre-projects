<?php

namespace Tests\Unit\Services\Import\Quickbooks;

use App\Services\Quickbooks\Contracts\SdkInterface as QuickbooksInterface;
use App\Services\Quickbooks\Service as QuickbooksService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('no bueno');
        // Inject the mock into the IntuitSDKservice instance
        $this->service = Mockery::mock(new QuickbooksService(Mockery::mock(QuickbooksInterface::class)))->shouldAllowMockingProtectedMethods();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_total_records()
    {
        $entity = 'Customer';
        $count = 10;

        $this->service->shouldReceive('totalRecords')
            ->with($entity)
            ->andReturn($count);

        $result = $this->service->totalRecords($entity);

        $this->assertEquals($count, $result);
    }

    public function test_has_fetch_records()
    {
        $entity = 'Customer';
        $count = 10;

        $this->service->shouldReceive('fetchRecords')
            ->with($entity, $count)
            ->andReturn(collect());

        $result = $this->service->fetchCustomers($count);

        $this->assertInstanceOf(Collection::class, $result);
    }
}
