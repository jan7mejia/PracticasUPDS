@extends('layouts.app')

@section('title', 'Nuevo Período Académico')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.periodos.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i> Volver a Períodos
    </a>
    <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
        <i class="bi bi-calendar-plus me-2"></i>Nuevo Período Académico
    </h4>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3" style="background:var(--upds-primary);border-radius:12px 12px 0 0;">
                <h6 class="mb-0 text-white fw-semibold"><i class="bi bi-calendar3 me-2"></i>Datos del Período</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.periodos.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Código *</label>
                        <input type="text" name="codigo" class="form-control @error('codigo') is-invalid @enderror"
                               value="{{ old('codigo') }}" placeholder="Ej: 2026-I" style="text-transform:uppercase" required>
                        <div class="form-text text-muted">Código único, Ej: 2026-I, 2026-II</div>
                        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nombre del Período *</label>
                        <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                               value="{{ old('nombre') }}" placeholder="Ej: Gestión I - 2026" required>
                        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Fecha de Inicio *</label>
                            <input type="date" name="fecha_inicio" class="form-control @error('fecha_inicio') is-invalid @enderror"
                                   value="{{ old('fecha_inicio') }}" required>
                            @error('fecha_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Fecha de Fin *</label>
                            <input type="date" name="fecha_fin" class="form-control @error('fecha_fin') is-invalid @enderror"
                                   value="{{ old('fecha_fin') }}" required>
                            @error('fecha_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="activo" value="1"
                                   id="activoCheck" {{ old('activo') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="activoCheck">
                                Marcar como Período Activo
                            </label>
                        </div>
                        <small class="text-muted">Solo puede haber un período activo a la vez.</small>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold">
                            <i class="bi bi-calendar-check me-1"></i> Crear Período
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
