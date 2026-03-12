<?php

use App\DataMapper\Tax\TaxModel;
use App\Models\Company;
use App\Utils\Ninja;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Ninja::isSelfHost()) {

            Company::query()->cursor()->each(function ($company) {
                $company->tax_data = new TaxModel($company->tax_data);
                $company->save();
            });

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
