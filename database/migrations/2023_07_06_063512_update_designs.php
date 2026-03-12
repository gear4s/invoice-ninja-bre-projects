<?php

use App\Models\Country;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call('ninja:design-update');

        $t = Country::find(158);

        if ($t) {
            $t->full_name = 'Taiwan';
            $t->name = 'Taiwan';
            $t->save();
        }

        $m = Country::find(807);

        if ($m) {
            $m->full_name = 'Macedonia';
            $m->name = 'Macedonia';
            $m->save();
        }

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
