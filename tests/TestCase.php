<?php

namespace Tests;

define('LARAVEL_START', microtime(true));

use App\Utils\Traits\AppSetup;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use AppSetup;
    use CreatesApplication;

    protected function setUp(): void
    {

        parent::setUp();
    }
}
