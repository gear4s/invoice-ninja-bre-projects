<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Tests\Integration\Validation;

use App\Http\ValidationRules\ValidAmount;
use Tests\TestCase;

class AmountValidationRuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_simple_amount_valid()
    {
        $rules = [
            'amount' => [new ValidAmount],
        ];

        $data = [
            'amount' => 1,
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function test_invalid_amount_valid()
    {
        $rules = [
            'amount' => [new ValidAmount],
        ];

        $data = [
            'amount' => 'aa',
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertFalse($v->passes());
    }

    public function test_illegal_chars()
    {
        $rules = [
            'amount' => [new ValidAmount],
        ];

        $data = [
            'amount' => '5+5',
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertFalse($v->passes());
    }

    public function test_illegal_chars_naked()
    {
        $rules = [
            'amount' => [new ValidAmount],
        ];

        $data = [
            'amount' => 5 + 5, // resolves as 10 - but in practice, i believe this amount is wrapped in quotes so interpreted as a string
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testin_valid_scenario1()
    {
        $rules = [
            'amount' => [new ValidAmount],
        ];

        $data = [
            'amount' => '-10x',
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertFalse($v->passes());
    }

    public function test_valid_scenario2()
    {
        $rules = [
            'amount' => [new ValidAmount],
        ];

        $data = [
            'amount' => -10,
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function test_valid_scenario3()
    {
        $rules = [
            'amount' => [new ValidAmount],
        ];

        $data = [
            'amount' => '-10',
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function test_in_valid_scenario4()
    {
        $rules = [
            'amount' => [new ValidAmount],
        ];

        $data = [
            'amount' => '-0 1',
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertFalse($v->passes());
    }
}
