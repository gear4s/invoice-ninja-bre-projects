<?php

use App\Models\Gateway;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Gateway::whereIn('id', [60, 15])->update(['visible' => 1]);

        Artisan::call('ninja:design-update');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
