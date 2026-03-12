<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Services\EDocument\Gateway;

use InvoiceNinja\EInvoice\Models\Peppol\CreditNote;
use InvoiceNinja\EInvoice\Models\Peppol\Invoice;

interface MutatorInterface
{
    public function receiverSpecificLevelMutators(): self;

    public function senderSpecificLevelMutators(): self;

    public function setInvoice($invoice): self;

    /**
     * @param  Invoice|CreditNote  $p_invoice
     */
    public function setPeppol($p_invoice): self;

    /**
     * @return Invoice|CreditNote
     */
    public function getPeppol(): mixed;

    public function setClientSettings($client_settings): self;

    public function setCompanySettings($company_settings): self;

    public function getClientSettings(): mixed;

    public function getCompanySettings(): mixed;

    public function getInvoice(): mixed;

    public function getSetting(string $property_path): mixed;

    // Country-specific methods
    public function DE(): self;

    public function CH(): self;

    public function AT(): self;

    public function AU(): self;

    public function ES(): self;

    public function FI(): self;

    public function FR(): self;

    public function IT(): self;

    public function client_IT(): self;

    public function MY(): self;

    public function NL(): self;

    public function NZ(): self;

    public function PL(): self;

    public function RO(): self;

    public function SG(): self;

    public function SE(): self;
}
