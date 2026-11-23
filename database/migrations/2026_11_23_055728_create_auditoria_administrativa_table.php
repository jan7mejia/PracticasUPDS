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
        if (!Schema::hasTable('auditoria_administrativa')) {
            Schema::create('auditoria_administrativa', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
                $table->string('accion', 100);
                $table->foreignId('usuario_afectado_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('detalles')->nullable();
                $table->string('motivo', 255)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('accion');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_administrativa');
    }
};
