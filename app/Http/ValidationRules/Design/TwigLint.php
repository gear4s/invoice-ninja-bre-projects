<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\ValidationRules\Design;

use App\Services\Template\TemplateService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Twig\Error\SyntaxError;
use Twig\Source;

class TwigLint implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $ts = new TemplateService;
        $twig = $ts->twig;

        try {
            $twig->parse($twig->tokenize(new Source(preg_replace('/<!--.*?-->/s', '', $value ?? ''), '')));
        } catch (SyntaxError $e) {
            $fail($e->getMessage());
        }

    }
}
