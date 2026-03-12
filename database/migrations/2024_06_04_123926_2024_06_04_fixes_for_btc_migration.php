<?php

use App\Models\Gateway;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ($g = Gateway::find(62)) {
            $g->fields = '{"btcpayUrl":"", "apiKey":"", "storeId":"", "webhookSecret":""}';
            $g->save();
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
