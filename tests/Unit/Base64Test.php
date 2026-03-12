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

use App\Utils\Ninja;
use Tests\TestCase;

class Base64Test extends TestCase
{
    /**
     * Important consideration with Base64
     * encoding checks.
     *
     * No method can guarantee against false positives.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_bad_base64_string()
    {
        $this->assertFalse(Ninja::isBase64Encoded('x'));
    }

    public function test_correct_base64_encoding()
    {
        $this->assertTrue(Ninja::isBase64Encoded('MTIzNDU2'));
    }

    public function test_bad_base64_string_scenaro1()
    {
        $this->assertFalse(Ninja::isBase64Encoded('Matthies'));
    }

    public function test_bad_base64_string_scenaro2()
    {
        $this->assertFalse(Ninja::isBase64Encoded('Barthels'));
    }

    public function test_bad_base64_string_scenaro3()
    {
        $this->assertFalse(Ninja::isBase64Encoded('aaa'));
    }
}
