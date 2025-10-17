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
        // Renombrar tabla de tablets a news
        Schema::rename('tablets', 'news');
        
        // Modificar campos para que sean requeridos
        Schema::table('news', function (Blueprint $table) {
            // Cambiar fecha_hora de nullable a required
            $table->timestamp('fecha_hora')->nullable(false)->change();
            
            // Cambiar created_at de nullable a required (si es necesario)
            $table->timestamp('created_at')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios
        Schema::table('news', function (Blueprint $table) {
            $table->timestamp('fecha_hora')->nullable()->change();
            $table->timestamp('created_at')->nullable()->change();
        });
        
        // Renombrar tabla de news a tablets
        Schema::rename('news', 'tablets');
    }
};
