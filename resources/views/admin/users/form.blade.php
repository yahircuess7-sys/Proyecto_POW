<form action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}"
      method="POST"
      class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
    @csrf
    @if(isset($user))
        @method('PUT')
    @endif

    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h2 class="text-sm font-bold text-slate-900">
                {{ isset($user) ? 'Datos del Usuario' : 'Formulario de Registro' }}
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ isset($user) ? 'Modifica los permisos de acceso y perfil.' : 'Completa todos los campos obligatorios para generar las credenciales.' }}
            </p>
        </div>
        @if(isset($user))
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-mono font-medium bg-slate-100 text-slate-600">
                ID: #{{ $user->id }}
            </span>
        @endif
    </div>

    <div class="p-6 space-y-5 text-xs">
        <!-- Nombre Completo -->
        <div>
            <label for="name" class="block font-semibold text-slate-700 mb-1.5">
                Nombre y Apellido <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <i class="bi bi-person absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       name="name"
                       id="name"
                       value="{{ old('name', $user->name ?? '') }}"
                       placeholder="Ej. Roberto Andrade"
                       required
                       class="w-full pl-10 pr-4 py-2.5 text-xs text-slate-800 bg-slate-50 border rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all {{ $errors->has('name') ? 'border-rose-300 ring-rose-500/20' : 'border-slate-200' }}">
            </div>
            @error('name')
                <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block font-semibold text-slate-700 mb-1.5">
                Correo Electrónico de Acceso <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <i class="bi bi-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="email"
                       name="email"
                       id="email"
                       value="{{ old('email', $user->email ?? '') }}"
                       placeholder="usuario@eltornillo.com"
                       required
                       class="w-full pl-10 pr-4 py-2.5 text-xs text-slate-800 bg-slate-50 border rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all {{ $errors->has('email') ? 'border-rose-300 ring-rose-500/20' : 'border-slate-200' }}">
            </div>
            @error('email')
                <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block font-semibold text-slate-700">
                    Contraseña {{ isset($user) ? '(Opcional)' : '' }} <span class="{{ isset($user) ? 'hidden' : 'text-rose-500' }}">*</span>
                </label>
                @if(isset($user))
                    <span class="text-[11px] text-slate-400">Dejar en blanco para mantener la actual</span>
                @else
                    <span class="text-[11px] text-slate-400">Mínimo 8 caracteres</span>
                @endif
            </div>
            <div class="relative">
                <i class="bi bi-key absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="password"
                       name="password"
                       id="password"
                       placeholder="••••••••"
                       {{ isset($user) ? '' : 'required' }}
                       class="w-full pl-10 pr-4 py-2.5 text-xs text-slate-800 bg-slate-50 border rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all {{ $errors->has('password') ? 'border-rose-300 ring-rose-500/20' : 'border-slate-200' }}">
            </div>
            @error('password')
                <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Rol y Estado Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <div>
                <label for="role" class="block font-semibold text-slate-700 mb-1.5">
                    Rol Operativo <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <select name="role"
                            id="role"
                            required
                            class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all appearance-none cursor-pointer">
                        <option value="admin" {{ old('role', $user->role ?? '') === 'admin' ? 'selected' : '' }}>
                            👑 Administrador (Acceso total al sistema)
                        </option>
                        <option value="cajero" {{ old('role', $user->role ?? '') === 'cajero' ? 'selected' : '' }}>
                            ⚡ Cajero POS (Punto de venta y facturación)
                        </option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                </div>
                @error('role')
                    <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block font-semibold text-slate-700 mb-1.5">
                    Estado de la Cuenta <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <select name="status"
                            id="status"
                            required
                            class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all appearance-none cursor-pointer">
                        <option value="active" {{ old('status', $user->status ?? '') === 'active' ? 'selected' : '' }}>
                            🟢 Activo (Puede iniciar sesión)
                        </option>
                        <option value="inactive" {{ old('status', $user->status ?? '') === 'inactive' ? 'selected' : '' }}>
                            ⚪ Inactivo (Acceso suspendido)
                        </option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                </div>
                @error('status')
                    <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Actions Footer -->
    <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-end gap-3">
        <a href="{{ route('admin.users.index') }}"
           class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-200/50 rounded-xl transition-colors">
            Cancelar
        </a>
        <button type="submit"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-sm hover:shadow transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
            <i class="bi bi-check-lg text-sm"></i>
            <span>{{ isset($user) ? 'Guardar Cambios' : 'Crear Usuario' }}</span>
        </button>
    </div>
</form>
