<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add an account_id column to the clients table so that clients can be
     * shared globally across all companies that belong to the same account.
     * The account_id is denormalised here for query performance — rather than
     * joining through companies every time we need to list clients visible to
     * a user regardless of which company they currently have selected.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Nullable during the migration so existing rows are accepted
            // before we backfill the value below.
            $table->unsignedInteger('account_id')->nullable()->index()->after('company_id');
        });

        // Backfill account_id from the parent companies table.
        // We use a direct DB statement for efficiency on large datasets instead
        // of loading every Client model through Eloquent.
        DB::statement('
            UPDATE clients
            INNER JOIN companies ON companies.id = clients.company_id
            SET clients.account_id = companies.account_id
        ');

        // Now that every existing row has a value we can tighten the column.
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
