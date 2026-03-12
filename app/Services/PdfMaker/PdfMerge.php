<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Services\PdfMaker;

use Modules\Admin\Services\PdfParse;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\StreamReader;

class PdfMerge
{
    /**
     * __construct
     *
     * @return void
     */
    public function __construct(private array $files) {}

    public function run()
    {
        $pdf = new Fpdi;

        foreach ($this->files as $file) {

            $pageCount = 0;

            try {
                // Try to open with FPDI first
                $pageCount = $pdf->setSourceFile(StreamReader::createByString($file));
            } catch (PdfParserException $e) {
                // If FPDI fails, try downgrading the PDF

                if (class_exists(PdfParse::class)) {

                    $downgradedPdf = PdfParse::downgrade($file);

                    $pageCount = $pdf->setSourceFile(StreamReader::createByString($downgradedPdf));
                }

            }

            for ($i = 0; $i < $pageCount; $i++) {
                $tpl = $pdf->importPage($i + 1, '/MediaBox');
                $size = $pdf->getTemplateSize($tpl);

                // Preserve original page orientation and dimensions
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->addPage($orientation, [$size['width'], $size['height']]);

                $pdf->useTemplate($tpl);
            }
        }

        return $pdf->Output('S');
    }
}
