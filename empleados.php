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
                    OR p.DOC_IDENTIDAD LIKE ?
                    OR ca.NOMCARGO LIKE ?
                    OR u.LOGEO LIKE ?
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
$sql_total = "SELECT COUNT(e.IDEMPLEADO) AS total 
              FROM EMPLEADO e
              INNER JOIN PERSONA p ON e.IDPERSONA = p.IDPERSONA
              INNER JOIN CARGO ca ON e.IDCARGO = ca.IDCARGO
              LEFT JOIN USUARIO u ON p.IDPERSONA = u.IDPERSONA
              WHERE 1=1" . $search_sql;
              
$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// ==================================================
// BLOQUE DE ESTADÍSTICAS (KPIs)
// ==================================================
$stats = [];
$stats['total_empleados'] = $total_registros;

$sql_users = "SELECT COUNT(DISTINCT p.IDPERSONA) as total 
              FROM EMPLEADO e
              JOIN PERSONA p ON e.IDPERSONA = p.IDPERSONA
              JOIN USUARIO u ON p.IDPERSONA = u.IDPERSONA
              WHERE e.ESTADO = '1' AND u.ESTADO = '1'";
$stmt_users = sqlsrv_query($conn, $sql_users);
$stats['con_acceso'] = sqlsrv_fetch_array($stmt_users, SQLSRV_FETCH_ASSOC)['total'];

$sql_salarios = "SELECT SUM(SALARIO) as total FROM EMPLEADO WHERE ESTADO = '1'";
$stmt_salarios = sqlsrv_query($conn, $sql_salarios);
$stats['nomina_total'] = sqlsrv_fetch_array($stmt_salarios, SQLSRV_FETCH_ASSOC)['total'];

// ==================================================
// Lógica para OBTENER LISTA (CON FILTRO Y PAGINACIÓN)
// ==================================================
$sql_empleados = "
    SELECT 
        e.IDEMPLEADO, e.SALARIO, e.FEC_CONTRATACION, e.ESTADO, e.IDCARGO, e.IDCONTRATO,
        p.NOMBRES, p.APEPATERNO, p.DOC_IDENTIDAD,
        ca.NOMCARGO, co.NOMCONTRATO,
        u.IDUSUARIO, u.LOGEO, u.IDTIPO_USUARIO, tu.NOMUSUARIO AS ROL
    FROM EMPLEADO e
    INNER JOIN PERSONA p ON e.IDPERSONA = p.IDPERSONA
    INNER JOIN CARGO ca ON e.IDCARGO = ca.IDCARGO
    INNER JOIN CONTRATO co ON e.IDCONTRATO = co.IDCONTRATO
    LEFT JOIN USUARIO u ON p.IDPERSONA = u.IDPERSONA 
    LEFT JOIN TIPO_USUARIO tu ON u.IDTIPO_USUARIO = tu.IDTIPO_USUARIO
    WHERE 1=1" . $search_sql . "
    ORDER BY p.APEPATERNO ASC, p.NOMBRES ASC
    OFFSET ? ROWS 
    FETCH NEXT ? ROWS ONLY";
                  
$params_paginacion = array_merge($params_search, [$offset, $registros_por_pagina]);
$stmt_empleados = sqlsrv_query($conn, $sql_empleados, $params_paginacion);
if ($stmt_empleados === false) { die(print_r(sqlsrv_errors(), true)); }

// ==================================================
// CARGAR DATOS PARA LOS SELECTS (CARGOS, CONTRATOS, ROLES)
// ==================================================
$sql_cargos = "SELECT IDCARGO, NOMCARGO FROM CARGO WHERE ESTADO = '1' ORDER BY NOMCARGO";
$stmt_cargos = sqlsrv_query($conn, $sql_cargos);
$cargos = [];
while ($row = sqlsrv_fetch_array($stmt_cargos, SQLSRV_FETCH_ASSOC)) $cargos[] = $row;

$sql_contratos = "SELECT IDCONTRATO, NOMCONTRATO FROM CONTRATO WHERE ESTADO = '1' ORDER BY NOMCONTRATO";
$stmt_contratos = sqlsrv_query($conn, $sql_contratos);
$contratos = [];
while ($row = sqlsrv_fetch_array($stmt_contratos, SQLSRV_FETCH_ASSOC)) $contratos[] = $row;

$sql_tipos = "SELECT IDTIPO_USUARIO, NOMUSUARIO FROM TIPO_USUARIO WHERE ESTADO = '1' ORDER BY NOMUSUARIO";
$stmt_tipos = sqlsrv_query($conn, $sql_tipos);
$tipos_usuario = [];
while ($row = sqlsrv_fetch_array($stmt_tipos, SQLSRV_FETCH_ASSOC)) $tipos_usuario[] = $row;
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="page-title"><i class="bi bi-person-badge me-2"></i> Gestión de Empleados</h1>
            <p class="page-description">Administra el personal que tiene acceso al sistema.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="crear_empleado.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Registrar Empleado</a>
        </div>
    </div>
</div>

<?php
if (isset($_SESSION['mensaje_empleado'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_empleado'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_empleado']);
}
if (isset($_SESSION['error_empleado'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_empleado'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_empleado']);
}
?>

<div class="row g-3 mb-4">
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft"><i class="bi bi-person-lines-fill"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_empleados']; ?></h3>
                <p>Empleados (Filtrados)</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-success-soft"><i class="bi bi-person-check"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['con_acceso']; ?></h3>
                <p>Usuarios con Acceso</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-info-soft"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-content-mini">
                <h3>S/ <?php echo number_format($stats['nomina_total'], 2); ?></h3>
                <p>Nómina Mensual Total</p>
            </div>
        </div>
    </div>
</div>

<div class="search-card mb-4">
    <form action="empleados.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input" 
                   value="<?php echo htmlspecialchars($search_term); ?>"
                   placeholder="Buscar por nombre, documento, cargo o usuario...">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="empleados.php" class="btn-clear-search" title="Limpiar búsqueda"><i class="bi bi-x-circle"></i></a>
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
        <h5><i class="bi bi-list-ul me-2"></i> Listado de Personal</h5>
        <span class="pagination-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table data-table mb-0"> 
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Documento</th>
                    <th>Cargo</th>
                    <th>Salario</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros == 0) {
                    $mensaje_vacio = !empty($search_term) ? "No se encontraron empleados con el término '" . htmlspecialchars($search_term) . "'." : "No hay empleados registrados.";
                    echo "<tr><td colspan='8' class='text-center p-4 text-muted'>$mensaje_vacio</td></tr>";
                }
                while ($row = sqlsrv_fetch_array($stmt_empleados, SQLSRV_FETCH_ASSOC)) {
                    $estado = ($row['ESTADO'] == '1') ? '<span class="status-badge status-active"><i class="bi bi-check-circle"></i> Activo</span>' : '<span class="status-badge status-inactive"><i class="bi bi-x-circle"></i> Inactivo</span>';
                    
                    // Preparamos los datos para el modal
                    $tiene_usuario = !empty($row['IDUSUARIO']) ? '1' : '0';
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['NOMBRES'] . ' ' . $row['APEPATERNO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['DOC_IDENTIDAD']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['NOMCARGO']) . "</td>";
                    echo "<td>S/ " . number_format($row['SALARIO'], 2) . "</td>";
                    echo "<td>" . htmlspecialchars($row['LOGEO'] ?? '---') . "</td>";
                    echo "<td>" . htmlspecialchars($row['ROL'] ?? '---') . "</td>";
                    echo "<td>" . $estado . "</td>";
                    
                    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
                    echo "<td class='action-buttons'>";
                    
                    // BOTÓN EDITAR (ABRE EL MODAL)
                    echo "<button type='button' 
                                 class='btn-action btn-edit' 
                                 data-bs-toggle='modal' 
                                 data-bs-target='#modalEditarEmpleado'
                                 data-id='" . $row['IDEMPLEADO'] . "'
                                 data-nombre='" . htmlspecialchars($row['NOMBRES'] . ' ' . $row['APEPATERNO'], ENT_QUOTES) . "'
                                 data-idcargo='" . $row['IDCARGO'] . "'
                                 data-idcontrato='" . $row['IDCONTRATO'] . "'
                                 data-salario='" . $row['SALARIO'] . "'
                                 data-fecha='" . ($row['FEC_CONTRATACION'] ? $row['FEC_CONTRATACION']->format('Y-m-d') : '') . "'
                                 data-estado='" . $row['ESTADO'] . "'
                                 data-tieneusuario='" . $tiene_usuario . "'
                                 data-idusuario='" . ($row['IDUSUARIO'] ?? '') . "'
                                 data-logeo='" . htmlspecialchars($row['LOGEO'] ?? '', ENT_QUOTES) . "'
                                 data-idrol='" . ($row['IDTIPO_USUARIO'] ?? '') . "'
                                 title='Editar'>
                              <i class='bi bi-pencil-square'></i>
                           </button>";

                    if ($row['ESTADO'] == '1') {
                        $onclick_js = "return confirm('¿Seguro que deseas DESACTIVAR a " . htmlspecialchars($row['NOMBRES']) . "?');";
                        echo "<a href='backend/gestionar_empleado.php?accion=desactivar&id=" . $row['IDEMPLEADO'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-delete' onclick=\"" . $onclick_js . "\" title='Desactivar'><i class='bi bi-trash'></i></a>";
                    } else {
                        $onclick_js = "return confirm('¿Seguro que deseas ACTIVAR a " . htmlspecialchars($row['NOMBRES']) . "?');";
                        echo "<a href='backend/gestionar_empleado.php?accion=activar&id=" . $row['IDEMPLEADO'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
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
        $url_base = "empleados.php" . (!empty($url_params) ? "?" . $url_params : "");
        include 'includes/paginador.php'; 
        ?>
    </div>
</div>

<div class="modal fade" id="modalEditarEmpleado" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_empleado.php" method="POST">
                <input type="hidden" name="accion" value="editar_empleado">
                <input type="hidden" name="idempleado" id="edit_id">
                <input type="hidden" name="idusuario" id="edit_idusuario">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">
                        <i class="bi bi-pencil-square me-2"></i>Editar Empleado: <span id="edit_nombre_display" class="fw-bold"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <h6 class="text-primary mb-3">Datos Laborales</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_idcargo" class="form-label">Cargo:</label>
                            <select id="edit_idcargo" name="idcargo" class="form-select" required>
                                <?php foreach ($cargos as $c): ?>
                                <option value="<?php echo $c['IDCARGO']; ?>"><?php echo htmlspecialchars($c['NOMCARGO']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_idcontrato" class="form-label">Tipo de Contrato:</label>
                            <select id="edit_idcontrato" name="idcontrato" class="form-select" required>
                                <?php foreach ($contratos as $c): ?>
                                <option value="<?php echo $c['IDCONTRATO']; ?>"><?php echo htmlspecialchars($c['NOMCONTRATO']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_salario" class="form-label">Salario (S/):</label>
                            <input type="number" id="edit_salario" name="salario" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_fec_contratacion" class="form-label">Fecha Contratación:</label>
                            <input type="date" id="edit_fec_contratacion" name="fec_contratacion" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_estado" class="form-label">Estado:</label>
                            <select id="edit_estado" name="estado_empleado" class="form-select" required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-primary mb-3">Acceso al Sistema</h6>
                    <div id="seccion_usuario" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_logeo" class="form-label">Usuario (Logeo):</label>
                                <input type="text" id="edit_logeo" name="logeo" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_idrol" class="form-label">Rol de Usuario:</label>
                                <select id="edit_idrol" name="idtipo_usuario" class="form-select">
                                    <?php foreach ($tipos_usuario as $t): ?>
                                    <option value="<?php echo $t['IDTIPO_USUARIO']; ?>"><?php echo htmlspecialchars($t['NOMUSUARIO']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_clave" class="form-label">Nueva Contraseña:</label>
                            <input type="password" id="edit_clave" name="clave" class="form-control" placeholder="Dejar en blanco para no cambiar">
                        </div>
                    </div>
                    <div id="seccion_sin_usuario" class="alert alert-secondary" style="display: none;">
                        Este empleado no tiene una cuenta de usuario asociada.
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEditar = document.getElementById('modalEditarEmpleado');
    
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        // Extraer datos
        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        const idcargo = button.getAttribute('data-idcargo');
        const idcontrato = button.getAttribute('data-idcontrato');
        const salario = button.getAttribute('data-salario');
        const fecha = button.getAttribute('data-fecha');
        const estado = button.getAttribute('data-estado');
        
        const tieneUsuario = button.getAttribute('data-tieneusuario') === '1';
        const idUsuario = button.getAttribute('data-idusuario');
        const logeo = button.getAttribute('data-logeo');
        const idRol = button.getAttribute('data-idrol');
        
        // Llenar campos básicos
        modalEditar.querySelector('#edit_id').value = id;
        modalEditar.querySelector('#edit_nombre_display').textContent = nombre;
        modalEditar.querySelector('#edit_idcargo').value = idcargo;
        modalEditar.querySelector('#edit_idcontrato').value = idcontrato;
        // Formatear salario (quitar .00 extra si es necesario)
        modalEditar.querySelector('#edit_salario').value = parseFloat(salario).toFixed(2);
        modalEditar.querySelector('#edit_fec_contratacion').value = fecha;
        modalEditar.querySelector('#edit_estado').value = estado;
        
        // Lógica de Usuario
        const divUsuario = modalEditar.querySelector('#seccion_usuario');
        const divSinUsuario = modalEditar.querySelector('#seccion_sin_usuario');
        const inputIdUsuario = modalEditar.querySelector('#edit_idusuario');
        const inputLogeo = modalEditar.querySelector('#edit_logeo');
        const inputRol = modalEditar.querySelector('#edit_idrol');
        
        if (tieneUsuario) {
            divUsuario.style.display = 'block';
            divSinUsuario.style.display = 'none';
            
            inputIdUsuario.value = idUsuario;
            inputLogeo.value = logeo;
            inputRol.value = idRol;
            
            inputLogeo.setAttribute('required', 'required');
            inputRol.setAttribute('required', 'required');
        } else {
            divUsuario.style.display = 'none';
            divSinUsuario.style.display = 'block';
            
            inputIdUsuario.value = '';
            inputLogeo.value = '';
            inputLogeo.removeAttribute('required');
            inputRol.removeAttribute('required');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>