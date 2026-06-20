@extends('layouts.app')

@section('title', 'Control de Inventario - Ferretería El Tornillo')

@section('content')
<div class="container-fluid">
    <!-- Fila del Encabezado con Botones de Acción -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-6">
            <h1 class="h3 fw-bold text-dark mb-0">Inventario General de Bodega</h1>
            <p class="text-muted">Monitorea niveles de stock, marcas de cemento, tornillos de acero y herramientas del local.</p>
        </div>
        <div class="col-md-6 text-md-end d-flex flex-wrap gap-2 justify-content-md-end">
            <!-- Botones de Exportación UI (Sin lógica compleja) -->
            <a href="{{ route('inventory.export.pdf') }}" class="btn btn-outline-danger fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-filetype-pdf fs-5 text-danger"></i>
                <span>Exportar PDF</span>
            </a>
            <a href="{{ route('inventory.export.csv') }}" class="btn btn-outline-success fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-filetype-xlsx fs-5 text-success"></i>
                <span>Exportar CSV</span>
            </a>

            <!-- Registrar Producto Nuevo -->
            <a href="{{ route('inventory.create') }}" class="btn btn-primary fw-bold d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle"></i>
                <span>Nuevo Producto</span>
            </a>
        </div>
    </div>

    <!-- Filtros de Búsqueda y Selección -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('inventory.index') }}" method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label text-muted fw-bold small">BUSCAR PRODUCTO</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Escribe el nombre, código SKU o material..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fw-bold small">CATEGORIA</label>
                    <select name="category" class="form-select">
                        <option value="" {{ request('category') === '' ? 'selected' : '' }}>Todas las Familias</option>
                        <option value="Herramientas Manuales" {{ request('category') === 'Herramientas Manuales' ? 'selected' : '' }}>Herramientas Manuales</option>
                        <option value="Herramientas Eléctricas" {{ request('category') === 'Herramientas Eléctricas' ? 'selected' : '' }}>Herramientas Eléctricas</option>
                        <option value="Fijaciones y Tornillos" {{ request('category') === 'Fijaciones y Tornillos' ? 'selected' : '' }}>Fijaciones y Tornillos</option>
                        <option value="Construcción y Áridos" {{ request('category') === 'Construcción y Áridos' ? 'selected' : '' }}>Construcción y Áridos</option>
                        <option value="Plomería" {{ request('category') === 'Plomería' ? 'selected' : '' }}>Plomería</option>
                        <option value="Pinturas" {{ request('category') === 'Pinturas' ? 'selected' : '' }}>Pinturas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted fw-bold small">ESTADO FILTRO</label>
                    <select name="status" class="form-select">
                        <option value="" {{ request('status') === '' ? 'selected' : '' }}>Todos los Estados</option>
                        <option value="disponible" {{ request('status') === 'disponible' ? 'selected' : '' }}>Suficiente/Disponible</option>
                        <option value="critico" {{ request('status') === 'critico' ? 'selected' : '' }}>Bajo Stock</option>
                        <option value="sin_stock" {{ request('status') === 'sin_stock' ? 'selected' : '' }}>Agotado (Sin Stock)</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100 py-2 fw-semibold">
                        <i class="bi bi-funnel me-1"></i> Filtrar Stock
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Base de Datos -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-dark">
                    <thead class="table-light">
                        <tr class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                            <th class="px-4 py-3">Codigo SKU</th>
                            <th class="py-3">Nombre del Material</th>
                            <th class="py-3 text-center">Categoria</th>
                            <th class="py-3 text-end">Precio</th>
                            <th class="py-3 text-center">Stock</th>
                            <th class="py-3 text-center">Estado</th>
                            <th class="py-3 text-end px-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        <tr>
                            <td class="px-4 py-3 font-mono fw-bold">{{ $product->sku }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $product->name }}</div>
                                <small class="text-muted">{{ Str::limit($product->description, 80) }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border py-1.5 px-2.5">{{ $product->category }}</span>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold">${{ number_format($product->price, 2) }}</div>
                                <small class="text-muted" style="font-size: 0.75rem;">Unidad: {{ $product->unit }}</small>
                            </td>
                            <td class="text-center fw-bold font-mono">{{ $product->stock }}</td>
                            <td class="text-center">
                                @if($product->stock <= 0)
                                    <span class="badge bg-danger text-white py-1.5 px-3">
                                        <i class="bi bi-shield-slash-fill me-1"></i> Sin Stock
                                    </span>
                                @elseif($product->stock <= 10)
                                    <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-15 py-1.5 px-3">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Stock Crítico
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-10 py-1.5 px-3">
                                        <i class="bi bi-check-circle me-1"></i> Disponible
                                    </span>
                                @endif
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group">
                                    <a href="{{ route('inventory.edit', $product->id) }}" class="btn btn-sm btn-outline-secondary" title="Editar ficha de producto">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form id="delete-form-{{ $product->id }}" action="{{ route('inventory.destroy', $product->id) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="btn btn-sm btn-outline-danger btn-delete-product"
                                            type="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal"
                                            data-product-name="{{ $product->name }}"
                                            data-form-id="delete-form-{{ $product->id }}"
                                            title="Eliminar producto"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No hay productos disponibles en este momento.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Navegación de Páginas Maqueta -->
            <div class="card-footer bg-white border-top py-3 d-flex align-items-center justify-content-between">
                <span class="text-muted small">Mostrando <strong>{{ $products->count() ? 1 : 0 }}</strong> a <strong>{{ $products->count() }}</strong> de <strong>{{ $products->count() }}</strong> registros de materiales</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled">
                            <span class="page-link">Anterior</span>
                        </li>
                        <li class="page-item active" aria-current="page">
                            <span class="page-link">1</span>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">2</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal de eliminación elegante -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white py-3 px-4">
                    <div>
                        <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-trash-fill me-2"></i>Eliminar Producto</h5>
                        <p class="mb-0 text-white-75 small">Esta acción no se puede deshacer.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-exclamation-octagon-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Confirmar eliminación</h6>
                            <p class="mb-0 text-muted" id="deleteModalText">¿Deseas eliminar este producto del inventario?</p>
                        </div>
                    </div>
                    <div class="alert alert-warning mb-0" role="alert">
                        <strong>Atención:</strong> los registros de ventas relacionados no se borrarán automáticamente.
                    </div>
                </div>
                <div class="modal-footer px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger fw-semibold" id="confirmDeleteBtn">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteModal = document.getElementById('deleteModal');
        const deleteModalText = document.getElementById('deleteModalText');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        let currentForm = null;

        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            currentForm = document.getElementById(button.getAttribute('data-form-id'));
            const productName = button.getAttribute('data-product-name');

            deleteModalText.textContent = `¿Estás seguro de eliminar "${productName}"? Esta acción es permanente.`;
        });

        confirmDeleteBtn.addEventListener('click', function () {
            if (currentForm) {
                currentForm.submit();
            }
        });
    });
</script>
@endsection
