<?php
// 1. Seguridad, Header y Conexión
include 'includes/seguridad.php'; 
include 'includes/header.php';
include 'includes/conexion.php';

// 2. Lógica de Fechas
$hoy = date('Y-m-d');
$fecha_inicio = $_POST['fecha_inicio'] ?? $hoy;
$fecha_fin = $_POST['fecha_fin'] ?? $hoy;

// Ajustamos la fecha final para que incluya todo el día
$fecha_fin_ajustada = $fecha_fin . ' 23:59:59';
$params_fecha = array($fecha_inicio, $fecha_fin_ajustada);

// ==================================================
// 3. CONSULTA DE TOTALES (SIN PAGINACIÓN)
// Esta consulta calcula los grandes totales para el rango de fechas.
// ==================================================
$sql_reporte = "
    SELECT 
        COUNT(IDVENTA) AS NumeroVentas,
        ISNULL(SUM(SUBTOTAL), 0) AS TotalSubtotal,
        ISNULL(SUM(IGV), 0) AS TotalIGV,
        ISNULL(SUM(TOTAL), 0) AS GranTotal
    FROM VENTA
    WHERE 
        ESTADO = '1' 
        AND FECHAVENTA BETWEEN ? AND ?";
        
$stmt_reporte = sqlsrv_query($conn, $sql_reporte, $params_fecha);
if ($stmt_reporte === false) { die(print_r(sqlsrv_errors(), true)); }
$reporte = sqlsrv_fetch_array($stmt_reporte, SQLSRV_FETCH_ASSOC);

// ==================================================
// 4. LÓGICA DE PAGINACIÓN (PARA LA TABLA DE DETALLE)
// ==================================================
$registros_por_pagina = 20; 
$pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// 4a. Contar el TOTAL de registros para el paginador
$sql_total_detalle = "SELECT COUNT(IDVENTA) AS total FROM VENTA WHERE ESTADO = '1' AND FECHAVENTA BETWEEN ? AND ?";
$stmt_total_detalle = sqlsrv_query($conn, $sql_total_detalle, $params_fecha);
$total_registros = sqlsrv_fetch_array($stmt_total_detalle, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// 4b. Lógica para OBTENER LISTA de detalle con paginación
$sql_detalle = "
    SELECT v.IDVENTA, v.FECHAVENTA, v.TOTAL, 
           COALESCE(p.NOMBRES + ' ' + p.APEPATERNO, e.RAZON_SOCIAL) AS NombreCliente,
           u.LOGEO AS Vendedor
    FROM VENTA v
    INNER JOIN CLIENTE c ON v.IDCLIENTE = c.IDCLIENTE
    INNER JOIN USUARIO u ON v.IDUSUARIO = u.IDUSUARIO
    LEFT JOIN PERSONA p ON c.IDPERSONA = p.IDPERSONA
    LEFT JOIN EMPRESA e ON c.IDEMPRESA = e.IDEMPRESA
    WHERE 
        v.ESTADO = '1' 
        AND v.FECHAVENTA BETWEEN ? AND ?
    ORDER BY v.FECHAVENTA DESC
    OFFSET ? ROWS 
    FETCH NEXT ? ROWS ONLY";

// ¡Importante! Añadimos el offset y fetch a los parámetros
$params_detalle = array($fecha_inicio, $fecha_fin_ajustada, $offset, $registros_por_pagina);
$stmt_detalle = sqlsrv_query($conn, $sql_detalle, $params_detalle);
if ($stmt_detalle === false) { die(print_r(sqlsrv_errors(), true)); }
?>

<div class="mb-4 pb-2 border-bottom">
    <h1 class="h2">Reportes de Ventas</h1>
    <p>Selecciona un rango de fechas para consultar las ventas realizadas.</p>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="reportes.php" method="POST" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="fecha_inicio" class="form-label">Desde:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
            </div>
            <div class="col-md-5">
                <label for="fecha_fin" class="form-label">Hasta:</label>
                <input type="date" id="fecha_fin" name="fecha_fin" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">Generar Reporte</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h5 class="mb-0">Resumen del Período (<?php echo htmlspecialchars($fecha_inicio); ?> al <?php echo htmlspecialchars($fecha_fin); ?>)</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3 col-6 mb-3 mb-md-0">
                <h6 class="text-muted">Total Ventas (S/)</h6>
                <h3 class="fw-bold text-primary">S/ <?php echo number_format($reporte['GranTotal'], 2); ?></h3>
            </div>
            <div class="col-md-3 col-6 mb-3 mb-md-0">
                <h6 class="text-muted">N° de Transacciones</h6>
                <h3 class="fw-bold"><?php echo $reporte['NumeroVentas']; ?></h3>
            </div>
            <div class="col-md-3 col-6">
                <h6 class="text-muted">Subtotal (S/)</h6>
                <h3 class="fw-bold">S/ <?php echo number_format($reporte['TotalSubtotal'], 2); ?></h3>
            </div>
            <div class="col-md-3 col-6">
                <h6 class="text-muted">IGV (S/)</h6>
                <h3 class="fw-bold">S/ <?php echo number_format($reporte['TotalIGV'], 2); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Detalle de Ventas (Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-3">ID Venta</th>
                        <th class="py-3 px-3">Fecha y Hora</th>
                        <th class="py-3 px-3">Cliente</th>
                        <th class="py-3 px-3">Vendedor</th>
                        <th class="py-3 px-3">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = sqlsrv_fetch_array($stmt_detalle, SQLSRV_FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td class='py-3 px-3'>" . $row['IDVENTA'] . "</td>";
                        echo "<td class='py-3 px-3'>" . $row['FECHAVENTA']->format('Y-m-d H:i') . "</td>";
                        echo "<td class='py-3 px-3'>" . htmlspecialchars($row['NombreCliente']) . "</td>";
                        echo "<td class='py-3 px-3'>" . htmlspecialchars($row['Vendedor']) . "</td>";
                        echo "<td class='py-3 px-3'>S/ " . number_format($row['TOTAL'], 2) . "</td>";
                        echo "</tr>";
                    }
                    if ($total_registros == 0) {
                        echo "<tr><td colspan='5' class='text-center p-3'>No se encontraron ventas en este rango de fechas.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php 
        // Para que el paginador mantenga el filtro de fecha, necesitamos
        // construir una URL base que incluya las fechas.
        $url_base = "reportes.php?fecha_inicio=" . urlencode($fecha_inicio) . "&fecha_fin=" . urlencode($fecha_fin); 
        include 'includes/paginador.php'; 
        ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>