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

use App\Libraries\Currency\Conversion\CurrencyApi;
use App\Models\Currency;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 *   App\Libraries\Currency\Conversion\CurrencyApi
 */
class CurrencyApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_conversion_aud_to_eur()
    {
        $converter = new CurrencyApi;

        $converted_amount = $converter->convert(100, 12, 3);

        $aud_currency = Currency::find(12);
        $eur_currency = Currency::find(3);

        $converted_synthetic = 100 / ($aud_currency->exchange_rate / $eur_currency->exchange_rate);

        $this->assertEquals(round($converted_synthetic, 2), round($converted_amount, 2));

    }

    public function test_currency_conversion_working()
    {
        $converter = new CurrencyApi;

        $converted_amount = $converter->convert(100, 1, 2);

        $this->assertIsFloat($converted_amount);
    }

    public function test_exchange_rate()
    {
        $converter = new CurrencyApi;

        $exchange_rate = $converter->exchangeRate(1, 2);

        $this->assertIsNumeric($exchange_rate);
    }

    public function test_exchange_rate_with_date()
    {
        $date = Carbon::parse('2020-03-08');

        $converter = new CurrencyApi;

        $exchange_rate = $converter->exchangeRate(1, 2, $date);

        $this->assertIsNumeric($exchange_rate);
    }
}
