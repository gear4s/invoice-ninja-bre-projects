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

use App\Http\ValidationRules\Company\ValidSubdomain;
use Illuminate\Support\Facades\Validator;
use Tests\MockUnitData;
use Tests\TestCase;

class ValidSubdomainTest extends TestCase
{
    use MockUnitData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_check_valid_subdomain_name()
    {

        $data = ['subdomain' => 'invoiceyninjay'];
        $rules = ['subdomain' => ['nullable', 'regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/', new ValidSubdomain]];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes());

    }

    public function test_check_empty_valid_subdomain_name()
    {

        $data = ['subdomain' => ''];
        $rules = ['subdomain' => ['nullable', 'regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/', new ValidSubdomain]];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes());

    }

    public function test_check_empty2_valid_subdomain_name()
    {

        $data = ['subdomain' => ' '];
        $rules = ['subdomain' => ['nullable', 'regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/', new ValidSubdomain]];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes());

    }

    public function test_check_in_valid_subdomain_name()
    {

        $data = ['subdomain' => 'domain.names'];
        $rules = ['subdomain' => ['nullable', 'regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/', new ValidSubdomain]];

        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes());

    }
}
