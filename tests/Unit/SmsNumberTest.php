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

use App\DataProviders\SMSNumbers;
use Tests\TestCase;

class SmsNumberTest extends TestCase
{
    public function test_array_hit()
    {
        $this->assertTrue(SMSNumbers::hasNumber('+461614222'));
    }

    public function test_array_miss()
    {
        $this->assertFalse(SMSNumbers::hasNumber('+5485454'));
    }

    public function test_sms_array_type()
    {
        $this->assertIsArray(SMSNumbers::getNumbers());
    }
}
