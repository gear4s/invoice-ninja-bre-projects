<?php

namespace Tests\Feature\EInvoice\RequestValidation;

use App\Http\Requests\EInvoice\UpdateEInvoiceConfiguration;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateEInvoiceConfigurationTest extends TestCase
{
    protected UpdateEInvoiceConfiguration $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );

        $this->request = new UpdateEInvoiceConfiguration;
    }

    public function test_config_validation_fails()
    {
        $data = [
            'entddity' => 'invoice',
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
    }

    public function test_config_validation()
    {
        $data = [
            'entity' => 'invoice',
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_config_validation_invalidcode()
    {
        $data = [
            'entity' => 'invoice',
            'payment_means' => [[
                'code' => 'invalidcodehere',
            ]],
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
    }

    public function test_validates_payment_means_for_bank_transfer()
    {
        $data = [
            'entity' => 'invoice',
            'payment_means' => [[
                'code' => '30',
                'iban' => '123456789101112254',
                'bic_swift' => 'DEUTDEFF',
                'account_holder' => 'John Doe Company Limited',
            ]],
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validates_payment_means_for_card_payment()
    {
        $data = [
            'entity' => 'invoice',
            'payment_means' => [[
                'code' => '48',
                'card_type' => 'VISA',
                'iban' => '12345678',
            ]],
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
    }

    public function test_validates_payment_means_for_credit_card()
    {
        $data = [
            'entity' => 'invoice',
            'payment_means' => [[
                'code' => '54',
                'card_type' => 'VISA',
                'card_number' => '************1234',
                'card_holder' => 'John Doe',
            ]],
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_fails_validation_when_required_fields_are_missing()
    {
        $data = [
            'entity' => 'invoice',
            'payment_means' => [[
                'code' => '30',
            ]],
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());
        $this->assertFalse($validator->passes());

        $this->assertTrue($validator->errors()->has('payment_means.0.bic_swift'));

    }

    public function test_fails_validation_with_invalid_payment_means_code()
    {
        $data = [
            'entity' => 'invoice',
            'payment_means' => [[
                'code' => '999',
            ]],
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('payment_means.0.code'));
    }

    public function test_validates_payment_means_for_direct_debit()
    {
        $data = [
            'entity' => 'invoice',
            'payment_means' => [[
                'code' => '49',
                'payer_bank_account' => '12345678',
                'bic_swift' => 'DEUTDEFF',
            ]],
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validates_payment_means_for_book_entry()
    {
        $data = [
            'entity' => 'invoice',
            'payment_means' => [[
                'code' => '15',
                'account_holder' => 'John Doe Company Limited',
                'bsb_sort' => '123456',
            ]],
        ];

        $this->request->initialize($data);
        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->passes());
    }
}
