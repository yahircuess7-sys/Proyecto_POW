@extends('layouts.app')

@section('title', 'Gestionar Usuarios Activos')

@section('content')
<div class="row align-items-center mb-4 fade-in">
    <div class="col-md-6">
        <h4 class="fw-bold mb-0 text-dark">
            <i class="bi bi-people-fill text-warning me-2"></i>
            Gestión de Usuarios Activos
        </h4>
        <p class="text-secondary small mb-0 mt-1">Control de credenciales y roles para administradores y cajeros.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <a href="{{ route('admin.users.create') }}" class="btn btn-warning fw-bold text-dark px-4 shadow-sm" style="border-radius: 6px;">
            <i class="bi bi-person-plus-fill me-1"></i>
            Crear Nuevo Usuario
        </a>
    </div>
</div>

<!-- Usuarios Table Card -->
<div class="card border-0 shadow-sm fade-in mb-4" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <h6 class="mb-0 fw-bold text-secondary">Identidades del Sistema</h6>
        <form action="{{ route('admin.users.index') }}" method="GET" class="input-group" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Buscar empleado..." style="font-size: 0.85rem;" value="{{ request('search') }}">
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-nowrap">
            <thead class="bg-light text-muted" style="font-size: 0.8rem;">
                <tr>
                    <th class="ps-4 fw-semibold text-uppercase">Nombre y Apellido</th>
                    <th class="fw-semibold text-uppercase">Email de Acceso</th>
                    <th class="fw-semibold text-uppercase">Rol</th>
                    <th class="fw-semibold text-uppercase">Estado</th>
                    <th class="fw-semibold text-uppercase">Creación</th>
                    <th class="text-end pe-4 fw-semibold text-uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody style="font-size: 0.9rem;">
                @forelse($users as $user)
                <tr>
                    <td class="ps-4 fw-medium text-dark">{{ $user->name }}</td>
                    <td class="text-muted">{{ $user->email }}</td>
                    <td>
                        @if($user->role === 'admin')
                            <span class="badge bg-danger text-white">Administrador</span>
                        @else
                            <span class="badge bg-primary text-white">Cajero POS</span>
                        @endif
                    </td>
                    <td>
                        @if($user->status === 'active')
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Activo</span>
                        @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-muted font-monospace">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="text-end pe-4">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary py-1 px-2 border-0" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline-block" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 border-0" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No se encontraron usuarios registrados en la base de datos.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($users, 'links'))
<div class="d-flex justify-content-center mt-4">
    {{ $users->links() }}
</div>
@endif
@endsection