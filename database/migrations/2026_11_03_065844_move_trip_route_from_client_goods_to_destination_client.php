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
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('destination_client_id')
                ->nullable()
                ->after('client_id')
                ->constrained('clients')
                ->restrictOnDelete();
        });

        Schema::table('client_goods', function (Blueprint $table) {
            $table->dropColumn('rute');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_goods', function (Blueprint $table) {
            $table->string('rute')->nullable();
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['destination_client_id']);
            $table->dropColumn('destination_client_id');
        });
    }
};
