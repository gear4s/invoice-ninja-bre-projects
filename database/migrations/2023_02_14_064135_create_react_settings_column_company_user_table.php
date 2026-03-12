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
        Schema::table('company_user', function (Blueprint $table) {
            $table->mediumText('react_settings')->nullable();
        });

        Artisan::call('ninja:design-update');

        Schema::table('schedulers', function (Blueprint $table) {
            $table->integer('remaining_cycles')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
};
