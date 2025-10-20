<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::rename('tablets', 'news');

        // Backfill para no romper el NOT NULL
        DB::table('news')->whereNull('fecha_hora')
            ->update(['fecha_hora' => DB::raw('CURRENT_TIMESTAMP')]);
        DB::table('news')->whereNull('created_at')
            ->update(['created_at' => DB::raw('CURRENT_TIMESTAMP')]);

        Schema::table('news', function (Blueprint $table) {
            $table->timestamp('fecha_hora')
                  ->useCurrent()        // DEFAULT CURRENT_TIMESTAMP
                  ->nullable(false)
                  ->change();

            // Solo si de verdad necesitas NOT NULL en created_at:
            $table->timestamp('created_at')
                  ->useCurrent()        // DEFAULT CURRENT_TIMESTAMP
                  ->nullable(false)
                  ->change();

            // Si vas a tocar updated_at también:
            // $table->timestamp('updated_at')
            //       ->useCurrent()->useCurrentOnUpdate()
            //       ->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->timestamp('fecha_hora')->nullable()->default(null)->change();
            $table->timestamp('created_at')->nullable()->default(null)->change();
            // $table->timestamp('updated_at')->nullable()->default(null)->change();
        });
        Schema::rename('news', 'tablets');
    }
};

