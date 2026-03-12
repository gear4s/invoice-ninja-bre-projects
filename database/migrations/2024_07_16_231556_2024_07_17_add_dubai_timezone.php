<?php

use App\Models\Timezone;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $t = Timezone::find(115);

        if (!$t && Timezone::count() > 1) {

            $t = new Timezone;
            $t->id = 115;
            $t->name = 'Asia/Dubai';
            $t->location = '(GMT+04:00) Dubai';
            $t->utc_offset = 14400;
            $t->save();

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
