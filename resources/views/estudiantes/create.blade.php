@extends('layouts.app')

@section('title', 'Nuevo Estudiante - UPDSonline')

@section('content')
<div class="mb-4">
    <a href="{{ route('estudiantes.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i> Volver a Estudiantes
    </a>
    <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
        <i class="bi bi-person-plus-fill me-2 text-primary"></i>Registrar Nuevo Estudiante
    </h4>
    <small class="text-muted">Crea el perfil del estudiante y su cuenta de acceso al sistema con asignación automática de requisitos académicos.</small>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header py-3" style="background:var(--upds-primary);border-radius:16px 16px 0 0;">
                <h6 class="mb-0 text-white fw-semibold"><i class="bi bi-person-badge me-2"></i>Datos Personales y Académicos</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('estudiantes.store') }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nombres *</label>
                            <input type="text" name="nombres" class="form-control @error('nombres') is-invalid @enderror"
                                   value="{{ old('nombres') }}" placeholder="Ej: Ana María" required>
                            @error('nombres') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Apellidos *</label>
                            <input type="text" name="apellidos" class="form-control @error('apellidos') is-invalid @enderror"
                                   value="{{ old('apellidos') }}" placeholder="Ej: Gómez Rojas" required>
                            @error('apellidos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Registro Universitario (RU) *</label>
                            <input type="text" name="ru" class="form-control @error('ru') is-invalid @enderror"
                                   value="{{ old('ru') }}" placeholder="Ej: 3200480" required>
                            @error('ru') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Cédula de Identidad (CI)</label>
                            <input type="text" name="ci" class="form-control @error('ci') is-invalid @enderror"
                                   value="{{ old('ci') }}" placeholder="Ej: 8945123 CB">
                            @error('ci') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold small">Cuenta de Usuario Vinculada (Opcional)</label>
                            <select name="usuario_id" class="form-select @error('usuario_id') is-invalid @enderror">
                                <option value="">— Sin cuenta vinculada (El Administrador creará la cuenta) —</option>
                                @foreach($cuentasEstudiantes as $cu)
                                    <option value="{{ $cu->id }}" {{ old('usuario_id') == $cu->id ? 'selected' : '' }}>
                                        {{ $cu->name }} ({{ $cu->email }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted">Las cuentas de acceso con credenciales son administradas por el Administrador General del sistema.</div>
                            @error('usuario_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">Teléfono / Celular de Contacto</label>
                            <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                   value="{{ old('telefono') }}" placeholder="Ej: 77912345">
                            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Carrera Universitaria *</label>
                            <select name="carrera_id" class="form-select @error('carrera_id') is-invalid @enderror" required>
                                <option value="">— Seleccionar carrera —</option>
                                @foreach($carreras as $c)
                                    <option value="{{ $c->id }}" {{ old('carrera_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('carrera_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Semestre Actual</label>
                            <select name="semestre" class="form-select @error('semestre') is-invalid @enderror">
                                <option value="">— Semestre —</option>
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ old('semestre') == $i ? 'selected' : '' }}>{{ $i }}° Semestre</option>
                                @endfor
                            </select>
                            @error('semestre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="{{ route('estudiantes.index') }}" class="btn btn-outline-secondary px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check-circle me-1"></i> Registrar Estudiante
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

