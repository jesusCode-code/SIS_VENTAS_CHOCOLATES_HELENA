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
    // Buscamos por ID Venta, Nombre Cliente (Persona/Empresa)
    $search_sql = " AND (
                    CAST(v.IDVENTA AS VARCHAR(20)) LIKE ? 
                    OR (p.NOMBRES + ' ' + p.APEPATERNO) LIKE ?
                    OR e.RAZON_SOCIAL LIKE ?
                  ) ";
    $params_search = [$search_like, $search_like, $search_like];
    $url_params = "search=" . urlencode($search_term);
}

// ==================================================
// BLOQUE DE PAGINACIÓN
// ==================================================
$registros_por_pagina = 20; 
$pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// 4. Contar el TOTAL de registros (Ventas SIN comprobante)
$sql_base_where = " WHERE v.ESTADO = '1' AND b.IDBOLETA IS NULL AND f.IDFACTURA IS NULL ";

$sql_total = "SELECT COUNT(v.IDVENTA) AS total
              FROM VENTA v
              INNER JOIN CLIENTE c ON v.IDCLIENTE = c.IDCLIENTE
              LEFT JOIN PERSONA p ON c.IDPERSONA = p.IDPERSONA
              LEFT JOIN EMPRESA e ON c.IDEMPRESA = e.IDEMPRESA
              LEFT JOIN BOLETA b ON v.IDVENTA = b.IDVENTA
              LEFT JOIN FACTURA f ON v.IDVENTA = f.IDVENTA" . 
              $sql_base_where . $search_sql;

$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// ==================================================
// ¡NUEVO! BLOQUE DE ESTADÍSTICAS (KPIs)
// ==================================================
$stats = [];
$stats['total_pendientes'] = $total_registros; // Total filtrado

// Total Boletas Pendientes (Clientes tipo Persona)
$sql_bol_pend = "SELECT COUNT(*) as total 
                 FROM VENTA v 
                 JOIN CLIENTE c ON v.IDCLIENTE = c.IDCLIENTE
                 LEFT JOIN BOLETA b ON v.IDVENTA = b.IDVENTA
                 WHERE v.ESTADO='1' AND c.IDPERSONA IS NOT NULL AND b.IDBOLETA IS NULL";
$stmt_bol = sqlsrv_query($conn, $sql_bol_pend);
$stats['boletas_pendientes'] = sqlsrv_fetch_array($stmt_bol, SQLSRV_FETCH_ASSOC)['total'];

// Total Facturas Pendientes (Clientes tipo Empresa)
$sql_fac_pend = "SELECT COUNT(*) as total 
                 FROM VENTA v 
                 JOIN CLIENTE c ON v.IDCLIENTE = c.IDCLIENTE
                 LEFT JOIN FACTURA f ON v.IDVENTA = f.IDVENTA
                 WHERE v.ESTADO='1' AND c.IDEMPRESA IS NOT NULL AND f.IDFACTURA IS NULL";
$stmt_fac = sqlsrv_query($conn, $sql_fac_pend);
$stats['facturas_pendientes'] = sqlsrv_fetch_array($stmt_fac, SQLSRV_FETCH_ASSOC)['total'];

// Total Emitidos Hoy (Contexto)
$sql_hoy = "SELECT COUNT(*) as total 
            FROM VENTA v 
            LEFT JOIN BOLETA b ON v.IDVENTA = b.IDVENTA
            LEFT JOIN FACTURA f ON v.IDVENTA = f.IDVENTA
            WHERE (b.FECHA_EMISION >= CAST(GETDATE() AS DATE) OR f.FECHA_EMISION >= CAST(GETDATE() AS DATE))";
$stmt_hoy = sqlsrv_query($conn, $sql_hoy);
$stats['emitidos_hoy'] = sqlsrv_fetch_array($stmt_hoy, SQLSRV_FETCH_ASSOC)['total'];

// ==================================================
// Lógica para OBTENER LISTA con paginación
// ==================================================
$sql_pendientes = "
    SELECT 
        v.IDVENTA, v.FECHAVENTA, v.TOTAL,
        c.IDPERSONA, c.IDEMPRESA,
        COALESCE(p.NOMBRES + ' ' + p.APEPATERNO, e.RAZON_SOCIAL) AS NombreCliente,
        u.LOGEO AS Vendedor
    FROM VENTA v
    INNER JOIN CLIENTE c ON v.IDCLIENTE = c.IDCLIENTE
    INNER JOIN USUARIO u ON v.IDUSUARIO = u.IDUSUARIO
    LEFT JOIN PERSONA p ON c.IDPERSONA = p.IDPERSONA
    LEFT JOIN EMPRESA e ON c.IDEMPRESA = e.IDEMPRESA
    LEFT JOIN BOLETA b ON v.IDVENTA = b.IDVENTA
    LEFT JOIN FACTURA f ON v.IDVENTA = f.IDVENTA
    " . $sql_base_where . $search_sql . "
    ORDER BY v.FECHAVENTA ASC -- Ordenamos por las más antiguas primero (urgencia)
    OFFSET ? ROWS 
    FETCH NEXT ? ROWS ONLY";

$params_paginacion = array_merge($params_search, [$offset, $registros_por_pagina]);
$stmt_pendientes = sqlsrv_query($conn, $sql_pendientes, $params_paginacion);
if ($stmt_pendientes === false) { die(print_r(sqlsrv_errors(), true)); }
?>

<!-- Header de Página -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="page-title"><i class="bi bi-file-earmark-text me-2"></i> Gestión de Comprobantes</h1>
            <p class="page-description">Emisión de boletas y facturas para ventas pendientes.</p>
        </div>
    </div>
</div>

<!-- Mensajes -->
<?php
if (isset($_SESSION['mensaje_comprobante'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_comprobante'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_comprobante']);
}
if (isset($_SESSION['error_comprobante'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_comprobante'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_comprobante']);
}
?>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-warning-soft"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_pendientes']; ?></h3>
                <p>Total Pendientes</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-info-soft"><i class="bi bi-receipt"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['boletas_pendientes']; ?></h3>
                <p>Boletas por Emitir</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft"><i class="bi bi-file-earmark-text"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['facturas_pendientes']; ?></h3>
                <p>Facturas por Emitir</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-success-soft"><i class="bi bi-check2-all"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['emitidos_hoy']; ?></h3>
                <p>Emitidos Hoy</p>
            </div>
        </div>
    </div>
</div>

<!-- Buscador -->
<div class="search-card mb-4">
    <form action="comprobantes.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input" 
                   value="<?php echo htmlspecialchars($search_term); ?>"
                   placeholder="Buscar por ID de Venta o Nombre de Cliente...">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="comprobantes.php" class="btn-clear-search" title="Limpiar búsqueda"><i class="bi bi-x-circle"></i></a>
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
        <h5><i class="bi bi-list-ul me-2"></i> Ventas Pendientes de Comprobante</h5>
        <span class="pagination-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table data-table mb-0">
            <thead>
                <tr>
                    <th>ID Venta</th>
                    <th>Fecha Venta</th>
                    <th>Cliente</th>
                    <th>Tipo Cliente</th>
                    <th>Total</th>
                    <th class="text-center">Acción Requerida</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros == 0) {
                    $mensaje_vacio = !empty($search_term) ? "No se encontraron ventas pendientes con ese criterio." : "¡Excelente! No hay comprobantes pendientes.";
                    echo "<tr><td colspan='6' class='text-center p-5 text-muted'>
                            <i class='bi bi-check-circle fs-1 d-block mb-2 text-success'></i>
                            $mensaje_vacio
                          </td></tr>";
                }
                while ($row = sqlsrv_fetch_array($stmt_pendientes, SQLSRV_FETCH_ASSOC)) {
                    
                    $tipo_comprobante = '';
                    $accion_url = 'backend/generar_comprobante.php?idventa=' . $row['IDVENTA'];
                    $accion_texto = '';
                    $btn_clase = '';
                    $badge_tipo = '';

                    if (!empty($row['IDEMPRESA'])) {
                        // Es una Empresa -> FACTURA
                        $tipo_comprobante = 'Factura';
                        $accion_url .= '&tipo=factura';
                        $accion_texto = 'Generar Factura';
                        $btn_clase = 'btn-primary'; // Estilo azul fuerte
                        $badge_tipo = '<span class="badge bg-info-subtle text-info-emphasis"><i class="bi bi-building me-1"></i>Empresa</span>';
                    } else {
                        // Es una Persona -> BOLETA
                        $tipo_comprobante = 'Boleta';
                        $accion_url .= '&tipo=boleta';
                        $accion_texto = 'Generar Boleta';
                        $btn_clase = 'btn-success'; // Estilo verde
                        $badge_tipo = '<span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-person me-1"></i>Persona</span>';
                    }

                    echo "<tr>";
                    echo "<td><span class='fw-bold text-primary'>#" . $row['IDVENTA'] . "</span></td>";
                    echo "<td>" . $row['FECHAVENTA']->format('d/m/Y H:i') . "</td>";
                    echo "<td>" . htmlspecialchars($row['NombreCliente']) . "</td>";
                    echo "<td>" . $badge_tipo . "</td>";
                    echo "<td class='fw-bold'>S/ " . number_format($row['TOTAL'], 2) . "</td>";
                    
                    // Botón de Acción
                    echo "<td class='text-center'>";
                    echo "<a href='" . $accion_url . "' 
                             onclick=\"return confirm('¿Está seguro de generar la " . $tipo_comprobante . " para la venta #" . $row['IDVENTA'] . "?')\"
                             class='btn btn-sm " . $btn_clase . " px-3 rounded-pill fw-bold'>
                             <i class='bi bi-file-earmark-plus me-1'></i>" . $accion_texto . "
                          </a>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div class="table-footer">
        <?php 
        $url_base = "comprobantes.php?" . $url_params; 
        include 'includes/paginador.php'; 
        ?>
    </div>
</div>

<!-- Enlace al CSS General -->
<link rel="stylesheet" href="css/listados_generales.css?v=1.1">

<?php include 'includes/footer.php'; ?>