<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Services\EDocument\Imports;

use App\Models\Account;
use App\Models\Company;
use App\Models\Expense;
use App\Services\AbstractService;
use App\Utils\Ninja;
use Exception;
use Illuminate\Http\UploadedFile;

class ParseEDocument extends AbstractService
{
    /**
     * @throws Exception
     */
    public function __construct(private UploadedFile $file, private Company $company) {}

    /**
     * Execute the service.
     * the service will parse the file with all available libraries of the system and will return an expense, when possible
     *
     * @developer the function should be implemented with local first aproach to save costs of external providers (like mindee ocr)
     *
     * @throws \Throwable
     */
    public function run(): Expense
    {
        nlog('starting');
        nlog($this->company->id);
        // nlog($this->file->get());

        /** @var Account $account */
        $account = $this->company->owner()->account;

        $extension = $this->file->getClientOriginalExtension() ?: $this->file->getExtension();
        $mimetype = $this->file->getClientMimeType() ?: $$this->file->getMimeType();

        // ZUGFERD - try to parse via Zugferd lib
        switch (true) {
            case $extension == 'pdf' || $mimetype == 'application/pdf':
            case ($extension == 'xml' || $mimetype == 'application/xml') && stristr($this->file->get(), '<rsm:CrossIndustryInvoice'):
                try {
                    return (new ZugferdEDocument($this->file, $this->company))->run();
                } catch (\Throwable $e) {
                    nlog('Zugferd Exception: ' . $e->getMessage());
                    break;
                }
            case ($extension == 'xml' || $mimetype == 'application/xml') && stristr($this->file->get(), '<Invoice'):
                try {
                    return (new UblEDocument($this->file, $this->company))->run();
                } catch (\Throwable $e) {
                    nlog('UBL Import Exception: ' . $e->getMessage());
                    break;
                }
        }

        // MINDEE OCR - try to parse via mindee external service
        if (config('services.mindee.api_key') && !(Ninja::isHosted() && !($account->isPaid() && $account->plan == 'enterprise'))) {
            switch (true) {
                case $extension == 'pdf' || $mimetype == 'application/pdf':
                case $extension == 'heic' || $extension == 'heic' || $extension == 'png' || $extension == 'jpg' || $extension == 'jpeg' || $extension == 'webp' || str_starts_with($mimetype, 'image/'):
                    try {
                        return (new MindeeEDocument($this->file, $this->company))->run();
                    } catch (Exception $e) {
                        if (!($e->getMessage() == 'Unsupported document type')) {
                            nlog('Mindee Exception: ' . $e->getMessage());
                        }
                    }
            }
        }

        // NO PARSER OR ERROR
        throw new Exception('File type not supported or issue while parsing', 409);
    }
}
