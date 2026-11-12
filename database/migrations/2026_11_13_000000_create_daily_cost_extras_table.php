<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL with IF NOT EXISTS for maximum compatibility
        DB::statement(<<<SQL
            CREATE TABLE IF NOT EXISTS daily_cost_extras (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                daily_cost_id BIGINT UNSIGNED NOT NULL,
                nama_biaya VARCHAR(255) NOT NULL,
                nominal BIGINT UNSIGNED NOT NULL DEFAULT '0',
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX daily_cost_extras_daily_cost_id_foreign (daily_cost_id),
                FOREIGN KEY (daily_cost_id) REFERENCES daily_costs(id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS daily_cost_extras');
    }
};