<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Utils;

use App\Models\Country;
use App\Models\Currency;
use App\Models\PaymentTerm;
use Illuminate\Support\Collection;

class TranslationHelper
{
    // public static function getIndustries()
    // {

    //     /** @var \Illuminate\Support\Collection<\App\Models\Currency> */
    //     $industries = app('industries');

    //     return $industries->each(function ($industry) {
    //         $industry->name = ctrans('texts.industry_'.$industry->name);
    //     })->sortBy(function ($industry) {
    //         return $industry->name;
    //     });
    // }

    public static function getCountries()
    {

        /** @var Collection<Country> */
        return app('countries');

    }

    // public static function getPaymentTypes()
    // {

    //     /** @var \Illuminate\Support\Collection<\App\Models\PaymentType> */
    //     // $payment_types = app('payment_types');

    //     return \App\Models\PaymentType::all()->each(function ($pType) {
    //         $pType->name = ctrans('texts.payment_type_'.$pType->name);
    //     })->sortBy(function ($pType) {
    //         return $pType->name;
    //     });
    // }

    // public static function getLanguages()
    // {

    //     /** @var \Illuminate\Support\Collection<\App\Models\Language> */
    //     // $languages = app('languages');

    //     return \App\Models\Language::all()->each(function ($lang) {
    //         $lang->name = ctrans('texts.lang_'.$lang->name);
    //     })->sortBy(function ($lang) {
    //         return $lang->name;
    //     });
    // }

    public static function getCurrencies()
    {

        /** @var Collection<Currency> */
        return app('currencies');

    }

    // public static function getPaymentTerms()
    // {
    //     return PaymentTerm::getCompanyTerms()->map(function ($term) {
    //         $term['name'] = ctrans('texts.payment_terms_net').' '.$term['num_days'];

    //         return $term;
    //     });
    // }
}
