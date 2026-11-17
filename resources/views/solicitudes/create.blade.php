@extends('layouts.app')

@section('title', 'Nueva Solicitud de Práctica - UPDSonline')

@section('content')
<div class="mb-4">
    <a href="{{ route('solicitudes.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Volver a solicitudes
    </a>
    <h4 class="fw-bold text-navy mt-2" style="color: var(--upds-navy);">
        Iniciar Solicitud de Prácticas Profesionales
    </h4>
    <p class="text-muted small">Completa los datos de la institución o empresa receptora donde realizarás tu práctica.</p>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4" style="max-width: 800px;">
    <form action="{{ route('solicitudes.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label small fw-semibold">Seleccionar Organización Receptora *</label>
                <select name="organizacion_id" class="form-select" required>
                    <option value="">-- Elige una organización registrada --</option>
                    @foreach($organizaciones as $org)
                        <option value="{{ $org->id }}">{{ $org->nombre }} ({{ $org->rubro ?? 'General' }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Si tu empresa no aparece en la lista, solicita al Coordinador que la dé de alta.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-semibold">Período Académico *</label>
                <select name="periodo_id" class="form-select" required>
                    @foreach($periodos as $p)
                        <option value="{{ $p->id }}">{{ $p->codigo }} - {{ $p->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-semibold">Modalidad de Práctica</label>
                <input type="text" class="form-control" value="Práctica Preprofesional Regular" readonly disabled>
            </div>

            <div class="col-12">
                <label class="form-label small fw-semibold">Observaciones o descripción inicial de actividades previstas</label>
                <textarea name="observacion" class="form-control" rows="3" placeholder="Describe brevemente las áreas o proyectos donde desarrollarás tu práctica..."></textarea>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
            <a href="{{ route('solicitudes.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-upds">Enviar Solicitud a Coordinación</button>
        </div>
    </form>
</div>
@endsection

