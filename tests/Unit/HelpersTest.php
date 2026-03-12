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

use App\Utils\Helpers;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_fonts_return_format(): void
    {
        $font = Helpers::resolveFont();

        $this->assertArrayHasKey('name', $font);
        $this->assertArrayHasKey('url', $font);
    }

    public function test_resolving_font(): void
    {
        $font = Helpers::resolveFont('Inter');

        $this->assertEquals('Inter', $font['name']);
    }

    public function test_default_font_is_arial(): void
    {
        $font = Helpers::resolveFont();

        $this->assertEquals('Arial', $font['name']);
    }
}
