<?php
// Seguridad, Header y Conexión
include 'includes/seguridad.php';
include 'includes/header.php';
include 'includes/conexion.php';

// LÓGICA DE BÚSQUEDA
$search_term = $_GET['search'] ?? '';
$search_sql = "";
$params_search = [];
$url_params = "";

if (!empty($search_term)) {
    $search_like = '%' . $search_term . '%';
    $search_sql = " AND (p.NOMPRODUCTO LIKE ? OR c.NOMCATEGORIA LIKE ?) ";
    $params_search = [$search_like, $search_like];
    $url_params = "search=" . urlencode($search_term);
}

// LÓGICA DE PAGINACIÓN
$registros_por_pagina = 15;
$pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Contar total de registros
$sql_total = "SELECT COUNT(p.IDPRODUCTO) AS total 
              FROM PRODUCTO p 
              LEFT JOIN CATEGORIA_PRODUCTO c ON p.IDCATEGORIA = c.IDCATEGORIA
              WHERE 1=1" . $search_sql;

$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Estadísticas adicionales
$sql_stats = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN ESTADO = '1' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN STOCK <= 10 THEN 1 ELSE 0 END) as bajo_stock
              FROM PRODUCTO";
$stmt_stats = sqlsrv_query($conn, $sql_stats);
$stats = sqlsrv_fetch_array($stmt_stats, SQLSRV_FETCH_ASSOC);

// Obtener lista de productos
$sql_productos = "SELECT 
                        p.IDPRODUCTO, p.NOMPRODUCTO, p.PRECIO, p.STOCK, p.ESTADO, 
                        p.IMAGEN_URL, p.DESCRIPCION, c.NOMCATEGORIA 
                  FROM PRODUCTO p
                  LEFT JOIN CATEGORIA_PRODUCTO c ON p.IDCATEGORIA = c.IDCATEGORIA
                  WHERE 1=1" . $search_sql . "
                  ORDER BY p.NOMPRODUCTO ASC
                  OFFSET ? ROWS 
                  FETCH NEXT ? ROWS ONLY";

$params_paginacion = array_merge($params_search, [$offset, $registros_por_pagina]);
$stmt_productos = sqlsrv_query($conn, $sql_productos, $params_paginacion);
if ($stmt_productos === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Cargar Categorías
$sql_categorias = "SELECT IDCATEGORIA, NOMCATEGORIA FROM CATEGORIA_PRODUCTO WHERE ESTADO = '1' ORDER BY NOMCATEGORIA";
$stmt_categorias = sqlsrv_query($conn, $sql_categorias);
$categorias = [];
if ($stmt_categorias !== false) {
    while ($row_cat = sqlsrv_fetch_array($stmt_categorias, SQLSRV_FETCH_ASSOC)) {
        $categorias[] = $row_cat;
    }
}
?>

<!-- Header de Página -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="page-title">
                <i class="bi bi-box-seam me-2"></i>
                Gestión de Productos
            </h1>
            <p class="page-description">Administra el inventario completo de productos</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
                <i class="bi bi-plus-circle me-2"></i>Nuevo Producto
            </button>
        </div>
    </div>
</div>

<!-- Mensajes -->
<?php
if (isset($_SESSION['mensaje_producto'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_producto'] .
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_producto']);
}
if (isset($_SESSION['error_producto'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_producto'] .
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_producto']);
}
?>

<!-- Estadísticas -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Productos</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-success-soft">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['activos']; ?></h3>
                <p>Productos Activos</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-warning-soft">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['bajo_stock']; ?></h3>
                <p>Stock Bajo (≤10)</p>
            </div>
        </div>
    </div>
</div>

<!-- Buscador -->
<div class="search-card mb-4">
    <form action="productos.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text"
                name="search"
                class="search-input"
                value="<?php echo htmlspecialchars($search_term); ?>"
                placeholder="Buscar por nombre de producto o categoría...">
            <button type="submit" class="btn-search">
                <i class="bi bi-search me-2"></i>Buscar
            </button>
            <?php if (!empty($search_term)): ?>
                <a href="productos.php" class="btn-clear-search">
                    <i class="bi bi-x-circle"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!empty($search_term)): ?>
        <div class="search-results-info">
            <span class="results-badge">
                <i class="bi bi-funnel me-2"></i>
                Filtrando por: "<?php echo htmlspecialchars($search_term); ?>"
                (<?php echo $total_registros; ?> resultado<?php echo $total_registros != 1 ? 's' : ''; ?>)
            </span>
        </div>
    <?php endif; ?>
</div>

<!-- Lista de Productos -->
<div class="products-table-card">
    <div class="table-header">
        <h5>
            <i class="bi bi-list-ul me-2"></i>
            Lista de Productos
        </h5>
        <span class="pagination-info">
            Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>
        </span>
    </div>

    <div class="table-responsive">
        <table class="table products-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Imagen</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th class="text-end">Precio</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center" style="width: 150px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros == 0) {
                    $mensaje = !empty($search_term)
                        ? "No se encontraron productos con el término '" . htmlspecialchars($search_term) . "'"
                        : "No hay productos registrados";
                    echo "<tr><td colspan='7' class='text-center py-5'>
                            <i class='bi bi-inbox' style='font-size: 3rem; color: #ddd;'></i>
                            <p class='mt-3 text-muted'>$mensaje</p>
                          </td></tr>";
                }

                while ($row = sqlsrv_fetch_array($stmt_productos, SQLSRV_FETCH_ASSOC)):
                    $imagen_url = "img/" . htmlspecialchars($row['IMAGEN_URL']);
                    if (empty($row['IMAGEN_URL']) || !file_exists($imagen_url)) {
                        $imagen_url = "https://www.lapurita.com/cdn/shop/collections/LAPURITA4229.jpg?v=1724384944&width=320";
                    } else {
                        $imagen_url .= "?v=" . time();
                    }

                    $stock_class = $row['STOCK'] <= 10 ? 'stock-low' : 'stock-ok';
                    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
                ?>
                    <tr>
                        <td>
                            <div class="product-image-cell">
                                <img src="<?php echo $imagen_url; ?>"
                                    alt="<?php echo htmlspecialchars($row['NOMPRODUCTO']); ?>">
                            </div>
                        </td>
                        <td>
                            <div class="product-name-cell">
                                <strong><?php echo htmlspecialchars($row['NOMPRODUCTO']); ?></strong>
                                <?php if (!empty($row['DESCRIPCION'])): ?>
                                    <small class="d-block text-muted">
                                        <?php echo htmlspecialchars(substr($row['DESCRIPCION'], 0, 50)) . '...'; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="category-badge">
                                <i class="bi bi-tag me-1"></i>
                                <?php echo htmlspecialchars($row['NOMCATEGORIA']); ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="price-tag">S/ <?php echo number_format($row['PRECIO'], 2); ?></span>
                        </td>
                        <td class="text-center">
                            <span class="stock-badge <?php echo $stock_class; ?>">
                                <?php echo $row['STOCK']; ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if ($row['ESTADO'] == '1'): ?>
                                <span class="status-badge status-active">
                                    <i class="bi bi-check-circle"></i> Activo
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">
                                    <i class="bi bi-x-circle"></i> Inactivo
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="editar_producto.php?id=<?php echo $row['IDPRODUCTO']; ?>"
                                    class="btn-action btn-edit"
                                    title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($row['ESTADO'] == '1'): ?>
                                    <a href="backend/gestionar_producto.php?accion=eliminar&id=<?php echo $row['IDPRODUCTO']; ?>&pagina=<?php echo $pagina_actual . $search_url_param; ?>"
                                        class="btn-action btn-delete"
                                        title="Desactivar"
                                        onclick="return confirm('¿Desactivar &quot;<?php echo htmlspecialchars($row['NOMPRODUCTO']); ?>&quot;?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="backend/gestionar_producto.php?accion=activar&id=<?php echo $row['IDPRODUCTO']; ?>&pagina=<?php echo $pagina_actual . $search_url_param; ?>"
                                        class="btn-action btn-activate"
                                        title="Activar"
                                        onclick="return confirm('¿Activar &quot; <?php echo htmlspecialchars($row['NOMPRODUCTO']); ?>&quot;?');">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
        <div class="table-footer">
            <?php
            $url_base = "productos.php" . (!empty($url_params) ? "?" . $url_params : "");
            include 'includes/paginador.php';
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Nuevo Producto -->
<div class="modal fade" id="modalNuevoProducto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Añadir Nuevo Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="backend/gestionar_producto.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">
                                <i class="bi bi-box me-1"></i>Nombre del Producto
                            </label>
                            <input type="text" name="nomproducto" class="form-control" required
                                placeholder="Ej: Chocotejas de Chocolate">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag me-1"></i>Categoría
                            </label>
                            <select name="idcategoria" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['IDCATEGORIA']; ?>">
                                        <?php echo htmlspecialchars($cat['NOMCATEGORIA']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-cash me-1"></i>Precio (S/)
                            </label>
                            <input type="number" name="precio" class="form-control"
                                step="0.01" min="0" required placeholder="0.00">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-boxes me-1"></i>Stock Inicial
                            </label>
                            <input type="number" name="stock" class="form-control"
                                min="0" required placeholder="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-text-paragraph me-1"></i>Descripción
                        </label>
                        <textarea name="descripcion" class="form-control" rows="3"
                            placeholder="Descripción detallada del producto..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-image me-1"></i>Imagen del Producto
                        </label>
                        <input type="file" name="imagen" class="form-control" accept="image/jpeg, image/png">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Formatos: JPG, PNG (Opcional)
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="css/productos.css">

<?php include 'includes/footer.php'; ?>