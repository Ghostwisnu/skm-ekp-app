<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->text('alamat')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('clients')->whereNull('alamat')->update(['alamat' => '']);

        Schema::table('clients', function (Blueprint $table) {
            $table->text('alamat')->nullable(false)->change();
        });
    }
};
