@extends('layouts.app')

@section('title', 'Editar Usuario - Ferretería El Tornillo')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold text-dark">Editar Usuario</h4>
            <p class="text-muted">Actualiza los datos de acceso del usuario.</p>
        </div>
    </div>

    @include('admin.users.form')
</div>
@endsection
