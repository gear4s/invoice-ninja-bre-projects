<?php

use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $cur = Currency::find(118);

        if (!$cur) {
            $cur = new Currency;
            $cur->id = 118;
            $cur->code = 'NIO';
            $cur->name = 'Nicaraguan Córdoba';
            $cur->symbol = 'C$';
            $cur->thousand_separator = ',';
            $cur->decimal_separator = '.';
            $cur->precision = 2;
            $cur->save();
        }

        Schema::table('vendors', function (Blueprint $table) {
            $table->unsignedInteger('language_id')->nullable();
            $table->timestamp('last_login')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
