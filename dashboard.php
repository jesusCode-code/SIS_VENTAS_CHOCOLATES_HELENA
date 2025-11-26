<?php
session_start();

// Control de seguridad
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header("Location: login.php");
    exit;
}

include 'includes/header.php';
include 'includes/conexion.php';

// Obtener estadísticas del sistema
$stats = [];

// Total de productos
$sql = "SELECT COUNT(*) as total FROM PRODUCTO WHERE ESTADO = '1'";
$result = sqlsrv_query($conn, $sql);
$stats['productos'] = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)['total'];

// Total de clientes
$sql = "SELECT COUNT(*) as total FROM CLIENTE WHERE ESTADO = '1'";
$result = sqlsrv_query($conn, $sql);
$stats['clientes'] = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)['total'];

// Total de ventas del mes
$sql = "SELECT COUNT(*) as total FROM VENTA WHERE MONTH(FECHAVENTA) = MONTH(GETDATE()) AND YEAR(FECHAVENTA) = YEAR(GETDATE())";
$result = sqlsrv_query($conn, $sql);
$stats['ventas_mes'] = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)['total'];

// Ingresos del mes
$sql = "SELECT ISNULL(SUM(TOTAL), 0) as total FROM VENTA WHERE MONTH(FECHAVENTA) = MONTH(GETDATE()) AND YEAR(FECHAVENTA) = YEAR(GETDATE()) AND ESTADO = '1'";
$result = sqlsrv_query($conn, $sql);
$stats['ingresos_mes'] = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)['total'];

// Productos con bajo stock
$sql = "SELECT COUNT(*) as total FROM PRODUCTO WHERE STOCK <= 10 AND ESTADO = '1'";
$result = sqlsrv_query($conn, $sql);
$stats['bajo_stock'] = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)['total'];

// Últimas ventas
$sql = "SELECT TOP 5 v.IDVENTA, v.FECHAVENTA, v.TOTAL, 
        COALESCE(p.NOMBRES + ' ' + p.APEPATERNO, 'Cliente Anónimo') as NombreCliente
        FROM VENTA v
        LEFT JOIN CLIENTE c ON v.IDCLIENTE = c.IDCLIENTE
        LEFT JOIN PERSONA p ON c.IDPERSONA = p.IDPERSONA
        ORDER BY v.FECHAVENTA DESC";
$result = sqlsrv_query($conn, $sql);
$ultimas_ventas = [];
while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $ultimas_ventas[] = $row;
}
?>

<!-- Hero Dashboard -->
<div class="dashboard-hero">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="welcome-section">
                <h1 class="welcome-title">
                    <i class="bi bi-speedometer2 me-3"></i>
                    ¡Bienvenido al Panel Administrativo!
                </h1>
                <p class="welcome-subtitle">
                    Hola, <strong><?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></strong> 👋
                </p>
                <p class="welcome-role">
                    <i class="bi bi-shield-check me-2"></i>
                    Rol: <span class="badge-role"><?php echo htmlspecialchars($_SESSION['rol']); ?></span>
                </p>
            </div>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <div class="quick-actions">
                <a href="ventas.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Nueva Venta
                </a>
                <a href="productos.php" class="btn btn-outline-primary">
                    <i class="bi bi-box-seam me-2"></i>Productos
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row g-4 mb-4">
    <!-- Tarjeta 1: Productos -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-primary">
            <div class="stat-icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $stats['productos']; ?></h3>
                <p class="stat-label">Productos Activos</p>
            </div>
            <a href="productos.php" class="stat-link">
                Ver todos <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Tarjeta 2: Clientes -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-success">
            <div class="stat-icon">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $stats['clientes']; ?></h3>
                <p class="stat-label">Clientes Registrados</p>
            </div>
            <a href="clientes.php" class="stat-link">
                Ver todos <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Tarjeta 3: Ventas del Mes -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-info">
            <div class="stat-icon">
                <i class="bi bi-cart-check"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $stats['ventas_mes']; ?></h3>
                <p class="stat-label">Ventas este Mes</p>
            </div>
            <a href="listado_ventas.php" class="stat-link">
                Ver todas <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Tarjeta 4: Ingresos -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-warning">
            <div class="stat-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number">S/ <?php echo number_format($stats['ingresos_mes'], 2); ?></h3>
                <p class="stat-label">Ingresos del Mes</p>
            </div>
            <a href="reportes.php" class="stat-link">
                Ver reporte <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Alertas y Contenido -->
<div class="row g-4">
    <!-- Últimas Ventas -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>
                    Últimas Ventas
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($ultimas_ventas) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimas_ventas as $venta): ?>
                            <tr>
                                <td><strong>#<?php echo $venta['IDVENTA']; ?></strong></td>
                                <td><?php echo htmlspecialchars($venta['NombreCliente']); ?></td>
                                <td><?php echo $venta['FECHAVENTA']->format('d/m/Y H:i'); ?></td>
                                <td class="text-end"><strong>S/ <?php echo number_format($venta['TOTAL'], 2); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ddd;"></i>
                    <p class="mt-3 text-muted">No hay ventas registradas aún</p>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white border-0 text-center">
                <a href="listado_ventas.php" class="btn btn-sm btn-outline-primary">
                    Ver todas las ventas <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Alertas y Accesos Rápidos -->
    <div class="col-lg-4">
        <!-- Alerta de Stock Bajo -->
        <?php if ($stats['bajo_stock'] > 0): ?>
        <div class="alert alert-warning shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle fs-2 me-3"></i>
                <div>
                    <h6 class="alert-heading mb-1">Stock Bajo</h6>
                    <p class="mb-0 small"><?php echo $stats['bajo_stock']; ?> producto(s) con stock crítico</p>
                    <a href="productos.php" class="alert-link small">Ver productos</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Accesos Rápidos -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning me-2"></i>
                    Accesos Rápidos
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="ventas.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-plus-circle me-2 text-primary"></i>
                        Registrar Nueva Venta
                    </a>
                    <a href="productos.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-box-seam me-2 text-success"></i>
                        Gestionar Productos
                    </a>
                    <a href="clientes.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-people me-2 text-info"></i>
                        Ver Clientes
                    </a>
                    <a href="promociones.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-tag me-2 text-warning"></i>
                        Gestionar Promociones
                    </a>
                    <a href="reportes.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-graph-up me-2 text-danger"></i>
                        Ver Reportes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="css/dashboard.css">

<?php include 'includes/footer.php'; ?>a