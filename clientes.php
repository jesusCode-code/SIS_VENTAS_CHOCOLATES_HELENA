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
    $search_sql = " AND (
                    (p.NOMBRES + ' ' + p.APEPATERNO) LIKE ? 
                    OR e.RAZON_SOCIAL LIKE ?
                    OR p.DOC_IDENTIDAD LIKE ?
                    OR e.RUC LIKE ?
                  ) ";
    $params_search = [$search_like, $search_like, $search_like, $search_like];
    $url_params = "search=" . urlencode($search_term);
}

// ==================================================
// BLOQUE DE PAGINACIÓN
// ==================================================
$registros_por_pagina = 15;
$pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// 4. Contar el TOTAL de registros (CON FILTRO)
$sql_total = "SELECT COUNT(c.IDCLIENTE) AS total 
              FROM CLIENTE c
              LEFT JOIN PERSONA p ON c.IDPERSONA = p.IDPERSONA
              LEFT JOIN EMPRESA e ON c.IDEMPRESA = e.IDEMPRESA
              WHERE 1=1" . $search_sql;

$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// ==================================================
// ¡NUEVO! BLOQUE DE ESTADÍSTICAS (KPIs)
// ==================================================
$stats = [];
// (Usamos el total de la paginación para no consultar dos veces)
$stats['total_clientes'] = $total_registros;

// Total Personas
$sql_personas = "SELECT COUNT(*) as total FROM CLIENTE WHERE IDPERSONA IS NOT NULL AND ESTADO = '1'";
$stmt_personas = sqlsrv_query($conn, $sql_personas);
$stats['total_personas'] = sqlsrv_fetch_array($stmt_personas, SQLSRV_FETCH_ASSOC)['total'];

// Total Empresas
$sql_empresas = "SELECT COUNT(*) as total FROM CLIENTE WHERE IDEMPRESA IS NOT NULL AND ESTADO = '1'";
$stmt_empresas = sqlsrv_query($conn, $sql_empresas);
$stats['total_empresas'] = sqlsrv_fetch_array($stmt_empresas, SQLSRV_FETCH_ASSOC)['total'];

// Total Contactos
$sql_contactos = "SELECT COUNT(*) as total FROM EMPRESA_CONTACTO WHERE ESTADO = '1'";
$stmt_contactos = sqlsrv_query($conn, $sql_contactos);
$stats['total_contactos'] = sqlsrv_fetch_array($stmt_contactos, SQLSRV_FETCH_ASSOC)['total'];

// ==================================================
// Lógica para OBTENER LISTA (CON FILTRO Y PAGINACIÓN)
// ==================================================
$sql_clientes = "
    SELECT 
        c.IDCLIENTE, c.ESTADO,
        COALESCE(p.NOMBRES + ' ' + p.APEPATERNO, e.RAZON_SOCIAL) AS NombreCompleto,
        COALESCE(p.DOC_IDENTIDAD, e.RUC) AS Documento,
        COALESCE(p.CELULAR, e.TELEFONO) AS Contacto,
        CASE 
            WHEN p.IDPERSONA IS NOT NULL THEN 'Persona Natural'
            WHEN e.IDEMPRESA IS NOT NULL THEN 'Empresa'
            ELSE 'Indefinido'
        END AS TipoCliente
    FROM CLIENTE c
    LEFT JOIN PERSONA p ON c.IDPERSONA = p.IDPERSONA
    LEFT JOIN EMPRESA e ON c.IDEMPRESA = e.IDEMPRESA
    WHERE 1=1" . $search_sql . "
    ORDER BY NombreCompleto ASC
    OFFSET ? ROWS 
    FETCH NEXT ? ROWS ONLY";

$params_paginacion = array_merge($params_search, [$offset, $registros_por_pagina]);
$stmt_clientes = sqlsrv_query($conn, $sql_clientes, $params_paginacion);
if ($stmt_clientes === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="page-title"><i class="bi bi-people me-2"></i> Gestión de Clientes</h1>
            <p class="page-description">Administra tus clientes, tanto personas naturales como empresas.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="crear_cliente_persona.php" class="btn btn-primary me-2">
                <i class="bi bi-person-plus me-2"></i>Añadir Persona
            </a>
            <a href="crear_cliente_empresa.php" class="btn btn-primary me-2">
                <i class="bi bi-building-add me-2"></i>Añadir Empresa
            </a>
        </div>
    </div>
</div>

<?php
if (isset($_SESSION['mensaje_cliente'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_cliente'] .
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_cliente']);
}
if (isset($_SESSION['error_cliente'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_cliente'] .
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_cliente']);
}
?>

<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft"><i class="bi bi-people"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_clientes']; ?></h3>
                <p>Total Clientes (Filtrados)</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-success-soft"><i class="bi bi-person-fill"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_personas']; ?></h3>
                <p>Clientes (Personas)</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-info-soft"><i class="bi bi-building"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_empresas']; ?></h3>
                <p>Clientes (Empresas)</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-warning-soft"><i class="bi bi-person-lines-fill"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_contactos']; ?></h3>
                <p>Contactos (Empresa)</p>
            </div>
        </div>
    </div>
</div>


<div class="search-card mb-4">
    <form action="clientes.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input"
                value="<?php echo htmlspecialchars($search_term); ?>"
                placeholder="Buscar por nombre, razón social, DNI o RUC...">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="clientes.php" class="btn-clear-search" title="Limpiar búsqueda"><i class="bi bi-x-circle"></i></a>
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
        <h5><i class="bi bi-list-ul me-2"></i> Listado de Clientes</h5>
        <span class="pagination-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
    </div>

    <div class="table-responsive">
        <table class="table data-table mb-0">
            <thead>
                <tr>
                    <th>Nombre / Razón Social</th>
                    <th>Documento (DNI/RUC)</th>
                    <th>Contacto</th>
                    <th>Tipo Cliente</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros == 0) {
                    $mensaje_vacio = !empty($search_term) ? "No se encontraron clientes con el término '" . htmlspecialchars($search_term) . "'." : "No hay clientes registrados.";
                    echo "<tr><td colspan='6' class='text-center p-4 text-muted'>$mensaje_vacio</td></tr>";
                }
                while ($row = sqlsrv_fetch_array($stmt_clientes, SQLSRV_FETCH_ASSOC)) {
                    $estado = ($row['ESTADO'] == '1') ? '<span class="status-badge status-active"><i class="bi bi-check-circle"></i> Activo</span>' : '<span class="status-badge status-inactive"><i class="bi bi-x-circle"></i> Inactivo</span>';
                    $tipo_cliente_raw = ($row['TipoCliente'] == 'Persona Natural') ? 'persona' : 'empresa';

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['NombreCompleto']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Documento']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Contacto'] ?? '---') . "</td>";
                    echo "<td>" . $row['TipoCliente'] . "</td>";
                    echo "<td>" . $estado . "</td>";

                    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
                    echo "<td class='action-buttons'>";
                    echo "<a href='editar_cliente_{$tipo_cliente_raw}.php?id=" . $row['IDCLIENTE'] . "' class='btn-action btn-edit' title='Editar'><i class='bi bi-pencil-square'></i></a>";

                    if ($row['ESTADO'] == '1') {
                        $onclick_js = "return confirm('¿Seguro que deseas DESACTIVAR a este cliente?');";
                        echo "<a href='backend/gestionar_cliente.php?accion=desactivar&id=" . $row['IDCLIENTE'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-delete' onclick=\"" . $onclick_js . "\" title='Desactivar'><i class='bi bi-trash'></i></a>";
                    } else {
                        $onclick_js = "return confirm('¿Seguro que deseas ACTIVAR a este cliente?');";
                        echo "<a href='backend/gestionar_cliente.php?accion=activar&id=" . $row['IDCLIENTE'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
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
        $url_base = "clientes.php" . (!empty($url_params) ? "?" . $url_params : "");
        include 'includes/paginador.php';
        ?>
    </div>
</div>

<link rel="stylesheet" href="css/listados_generales.css">

<?php include 'includes/footer.php'; ?>