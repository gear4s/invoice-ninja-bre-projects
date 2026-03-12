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

            Company::whereNotNull('tax_data')
                ->cursor()
                ->each(function ($company) {

                    if ($company->tax_data?->version == 'alpha' && ($company->tax_data->seller_subregion ?? false)) {

                        $company->update(['tax_data' => new TaxModel($company->tax_data)]);

                    }

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
