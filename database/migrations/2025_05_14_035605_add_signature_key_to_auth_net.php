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

        if ($g = Gateway::where('key', '3b6621f970ab18887c4f6dca78d3f8bb')->first()) {
            $g->fields = json_encode(array_merge(json_decode($g->fields, true), ['signatureKey' => '']));
            $g->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
