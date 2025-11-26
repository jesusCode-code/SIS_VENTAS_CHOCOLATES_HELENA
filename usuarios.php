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
    // Buscamos por Logeo, Nombre de Persona o Rol
    $search_sql = " AND (u.LOGEO LIKE ? OR (p.NOMBRES + ' ' + p.APEPATERNO) LIKE ? OR tu.NOMUSUARIO LIKE ?) ";
    $params_search = [$search_like, $search_like, $search_like];
    $url_params = "search=" . urlencode($search_term);
}

// ==================================================
// BLOQUE DE PAGINACIÓN
// ==================================================
$registros_por_pagina = 20; 
$pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// 4. Contar el TOTAL de registros (CON FILTRO)
$sql_total = "SELECT COUNT(u.IDUSUARIO) AS total 
              FROM USUARIO u
              JOIN PERSONA p ON u.IDPERSONA = p.IDPERSONA
              JOIN TIPO_USUARIO tu ON u.IDTIPO_USUARIO = tu.IDTIPO_USUARIO
              WHERE 1=1" . $search_sql;
              
$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// ==================================================
// BLOQUE DE ESTADÍSTICAS (KPIs)
// ==================================================
$stats = [];
$stats['total_usuarios'] = $total_registros;

// Usuarios Activos
$sql_activos = "SELECT COUNT(*) as total FROM USUARIO WHERE ESTADO = '1'";
$stmt_activos = sqlsrv_query($conn, $sql_activos);
$stats['activos'] = sqlsrv_fetch_array($stmt_activos, SQLSRV_FETCH_ASSOC)['total'];

// Usuarios Inactivos
$stats['inactivos'] = $total_registros - $stats['activos'];

// Rol más común
$sql_top_rol = "SELECT TOP 1 tu.NOMUSUARIO, COUNT(u.IDUSUARIO) as total
                FROM USUARIO u
                JOIN TIPO_USUARIO tu ON u.IDTIPO_USUARIO = tu.IDTIPO_USUARIO
                WHERE u.ESTADO = '1'
                GROUP BY tu.NOMUSUARIO
                ORDER BY total DESC";
$stmt_top_rol = sqlsrv_query($conn, $sql_top_rol);
$top_rol = sqlsrv_fetch_array($stmt_top_rol, SQLSRV_FETCH_ASSOC);
$stats['top_rol'] = $top_rol ? $top_rol['NOMUSUARIO'] : 'N/A';

// ==================================================
// Lógica para OBTENER LISTA
// ==================================================
$sql = "SELECT 
            u.IDUSUARIO, u.LOGEO, u.ESTADO, u.IDTIPO_USUARIO,
            p.IDPERSONA, p.NOMBRES, p.APEPATERNO, p.DOC_IDENTIDAD,
            tu.NOMUSUARIO AS ROL
        FROM USUARIO u
        JOIN PERSONA p ON u.IDPERSONA = p.IDPERSONA
        JOIN TIPO_USUARIO tu ON u.IDTIPO_USUARIO = tu.IDTIPO_USUARIO
        WHERE 1=1 " . $search_sql . "
        ORDER BY p.APEPATERNO, u.LOGEO
        OFFSET ? ROWS 
        FETCH NEXT ? ROWS ONLY";
                  
$params_paginacion = array_merge($params_search, [$offset, $registros_por_pagina]);
$stmt = sqlsrv_query($conn, $sql, $params_paginacion);
if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }

// 7. Cargar Personas SIN USUARIO (para el modal de Crear)
$sql_personas = "
    SELECT p.IDPERSONA, p.NOMBRES, p.APEPATERNO, p.DOC_IDENTIDAD
    FROM PERSONA p
    LEFT JOIN USUARIO u ON p.IDPERSONA = u.IDPERSONA
    WHERE u.IDUSUARIO IS NULL AND p.ESTADO = '1'
    ORDER BY p.APEPATERNO";
$stmt_personas = sqlsrv_query($conn, $sql_personas);
$personas_libres = [];
while ($row_p = sqlsrv_fetch_array($stmt_personas, SQLSRV_FETCH_ASSOC)) $personas_libres[] = $row_p;

// 8. Cargar Roles (para los modales)
$sql_roles = "SELECT IDTIPO_USUARIO, NOMUSUARIO FROM TIPO_USUARIO WHERE ESTADO = '1' ORDER BY NOMUSUARIO";
$stmt_roles = sqlsrv_query($conn, $sql_roles);
$roles = [];
while ($row_r = sqlsrv_fetch_array($stmt_roles, SQLSRV_FETCH_ASSOC)) $roles[] = $row_r;
?>

<!-- Header de Página -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="page-title"><i class="bi bi-person-lock me-2"></i> Gestión de Usuarios</h1>
            <p class="page-description">Administra las cuentas de acceso y permisos del sistema.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
                <i class="bi bi-person-plus-fill me-2"></i>Nuevo Usuario
            </button>
        </div>
    </div>
</div>

<!-- Mensajes -->
<?php
if (isset($_SESSION['mensaje_usuario'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_usuario'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_usuario']);
}
if (isset($_SESSION['error_usuario'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_usuario'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_usuario']);
}
?>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft"><i class="bi bi-people"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_usuarios']; ?></h3>
                <p>Usuarios Totales</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-success-soft"><i class="bi bi-person-check"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['activos']; ?></h3>
                <p>Usuarios Activos</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-info-soft"><i class="bi bi-shield-lock"></i></div>
            <div class="stat-content-mini">
                <h3 style="font-size: 1.5rem;"><?php echo htmlspecialchars($stats['top_rol']); ?></h3>
                <p>Rol más común</p>
            </div>
        </div>
    </div>
</div>

<!-- Buscador -->
<div class="search-card mb-4">
    <form action="usuarios.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input" 
                   value="<?php echo htmlspecialchars($search_term); ?>"
                   placeholder="Buscar por usuario, nombre de persona o rol...">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="usuarios.php" class="btn-clear-search" title="Limpiar búsqueda"><i class="bi bi-x-circle"></i></a>
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
        <h5><i class="bi bi-list-ul me-2"></i> Listado de Cuentas</h5>
        <span class="pagination-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table data-table mb-0">
            <thead>
                <tr>
                    <th>Usuario (Logeo)</th>
                    <th>Persona Asignada</th>
                    <th>Documento</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros == 0) {
                    $mensaje_vacio = !empty($search_term) ? "No se encontraron usuarios con ese término." : "No hay usuarios registrados.";
                    echo "<tr><td colspan='6' class='text-center p-4 text-muted'>$mensaje_vacio</td></tr>";
                }
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $estado = ($row['ESTADO'] == '1') ? '<span class="status-badge status-active">Activo</span>' : '<span class="status-badge status-inactive">Inactivo</span>';

                    echo "<tr>";
                    echo "<td class='fw-bold text-primary'>" . htmlspecialchars($row['LOGEO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['APEPATERNO'] . ' ' . $row['NOMBRES']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['DOC_IDENTIDAD']) . "</td>";
                    echo "<td><span class='category-badge'>" . htmlspecialchars($row['ROL']) . "</span></td>";
                    echo "<td>" . $estado . "</td>";
                    
                    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
                    echo "<td class='action-buttons'>";
                    
                    // BOTÓN EDITAR (ABRE EL MODAL)
                    echo "<button type='button' 
                                 class='btn-action btn-edit' 
                                 data-bs-toggle='modal' 
                                 data-bs-target='#modalEditarUsuario'
                                 data-id='" . $row['IDUSUARIO'] . "'
                                 data-persona='" . htmlspecialchars($row['APEPATERNO'] . ' ' . $row['NOMBRES'], ENT_QUOTES) . "'
                                 data-logeo='" . htmlspecialchars($row['LOGEO'], ENT_QUOTES) . "'
                                 data-idrol='" . $row['IDTIPO_USUARIO'] . "'
                                 data-estado='" . $row['ESTADO'] . "'
                                 title='Editar'>
                              <i class='bi bi-pencil-square'></i>
                           </button>";
                           
                    if ($row['ESTADO'] == '1') {
                        $onclick_js = "return confirm('¿Seguro que deseas DESACTIVAR la cuenta de " . htmlspecialchars($row['LOGEO']) . "?');";
                        echo "<a href='backend/gestionar_usuario.php?accion=desactivar&id=" . $row['IDUSUARIO'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-delete' onclick=\"" . $onclick_js . "\" title='Desactivar'><i class='bi bi-trash'></i></a>";
                    } else {
                        $onclick_js = "return confirm('¿Seguro que deseas ACTIVAR la cuenta de " . htmlspecialchars($row['LOGEO']) . "?');";
                        echo "<a href='backend/gestionar_usuario.php?accion=activar&id=" . $row['IDUSUARIO'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
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
        $url_base = "usuarios.php" . (!empty($url_params) ? "?" . $url_params : "");
        include 'includes/paginador.php'; 
        ?>
    </div>
</div>

<!-- MODAL: CREAR USUARIO -->
<div class="modal fade" id="modalCrearUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_usuario.php" method="POST">
                <input type="hidden" name="accion" value="crear">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Crear Nueva Cuenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person me-2"></i>Persona:</label>
                        <select name="idpersona" class="form-select" required>
                            <option value="">-- Seleccione Persona (Sin Usuario) --</option>
                            <?php foreach ($personas_libres as $p): ?>
                                <option value="<?php echo $p['IDPERSONA']; ?>">
                                    <?php echo htmlspecialchars($p['APEPATERNO'] . ' ' . $p['NOMBRES'] . ' (' . $p['DOC_IDENTIDAD'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (count($personas_libres) == 0): ?>
                            <small class="text-danger d-block mt-1">Todas las personas activas ya tienen usuario.</small>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-person-badge me-2"></i>Usuario (Logeo):</label>
                            <input type="text" name="logeo" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-key me-2"></i>Contraseña:</label>
                            <input type="password" name="clave" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-shield-lock me-2"></i>Rol:</label>
                        <select name="idtipo_usuario" class="form-select" required>
                            <option value="">-- Seleccione Rol --</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo $r['IDTIPO_USUARIO']; ?>"><?php echo htmlspecialchars($r['NOMUSUARIO']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: EDITAR USUARIO -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_usuario.php" method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="idusuario" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Persona Asignada:</label>
                        <input type="text" id="edit_persona_nombre" class="form-control" disabled readonly>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Usuario (Logeo):</label>
                            <input type="text" id="edit_logeo" name="logeo" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nueva Contraseña:</label>
                            <input type="password" name="clave" class="form-control" placeholder="(Opcional)">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rol:</label>
                            <select id="edit_rol" name="idtipo_usuario" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r['IDTIPO_USUARIO']; ?>"><?php echo htmlspecialchars($r['NOMUSUARIO']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado:</label>
                            <select id="edit_estado" name="estado" class="form-select" required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
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
    const modalEditar = document.getElementById('modalEditarUsuario');
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        modalEditar.querySelector('#edit_id').value = btn.getAttribute('data-id');
        modalEditar.querySelector('#edit_persona_nombre').value = btn.getAttribute('data-persona');
        modalEditar.querySelector('#edit_logeo').value = btn.getAttribute('data-logeo');
        modalEditar.querySelector('#edit_rol').value = btn.getAttribute('data-idrol');
        modalEditar.querySelector('#edit_estado').value = btn.getAttribute('data-estado');
    });
});
</script>

<?php include 'includes/footer.php'; ?>