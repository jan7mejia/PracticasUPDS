@extends('layouts.app')

@section('title', 'Editar Estudiante - UPDSonline')

@section('content')
<div class="mb-4">
    <a href="{{ route('estudiantes.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Volver al listado de estudiantes
    </a>
    <h4 class="fw-bold text-navy mt-2" style="color: var(--upds-navy);">
        Editar Estudiante: {{ $estudiante->nombre_completo }}
    </h4>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="card-header bg-transparent border-0 px-0 pt-0 pb-3 mb-2 border-bottom">
                <h5 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">
                    <i class="bi bi-pencil-square me-2 text-primary"></i>Formulario de Actualización Académica
                </h5>
                <small class="text-muted">Modifica los datos personales y académicos del estudiante seleccionado.</small>
            </div>
            <form action="{{ route('estudiantes.update', $estudiante->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nombres *</label>
                        <input type="text" name="nombres" class="form-control" value="{{ old('nombres', $estudiante->nombres) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Apellidos *</label>
                        <input type="text" name="apellidos" class="form-control" value="{{ old('apellidos', $estudiante->apellidos) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">RU (Registro Universitario) *</label>
                        <input type="text" name="ru" class="form-control" value="{{ old('ru', $estudiante->ru) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Cédula de Identidad (CI)</label>
                        <input type="text" name="ci" class="form-control" value="{{ old('ci', $estudiante->ci) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $estudiante->telefono) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Carrera *</label>
                        <select name="carrera_id" class="form-select" required>
                            @foreach($carreras as $c)
                                <option value="{{ $c->id }}" {{ $estudiante->carrera_id == $c->id ? 'selected' : '' }}>
                                    {{ $c->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Semestre</label>
                        <input type="number" name="semestre" class="form-control" min="1" max="15" value="{{ old('semestre', $estudiante->semestre) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Estado</label>
                        <select name="estado" class="form-select" required>
                            <option value="activo" {{ $estudiante->estado == 'activo' ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ $estudiante->estado == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                            <option value="egresado" {{ $estudiante->estado == 'egresado' ? 'selected' : '' }}>Egresado</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <a href="{{ route('estudiantes.index') }}" class="btn btn-light rounded-pill px-4">Cancelar</a>
                    <button type="submit" class="btn btn-upds rounded-pill px-4">
                        <i class="bi bi-save me-1"></i> Actualizar Información
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

