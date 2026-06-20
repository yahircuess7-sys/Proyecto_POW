@extends('layouts.app')

@section('title', 'Crear Usuario - Ferretería El Tornillo')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold text-dark">Crear Nuevo Usuario</h4>
            <p class="text-muted">Registra un nuevo administrador o cajero con acceso al sistema.</p>
        </div>
    </div>

    @include('admin.users.form')
</div>
@endsection
