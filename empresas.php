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
    $search_sql = " AND (e.RAZON_SOCIAL LIKE ? OR e.RUC LIKE ? OR e.TELEFONO LIKE ?) ";
    $params_search = [$search_like, $search_like, $search_like];
    $url_params = "search=" . urlencode($search_term);
}

// ==================================================
// BLOQUE DE PAGINACIÓN
// ==================================================
$registros_por_pagina = 20; 
$pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// 4. Contar el TOTAL de registros (Solo Empresas)
$sql_total = "SELECT COUNT(e.IDEMPRESA) AS total 
              FROM EMPRESA e 
              JOIN CLIENTE c ON e.IDEMPRESA = c.IDEMPRESA
              WHERE 1=1" . $search_sql;
              
$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// ==================================================
// KPIs
// ==================================================
$stats = [];
$stats['total_empresas'] = $total_registros;

$sql_contactos = "SELECT COUNT(*) as total FROM EMPRESA_CONTACTO WHERE ESTADO = '1'";
$stmt_contactos = sqlsrv_query($conn, $sql_contactos);
$stats['total_contactos'] = sqlsrv_fetch_array($stmt_contactos, SQLSRV_FETCH_ASSOC)['total'];

// ==================================================
// OBTENER LISTA (Solo Empresas)
// ==================================================
$sql = "SELECT 
            e.IDEMPRESA, e.RUC, e.RAZON_SOCIAL, e.DIRECCION, e.TELEFONO, e.ESTADO,
            c.IDCLIENTE, c.ESTADO AS ESTADO_CLIENTE
        FROM EMPRESA e
        JOIN CLIENTE c ON e.IDEMPRESA = c.IDEMPRESA
        WHERE 1=1 " . $search_sql . "
        ORDER BY e.RAZON_SOCIAL ASC
        OFFSET ? ROWS 
        FETCH NEXT ? ROWS ONLY";
                  
$params_paginacion = array_merge($params_search, [$offset, $registros_por_pagina]);
$stmt = sqlsrv_query($conn, $sql, $params_paginacion);
if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
?>

<!-- Header de Página -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="page-title"><i class="bi bi-building me-2"></i> Gestión de Empresas</h1>
            <p class="page-description">Administra los clientes corporativos (Personas Jurídicas).</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearEmpresa">
                <i class="bi bi-plus-circle me-2"></i>Nueva Empresa
            </button>
        </div>
    </div>
</div>

<!-- Mensajes -->
<?php
if (isset($_SESSION['mensaje_empresa'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_empresa'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_empresa']);
}
if (isset($_SESSION['error_empresa'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_empresa'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_empresa']);
}
?>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-lg-6 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft"><i class="bi bi-building"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_empresas']; ?></h3>
                <p>Empresas Registradas</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-info-soft"><i class="bi bi-person-lines-fill"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_contactos']; ?></h3>
                <p>Contactos Vinculados</p>
            </div>
        </div>
    </div>
</div>

<!-- Buscador -->
<div class="search-card mb-4">
    <form action="empresas.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input" 
                   value="<?php echo htmlspecialchars($search_term); ?>"
                   placeholder="Buscar por Razón Social o RUC...">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="empresas.php" class="btn-clear-search" title="Limpiar búsqueda"><i class="bi bi-x-circle"></i></a>
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
        <h5><i class="bi bi-list-ul me-2"></i> Listado de Empresas</h5>
        <span class="pagination-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table data-table mb-0">
            <thead>
                <tr>
                    <th>Razón Social</th>
                    <th>RUC</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros == 0) {
                    echo "<tr><td colspan='6' class='text-center p-4 text-muted'>No hay empresas registradas.</td></tr>";
                }
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $estado = ($row['ESTADO_CLIENTE'] == '1') ? '<span class="status-badge status-active">Activo</span>' : '<span class="status-badge status-inactive">Inactivo</span>';

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['RAZON_SOCIAL']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['RUC']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['TELEFONO'] ?? '---') . "</td>";
                    echo "<td>" . htmlspecialchars($row['DIRECCION'] ?? '---') . "</td>";
                    echo "<td>" . $estado . "</td>";
                    
                    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
                    echo "<td class='action-buttons'>";
                    
                    // Botón Editar (Modal)
                    echo "<button type='button' 
                                 class='btn-action btn-edit' 
                                 data-bs-toggle='modal' 
                                 data-bs-target='#modalEditarEmpresa'
                                 data-id='" . $row['IDEMPRESA'] . "'
                                 data-idcliente='" . $row['IDCLIENTE'] . "'
                                 data-razon='" . htmlspecialchars($row['RAZON_SOCIAL'], ENT_QUOTES) . "'
                                 data-ruc='" . htmlspecialchars($row['RUC'], ENT_QUOTES) . "'
                                 data-telefono='" . htmlspecialchars($row['TELEFONO'] ?? '', ENT_QUOTES) . "'
                                 data-direccion='" . htmlspecialchars($row['DIRECCION'] ?? '', ENT_QUOTES) . "'
                                 data-estado='" . $row['ESTADO_CLIENTE'] . "'
                                 title='Editar'>
                              <i class='bi bi-pencil-square'></i>
                           </button>";

                    // Botones Activar/Desactivar (usamos backend/gestionar_empresa.php)
                    if ($row['ESTADO_CLIENTE'] == '1') {
                        $onclick_js = "return confirm('¿Seguro que deseas DESACTIVAR a " . htmlspecialchars($row['RAZON_SOCIAL']) . "?');";
                        echo "<a href='backend/gestionar_empresa.php?accion=desactivar&id=" . $row['IDCLIENTE'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-delete' onclick=\"" . $onclick_js . "\" title='Desactivar'><i class='bi bi-trash'></i></a>";
                    } else {
                        $onclick_js = "return confirm('¿Seguro que deseas ACTIVAR a " . htmlspecialchars($row['RAZON_SOCIAL']) . "?');";
                        echo "<a href='backend/gestionar_empresa.php?accion=activar&id=" . $row['IDCLIENTE'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
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
        $url_base = "empresas.php" . (!empty($url_params) ? "?" . $url_params : "");
        include 'includes/paginador.php'; 
        ?>
    </div>
</div>

<!-- MODAL: CREAR EMPRESA -->
<div class="modal fade" id="modalCrearEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_empresa.php" method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Nueva Empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Razón Social:</label>
                        <input type="text" name="razon_social" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RUC (11 dígitos):</label>
                            <input type="text" name="ruc" class="form-control" required pattern="[0-9]{11}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono:</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección:</label>
                        <input type="text" name="direccion" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: EDITAR EMPRESA -->
<div class="modal fade" id="modalEditarEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_empresa.php" method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="idempresa" id="edit_idempresa">
                <input type="hidden" name="idcliente" id="edit_idcliente">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Razón Social:</label>
                        <input type="text" id="edit_razon" name="razon_social" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RUC:</label>
                            <input type="text" id="edit_ruc" name="ruc" class="form-control" required pattern="[0-9]{11}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono:</label>
                            <input type="text" id="edit_telefono" name="telefono" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección:</label>
                        <input type="text" id="edit_direccion" name="direccion" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado:</label>
                        <select id="edit_estado" name="estado_cliente" class="form-select" required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="css/listados_generales.css?v=1.1">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEditar = document.getElementById('modalEditarEmpresa');
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        modalEditar.querySelector('#edit_idempresa').value = button.getAttribute('data-id');
        modalEditar.querySelector('#edit_idcliente').value = button.getAttribute('data-idcliente');
        modalEditar.querySelector('#edit_razon').value = button.getAttribute('data-razon');
        modalEditar.querySelector('#edit_ruc').value = button.getAttribute('data-ruc');
        modalEditar.querySelector('#edit_telefono').value = button.getAttribute('data-telefono');
        modalEditar.querySelector('#edit_direccion').value = button.getAttribute('data-direccion');
        modalEditar.querySelector('#edit_estado').value = button.getAttribute('data-estado');
    });
});
</script>

<?php include 'includes/footer.php'; ?>