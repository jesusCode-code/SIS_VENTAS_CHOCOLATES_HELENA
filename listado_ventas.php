<?php
// Seguridad, Header y Conexión
include 'includes/seguridad.php'; 
include 'includes/header.php';
include 'includes/conexion.php';

// BÚSQUEDA
$search_term = $_GET['search'] ?? ''; 
$search_sql = ""; 
$params_search = []; 
$url_params = ""; 

if (!empty($search_term)) {
    $search_like = '%' . $search_term . '%';
    $search_sql = " AND (
                    CAST(v.IDVENTA AS VARCHAR(20)) LIKE ? 
                    OR (p_cli.NOMBRES + ' ' + p_cli.APEPATERNO) LIKE ?
                    OR e_cli.RAZON_SOCIAL LIKE ?
                    OR (p_emp.NOMBRES + ' ' + p_emp.APEPATERNO) LIKE ?
                    OR COALESCE(b.SERIE + '-' + b.NUMERO, f.SERIE + '-' + f.NUMERO) LIKE ?
                  ) ";
    $params_search = [$search_like, $search_like, $search_like, $search_like, $search_like];
    $url_params = "search=" . urlencode($search_term);
}

// PAGINACIÓN
$registros_por_pagina = 15; 
$pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Estadísticas
$sql_stats = "SELECT 
                COUNT(*) as total_ventas,
                SUM(CASE WHEN ESTADO = '1' THEN 1 ELSE 0 END) as ventas_completadas,
                ISNULL(SUM(CASE WHEN ESTADO = '1' THEN TOTAL ELSE 0 END), 0) as ingresos_totales,
                ISNULL(SUM(CASE WHEN ESTADO = '1' AND MONTH(FECHAVENTA) = MONTH(GETDATE()) AND YEAR(FECHAVENTA) = YEAR(GETDATE()) THEN TOTAL ELSE 0 END), 0) as ingresos_mes
              FROM VENTA";
$stmt_stats = sqlsrv_query($conn, $sql_stats);
$stats = sqlsrv_fetch_array($stmt_stats, SQLSRV_FETCH_ASSOC);

// Contar registros con filtro
$sql_total = "
    SELECT COUNT(v.IDVENTA) AS total 
    FROM VENTA v
    LEFT JOIN CLIENTE c ON v.IDCLIENTE = c.IDCLIENTE
    LEFT JOIN PERSONA p_cli ON c.IDPERSONA = p_cli.IDPERSONA
    LEFT JOIN EMPRESA e_cli ON c.IDEMPRESA = e_cli.IDEMPRESA
    LEFT JOIN USUARIO u ON v.IDUSUARIO = u.IDUSUARIO
    LEFT JOIN PERSONA p_emp ON u.IDPERSONA = p_emp.IDPERSONA
    LEFT JOIN BOLETA b ON v.IDVENTA = b.IDVENTA
    LEFT JOIN FACTURA f ON v.IDVENTA = f.IDVENTA
    WHERE 1=1" . $search_sql; 

$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// CONSULTA PRINCIPAL
$sql_ventas = "
    SELECT 
        v.IDVENTA, v.FECHAVENTA, v.SUBTOTAL, v.IGV, v.TOTAL, v.ESTADO,
        p_cli.NOMBRES + ' ' + p_cli.APEPATERNO AS NombreCliente,
        e_cli.RAZON_SOCIAL AS NombreEmpresa,
        p_emp.NOMBRES + ' ' + p_emp.APEPATERNO AS NombreVendedor, 
        mp.NOMMETODO, 
        COALESCE(b.SERIE + '-' + b.NUMERO, f.SERIE + '-' + f.NUMERO) AS NumeroComprobante,
        CASE
            WHEN f.IDFACTURA IS NOT NULL THEN 'Factura'
            WHEN b.IDBOLETA IS NOT NULL THEN 'Boleta'
            ELSE 'Pendiente'
        END AS TipoComprobante
    FROM VENTA v
    LEFT JOIN CLIENTE c ON v.IDCLIENTE = c.IDCLIENTE
    LEFT JOIN PERSONA p_cli ON c.IDPERSONA = p_cli.IDPERSONA
    LEFT JOIN EMPRESA e_cli ON c.IDEMPRESA = e_cli.IDEMPRESA
    LEFT JOIN USUARIO u ON v.IDUSUARIO = u.IDUSUARIO
    LEFT JOIN PERSONA p_emp ON u.IDPERSONA = p_emp.IDPERSONA 
    LEFT JOIN BOLETA b ON v.IDVENTA = b.IDVENTA
    LEFT JOIN FACTURA f ON v.IDVENTA = f.IDVENTA
    LEFT JOIN METODO_PAGO mp ON v.IDMETODOPAGO = mp.IDMETODOPAGO
    WHERE 1=1 " . $search_sql . "
    ORDER BY v.FECHAVENTA DESC
    OFFSET ? ROWS 
    FETCH NEXT ? ROWS ONLY";
                  
$params_paginacion = array_merge($params_search, [$offset, $registros_por_pagina]);
$stmt_ventas = sqlsrv_query($conn, $sql_ventas, $params_paginacion);

if ($stmt_ventas === false) { 
    die(print_r(sqlsrv_errors(), true)); 
}

$ventas = [];
$ids_de_ventas = [];
while ($row = sqlsrv_fetch_array($stmt_ventas, SQLSRV_FETCH_ASSOC)) {
    $ventas[] = $row;
    $ids_de_ventas[] = $row['IDVENTA'];
}

// Obtener detalles
$detalles = [];
if (count($ids_de_ventas) > 0) {
    $placeholders = implode(',', array_fill(0, count($ids_de_ventas), '?'));
    $sql_detalle = "
        SELECT dv.IDVENTA, dv.CANTIDAD, p.NOMPRODUCTO, dv.PRECIO_UNITARIO, dv.SUBTOTAL
        FROM DETALLE_VENTA dv
        INNER JOIN PRODUCTO p ON dv.IDPRODUCTO = p.IDPRODUCTO
        WHERE dv.IDVENTA IN ($placeholders)
        ORDER BY p.NOMPRODUCTO ASC";
    $stmt_detalle = sqlsrv_query($conn, $sql_detalle, $ids_de_ventas);
    while ($row_det = sqlsrv_fetch_array($stmt_detalle, SQLSRV_FETCH_ASSOC)) {
        $detalles[$row_det['IDVENTA']][] = $row_det;
    }
}
?>

<!-- Header de Página -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="page-title">
                <i class="bi bi-receipt-cutoff me-2"></i>
                Historial de Ventas
            </h1>
            <p class="page-description">Consulta y gestiona todas las ventas registradas en el sistema</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="ventas.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Nueva Venta
            </a>
        </div>
    </div>
</div>

<!-- Mensajes -->
<?php
if (isset($_SESSION['mensaje_venta'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_venta'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_venta']);
}
if (isset($_SESSION['error_venta'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_venta'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_venta']);
}
?>

<!-- Estadísticas -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_ventas']; ?></h3>
                <p>Total Ventas</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-success-soft">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['ventas_completadas']; ?></h3>
                <p>Completadas</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-info-soft">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-content-mini">
                <h3>S/ <?php echo number_format($stats['ingresos_totales'], 2); ?></h3>
                <p>Ingresos Totales</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-warning-soft">
                <i class="bi bi-calendar-month"></i>
            </div>
            <div class="stat-content-mini">
                <h3>S/ <?php echo number_format($stats['ingresos_mes'], 2); ?></h3>
                <p>Este Mes</p>
            </div>
        </div>
    </div>
</div>

<!-- Buscador -->
<div class="search-card mb-4">
    <form action="listado_ventas.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" 
                   name="search" 
                   class="search-input" 
                   value="<?php echo htmlspecialchars($search_term); ?>"
                   placeholder="Buscar por ID, Cliente, Vendedor o N° Comprobante...">
            <button type="submit" class="btn-search">
                <i class="bi bi-search me-2"></i>Buscar
            </button>
            <?php if (!empty($search_term)): ?>
                <a href="listado_ventas.php" class="btn-clear-search" title="Limpiar búsqueda">
                    <i class="bi bi-x-circle"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
    
    <?php if (!empty($search_term)): ?>
    <div class="search-results-info">
        <span class="results-badge">
            <i class="bi bi-funnel me-2"></i>
            Filtrando: "<?php echo htmlspecialchars($search_term); ?>"
            (<?php echo $total_registros; ?> resultado<?php echo $total_registros != 1 ? 's' : ''; ?>)
        </span>
    </div>
    <?php endif; ?>
</div>

<!-- Lista de Ventas -->
<div class="sales-list-container">
    <div class="list-header">
        <h5>
            <i class="bi bi-list-ul me-2"></i>
            Lista de Ventas
        </h5>
        <span class="pagination-info">
            Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>
        </span>
    </div>
    
    <?php if ($total_registros == 0): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h4>No hay ventas registradas</h4>
            <p><?php echo !empty($search_term) ? 'No se encontraron ventas con ese término de búsqueda' : 'Aún no se han registrado ventas en el sistema'; ?></p>
            <a href="ventas.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Registrar Primera Venta
            </a>
        </div>
    <?php else: ?>
        <div class="sales-list">
            <?php foreach ($ventas as $row): 
                $id_collapse = "detalle-" . $row['IDVENTA'];
                $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
            ?>
            <div class="sale-card">
                <!-- Header de la venta -->
                <div class="sale-header" data-bs-toggle="collapse" data-bs-target="#<?php echo $id_collapse; ?>" role="button">
                    <div class="sale-id">
                        <i class="bi bi-receipt me-2"></i>
                        <span class="id-number">#<?php echo $row['IDVENTA']; ?></span>
                        <span class="sale-date">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?php echo $row['FECHAVENTA']->format('d/m/Y H:i'); ?>
                        </span>
                    </div>
                    
                    <div class="sale-info-grid">
                        <!-- Cliente -->
                        <div class="info-item">
                            <i class="bi bi-person"></i>
                            <div>
                                <small>Cliente</small>
                                <strong><?php echo htmlspecialchars($row['NombreCliente'] ?? $row['NombreEmpresa'] ?? 'No especificado'); ?></strong>
                            </div>
                        </div>
                        
                        <!-- Vendedor -->
                        <div class="info-item">
                            <i class="bi bi-person-badge"></i>
                            <div>
                                <small>Vendedor</small>
                                <strong><?php echo htmlspecialchars($row['NombreVendedor'] ?? 'N/A'); ?></strong>
                            </div>
                        </div>
                        
                        <!-- Método de Pago -->
                        <div class="info-item">
                            <i class="bi bi-credit-card"></i>
                            <div>
                                <small>Método</small>
                                <strong><?php echo htmlspecialchars($row['NOMMETODO'] ?? 'N/A'); ?></strong>
                            </div>
                        </div>
                        
                        <!-- Total -->
                        <div class="info-item">
                            <i class="bi bi-cash-stack"></i>
                            <div>
                                <small>Total</small>
                                <strong class="total-amount">S/ <?php echo number_format($row['TOTAL'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sale-badges">
                        <!-- Comprobante -->
                        <?php 
                        $comp_class = '';
                        $comp_icon = '';
                        switch($row['TipoComprobante']) {
                            case 'Factura':
                                $comp_class = 'badge-factura';
                                $comp_icon = 'bi-file-earmark-text';
                                break;
                            case 'Boleta':
                                $comp_class = 'badge-boleta';
                                $comp_icon = 'bi-receipt';
                                break;
                            default:
                                $comp_class = 'badge-pending';
                                $comp_icon = 'bi-hourglass-split';
                        }
                        ?>
                        <div class="badge-group">
                            <span class="custom-badge <?php echo $comp_class; ?>">
                                <i class="<?php echo $comp_icon; ?> me-1"></i>
                                <?php echo $row['TipoComprobante']; ?>
                            </span>
                            <?php if ($row['NumeroComprobante']): ?>
                                <small class="badge-detail"><?php echo htmlspecialchars($row['NumeroComprobante']); ?></small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Estado -->
                        <?php if ($row['ESTADO'] == '1'): ?>
                            <span class="custom-badge badge-active">
                                <i class="bi bi-check-circle me-1"></i>
                                Completada
                            </span>
                        <?php else: ?>
                            <span class="custom-badge badge-cancelled">
                                <i class="bi bi-x-circle me-1"></i>
                                Anulada
                            </span>
                        <?php endif; ?>
                        
                        <!-- Botón Anular -->
                        <?php if ($row['ESTADO'] == '1'): ?>
                            <button type="button" 
                                    class="btn-anular"
                                    onclick="event.stopPropagation(); if(confirm('¿Anular venta #<?php echo $row['IDVENTA']; ?>? Se devolverá el stock.')) { window.location.href='backend/anular_venta.php?id=<?php echo $row['IDVENTA']; ?>&pagina=<?php echo $pagina_actual . $search_url_param; ?>'; }"
                                    title="Anular Venta">
                                <i class="bi bi-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <button class="btn-expand" type="button">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
                
                <!-- Detalle expandible -->
                <div class="collapse sale-detail" id="<?php echo $id_collapse; ?>">
                    <div class="detail-container">
                        <h6 class="detail-title">
                            <i class="bi bi-box-seam me-2"></i>
                            Productos de la venta
                        </h6>
                        
                        <div class="products-detail-table">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-end">Precio Unit.</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($detalles[$row['IDVENTA']])): ?>
                                        <?php foreach ($detalles[$row['IDVENTA']] as $producto): ?>
                                        <tr>
                                            <td>
                                                <i class="bi bi-box me-2 text-muted"></i>
                                                <?php echo htmlspecialchars($producto['NOMPRODUCTO']); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="quantity-badge">
                                                    <?php echo $producto['CANTIDAD']; ?>x
                                                </span>
                                            </td>
                                            <td class="text-end">S/ <?php echo number_format($producto['PRECIO_UNITARIO'], 2); ?></td>
                                            <td class="text-end fw-bold">S/ <?php echo number_format($producto['SUBTOTAL'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No se encontraron detalles</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="detail-summary">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span>S/ <?php echo number_format($row['SUBTOTAL'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>IGV (18%):</span>
                                <span>S/ <?php echo number_format($row['IGV'], 2); ?></span>
                            </div>
                            <div class="summary-row summary-total">
                                <span>Total:</span>
                                <span>S/ <?php echo number_format($row['TOTAL'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <div class="pagination-wrapper">
            <?php 
            $url_base = "listado_ventas.php" . (!empty($url_params) ? "?" . $url_params : ""); 
            include 'includes/paginador.php'; 
            ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="css/listado_ventas.css">

<script>
// Animación smooth para el colapso y rotación del botón
document.addEventListener('DOMContentLoaded', function() {
    const collapseButtons = document.querySelectorAll('[data-bs-toggle="collapse"]');
    
    collapseButtons.forEach(button => {
        const target = button.getAttribute('data-bs-target');
        const collapseElement = document.querySelector(target);
        
        if (collapseElement) {
            collapseElement.addEventListener('show.bs.collapse', function() {
                const expandBtn = button.querySelector('.btn-expand i');
                if (expandBtn) {
                    expandBtn.style.transform = 'rotate(180deg)';
                }
            });
            
            collapseElement.addEventListener('hide.bs.collapse', function() {
                const expandBtn = button.querySelector('.btn-expand i');
                if (expandBtn) {
                    expandBtn.style.transform = 'rotate(0deg)';
                }
            });
        }
    });
});

// Prevenir que el click en el botón anular expanda el collapse
document.querySelectorAll('.btn-anular').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});
</script>

<?php include 'includes/footer.php'; ?>