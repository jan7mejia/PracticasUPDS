@extends('layouts.app')

@section('title', 'Nueva Solicitud de Práctica - UPDSonline')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
        <!-- Navegación y Encabezado Centrado -->
        <div class="mb-4 text-center">
            <div class="d-inline-flex align-items-center gap-2 mb-2">
                <a href="{{ $organizacionPreseleccionada ? route('organizaciones.index', ['selected' => $organizacionPreseleccionada->id]) : route('solicitudes.index') }}" 
                   class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                    <i class="bi bi-mortarboard me-1"></i> Trámite de Prácticas Profesionales
                </span>
            </div>
            <h3 class="fw-bold text-navy mb-1" style="color: var(--upds-navy);">
                Iniciar Solicitud de Prácticas
            </h3>
            <p class="text-muted small mx-auto" style="max-width: 580px;">
                Formaliza tu postulación vinculando tus datos académicos con la institución receptora y el período universitario correspondiente.
            </p>
        </div>

        <!-- Tarjeta Principal del Formulario -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header py-3 px-4 text-white d-flex justify-content-between align-items-center" 
                 style="background: linear-gradient(135deg, var(--upds-navy) 0%, var(--upds-primary) 100%);">
                <span class="fw-semibold">
                    <i class="bi bi-file-earmark-text me-2"></i>Formulario Oficial de Postulación
                </span>
                <span class="badge bg-light text-primary rounded-pill px-3">Paso 1 de 2</span>
            </div>

            <div class="card-body p-4 p-md-5">
                <form action="{{ route('solicitudes.store') }}" method="POST">
                    @csrf

                    {{-- MODO A: Organización Preseleccionada desde el catálogo --}}
                    @if($organizacionPreseleccionada)
                        <input type="hidden" name="organizacion_id" value="{{ $organizacionPreseleccionada->id }}">

                        <div class="p-3 mb-4 rounded-4 border border-primary-subtle" style="background-color: #f0f7ff;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle p-2 bg-primary text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                        <i class="bi bi-building-check fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                            Organización Receptora Seleccionada
                                        </div>
                                        <h5 class="fw-bold text-dark mb-0" style="color: var(--upds-navy);">
                                            {{ $organizacionPreseleccionada->nombre }}
                                        </h5>
                                    </div>
                                </div>
                                <span class="badge bg-success text-white rounded-pill px-3 py-1">
                                    <i class="bi bi-check-circle me-1"></i> Convenio Activo
                                </span>
                            </div>

                            <hr class="my-2 border-primary-subtle opacity-50">

                            <div class="row g-2 small text-muted">
                                <div class="col-md-6">
                                    <i class="bi bi-tag me-1 text-primary"></i> <strong>Rubro:</strong> {{ $organizacionPreseleccionada->rubro ?? 'General' }}
                                </div>
                                <div class="col-md-6">
                                    <i class="bi bi-geo-alt me-1 text-primary"></i> <strong>Dirección:</strong> {{ $organizacionPreseleccionada->direccion ?? 'Sede Central' }}
                                </div>
                                @if($organizacionPreseleccionada->responsables->isNotEmpty())
                                    <div class="col-md-6">
                                        <i class="bi bi-person-badge me-1 text-primary"></i> <strong>Contacto:</strong> {{ $organizacionPreseleccionada->responsables->first()->nombre_completo }}
                                    </div>
                                    <div class="col-md-6">
                                        <i class="bi bi-telephone me-1 text-primary"></i> <strong>Teléfono:</strong> {{ $organizacionPreseleccionada->telefono ?? 'Sin teléfono' }}
                                    </div>
                                @endif
                            </div>

                            <div class="mt-2 pt-2 border-top border-primary-subtle d-flex justify-content-between align-items-center">
                                <small class="text-primary fst-italic">
                                    <i class="bi bi-info-circle me-1"></i> Esta empresa fue preseleccionada desde tu consulta de organizaciones.
                                </small>
                                <a href="{{ route('solicitudes.create') }}" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0" style="font-size: 0.75rem;">
                                    Cambiar empresa
                                </a>
                            </div>
                        </div>

                    {{-- MODO B: Selección manual desde lista completa --}}
                    @else
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark">
                                Seleccionar Organización Receptora *
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-buildings text-muted"></i></span>
                                <select name="organizacion_id" class="form-select bg-light border-start-0 @error('organizacion_id') is-invalid @enderror" required>
                                    <option value="">-- Elige una organización autorizada con convenio vigente --</option>
                                    @foreach($organizaciones as $org)
                                        <option value="{{ $org->id }}" {{ old('organizacion_id') == $org->id ? 'selected' : '' }}>
                                            {{ $org->nombre }} &bull; ({{ $org->rubro ?? 'General' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('organizacion_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted">Empresas e instituciones actualmente habilitadas por Coordinación.</small>
                                <a href="{{ route('organizaciones.index') }}" class="text-primary small text-decoration-none">
                                    <i class="bi bi-search me-1"></i> Ver catálogo completo
                                </a>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Período Académico *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-event text-muted"></i></span>
                                <select name="periodo_id" class="form-select bg-light border-start-0 @error('periodo_id') is-invalid @enderror" required>
                                    @foreach($periodos as $p)
                                        <option value="{{ $p->id }}" {{ old('periodo_id') == $p->id ? 'selected' : '' }}>
                                            {{ $p->codigo }} &bull; {{ $p->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('periodo_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Modalidad de Práctica</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-award text-muted"></i></span>
                                <input type="text" class="form-control bg-light border-start-0 text-muted" value="Práctica Profesional Regular" readonly disabled>
                            </div>
                            <small class="text-muted">Acreditación curricular obligatoria de carrera.</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-dark">
                            Propuesta inicial de área, funciones o proyecto a desarrollar
                        </label>
                        <textarea name="observacion" class="form-control @error('observacion') is-invalid @enderror" 
                                  rows="4" placeholder="Ejemplo: Práctica en el departamento de Sistemas y TI, apoyando en desarrollo de software, soporte a infraestructura de redes y gestión de bases de datos...">{{ old('observacion') }}</textarea>
                        @error('observacion')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">
                            Esta información orientará a la Coordinación Académica para la designación de tu docente tutor.
                        </small>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-4 d-flex align-items-center gap-3">
                        <i class="bi bi-shield-check fs-3 text-primary"></i>
                        <div class="small text-muted">
                            Al enviar esta solicitud, se registrará el trámite en estado <strong>Presentada</strong>. A continuación podrás adjuntar tu <strong>Carta de Aceptación</strong> y <strong>Plan de Trabajo</strong> en el visor documental.
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('solicitudes.index') }}" class="btn btn-light rounded-pill px-4">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-upds rounded-pill px-4 fw-semibold">
                            <i class="bi bi-send me-1"></i> Enviar Solicitud a Coordinación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
