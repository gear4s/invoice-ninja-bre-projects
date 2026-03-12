<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\PaymentDrivers\CBAPowerBoard\Models;

class Charge
{
    public ?string $external_id;

    public ?string $_id;

    public ?string $created_at;

    public ?string $updated_at;

    public ?string $remittance_date;

    public ?string $company_id;

    public float $amount;

    public ?string $currency;

    public ?int $__v;

    /** @var Transaction[] */
    public array $transactions;

    public ?bool $one_off;

    public ?bool $archived;

    public Customer $customer;

    public ?bool $capture;

    public ?string $status;

    public ?array $items;

    public function __construct(
        ?string $external_id,
        ?string $_id,
        ?string $created_at,
        ?string $updated_at,
        ?string $remittance_date,
        ?string $company_id,
        float $amount,
        ?string $currency,
        ?int $__v,
        array $transactions,
        ?bool $one_off,
        ?bool $archived,
        Customer $customer,
        ?bool $capture,
        ?string $status,
        ?array $items,
    ) {
        $this->external_id = $external_id;
        $this->_id = $_id;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
        $this->remittance_date = $remittance_date;
        $this->company_id = $company_id;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->__v = $__v;
        $this->transactions = $transactions;
        $this->one_off = $one_off;
        $this->archived = $archived;
        $this->customer = $customer;
        $this->capture = $capture;
        $this->status = $status;
        $this->items = $items;
    }
}
