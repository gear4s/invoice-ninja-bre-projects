<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Unit;

use App\DataProviders\Domains;
use Tests\TestCase;

class DomainCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_domain_check()
    {

        $this->assertTrue(in_array('yopmail.com', Domains::getDomains()));
        $this->assertFalse(in_array('invoiceninja.com', Domains::getDomains()));

    }

    public function test_subdomain_validation()
    {
        $this->assertFalse($this->checker('invoiceninja'));
        $this->assertFalse($this->checker('hello'));
        $this->assertTrue($this->checker('nasty.pasty'));
    }

    public function checker($subdomain)
    {
        return !preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?$/', $subdomain);
    }
}
