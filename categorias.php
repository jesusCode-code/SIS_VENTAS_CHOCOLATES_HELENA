<?php
// 1. Seguridad, Header y Conexión
include 'includes/seguridad.php'; 
include 'includes/header.php';
include 'includes/conexion.php';

// ==================================================
// BLOQUE DE BÚSQUEDA
// ==================================================
$search_term = $_GET['search'] ?? ''; 
$search_sql = ""; 
$params_search = []; 
$url_params = ""; 

if (!empty($search_term)) {
    $search_like = '%' . $search_term . '%';
    $search_sql = " AND (NOMCATEGORIA LIKE ?) ";
    $params_search = [$search_like];
    $url_params = "search=" . urlencode($search_term);
}

// ==================================================
// BLOQUE DE PAGINACIÓN
// ==================================================
$registros_por_pagina = 20; 
$pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// 4. Contar el TOTAL de registros (CON FILTRO)
$sql_total = "SELECT COUNT(IDCATEGORIA) AS total FROM CATEGORIA_PRODUCTO WHERE 1=1" . $search_sql;
$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// ==================================================
// BLOQUE DE ESTADÍSTICAS (KPIs)
// ==================================================
$stats = [];
$stats['total_categorias'] = $total_registros; // Total filtrado

// Total Productos (para saber cuántos productos hay en total)
$sql_prod = "SELECT COUNT(*) as total FROM PRODUCTO WHERE ESTADO = '1'";
$stmt_prod = sqlsrv_query($conn, $sql_prod);
$stats['total_productos'] = sqlsrv_fetch_array($stmt_prod, SQLSRV_FETCH_ASSOC)['total'];

// Categoría con más productos
$sql_top_cat = "SELECT TOP 1 c.NOMCATEGORIA, COUNT(p.IDPRODUCTO) as total_prod
                FROM CATEGORIA_PRODUCTO c
                JOIN PRODUCTO p ON c.IDCATEGORIA = p.IDCATEGORIA
                WHERE p.ESTADO = '1'
                GROUP BY c.NOMCATEGORIA
                ORDER BY total_prod DESC";
$stmt_top_cat = sqlsrv_query($conn, $sql_top_cat);
$top_cat = sqlsrv_fetch_array($stmt_top_cat, SQLSRV_FETCH_ASSOC);
$stats['top_categoria'] = $top_cat ? $top_cat['NOMCATEGORIA'] : 'N/A';
$stats['top_categoria_total'] = $top_cat ? $top_cat['total_prod'] : 0;


// ==================================================
// Lógica para OBTENER LISTA
// ==================================================
$sql = "SELECT IDCATEGORIA, NOMCATEGORIA, ESTADO 
        FROM CATEGORIA_PRODUCTO 
        WHERE 1=1" . $search_sql . "
        ORDER BY NOMCATEGORIA ASC
        OFFSET ? ROWS 
        FETCH NEXT ? ROWS ONLY";
                  
$params_paginacion = array_merge($params_search, [$offset, $registros_por_pagina]);
$stmt = sqlsrv_query($conn, $sql, $params_paginacion);
if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="page-title"><i class="bi bi-tags me-2"></i> Gestión de Categorías</h1>
            <p class="page-description">Administra las categorías de tus productos.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearCategoria">
                <i class="bi bi-plus-circle me-2"></i>Nueva Categoría
            </button>
        </div>
    </div>
</div>

<?php
if (isset($_SESSION['mensaje_categoria'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_categoria'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_categoria']);
}
if (isset($_SESSION['error_categoria'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_categoria'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_categoria']);
}
?>

<div class="row g-3 mb-4">
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft"><i class="bi bi-tags"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_categorias']; ?></h3>
                <p>Categorías (Filtradas)</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-success-soft"><i class="bi bi-box-seam"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_productos']; ?></h3>
                <p>Productos Totales</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-info-soft"><i class="bi bi-star"></i></div>
            <div class="stat-content-mini">
                <h3 style="font-size: 1.5rem;"><?php echo htmlspecialchars($stats['top_categoria']); ?></h3>
                <p>Top Cat. (<?php echo $stats['top_categoria_total']; ?> prods)</p>
            </div>
        </div>
    </div>
</div>

<div class="search-card mb-4">
    <form action="categorias.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input" 
                   value="<?php echo htmlspecialchars($search_term); ?>"
                   placeholder="Buscar por nombre de categoría...">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="categorias.php" class="btn-clear-search" title="Limpiar búsqueda"><i class="bi bi-x-circle"></i></a>
            <?php endif; ?>
        </div>
    </form>
    <?php if (!empty($search_term)): ?>
    <div class="search-results-info">
        <span class="results-badge">
            <i class="bi bi-funnel me-2"></i>
            Filtrando: "<?php echo htmlspecialchars($search_term); ?>" (<?php echo $total_registros; ?> resultados)
        </span>
    </div>
    <?php endif; ?>
</div>

<div class="table-card">
    <div class="table-header">
        <h5><i class="bi bi-list-ul me-2"></i> Listado de Categorías</h5>
        <span class="pagination-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table data-table mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros == 0) {
                    $mensaje_vacio = !empty($search_term) ? "No se encontraron categorías con el término '" . htmlspecialchars($search_term) . "'." : "No hay categorías registradas.";
                    echo "<tr><td colspan='4' class='text-center p-4 text-muted'>$mensaje_vacio</td></tr>";
                }
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $estado = ($row['ESTADO'] == '1') ? '<span class="status-badge status-active"><i class="bi bi-check-circle"></i> Activo</span>' : '<span class="status-badge status-inactive"><i class="bi bi-x-circle"></i> Inactivo</span>';

                    echo "<tr>";
                    echo "<td>" . $row['IDCATEGORIA'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['NOMCATEGORIA']) . "</td>";
                    echo "<td>" . $estado . "</td>";
                    
                    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
                    echo "<td class='action-buttons'>";
                    
                    // ===================================
                    // BOTÓN EDITAR (ABRE EL MODAL)
                    // ===================================
                    echo "<button type='button' 
                                 class='btn-action btn-edit' 
                                 data-bs-toggle='modal' 
                                 data-bs-target='#modalEditarCategoria'
                                 data-id='" . $row['IDCATEGORIA'] . "'
                                 data-nombre='" . htmlspecialchars($row['NOMCATEGORIA'], ENT_QUOTES) . "'
                                 data-estado='" . $row['ESTADO'] . "'
                                 title='Editar'>
                              <i class='bi bi-pencil-square'></i>
                           </button>";

                    if ($row['ESTADO'] == '1') {
                        $onclick_js = "return confirm('¿Seguro que deseas DESACTIVAR " . htmlspecialchars($row['NOMCATEGORIA']) . "?');";
                        echo "<a href='backend/gestionar_categoria.php?accion=desactivar&id=" . $row['IDCATEGORIA'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-delete' onclick=\"" . $onclick_js . "\" title='Desactivar'><i class='bi bi-trash'></i></a>";
                    } else {
                        $onclick_js = "return confirm('¿Seguro que deseas ACTIVAR " . htmlspecialchars($row['NOMCATEGORIA']) . "?');";
                        echo "<a href='backend/gestionar_categoria.php?accion=activar&id=" . $row['IDCATEGORIA'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-activate' onclick=\"" . $onclick_js . "\" title='Activar'><i class='bi bi-check-circle'></i></a>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div class="table-footer">
        <?php 
        $url_base = "categorias.php?" . $url_params; 
        include 'includes/paginador.php'; 
        ?>
    </div>
</div>

<div class="modal fade" id="modalCrearCategoria" tabindex="-1" aria-labelledby="modalCrearLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_categoria.php" method="POST">
                <input type="hidden" name="accion" value="crear">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearLabel">
                        <i class="bi bi-plus-circle me-2"></i>Añadir Nueva Categoría
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nomcategoria" class="form-label"><i class="bi bi-tag me-2"></i>Nombre de la Categoría:</label>
                        <input type="text" id="nomcategoria" name="nomcategoria" class="form-control" required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Guardar Categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_categoria.php" method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="idcategoria" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">
                        <i class="bi bi-pencil-square me-2"></i>Editar Categoría
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nomcategoria" class="form-label"><i class="bi bi-tag me-2"></i>Nombre de la Categoría:</label>
                        <input type="text" id="edit_nomcategoria" name="nomcategoria" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_estado" class="form-label"><i class="bi bi-toggle-on me-2"></i>Estado:</label>
                        <select id="edit_estado" name="estado" class="form-select" required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="css/listados_generales.css?v=1.1">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEditar = document.getElementById('modalEditarCategoria');
    
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        const estado = button.getAttribute('data-estado');
        
        const modalIdInput = modalEditar.querySelector('#edit_id');
        const modalNombreInput = modalEditar.querySelector('#edit_nomcategoria');
        const modalEstadoSelect = modalEditar.querySelector('#edit_estado');
        
        modalIdInput.value = id;
        modalNombreInput.value = nombre;
        modalEstadoSelect.value = estado;
    });
});
</script>

<?php include 'includes/footer.php'; ?>