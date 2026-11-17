@extends('layouts.app')

@section('title', 'Editar Período Académico')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.periodos.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i> Volver a Períodos
    </a>
    <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
        <i class="bi bi-calendar-event me-2"></i>Editar Período Académico
    </h4>
    <small class="text-muted">{{ $periodo->nombre }}</small>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3" style="background:var(--upds-primary);border-radius:12px 12px 0 0;">
                <h6 class="mb-0 text-white fw-semibold"><i class="bi bi-calendar3 me-2"></i>Datos del Período</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.periodos.update', $periodo) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Código *</label>
                        <input type="text" name="codigo" class="form-control @error('codigo') is-invalid @enderror"
                               value="{{ old('codigo', $periodo->codigo) }}" style="text-transform:uppercase" required>
                        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nombre del Período *</label>
                        <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                               value="{{ old('nombre', $periodo->nombre) }}" required>
                        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Fecha de Inicio *</label>
                            <input type="date" name="fecha_inicio" class="form-control"
                                   value="{{ old('fecha_inicio', $periodo->fecha_inicio?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Fecha de Fin *</label>
                            <input type="date" name="fecha_fin" class="form-control"
                                   value="{{ old('fecha_fin', $periodo->fecha_fin?->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="activo" value="1"
                                   id="activoCheck" {{ old('activo', $periodo->activo) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="activoCheck">Período Activo</label>
                        </div>
                        <small class="text-muted">Solo puede haber un período activo a la vez.</small>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold">
                            <i class="bi bi-check2 me-1"></i> Actualizar Período
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
