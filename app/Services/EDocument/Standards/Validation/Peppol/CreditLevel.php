<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Services\EDocument\Standards\Validation\Peppol;

use InvoiceNinja\EInvoice\Models\Peppol\DocumentReferenceType\InvoiceDocumentReference;
use Symfony\Component\Serializer\Attribute\SerializedName;

class CreditLevel
{
    #[SerializedName('cac:InvoiceDocumentReference')]
    public InvoiceDocumentReference $InvoiceDocumentReference;
}
