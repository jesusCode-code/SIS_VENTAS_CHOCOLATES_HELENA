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
    // Buscamos por Nombre de Cliente, Razón Social, Nombre de Producto o Comentario
    $search_sql = " AND (
                    p.NOMPRODUCTO LIKE ? 
                    OR (per.NOMBRES + ' ' + per.APEPATERNO) LIKE ? 
                    OR emp.RAZON_SOCIAL LIKE ?
                    OR v.COMENTARIO LIKE ?
                  ) ";
    $params_search = [$search_like, $search_like, $search_like, $search_like];
    $url_params = "search=" . urlencode($search_term);
}

// ==================================================
// BLOQUE DE PAGINACIÓN
// ==================================================
$registros_por_pagina = 20; 
$pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// 4. Contar el TOTAL de registros (CON FILTRO)
$sql_total = "SELECT COUNT(v.IDVALORACION) AS total 
              FROM VALORACION v
              JOIN PRODUCTO p ON v.IDPRODUCTO = p.IDPRODUCTO
              JOIN CLIENTE c ON v.IDCLIENTE = c.IDCLIENTE
              LEFT JOIN PERSONA per ON c.IDPERSONA = per.IDPERSONA
              LEFT JOIN EMPRESA emp ON c.IDEMPRESA = emp.IDEMPRESA
              WHERE 1=1" . $search_sql; 
              
$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// ==================================================
// BLOQUE DE ESTADÍSTICAS (KPIs)
// ==================================================
$stats = [];
// (Usamos el total de la paginación para no consultar dos veces)
$stats['total_valoraciones'] = $total_registros;

// Total Pendientes (Estado 0)
$sql_pendientes = "SELECT COUNT(*) as total FROM VALORACION WHERE ESTADO = '0'";
$stmt_pendientes = sqlsrv_query($conn, $sql_pendientes);
$stats['total_pendientes'] = sqlsrv_fetch_array($stmt_pendientes, SQLSRV_FETCH_ASSOC)['total'];

// Total Aprobadas (Estado 1)
$sql_aprobadas = "SELECT COUNT(*) as total FROM VALORACION WHERE ESTADO = '1'";
$stmt_aprobadas = sqlsrv_query($conn, $sql_aprobadas);
$stats['total_aprobadas'] = sqlsrv_fetch_array($stmt_aprobadas, SQLSRV_FETCH_ASSOC)['total'];

// Puntuación Promedio
$sql_promedio = "SELECT AVG(CAST(PUNTUACION AS DECIMAL(10,2))) as promedio FROM VALORACION WHERE ESTADO = '1'";
$stmt_promedio = sqlsrv_query($conn, $sql_promedio);
$promedio_raw = sqlsrv_fetch_array($stmt_promedio, SQLSRV_FETCH_ASSOC)['promedio'];
$stats['promedio_puntos'] = $promedio_raw ? number_format($promedio_raw, 1) : 'N/A';

// ==================================================
// Lógica para OBTENER LISTA (CON FILTRO Y PAGINACIÓN)
// ==================================================
$sql = "SELECT 
            v.IDVALORACION, v.PUNTUACION, v.COMENTARIO, v.FECHA_VALORACION, v.ESTADO,
            p.NOMPRODUCTO,
            COALESCE(per.NOMBRES + ' ' + per.APEPATERNO, emp.RAZON_SOCIAL) AS NombreCliente
        FROM VALORACION v
        JOIN PRODUCTO p ON v.IDPRODUCTO = p.IDPRODUCTO
        JOIN CLIENTE c ON v.IDCLIENTE = c.IDCLIENTE
        LEFT JOIN PERSONA per ON c.IDPERSONA = per.IDPERSONA
        LEFT JOIN EMPRESA emp ON c.IDEMPRESA = emp.IDEMPRESA
        WHERE 1=1 " . $search_sql . " -- <-- FILTRO APLICADO
        ORDER BY v.FECHA_VALORACION DESC
        OFFSET ? ROWS 
        FETCH NEXT ? ROWS ONLY";
                  
$params_paginacion = array_merge($params_search, [$offset, $registros_por_pagina]);
$stmt = sqlsrv_query($conn, $sql, $params_paginacion);
if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="page-title"><i class="bi bi-star-half me-2"></i> Gestión de Valoraciones</h1>
            <p class="page-description">Administra las reseñas y puntuaciones de los clientes.</p>
        </div>
    </div>
</div>

<?php
if (isset($_SESSION['mensaje_val'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_val'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_val']);
}
if (isset($_SESSION['error_val'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_val'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_val']);
}
?>

<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft"><i class="bi bi-chat-square-text"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_valoraciones']; ?></h3>
                <p>Total (Filtradas)</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-success-soft"><i class="bi bi-check-circle"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_aprobadas']; ?></h3>
                <p>Aprobadas</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-warning-soft"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_pendientes']; ?></h3>
                <p>Pendientes</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-info-soft"><i class="bi bi-star-fill"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['promedio_puntos']; ?></h3>
                <p>Promedio Puntos</p>
            </div>
        </div>
    </div>
</div>

<div class="search-card mb-4">
    <form action="valoraciones.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input" 
                   value="<?php echo htmlspecialchars($search_term); ?>"
                   placeholder="Buscar por cliente, producto o comentario...">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="valoraciones.php" class="btn-clear-search" title="Limpiar búsqueda"><i class="bi bi-x-circle"></i></a>
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
        <h5><i class="bi bi-list-ul me-2"></i> Listado de Valoraciones</h5>
        <span class="pagination-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table data-table mb-0">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Producto</th>
                    <th>Puntuación</th>
                    <th>Comentario</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros == 0) {
                    $mensaje_vacio = !empty($search_term) ? "No se encontraron valoraciones con el término '" . htmlspecialchars($search_term) . "'." : "No hay valoraciones registradas.";
                    echo "<tr><td colspan='7' class='text-center p-4 text-muted'>$mensaje_vacio</td></tr>";
                }
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    
                    // Estado
                    $estado = ($row['ESTADO'] == '1') ? '<span class="status-badge status-active"><i class="bi bi-check-circle"></i> Aprobado</span>' : '<span class="status-badge status-inactive" style="background-color: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); border: 2px solid var(--bs-warning-border-subtle);"><i class="bi bi-hourglass-split"></i> Pendiente</span>';
                    
                    // Puntuación
                    $puntuacion_estrellas = str_repeat('⭐', $row['PUNTUACION']);

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['NombreCliente'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['NOMPRODUCTO']) . "</td>";
                    echo "<td class='fw-bold' style='color: #f5b301;'>" . $puntuacion_estrellas . "</td>";
                    echo "<td style='max-width: 250px;'>" . htmlspecialchars($row['COMENTARIO']) . "</td>";
                    echo "<td>" . $row['FECHA_VALORACION']->format('Y-m-d') . "</td>";
                    echo "<td>" . $estado . "</td>";
                    
                    // Botones de Acción
                    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
                    echo "<td class='action-buttons'>";
                    if ($row['ESTADO'] == '0') {
                        $onclick_js = "return confirm('¿Seguro que deseas APROBAR este comentario?');";
                        echo "<a href='backend/gestionar_valoracion.php?accion=aprobar&id=" . $row['IDVALORACION'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-activate' onclick=\"" . $onclick_js . "\" title='Aprobar'><i class='bi bi-check-circle'></i></a>";
                    } else {
                        $onclick_js = "return confirm('¿Seguro que deseas OCULTAR este comentario? (Marcar como pendiente)');";
                        echo "<a href='backend/gestionar_valoracion.php?accion=ocultar&id=" . $row['IDVALORACION'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-delete' onclick=\"" . $onclick_js . "\" title='Ocultar'><i class='bi bi-trash'></i></a>";
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
        $url_base = "valoraciones.php" . (!empty($url_params) ? "?" . $url_params : "");
        include 'includes/paginador.php'; 
        ?>
    </div>
</div>

<link rel="stylesheet" href="css/listados_generales.css?v=1.1">

<?php include 'includes/footer.php'; ?>