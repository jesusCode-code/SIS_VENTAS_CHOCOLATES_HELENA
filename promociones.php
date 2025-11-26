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
    $search_sql = " AND (pr.NOMPROMOCION LIKE ? OR p.NOMPRODUCTO LIKE ?) ";
    $params_search = [$search_like, $search_like];
    $url_params = "search=" . urlencode($search_term);
}

// ==================================================
// BLOQUE DE PAGINACIÓN
// ==================================================
$registros_por_pagina = 20; 
$pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// 4. Contar el TOTAL de registros (CON FILTRO)
$sql_total = "SELECT COUNT(pr.IDPROMOCION) AS total 
              FROM PROMOCION pr
              JOIN PRODUCTO p ON pr.IDPRODUCTO = p.IDPRODUCTO
              WHERE 1=1" . $search_sql;
              
$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// ==================================================
// BLOQUE DE ESTADÍSTICAS (KPIs)
// ==================================================
$stats = [];
$stats['total_promociones'] = $total_registros;

$sql_activas = "SELECT COUNT(*) as total FROM PROMOCION WHERE ESTADO = '1' AND GETDATE() BETWEEN FECHA_INICIO AND FECHA_FIN";
$stmt_activas = sqlsrv_query($conn, $sql_activas);
$stats['total_activas'] = sqlsrv_fetch_array($stmt_activas, SQLSRV_FETCH_ASSOC)['total'];

$sql_expiradas = "SELECT COUNT(*) as total FROM PROMOCION WHERE FECHA_FIN < GETDATE()";
$stmt_expiradas = sqlsrv_query($conn, $sql_expiradas);
$stats['total_expiradas'] = sqlsrv_fetch_array($stmt_expiradas, SQLSRV_FETCH_ASSOC)['total'];

$sql_top_prod = "SELECT TOP 1 p.NOMPRODUCTO, COUNT(pr.IDPROMOCION) as total_prod
                 FROM PROMOCION pr
                 JOIN PRODUCTO p ON pr.IDPRODUCTO = p.IDPRODUCTO
                 GROUP BY p.NOMPRODUCTO
                 ORDER BY total_prod DESC";
$stmt_top_prod = sqlsrv_query($conn, $sql_top_prod);
$top_prod = sqlsrv_fetch_array($stmt_top_prod, SQLSRV_FETCH_ASSOC);
$stats['top_producto'] = $top_prod ? $top_prod['NOMPRODUCTO'] : 'N/A';

// ==================================================
// Lógica para OBTENER LISTA
// ==================================================
$sql_promos = "
    SELECT 
        pr.IDPROMOCION, pr.NOMPROMOCION, pr.PORCENTAJE_DESC, 
        pr.FECHA_INICIO, pr.FECHA_FIN, pr.ESTADO, pr.DESCRIPCION, pr.IDPRODUCTO,
        p.NOMPRODUCTO
    FROM PROMOCION pr
    INNER JOIN PRODUCTO p ON pr.IDPRODUCTO = p.IDPRODUCTO
    WHERE 1=1" . $search_sql . "
    ORDER BY pr.FECHA_FIN DESC
    OFFSET ? ROWS 
    FETCH NEXT ? ROWS ONLY";
                  
$params_paginacion = array_merge($params_search, [$offset, $registros_por_pagina]);
$stmt_promos = sqlsrv_query($conn, $sql_promos, $params_paginacion);
if ($stmt_promos === false) { die(print_r(sqlsrv_errors(), true)); }

// Lógica para el formulario (Cargar Productos) - Se usa para Crear y Editar
$sql_productos = "SELECT IDPRODUCTO, NOMPRODUCTO, PRECIO FROM PRODUCTO WHERE ESTADO = '1' ORDER BY NOMPRODUCTO";
$stmt_productos = sqlsrv_query($conn, $sql_productos);
$productos = [];
while ($row_prod = sqlsrv_fetch_array($stmt_productos, SQLSRV_FETCH_ASSOC)) {
    $productos[] = $row_prod;
}
?>

<!-- Header de Página -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="page-title"><i class="bi bi-gift me-2"></i> Gestión de Promociones</h1>
            <p class="page-description">Aplica descuentos porcentuales a productos específicos.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearPromocion">
                <i class="bi bi-plus-circle me-2"></i>Nueva Promoción
            </button>
        </div>
    </div>
</div>

<!-- Mensajes -->
<?php
if (isset($_SESSION['mensaje_promo'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_promo'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_promo']);
}
if (isset($_SESSION['error_promo'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_promo'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_promo']);
}
?>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft"><i class="bi bi-gift"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_promociones']; ?></h3>
                <p>Total (Filtradas)</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-success-soft"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_activas']; ?></h3>
                <p>Activas</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-danger-soft"><i class="bi bi-calendar-x"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_expiradas']; ?></h3>
                <p>Expiradas</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-info-soft"><i class="bi bi-star"></i></div>
            <div class="stat-content-mini">
                <h3 style="font-size: 1.5rem;"><?php echo htmlspecialchars($stats['top_producto']); ?></h3>
                <p>Producto Top</p>
            </div>
        </div>
    </div>
</div>

<!-- Buscador -->
<div class="search-card mb-4">
    <form action="promociones.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input" 
                   value="<?php echo htmlspecialchars($search_term); ?>"
                   placeholder="Buscar por nombre de promoción o producto...">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="promociones.php" class="btn-clear-search" title="Limpiar búsqueda"><i class="bi bi-x-circle"></i></a>
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

<!-- Tabla -->
<div class="table-card">
    <div class="table-header">
        <h5><i class="bi bi-list-ul me-2"></i> Listado de Promociones</h5>
        <span class="pagination-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table data-table mb-0">
            <thead>
                <tr>
                    <th>Promoción</th>
                    <th>Producto</th>
                    <th>Descuento</th>
                    <th>Periodo</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros == 0) {
                    $mensaje_vacio = !empty($search_term) ? "No se encontraron promociones con el término '" . htmlspecialchars($search_term) . "'." : "No hay promociones registradas.";
                    echo "<tr><td colspan='6' class='text-center p-4 text-muted'>$mensaje_vacio</td></tr>";
                }
                while ($row = sqlsrv_fetch_array($stmt_promos, SQLSRV_FETCH_ASSOC)) {
                    $hoy = new DateTime();
                    $fecha_fin = $row['FECHA_FIN'];
                    if ($row['ESTADO'] == '0') {
                        $estado = '<span class="status-badge status-inactive"><i class="bi bi-x-circle"></i> Inactivo</span>';
                    } elseif ($fecha_fin < $hoy) {
                        $estado = '<span class="status-badge" style="background-color: #f8f9fa; color: #6c757d; border: 2px solid #e0e0e0;"><i class="bi bi-calendar-x"></i> Expirado</span>';
                    } else {
                        $estado = '<span class="status-badge status-active"><i class="bi bi-check-circle"></i> Activo</span>';
                    }

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['NOMPROMOCION']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['NOMPRODUCTO']) . "</td>";
                    echo "<td class='fw-bold'>" . number_format($row['PORCENTAJE_DESC'], 2) . "%</td>";
                    echo "<td>" . $row['FECHA_INICIO']->format('d/m/Y') . " - " . $fecha_fin->format('d/m/Y') . "</td>";
                    echo "<td>" . $estado . "</td>";
                    
                    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
                    echo "<td class='action-buttons'>";
                    
                    // BOTÓN EDITAR (ABRE EL MODAL)
                    echo "<button type='button' 
                                 class='btn-action btn-edit' 
                                 data-bs-toggle='modal' 
                                 data-bs-target='#modalEditarPromocion'
                                 data-id='" . $row['IDPROMOCION'] . "'
                                 data-nombre='" . htmlspecialchars($row['NOMPROMOCION'], ENT_QUOTES) . "'
                                 data-idproducto='" . $row['IDPRODUCTO'] . "'
                                 data-descuento='" . number_format($row['PORCENTAJE_DESC'], 2, '.', '') . "'
                                 data-inicio='" . $row['FECHA_INICIO']->format('Y-m-d') . "'
                                 data-fin='" . $fecha_fin->format('Y-m-d') . "'
                                 data-descripcion='" . htmlspecialchars($row['DESCRIPCION'] ?? '', ENT_QUOTES) . "'
                                 data-estado='" . $row['ESTADO'] . "'
                                 title='Editar'>
                              <i class='bi bi-pencil-square'></i>
                           </button>";
                           
                    if ($row['ESTADO'] == '1') {
                        $onclick_js = "return confirm('¿Seguro que deseas DESACTIVAR esta promoción?');";
                        echo "<a href='backend/gestionar_promocion.php?accion=desactivar&id=" . $row['IDPROMOCION'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-delete' onclick=\"" . $onclick_js . "\" title='Desactivar'><i class='bi bi-trash'></i></a>";
                    } else {
                        $onclick_js = "return confirm('¿Seguro que deseas ACTIVAR esta promoción?');";
                        echo "<a href='backend/gestionar_promocion.php?accion=activar&id=" . $row['IDPROMOCION'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
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
        $url_base = "promociones.php" . (!empty($url_params) ? "?" . $url_params : "");
        include 'includes/paginador.php'; 
        ?>
    </div>
</div>

<!-- MODAL: CREAR PROMOCIÓN -->
<div class="modal fade" id="modalCrearPromocion" tabindex="-1" aria-labelledby="modalCrearLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_promocion.php" method="POST">
                <input type="hidden" name="accion" value="crear">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearLabel">
                        <i class="bi bi-plus-circle me-2"></i>Crear Nueva Promoción
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nompromocion" class="form-label"><i class="bi bi-tag me-2"></i>Nombre Promoción:</label>
                            <input type="text" id="nompromocion" name="nompromocion" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="idproducto" class="form-label"><i class="bi bi-box-seam me-2"></i>Producto Afectado:</label>
                            <select id="idproducto" name="idproducto" class="form-select" required>
                                <option value="">-- Seleccione un producto --</option>
                                <?php foreach ($productos as $p): ?>
                                <option value="<?php echo $p['IDPRODUCTO']; ?>">
                                    <?php echo htmlspecialchars($p['NOMPRODUCTO']) . " (S/ " . number_format($p['PRECIO'], 2) . ")"; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="porcentaje_desc" class="form-label"><i class="bi bi-percent me-2"></i>Descuento (%):</label>
                            <input type="number" id="porcentaje_desc" name="porcentaje_desc" class="form-control" step="0.01" min="1" max="100" required placeholder="Ej: 10">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="fecha_inicio" class="form-label"><i class="bi bi-calendar-play me-2"></i>Fecha Inicio:</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="fecha_fin" class="form-label"><i class="bi bi-calendar-check me-2"></i>Fecha Fin:</label>
                            <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label"><i class="bi bi-card-text me-2"></i>Descripción:</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Guardar Promoción</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: EDITAR PROMOCIÓN (¡NUEVO!) -->
<div class="modal fade" id="modalEditarPromocion" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_promocion.php" method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="idpromocion" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">
                        <i class="bi bi-pencil-square me-2"></i>Editar Promoción
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_nompromocion" class="form-label"><i class="bi bi-tag me-2"></i>Nombre Promoción:</label>
                            <input type="text" id="edit_nompromocion" name="nompromocion" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_idproducto" class="form-label"><i class="bi bi-box-seam me-2"></i>Producto Afectado:</label>
                            <select id="edit_idproducto" name="idproducto" class="form-select" required>
                                <?php foreach ($productos as $p): ?>
                                <option value="<?php echo $p['IDPRODUCTO']; ?>">
                                    <?php echo htmlspecialchars($p['NOMPRODUCTO']) . " (S/ " . number_format($p['PRECIO'], 2) . ")"; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_porcentaje" class="form-label"><i class="bi bi-percent me-2"></i>Descuento (%):</label>
                            <input type="number" id="edit_porcentaje" name="porcentaje_desc" class="form-control" step="0.01" min="1" max="100" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_fecha_inicio" class="form-label"><i class="bi bi-calendar-play me-2"></i>Fecha Inicio:</label>
                            <input type="date" id="edit_fecha_inicio" name="fecha_inicio" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_fecha_fin" class="form-label"><i class="bi bi-calendar-check me-2"></i>Fecha Fin:</label>
                            <input type="date" id="edit_fecha_fin" name="fecha_fin" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_descripcion" class="form-label"><i class="bi bi-card-text me-2"></i>Descripción:</label>
                        <textarea id="edit_descripcion" name="descripcion" class="form-control" rows="2"></textarea>
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

<!-- SCRIPT JS PARA PASAR DATOS AL MODAL -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEditar = document.getElementById('modalEditarPromocion');
    
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        const idproducto = button.getAttribute('data-idproducto');
        const descuento = button.getAttribute('data-descuento');
        const inicio = button.getAttribute('data-inicio');
        const fin = button.getAttribute('data-fin');
        const descripcion = button.getAttribute('data-descripcion');
        const estado = button.getAttribute('data-estado');
        
        modalEditar.querySelector('#edit_id').value = id;
        modalEditar.querySelector('#edit_nompromocion').value = nombre;
        modalEditar.querySelector('#edit_idproducto').value = idproducto;
        modalEditar.querySelector('#edit_porcentaje').value = descuento;
        modalEditar.querySelector('#edit_fecha_inicio').value = inicio;
        modalEditar.querySelector('#edit_fecha_fin').value = fin;
        modalEditar.querySelector('#edit_descripcion').value = descripcion;
        modalEditar.querySelector('#edit_estado').value = estado;
    });
});
</script>

<?php include 'includes/footer.php'; ?>