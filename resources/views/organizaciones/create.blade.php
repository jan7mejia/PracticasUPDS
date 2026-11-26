@extends('layouts.app')

@section('title', 'Nueva Organización Receptora - UPDSonline')

@section('content')
<div class="mb-4">
    <a href="{{ route('organizaciones.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i> Volver a Organizaciones
    </a>
    <h4 class="fw-bold mb-1 text-navy" style="color:var(--upds-navy);">
        <i class="bi bi-building-add text-primary me-2"></i>Registrar Organización Receptora
    </h4>
    <small class="text-muted">Empresas o instituciones habilitadas para recibir estudiantes de práctica profesional.</small>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header py-3" style="background:var(--upds-primary);border-radius:16px 16px 0 0;">
                <h6 class="mb-0 text-white fw-semibold"><i class="bi bi-briefcase me-2"></i>Datos de la Organización y Contacto</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('organizaciones.store') }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Nombre o Razón Social *</label>
                            <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                                   value="{{ old('nombre') }}" placeholder="Ej: Banco Unión S.A." required>
                            @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">NIT (Solo números)</label>
                            <input type="text" name="nit" class="form-control @error('nit') is-invalid @enderror"
                                   value="{{ old('nit') }}" placeholder="Ej: 1020304050" maxlength="15"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            @error('nit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Rubro / Sector *</label>
                            <input type="text" name="rubro" class="form-control @error('rubro') is-invalid @enderror"
                                   value="{{ old('rubro') }}" placeholder="Ej: Banca y Finanzas, Tecnología, Salud"
                                   oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\/\,\.\-]/g, '')">
                            @error('rubro') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Dirección</label>
                            <input type="text" name="direccion" class="form-control @error('direccion') is-invalid @enderror"
                                   value="{{ old('direccion') }}" placeholder="Ej: Av. Ballivián #1234, Cochabamba">
                            @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Teléfono Institucional (Solo números)</label>
                            <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                   value="{{ old('telefono') }}" placeholder="Ej: 4412345 ó 77012345" maxlength="15"
                                   oninput="this.value = this.value.replace(/[^0-9\+\-\s\(\)]/g, '')">
                            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Correo Electrónico</label>
                            <input type="email" name="correo" class="form-control @error('correo') is-invalid @enderror"
                                   value="{{ old('correo') }}" placeholder="contacto@empresa.com">
                            @error('correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-4 border">
                        <h6 class="fw-bold text-navy mb-3"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Responsable / Tutor Empresarial</h6>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Nombres (Solo letras)</label>
                                <input type="text" name="responsable_nombres" class="form-control" value="{{ old('responsable_nombres') }}" placeholder="Ej: Carlos"
                                       pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+"
                                       oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]/g, '')">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Apellidos (Solo letras)</label>
                                <input type="text" name="responsable_apellidos" class="form-control" value="{{ old('responsable_apellidos') }}" placeholder="Ej: Méndez"
                                       pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+"
                                       oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]/g, '')">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Cargo</label>
                                <input type="text" name="responsable_cargo" class="form-control" value="{{ old('responsable_cargo') }}" placeholder="Ej: Jefe de RRHH"
                                       oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\,\/\-]/g, '')">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Teléfono Directo (Solo números)</label>
                                <input type="text" name="responsable_telefono" class="form-control" value="{{ old('responsable_telefono') }}" placeholder="Ej: 77012345" maxlength="15"
                                       oninput="this.value = this.value.replace(/[^0-9\+\-\s\(\)]/g, '')">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Correo de Contacto</label>
                                <input type="email" name="responsable_correo" class="form-control" value="{{ old('responsable_correo') }}" placeholder="carlos.mendez@empresa.com">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('organizaciones.index') }}" class="btn btn-outline-secondary px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Guardar Organización
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

