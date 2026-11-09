<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_bank_name', 100)->nullable();
            $table->string('company_bank_account', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_address',
                'company_bank_name',
                'company_bank_account',
            ]);
        });
    }
};
