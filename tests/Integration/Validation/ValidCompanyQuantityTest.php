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

use App\Http\ValidationRules\Company\ValidCompanyQuantity;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Tests\MockUnitData;
use Tests\TestCase;

class ValidCompanyQuantityTest extends TestCase
{
    use MockUnitData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTestData();

    }

    public function test_company_quantity_validation()
    {
        auth()->login($this->user, true);

        $data = [];
        $rules = ['name' => [new ValidCompanyQuantity]];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes());
    }

    public function test_company_quantity_validation_fails()
    {

        auth()->login($this->user, true);
        auth()->user()->setCompany($this->company);

        $data = ['name' => 'bob'];
        $rules = ['name' => [new ValidCompanyQuantity]];

        Company::factory()->count(10)->create([
            'account_id' => auth()->user()->account->id,
        ]);

        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes());
    }
}
