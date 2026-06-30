<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ares_subjects', function (Blueprint $table) {
            $table->char('ic', 8)->primary();
            $table->string('name');
            $table->string('city', 100)->nullable();
            $table->timestamp('indexed_at')->useCurrent();
        });

        // Name search uses substring matching (LIKE '%term%'). On PostgreSQL a
        // trigram GIN index accelerates that; other drivers fall back to a plain
        // index, which still helps ordering and prefix lookups.
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            Schema::getConnection()->statement(
                'CREATE INDEX ares_subjects_name_trgm ON ares_subjects USING GIN (name gin_trgm_ops)'
            );
        } else {
            Schema::table('ares_subjects', function (Blueprint $table) {
                $table->index('name');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ares_subjects');
    }
};
