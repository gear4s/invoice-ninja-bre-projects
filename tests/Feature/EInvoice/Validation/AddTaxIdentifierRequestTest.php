<?php

namespace Tests\Feature\EInvoice\Validation;

use App\Http\Requests\EInvoice\Peppol\AddTaxIdentifierRequest;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Validator;
use Tests\MockAccountData;
use Tests\TestCase;

class AddTaxIdentifierRequestTest extends TestCase
{
    use MockAccountData;

    protected AddTaxIdentifierRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new AddTaxIdentifierRequest;

        $this->withoutMiddleware(
            ThrottleRequests::class
        );

        $this->makeTestData();
    }

    public function test_valid_input()
    {
        $this->actingAs($this->user);

        $data = [
            'country' => 'DE',
            'vat_number' => 'DE123456789',
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_invalid_country()
    {
        $this->actingAs($this->user);

        $data = [
            'country' => 'US',
            'vat_number' => 'DE123456789',
        ];

        $this->request->initialize($data);

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    public function test_invalid_vat_number()
    {
        $this->actingAs($this->user);

        $data = [
            'country' => 'DE',
            'vat_number' => 'DE12345', // Too short
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('vat_number', $validator->errors()->toArray());
    }

    public function test_missing_country()
    {
        $this->actingAs($this->user);

        $data = [
            'vat_number' => 'DE123456789',
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    public function test_missing_vat_number()
    {
        $this->actingAs($this->user);

        $data = [
            'country' => 'DE',
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('vat_number', $validator->errors()->toArray());
    }

    public function test_same_country_fails()
    {
        $this->actingAs($this->user);

        $this->user->setCompany($this->company);

        $settings = $this->company->settings;
        $settings->country_id = 276; // DE

        $this->company->settings = $settings;
        $this->company->save();

        $data = [
            'country' => $settings->country_id,
            'vat_number' => 'DE123456789',
        ];

        $this->request->initialize($data);
        $this->request->prepareForValidation();

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }
}
