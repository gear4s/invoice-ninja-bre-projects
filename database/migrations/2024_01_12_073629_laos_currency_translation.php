<?php

use App\Models\Currency;
use App\Models\Language;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Language::unguard();

        $language = Language::find(41);

        if (!$language) {
            Language::create(['id' => 41, 'name' => 'Lao', 'locale' => 'lo_LA']);
        }

        $cur = Currency::find(121);

        if (!$cur) {
            $cur = new Currency;
            $cur->id = 121;
            $cur->code = 'LAK';
            $cur->name = 'Lao kip';
            $cur->symbol = '₭';
            $cur->thousand_separator = ',';
            $cur->decimal_separator = '.';
            $cur->precision = 2;
            $cur->save();
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
