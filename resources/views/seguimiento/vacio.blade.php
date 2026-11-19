@extends('layouts.app')

@section('title', 'Seguimiento de Prácticas - UPDSonline')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-12 col-md-9 col-lg-7 col-xl-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 text-center">
            <div class="user-avatar mx-auto mb-3" style="width: 72px; height: 72px; font-size: 2rem; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                <i class="bi bi-clock-history"></i>
            </div>
            <h4 class="fw-bold mb-2" style="color: var(--upds-navy);">Sin Práctica Activa en Seguimiento</h4>
            <p class="text-muted mb-4">{{ $mensaje }}</p>
            <div>
                <a href="{{ route('solicitudes.create') }}" class="btn btn-upds rounded-pill px-4 py-2 fw-semibold">
                    <i class="bi bi-plus-lg me-1"></i> Iniciar Solicitud de Práctica
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
