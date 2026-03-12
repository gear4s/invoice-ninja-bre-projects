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

use App\DataMapper\CompanySettings;
use Tests\TestCase;

/**
 *   App\DataMapper\CompanySettings
 */
class CompanySettingsTest extends TestCase
{
    protected $company_settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company_settings = CompanySettings::defaults();
    }

    public function test_timezone_id()
    {
        $this->assertEquals($this->company_settings->timezone_id, 1);
    }

    public function test_language_id()
    {
        $this->assertEquals($this->company_settings->language_id, 1);
    }

    public function test_property_isset_ok()
    {
        $this->assertTrue(isset($this->company_settings->custom_value1));
    }

    public function test_property_is_set()
    {
        $this->assertTrue(isset($this->company_settings->timezone_id));
    }

    public function test_settings_array_against_casts_array()
    {
        $company_settings = json_decode(json_encode(CompanySettings::defaults()), true);
        $casts = CompanySettings::$casts;

        $diff = array_diff_key($company_settings, $casts);

        $this->assertEquals(1, count($diff));
    }

    public function test_string_equivalence()
    {
        $value = (strval(4) != strval(3));

        $this->assertTrue($value);

        $value = (strval(4) != strval(4));

        $this->assertFalse($value);

        $value = (strval('4') != strval(4));
        $this->assertFalse($value);

        $value = (strval('4') != strval('4'));

        $this->assertFalse($value);

        $value = (strval('4') != strval(3));

        $this->assertTrue($value);

        $value = (strval(4) != strval('3'));

        $this->assertTrue($value);

        $value = (strval('4') != strval('3'));

        $this->assertTrue($value);
    }
}
