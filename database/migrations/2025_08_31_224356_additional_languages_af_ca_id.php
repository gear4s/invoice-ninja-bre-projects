<?php

use App\Models\Language;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Language::unguard();

        if (!Language::find(43)) {
            Language::create(['id' => 43, 'name' => 'Catalan', 'locale' => 'ca']);
        }

        if (!Language::find(44)) {
            Language::create(['id' => 44, 'name' => 'Afrikaans', 'locale' => 'af_ZA']);
        }

        if (!Language::find(45)) {
            Language::create(['id' => 45, 'name' => 'Indonesian', 'locale' => 'id_ID']);
        }

        $resource = Language::query()->orderBy('name')->get();

        Cache::forever('languages', $resource);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
