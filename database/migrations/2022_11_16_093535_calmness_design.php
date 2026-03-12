<?php

use App\Models\Design;
use App\Utils\Ninja;
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
        if (Ninja::isHosted()) {
            $design = new Design;

            $design->name = 'Calm';
            $design->is_custom = false;
            $design->design = '';
            $design->is_active = true;

            $design->save();
        } elseif (Design::count() !== 0) {
            $design = new Design;

            $design->name = 'Calm';
            $design->is_custom = false;
            $design->design = '';
            $design->is_active = true;

            $design->save();
        }

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
