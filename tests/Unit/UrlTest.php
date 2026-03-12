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

use Tests\TestCase;

class UrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_no_scheme()
    {
        $url = 'google.com';

        $this->assertEquals('https://google.com', $this->addScheme($url));
    }

    public function test_no_scheme_and_trailing_slash()
    {
        $url = 'google.com/';

        $this->assertEquals('https://google.com', $this->addScheme($url));
    }

    public function test_no_scheme_and_trailing_slash_and_http()
    {
        $url = 'http://google.com/';

        $this->assertEquals('https://google.com', $this->addScheme($url));
    }

    private function addScheme($url, $scheme = 'https://')
    {
        $url = str_replace('http://', '', $url);

        $url = parse_url($url, PHP_URL_SCHEME) === null ? $scheme . $url : $url;

        return rtrim($url, '/');
    }
}
