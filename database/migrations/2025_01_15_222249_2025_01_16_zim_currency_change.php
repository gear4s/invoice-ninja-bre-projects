<?php

use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Artisan::call('ninja:design-update');

        $currency = Currency::where('code', 'ZWL')->first();

        if ($currency) {
            $currency->update(['name' => 'Zimbabwe Gold']);
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
