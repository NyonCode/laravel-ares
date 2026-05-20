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

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            Schema::getConnection()->statement(
                'ALTER TABLE ares_subjects ADD FULLTEXT INDEX ares_subjects_name_fulltext (name)'
            );
        } elseif ($driver === 'pgsql') {
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
