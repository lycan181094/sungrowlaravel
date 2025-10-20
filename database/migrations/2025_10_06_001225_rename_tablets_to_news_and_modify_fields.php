<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::rename('tablets', 'news');

        // Backfill para evitar NOT NULL con valores nulos
        DB::table('news')->whereNull('fecha_hora')->update(['fecha_hora' => DB::raw('CURRENT_TIMESTAMP')]);
        DB::table('news')->whereNull('created_at')->update(['created_at' => DB::raw('CURRENT_TIMESTAMP')]);

        // Alter con SQL (sin DBAL)
        DB::statement("ALTER TABLE `news` 
            MODIFY `fecha_hora` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            MODIFY `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        // Si no quieres tocar created_at, quita esa segunda línea.
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `news` 
            MODIFY `fecha_hora` TIMESTAMP NULL DEFAULT NULL,
            MODIFY `created_at` TIMESTAMP NULL DEFAULT NULL");

        Schema::rename('news', 'tablets');
    }
};

