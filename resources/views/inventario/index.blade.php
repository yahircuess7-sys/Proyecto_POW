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
            <button class="btn btn-outline-danger fw-semibold d-flex align-items-center gap-2" onclick="alert('Exportando listado de inventario a PDF...')">
                <i class="bi bi-filetype-pdf fs-5 text-danger"></i>
                <span>Exportar PDF</span>
            </button>
            <button class="btn btn-outline-success fw-semibold d-flex align-items-center gap-2" onclick="alert('Exportando catálogo de stock a plantilla Excel...')">
                <i class="bi bi-filetype-xlsx fs-5 text-success"></i>
                <span>Exportar Excel</span>
            </button>

            <!-- Registrar Producto Nuevo -->
            <a href="{{ route('inventario.create') }}" class="btn btn-primary fw-bold d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle"></i>
                <span>Nuevo Producto</span>
            </a>
        </div>
    </div>

    <!-- Filtros de Búsqueda y Selección -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('inventario.index') }}" method="GET" class="row g-3">
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
                    <select name="categoria" class="form-select">
                        <option value="">Todas las Familias</option>
                        <option value="Herramientas Manuales">Herramientas Manuales</option>
                        <option value="Herramientas Eléctricas">Herramientas Eléctricas</option>
                        <option value="Fijaciones y Tornillos">Fijaciones y Tornillos</option>
                        <option value="Construcción y Áridos">Construcción y Áridos</option>
                        <option value="Plomería">Plomeria</option>
                        <option value="Pinturas">Pinturas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted fw-bold small">ESTADO FILTRO</label>
                    <select name="status" class="form-select">
                        <option value="">Todos los Estados</option>
                        <option value="disponible">Suficiente/Disponible</option>
                        <option value="critico">Bajo Stock</option>
                        <option value="sin_stock">Agotado (Sin Stock)</option>
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
                            <th class="py-3 text-end">Precio Compra/Venta</th>
                            <th class="py-3 text-center">Stock Físico</th>
                            <th class="py-3 text-center">Estado Alerta</th>
                            <th class="py-3 text-end px-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Fila 1: Stock Normal -->
                        <tr>
                            <td class="px-4 py-3 font-mono fw-bold">SKU-TORN-009</td>
                            <td>
                                <div class="fw-bold text-dark">Tornillo Drywall 1x6 (100un)</div>
                                <small class="text-muted">Fijación de paneles de yeso y madera suave</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border py-1.5 px-2.5">Fijaciones y Tornillos</span>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold">$4.50</div>
                                <small class="text-muted" style="font-size: 0.75rem;">Sugerido: $6.00</small>
                            </td>
                            <td class="text-center fw-bold font-mono">140 Unidades</td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success border-opacity-10 py-1.5 px-3">
                                    <i class="bi bi-check-circle me-1"></i> Disponible
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group">
                                    <a href="{{ route('inventario.edit', 1) }}" class="btn btn-sm btn-outline-secondary" title="Editar ficha de producto">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirm('¿Estás seguro de eliminar el SKU-TORN-009?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Fila 2: Cemento Stock Critico -->
                        <tr>
                            <td class="px-4 py-3 font-mono fw-bold">SKU-CEME-512</td>
                            <td>
                                <div class="fw-bold text-dark">Cemento Gris Portlat de 50Kg</div>
                                <small class="text-muted">Material básico de alta resistencia estructural - Holcim</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border py-1.5 px-2.5">Construcción y Áridos</span>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold">$9.20</div>
                                <small class="text-muted" style="font-size: 0.75rem;">Sugerido: $11.50</small>
                            </td>
                            <td class="text-center fw-bold font-mono text-warning">8 Bolsas</td>
                            <td class="text-center">
                                <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-15 py-1.5 px-3">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Stock Critico
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group">
                                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i></a>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>

                        <!-- Fila 3: Cero Stock - Badge Rojo Requerido -->
                        <tr>
                            <td class="px-4 py-3 font-mono fw-bold text-danger">SKU-ELEC-401</td>
                            <td>
                                <div class="fw-bold text-dark text-decoration-line-through">Martillo Demoledor Bosch 1100W</div>
                                <small class="text-muted text-danger">Falta entrega del distribuidor alemán autorizado</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border py-1.5 px-2.5">Herramientas Eléctricas</span>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold">$345.00</div>
                                <small class="text-muted" style="font-size: 0.75rem;">Sugerido: $420.00</small>
                            </td>
                            <td class="text-center fw-bold font-mono text-danger">0 Unidades</td>
                            <td class="text-center">
                                <!-- REGLA VISUAL DE STOCK 0 - ETIQUETA ROJA OBLIGATORIA -->
                                <span class="badge bg-danger text-white py-1.5 px-3 animate-pulse">
                                    <i class="bi bi-shield-slash-fill me-1"></i> Sin Stock
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group">
                                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i></a>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>

                        <!-- Fila 4: Alambre Acero disponible -->
                        <tr>
                            <td class="px-4 py-3 font-mono fw-bold">SKU-ALAM-103</td>
                            <td>
                                <div class="fw-bold text-dark">Rollo de Alambre Recocido N.18</div>
                                <small class="text-muted">Amarras estribos metálicos gruesos</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border py-1.5 px-2.5">Fijaciones y Tornillos</span>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold">$1.80</div>
                                <small class="text-muted" style="font-size: 0.75rem;">Sugerido: $2.40</small>
                            </td>
                            <td class="text-center fw-bold font-mono">75 Rollos</td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success border-opacity-10 py-1.5 px-3">
                                    <i class="bi bi-check-circle me-1"></i> Disponible
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group">
                                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i></a>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Navegación de Páginas Maqueta -->
            <div class="card-footer bg-white border-top py-3 d-flex align-items-center justify-content-between">
                <span class="text-muted small">Mostrando <strong>1</strong> a <strong>4</strong> de <strong>4</strong> registros de materiales</span>
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
</div>
@endsection