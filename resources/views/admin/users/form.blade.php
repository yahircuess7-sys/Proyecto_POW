<form action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST" class="card border-0 shadow-sm rounded-3">
    @csrf
    @if(isset($user))
        @method('PUT')
    @endif

    <div class="card-header bg-white border-bottom py-3">
        <h5 class="fw-bold mb-0 text-dark">{{ isset($user) ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}</h5>
        <p class="text-muted small mb-0">{{ isset($user) ? 'Actualiza los datos y los permisos de acceso.' : 'Registra un empleado con permisos de administrador o cajero POS.' }}</p>
    </div>

    <div class="card-body p-4">
        <div class="row g-4">
            <div class="col-md-6">
                <label for="name" class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label fw-semibold">Email de acceso <span class="text-danger">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" class="form-control @error('email') is-invalid @enderror" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="password" class="form-label fw-semibold">Contraseña {{ isset($user) ? '(dejar en blanco para mantener)' : '*' }}</label>
                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" {{ isset($user) ? '' : 'required' }}>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="role" class="form-label fw-semibold">Rol de usuario <span class="text-danger">*</span></label>
                <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                    <option value="admin" {{ old('role', $user->role ?? '') === 'admin' ? 'selected' : '' }}>Administrador</option>
                    <option value="cajero" {{ old('role', $user->role ?? '') === 'cajero' ? 'selected' : '' }}>Cajero POS</option>
                </select>
                @error('role')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="status" class="form-label fw-semibold">Estado de usuario <span class="text-danger">*</span></label>
                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                    <option value="active" {{ old('status', $user->status ?? '') === 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="inactive" {{ old('status', $user->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="card-footer bg-white border-top py-3 d-flex justify-content-end gap-2">
        <a href="{{ route('admin.users.index') }}" class="btn btn-light border text-dark">Cancelar</a>
        <button type="submit" class="btn btn-warning text-dark fw-bold">{{ isset($user) ? 'Actualizar Usuario' : 'Guardar Usuario' }}</button>
    </div>
</form>
