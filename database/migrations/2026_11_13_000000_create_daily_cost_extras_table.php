<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Table already exists on production server - no-op
        // Original migration was applied manually before this file was committed
    }

    public function down(): void
    {
        // No-op
    }
};