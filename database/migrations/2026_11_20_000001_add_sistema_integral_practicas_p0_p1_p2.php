<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Columnas incrementales en 'practicas'
        Schema::table('practicas', function (Blueprint $table) {
            if (!Schema::hasColumn('practicas', 'version_reglamento')) {
                $table->string('version_reglamento', 30)->default('v2_automatizado')->after('estado');
            }
            if (!Schema::hasColumn('practicas', 'cronograma_aprobado')) {
                $table->boolean('cronograma_aprobado')->default(false)->after('version_reglamento');
            }
            if (!Schema::hasColumn('practicas', 'cronograma_aprobado_at')) {
                $table->dateTime('cronograma_aprobado_at')->nullable()->after('cronograma_aprobado');
            }
            if (!Schema::hasColumn('practicas', 'cronograma_aprobado_por')) {
                $table->unsignedBigInteger('cronograma_aprobado_por')->nullable()->after('cronograma_aprobado_at');
                $table->foreign('cronograma_aprobado_por')->references('id')->on('coordinadores')->nullOnDelete();
            }
            if (!Schema::hasColumn('practicas', 'horas_programadas')) {
                $table->decimal('horas_programadas', 7, 2)->nullable()->after('horas_objetivo');
            }
            if (!Schema::hasColumn('practicas', 'faltas_justificadas')) {
                $table->integer('faltas_justificadas')->default(0)->after('horas_programadas');
            }
            if (!Schema::hasColumn('practicas', 'incompletas_justificadas')) {
                $table->integer('incompletas_justificadas')->default(0)->after('faltas_justificadas');
            }
        });

        // 2. Columnas incrementales en 'cronograma_actividades'
        Schema::table('cronograma_actividades', function (Blueprint $table) {
            if (!Schema::hasColumn('cronograma_actividades', 'entregable_esperado')) {
                $table->string('entregable_esperado', 255)->nullable()->after('descripcion');
            }
            if (!Schema::hasColumn('cronograma_actividades', 'aprobada_coordinacion')) {
                $table->boolean('aprobada_coordinacion')->default(false)->after('entregable_esperado');
            }
            if (!Schema::hasColumn('cronograma_actividades', 'rubrica_definida')) {
                $table->boolean('rubrica_definida')->default(false)->after('aprobada_coordinacion');
            }
        });

        // 3. Columnas incrementales en 'informes_avance'
        Schema::table('informes_avance', function (Blueprint $table) {
            if (!Schema::hasColumn('informes_avance', 'actividad_id')) {
                $table->unsignedBigInteger('actividad_id')->nullable()->after('practica_id');
                $table->foreign('actividad_id')->references('id')->on('cronograma_actividades')->nullOnDelete();
            }
            if (!Schema::hasColumn('informes_avance', 'version')) {
                $table->integer('version')->default(1)->after('actividad_id');
            }
            if (!Schema::hasColumn('informes_avance', 'observaciones_tutor')) {
                $table->text('observaciones_tutor')->nullable()->after('resumen');
            }
            if (!Schema::hasColumn('informes_avance', 'calificacion')) {
                $table->decimal('calificacion', 5, 2)->nullable()->after('observaciones_tutor');
            }
        });

        // 4. Tabla 'justificaciones_asistencia'
        if (!Schema::hasTable('justificaciones_asistencia')) {
            Schema::create('justificaciones_asistencia', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('practica_id');
                $table->unsignedBigInteger('estudiante_id');
                $table->date('fecha');
                $table->enum('tipo', ['falta', 'incompleta']);
                $table->decimal('horas_reales', 4, 2)->default(0.00);
                $table->text('motivo');
                $table->string('ruta_documento', 500)->nullable();
                $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
                $table->text('observacion_resolucion')->nullable();
                $table->unsignedBigInteger('revisada_por_coordinador_id')->nullable();
                $table->dateTime('fecha_resolucion')->nullable();
                $table->timestamps();

                $table->foreign('practica_id')->references('id')->on('practicas')->cascadeOnDelete();
                $table->foreign('estudiante_id')->references('id')->on('estudiantes')->cascadeOnDelete();
                $table->foreign('revisada_por_coordinador_id')->references('id')->on('coordinadores')->nullOnDelete();
            });
        }

        // 5. Tabla 'incidencias_practica'
        if (!Schema::hasTable('incidencias_practica')) {
            Schema::create('incidencias_practica', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('practica_id');
                $table->string('tipo', 60); // 'tercera_falta', 'tercera_incompleta', 'ausencia_injustificada', 'administrativa'
                $table->text('descripcion');
                $table->boolean('bloquea_cierre')->default(true);
                $table->boolean('resuelta')->default(false);
                $table->text('resolucion')->nullable();
                $table->unsignedBigInteger('registrada_por_usuario_id')->nullable();
                $table->timestamps();

                $table->foreign('practica_id')->references('id')->on('practicas')->cascadeOnDelete();
                $table->foreign('registrada_por_usuario_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // 6. Tabla 'rubricas_actividad'
        if (!Schema::hasTable('rubricas_actividad')) {
            Schema::create('rubricas_actividad', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('actividad_id');
                $table->string('titulo', 200);
                $table->text('descripcion')->nullable();
                $table->timestamps();

                $table->foreign('actividad_id')->references('id')->on('cronograma_actividades')->cascadeOnDelete();
            });
        }

        // 7. Tabla 'criterios_rubrica'
        if (!Schema::hasTable('criterios_rubrica')) {
            Schema::create('criterios_rubrica', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rubrica_id');
                $table->string('nombre', 200);
                $table->text('descripcion')->nullable();
                $table->decimal('ponderacion', 5, 2); // % ponderación
                $table->decimal('puntuacion_maxima', 5, 2)->default(100.00);
                $table->timestamps();

                $table->foreign('rubrica_id')->references('id')->on('rubricas_actividad')->cascadeOnDelete();
            });
        }

        // 8. Tabla 'evaluaciones_criterio'
        if (!Schema::hasTable('evaluaciones_criterio')) {
            Schema::create('evaluaciones_criterio', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('criterio_id');
                $table->unsignedBigInteger('informe_avance_id')->nullable();
                $table->unsignedBigInteger('practica_id');
                $table->unsignedBigInteger('tutor_id');
                $table->decimal('puntuacion', 5, 2);
                $table->text('comentario')->nullable();
                $table->timestamps();

                $table->foreign('criterio_id')->references('id')->on('criterios_rubrica')->cascadeOnDelete();
                $table->foreign('informe_avance_id')->references('id')->on('informes_avance')->nullOnDelete();
                $table->foreign('practica_id')->references('id')->on('practicas')->cascadeOnDelete();
                $table->foreign('tutor_id')->references('id')->on('docentes_tutores')->cascadeOnDelete();
            });
        }

        // 9. Compatibilidad con datos históricos existentes:
        // Prácticas existentes (ID 1 Juan Pérez, ID 2 Luis Torres) se marcan como 'v1_historico'
        DB::table('practicas')->whereIn('id', [1, 2])->update([
            'version_reglamento' => 'v1_historico',
            'cronograma_aprobado' => true,
            'cronograma_aprobado_at' => now(),
        ]);

        DB::table('cronograma_actividades')->whereIn('practica_id', [1, 2])->update([
            'aprobada_coordinacion' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_criterio');
        Schema::dropIfExists('criterios_rubrica');
        Schema::dropIfExists('rubricas_actividad');
        Schema::dropIfExists('incidencias_practica');
        Schema::dropIfExists('justificaciones_asistencia');

        Schema::table('informes_avance', function (Blueprint $table) {
            if (Schema::hasColumn('informes_avance', 'actividad_id')) {
                $table->dropForeign(['actividad_id']);
                $table->dropColumn(['actividad_id', 'version', 'observaciones_tutor', 'calificacion']);
            }
        });

        Schema::table('cronograma_actividades', function (Blueprint $table) {
            $table->dropColumn(['entregable_esperado', 'aprobada_coordinacion', 'rubrica_definida']);
        });

        Schema::table('practicas', function (Blueprint $table) {
            if (Schema::hasColumn('practicas', 'cronograma_aprobado_por')) {
                $table->dropForeign(['cronograma_aprobado_por']);
            }
            $table->dropColumn([
                'version_reglamento',
                'cronograma_aprobado',
                'cronograma_aprobado_at',
                'cronograma_aprobado_por',
                'horas_programadas',
                'faltas_justificadas',
                'incompletas_justificadas'
            ]);
        });
    }
};

