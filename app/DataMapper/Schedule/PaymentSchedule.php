<?php

namespace App\DataMapper\Schedule;

class PaymentSchedule
{
    /**
     * The template name
     */
    public string $template = 'payment_schedule';

    /**
     * @var array(
     *  'id' => int,
     *  'date' => string,
     *  'amount' => float,
     *  'is_amount' => bool
     * )
     */
    public array $schedule = [];

    /**
     * The invoice id
     */
    public string $invoice_id = '';

    /**
     * Whether to auto bill the invoice
     */
    public bool $auto_bill = false;
}
