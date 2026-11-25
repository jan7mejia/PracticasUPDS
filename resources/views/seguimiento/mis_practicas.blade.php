@extends('layouts.app')

@section('title', 'Mis Prácticas Profesionales - UPDSonline')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-navy" style="color: var(--upds-navy);">
            <i class="bi bi-folder2-open text-primary me-2"></i>Mis Prácticas Profesionales
        </h4>
        <p class="text-muted small mb-0">Expediente académico de prácticas pre-profesionales, bitácoras de horas y calificaciones finales.</p>
    </div>
    <div>
        @php
            $concluidasCount = $practicas->where('estado', 'concluida')->count();
            $enCursoCount = $practicas->where('estado', 'en_curso')->count();
        @endphp
        @if($concluidasCount >= 2)
            <span class="badge bg-success fs-6 px-3 py-2 rounded-pill shadow-sm">
                <i class="bi bi-patch-check-fill me-1"></i> 2 de 2 Prácticas Acreditadas (Graduación Habilitada)
            </span>
        @elseif($concluidasCount == 1 && $enCursoCount == 0)
            <a href="{{ route('solicitudes.create') }}" class="btn btn-sm btn-primary rounded-pill px-3 py-2 fw-semibold shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Solicitar Segunda Práctica (Final)
            </a>
        @endif
    </div>
</div>

@if($concluidasCount >= 2)
    <!-- Banner de Felicitación / Graduación -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); color: #ffffff;">
        <div class="d-flex align-items-center gap-3">
            <div style="width: 58px; height: 58px; border-radius: 50%; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; flex-shrink: 0;">
                <i class="bi bi-mortarboard-fill text-warning"></i>
            </div>
            <div>
                <span class="badge bg-warning text-dark fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">REQUISITO DE GRADUACIÓN 100% CUMPLIDO</span>
                <h5 class="fw-bold mb-1 text-white">¡Felicitaciones, {{ $estudiante->nombre_completo }}!</h5>
                <p class="text-white-50 small mb-0">
                    Has completado y aprobado satisfactoriamente las <strong>2 Prácticas Profesionales obligatorias (Práctica I y Práctica II)</strong> exigidas por el plan de estudios de la UPDS. Ambas se encuentran acreditadas con calificaciones sobresalientes en tu expediente académico.
                </p>
            </div>
        </div>
    </div>
@endif

<!-- Tarjetas Resumen de Desempeño General -->
@php
    $totalHorasValidadas = $practicas->sum(fn($p) => $p->registrosHoras->sum('horas'));
    $totalInformes = $practicas->sum(fn($p) => $p->informes->where('estado', 'revisado')->count());
    $promedioNotas = $practicas->whereNotNull('evaluacionFinal')->avg(fn($p) => $p->evaluacionFinal->calificacion);
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2" style="background: #e0f2fe; color: #0284c7; font-size: 1.4rem;">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
                <div>
                    <div class="fs-5 fw-bold text-dark">{{ $practicas->count() }} de 2</div>
                    <small class="text-muted d-block">Prácticas cursadas</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2" style="background: #ede9fe; color: #7c3aed; font-size: 1.4rem;">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($totalHorasValidadas, 0) }} hrs</div>
                    <small class="text-muted d-block">Horas validadas</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2" style="background: #dcfce7; color: #16a34a; font-size: 1.4rem;">
                    <i class="bi bi-file-earmark-check-fill"></i>
                </div>
                <div>
                    <div class="fs-5 fw-bold text-dark">{{ $totalInformes }} aprobados</div>
                    <small class="text-muted d-block">Informes técnicos</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2" style="background: #fef3c7; color: #d97706; font-size: 1.4rem;">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div>
                    <div class="fs-5 fw-bold text-dark">{{ $promedioNotas ? number_format($promedioNotas, 1) . ' / 100' : 'En proceso' }}</div>
                    <small class="text-muted d-block">Promedio académico</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Listado de Prácticas Profesionales -->
<div class="mb-3">
    <h6 class="fw-bold text-muted text-uppercase small" style="letter-spacing: 0.5px;">
        Selecciona una práctica para abrir su módulo de seguimiento completo:
    </h6>
</div>

<div class="row g-4 mb-4">
    @foreach($practicas as $idx => $prac)
        @php
            $horasVal = $prac->registrosHoras->sum('horas');
            $porcentaje = $prac->horas_objetivo > 0 ? min(100, round(($horasVal / $prac->horas_objetivo) * 100, 1)) : 0;
            $tituloPractica = $idx === 0 ? 'Práctica Profesional I' : 'Práctica Profesional II (Final)';
        @endphp
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white d-flex flex-column position-relative border-top border-4 {{ $prac->estado === 'concluida' ? 'border-success' : 'border-primary' }}">
                <!-- Encabezado de la Tarjeta -->
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge {{ $prac->estado === 'concluida' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' }} rounded-pill px-3 py-1 fw-bold mb-1">
                            {{ $tituloPractica }}
                        </span>
                        <h5 class="fw-bold text-dark mb-0 mt-1">{{ $prac->solicitud->organizacion->nombre }}</h5>
                        <small class="text-muted">{{ $prac->solicitud->organizacion->rubro ?? 'Entorno Profesional' }}</small>
                    </div>
                    <div>
                        <span class="badge-{{ $prac->estado }} fs-6 px-3 py-1">
                            {{ ucfirst(str_replace('_', ' ', $prac->estado)) }}
                        </span>
                    </div>
                </div>

                <!-- Datos Clave de la Práctica -->
                <div class="p-3 bg-light rounded-3 small mb-3">
                    <div class="row g-2">
                        <div class="col-5 text-muted">Docente Tutor:</div>
                        <div class="col-7 fw-bold text-dark">
                            {{ $prac->tutor ? $prac->tutor->nombre_completo : 'Pendiente de asignación' }}
                        </div>

                        <div class="col-5 text-muted">Período Académico:</div>
                        <div class="col-7 text-secondary">
                            {{ $prac->solicitud->periodo->codigo ?? '2026' }} &bull;
                            {{ $prac->fecha_inicio ? $prac->fecha_inicio->format('d/m/Y') : '—' }} al {{ $prac->fecha_fin_real ? $prac->fecha_fin_real->format('d/m/Y') : ($prac->fecha_fin_prevista ? $prac->fecha_fin_prevista->format('d/m/Y') : '—') }}
                        </div>

                        <div class="col-5 text-muted">Actividades Cronograma:</div>
                        <div class="col-7 fw-semibold text-dark">
                            {{ $prac->actividades->where('estado', 'completada')->count() }} de {{ $prac->actividades->count() }} completadas
                        </div>

                        <div class="col-5 text-muted">Informes Técnicos:</div>
                        <div class="col-7 fw-semibold text-dark">
                            {{ $prac->informes->where('estado', 'revisado')->count() }} de {{ $prac->informes->count() }} aprobados
                        </div>

                        <div class="col-5 text-muted">Cómputo de Horas:</div>
                        <div class="col-7 fw-bold text-success">
                            {{ number_format($horasVal, 0) }} / {{ number_format($prac->horas_objetivo, 0) }} hrs ({{ $porcentaje }}%)
                        </div>
                    </div>

                    <!-- Barra de Progreso de Horas -->
                    <div class="progress mt-3" style="height: 8px; border-radius: 6px;">
                        <div class="progress-bar {{ $prac->estado === 'concluida' ? 'bg-success' : 'bg-primary progress-bar-striped' }}"
                             role="progressbar" style="width: {{ $porcentaje }}%;" aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Calificación y Botón de Acceso -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-auto">
                    <div>
                        <span class="text-muted d-block small" style="font-size: 0.75rem;">Calificación Final:</span>
                        @if($prac->evaluacionFinal)
                            <span class="fw-bold fs-5 text-success">
                                {{ $prac->evaluacionFinal->calificacion }} / 100
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">
                                {{ $prac->evaluacionFinal->resultado }}
                            </span>
                        @else
                            <span class="text-secondary small fw-semibold">
                                <i class="bi bi-hourglass-split me-1"></i>En evaluación
                            </span>
                        @endif
                    </div>
                    <div>
                        <a href="{{ route('seguimiento.show', $prac->id) }}" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                            Abrir Seguimiento <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

