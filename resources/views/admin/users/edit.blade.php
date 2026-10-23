@extends('layouts.app')

@section('title', 'Editar Usuario - PuntoÁgil')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div>
        <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
            <a href="{{ route('admin.users.index') }}" class="hover:text-emerald-600 transition-colors">Usuarios</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span>{{ $user->name }}</span>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-emerald-600 font-semibold">Editar</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Editar Usuario</h1>
        <p class="text-xs text-slate-500 mt-0.5">Actualiza los datos de acceso, el rol operativo o el estado de la cuenta.</p>
    </div>

    @include('admin.users.form')
</div>
@endsection
