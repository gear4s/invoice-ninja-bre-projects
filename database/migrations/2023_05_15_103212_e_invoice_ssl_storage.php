<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
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

        Schema::table('companies', function (Blueprint $table) {
            $table->text('e_invoice_certificate')->nullable();
            $table->text('e_invoice_certificate_passphrase')->nullable();
            $table->text('origin_tax_data')->nullable();
        });

        Artisan::call('ninja:design-update');

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
