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

use App\DataMapper\ClientSettings;
use App\DataMapper\CompanySettings;
use Tests\TestCase;

class GroupTest extends TestCase
{
    public $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = ClientSettings::buildClientSettings(CompanySettings::defaults(), ClientSettings::defaults());
    }

    public function test_groups_properties_exists_responses()
    {
        $this->assertTrue(property_exists($this->settings, 'timezone_id'));
    }

    public function test_property_value_accessors()
    {
        $this->settings->translations = (object) ['hello' => 'world'];

        $this->assertEquals('world', $this->settings->translations->hello);
    }

    public function test_property_is_set()
    {
        $this->assertFalse(isset($this->settings->translations->nope));
    }
}
