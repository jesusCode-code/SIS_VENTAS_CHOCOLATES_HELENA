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
    $search_sql = " AND (NOMDEPARTAMENTO LIKE ?) ";
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
$sql_total = "SELECT COUNT(IDDEPARTAMENTO) AS total FROM DEPARTAMENTO WHERE 1=1" . $search_sql;
$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// ==================================================
// BLOQUE DE ESTADÍSTICAS (KPIs)
// ==================================================
$stats = [];
$stats['total_departamentos'] = $total_registros; // Total filtrado

// Total Provincias
$sql_prov = "SELECT COUNT(*) as total FROM PROVINCIA WHERE ESTADO = '1'";
$stmt_prov = sqlsrv_query($conn, $sql_prov);
$stats['total_provincias'] = sqlsrv_fetch_array($stmt_prov, SQLSRV_FETCH_ASSOC)['total'];

// Total Distritos
$sql_dist = "SELECT COUNT(*) as total FROM DISTRITO WHERE ESTADO = '1'";
$stmt_dist = sqlsrv_query($conn, $sql_dist);
$stats['total_distritos'] = sqlsrv_fetch_array($stmt_dist, SQLSRV_FETCH_ASSOC)['total'];

// ==================================================
// Lógica para OBTENER LISTA (CON FILTRO Y PAGINACIÓN)
// ==================================================
$sql = "SELECT IDDEPARTAMENTO, NOMDEPARTAMENTO, ESTADO 
        FROM DEPARTAMENTO 
        WHERE 1=1" . $search_sql . "
        ORDER BY NOMDEPARTAMENTO ASC
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
            <h1 class="page-title"><i class="bi bi-geo-alt me-2"></i> Gestión de Departamentos</h1>
            <p class="page-description">Administra las regiones principales del país.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearDepartamento">
                <i class="bi bi-plus-circle me-2"></i>Nuevo Departamento
            </button>
        </div>
    </div>
</div>

<!-- Mensajes -->
<?php
if (isset($_SESSION['mensaje_dep'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_dep'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_dep']);
}
if (isset($_SESSION['error_dep'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_dep'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_dep']);
}
?>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft"><i class="bi bi-geo-alt"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_departamentos']; ?></h3>
                <p>Departamentos (Filtrados)</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-success-soft"><i class="bi bi-map"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_provincias']; ?></h3>
                <p>Provincias Totales</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-info-soft"><i class="bi bi-pin-map"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_distritos']; ?></h3>
                <p>Distritos Totales</p>
            </div>
        </div>
    </div>
</div>

<!-- Buscador -->
<div class="search-card mb-4">
    <form action="departamentos.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input" 
                   value="<?php echo htmlspecialchars($search_term); ?>"
                   placeholder="Buscar por nombre de departamento...">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="departamentos.php" class="btn-clear-search" title="Limpiar búsqueda"><i class="bi bi-x-circle"></i></a>
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
        <h5><i class="bi bi-list-ul me-2"></i> Listado de Departamentos</h5>
        <span class="pagination-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table data-table mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre del Departamento</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros == 0) {
                    $mensaje_vacio = !empty($search_term) ? "No se encontraron departamentos con el término '" . htmlspecialchars($search_term) . "'." : "No hay departamentos registrados.";
                    echo "<tr><td colspan='4' class='text-center p-4 text-muted'>$mensaje_vacio</td></tr>";
                }
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $estado = ($row['ESTADO'] == '1') ? '<span class="status-badge status-active"><i class="bi bi-check-circle"></i> Activo</span>' : '<span class="status-badge status-inactive"><i class="bi bi-x-circle"></i> Inactivo</span>';

                    echo "<tr>";
                    echo "<td>" . $row['IDDEPARTAMENTO'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['NOMDEPARTAMENTO']) . "</td>";
                    echo "<td>" . $estado . "</td>";
                    
                    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
                    echo "<td class='action-buttons'>";
                    
                    // BOTÓN EDITAR (ABRE EL MODAL)
                    echo "<button type='button' 
                                 class='btn-action btn-edit' 
                                 data-bs-toggle='modal' 
                                 data-bs-target='#modalEditarDepartamento'
                                 data-id='" . $row['IDDEPARTAMENTO'] . "'
                                 data-nombre='" . htmlspecialchars($row['NOMDEPARTAMENTO'], ENT_QUOTES) . "'
                                 data-estado='" . $row['ESTADO'] . "'
                                 title='Editar'>
                              <i class='bi bi-pencil-square'></i>
                           </button>";

                    if ($row['ESTADO'] == '1') {
                        $onclick_js = "return confirm('¿Seguro que deseas DESACTIVAR " . htmlspecialchars($row['NOMDEPARTAMENTO']) . "?');";
                        echo "<a href='backend/gestionar_departamento.php?accion=desactivar&id=" . $row['IDDEPARTAMENTO'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-delete' onclick=\"" . $onclick_js . "\" title='Desactivar'><i class='bi bi-trash'></i></a>";
                    } else {
                        $onclick_js = "return confirm('¿Seguro que deseas ACTIVAR " . htmlspecialchars($row['NOMDEPARTAMENTO']) . "?');";
                        echo "<a href='backend/gestionar_departamento.php?accion=activar&id=" . $row['IDDEPARTAMENTO'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
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
        $url_base = "departamentos.php" . (!empty($url_params) ? "?" . $url_params : "");
        include 'includes/paginador.php'; 
        ?>
    </div>
</div>

<!-- MODAL: CREAR DEPARTAMENTO -->
<div class="modal fade" id="modalCrearDepartamento" tabindex="-1" aria-labelledby="modalCrearLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_departamento.php" method="POST">
                <input type="hidden" name="accion" value="crear">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearLabel">
                        <i class="bi bi-plus-circle me-2"></i>Añadir Nuevo Departamento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nomdepartamento" class="form-label"><i class="bi bi-geo-alt me-2"></i>Nombre del Departamento:</label>
                        <input type="text" id="nomdepartamento" name="nomdepartamento" class="form-control" required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Guardar Departamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: EDITAR DEPARTAMENTO (¡NUEVO!) -->
<div class="modal fade" id="modalEditarDepartamento" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_departamento.php" method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="iddepartamento" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">
                        <i class="bi bi-pencil-square me-2"></i>Editar Departamento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nomdepartamento" class="form-label"><i class="bi bi-geo-alt me-2"></i>Nombre del Departamento:</label>
                        <input type="text" id="edit_nomdepartamento" name="nomdepartamento" class="form-control" required>
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
    const modalEditar = document.getElementById('modalEditarDepartamento');
    
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        const estado = button.getAttribute('data-estado');
        
        const modalIdInput = modalEditar.querySelector('#edit_id');
        const modalNombreInput = modalEditar.querySelector('#edit_nomdepartamento');
        const modalEstadoSelect = modalEditar.querySelector('#edit_estado');
        
        modalIdInput.value = id;
        modalNombreInput.value = nombre;
        modalEstadoSelect.value = estado;
    });
});
</script>

<?php include 'includes/footer.php'; ?>