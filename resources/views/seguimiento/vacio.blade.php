@extends('layouts.app')

@section('title', 'Seguimiento de Prácticas - UPDSonline')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4" style="max-width: 650px; margin: 0 auto;">
    <div class="user-avatar mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem; background: #e0f2fe; color: #0284c7;">
        <i class="bi bi-clock-history"></i>
    </div>
    <h4 class="fw-bold text-navy" style="color: var(--upds-navy);">Sin Práctica Activa en Seguimiento</h4>
    <p class="text-muted mt-2 mb-4">{{ $mensaje }}</p>
    <div>
        <a href="{{ route('solicitudes.create') }}" class="btn btn-upds px-4">
            <i class="bi bi-plus-lg me-1"></i> Iniciar Solicitud de Práctica
        </a>
    </div>
</div>
@endsection

