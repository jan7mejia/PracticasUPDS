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
        if (!Schema::hasColumn('docentes_tutores', 'carrera_id')) {
            Schema::table('docentes_tutores', function (Blueprint $table) {
                $table->foreignId('carrera_id')->nullable()->after('usuario_id')->constrained('carreras')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('docentes_tutores', 'carrera_id')) {
            Schema::table('docentes_tutores', function (Blueprint $table) {
                $table->dropForeign(['carrera_id']);
                $table->dropColumn('carrera_id');
            });
        }
    }
};
