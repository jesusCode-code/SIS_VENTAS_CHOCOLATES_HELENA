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
    // Buscamos por Nombre completo o Documento
    $search_sql = " AND (
                    (p.NOMBRES + ' ' + p.APEPATERNO + ' ' + p.APEMATERNO) LIKE ? 
                    OR p.DOC_IDENTIDAD LIKE ?
                    OR p.CORREO LIKE ?
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

// 4. Contar el TOTAL de registros (CON FILTRO)
$sql_total = "SELECT COUNT(IDPERSONA) AS total FROM PERSONA p WHERE 1=1" . $search_sql;
$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// ==================================================
// BLOQUE DE ESTADÍSTICAS (KPIs)
// ==================================================
$stats = [];
$stats['total_personas'] = $total_registros;

// Total DNI
$sql_dni = "SELECT COUNT(*) as total 
            FROM PERSONA p 
            JOIN TIPO_DOCUMENTO td ON p.IDTIPO_DOCUMENTO = td.IDTIPO_DOCUMENTO 
            WHERE td.NOMDOCUMENTO = 'DNI' AND p.ESTADO = '1'";
$stmt_dni = sqlsrv_query($conn, $sql_dni);
$stats['total_dni'] = sqlsrv_fetch_array($stmt_dni, SQLSRV_FETCH_ASSOC)['total'];

// Total Otros Documentos
$stats['total_otros'] = $stats['total_personas'] - $stats['total_dni'];

// ==================================================
// Lógica para OBTENER LISTA
// ==================================================
$sql = "SELECT 
            p.IDPERSONA, p.NOMBRES, p.APEPATERNO, p.APEMATERNO, 
            p.DOC_IDENTIDAD, p.CELULAR, p.CORREO, p.DIRECCION, p.ESTADO,
            p.FEC_NACIMIENTO, p.EST_CIVIL,
            td.IDTIPO_DOCUMENTO, td.NOMDOCUMENTO,
            d.IDDISTRITO, d.NOMDISTRITO, prov.NOMPROVINCIA
        FROM PERSONA p
        LEFT JOIN TIPO_DOCUMENTO td ON p.IDTIPO_DOCUMENTO = td.IDTIPO_DOCUMENTO
        LEFT JOIN DISTRITO d ON p.IDDISTRITO = d.IDDISTRITO
        LEFT JOIN PROVINCIA prov ON d.IDPROVINCIA = prov.IDPROVINCIA
        WHERE 1=1 " . $search_sql . "
        ORDER BY p.APEPATERNO ASC, p.NOMBRES ASC
        OFFSET ? ROWS 
        FETCH NEXT ? ROWS ONLY";
                  
$params_paginacion = array_merge($params_search, [$offset, $registros_por_pagina]);
$stmt = sqlsrv_query($conn, $sql, $params_paginacion);
if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }

// 7. Cargar Listas para Modales
// Tipos de Documento
$sql_docs = "SELECT IDTIPO_DOCUMENTO, NOMDOCUMENTO FROM TIPO_DOCUMENTO WHERE ESTADO = '1' ORDER BY NOMDOCUMENTO";
$stmt_docs = sqlsrv_query($conn, $sql_docs);
$tipos_documento = [];
while ($row = sqlsrv_fetch_array($stmt_docs, SQLSRV_FETCH_ASSOC)) $tipos_documento[] = $row;

// Distritos
$sql_dist = "SELECT d.IDDISTRITO, d.NOMDISTRITO, p.NOMPROVINCIA 
             FROM DISTRITO d JOIN PROVINCIA p ON d.IDPROVINCIA = p.IDPROVINCIA 
             WHERE d.ESTADO = '1' ORDER BY p.NOMPROVINCIA, d.NOMDISTRITO";
$stmt_dist = sqlsrv_query($conn, $sql_dist);
$distritos = [];
while ($row = sqlsrv_fetch_array($stmt_dist, SQLSRV_FETCH_ASSOC)) $distritos[] = $row;
?>

<!-- Header de Página -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="page-title"><i class="bi bi-people-fill me-2"></i> Directorio de Personas</h1>
            <p class="page-description">Gestión maestra de todas las personas registradas (clientes, empleados, contactos).</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearPersona">
                <i class="bi bi-person-plus me-2"></i>Nueva Persona
            </button>
        </div>
    </div>
</div>

<!-- Mensajes -->
<?php
if (isset($_SESSION['mensaje_persona'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_persona'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_persona']);
}
if (isset($_SESSION['error_persona'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_persona'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_persona']);
}
?>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft"><i class="bi bi-people"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_personas']; ?></h3>
                <p>Personas Registradas</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-info-soft"><i class="bi bi-person-vcard"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_dni']; ?></h3>
                <p>Con DNI</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-warning-soft"><i class="bi bi-globe"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_otros']; ?></h3>
                <p>Otros Documentos</p>
            </div>
        </div>
    </div>
</div>

<!-- Buscador -->
<div class="search-card mb-4">
    <form action="personas.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input" 
                   value="<?php echo htmlspecialchars($search_term); ?>"
                   placeholder="Buscar por nombre, apellido, DNI o correo...">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="personas.php" class="btn-clear-search" title="Limpiar búsqueda"><i class="bi bi-x-circle"></i></a>
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
        <h5><i class="bi bi-list-ul me-2"></i> Listado General</h5>
        <span class="pagination-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table data-table mb-0">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Documento</th>
                    <th>Ubicación</th>
                    <th>Contacto</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros == 0) {
                    $mensaje_vacio = !empty($search_term) ? "No se encontraron personas." : "No hay personas registradas.";
                    echo "<tr><td colspan='6' class='text-center p-4 text-muted'>$mensaje_vacio</td></tr>";
                }
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $estado = ($row['ESTADO'] == '1') ? '<span class="status-badge status-active">Activo</span>' : '<span class="status-badge status-inactive">Inactivo</span>';
                    $nombre_completo = htmlspecialchars($row['APEPATERNO'] . ' ' . $row['APEMATERNO'] . ', ' . $row['NOMBRES']);
                    
                    echo "<tr>";
                    echo "<td><strong>" . $nombre_completo . "</strong></td>";
                    echo "<td>" . htmlspecialchars($row['NOMDOCUMENTO'] . ': ' . $row['DOC_IDENTIDAD']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['NOMDISTRITO'] ?? '---') . "</td>";
                    echo "<td>" . htmlspecialchars($row['CELULAR'] ?? $row['CORREO'] ?? '---') . "</td>";
                    echo "<td>" . $estado . "</td>";
                    
                    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
                    echo "<td class='action-buttons'>";
                    
                    // BOTÓN EDITAR (ABRE MODAL)
                    echo "<button type='button' class='btn-action btn-edit' data-bs-toggle='modal' data-bs-target='#modalEditarPersona'
                            data-id='" . $row['IDPERSONA'] . "'
                            data-nombres='" . htmlspecialchars($row['NOMBRES'], ENT_QUOTES) . "'
                            data-apep='" . htmlspecialchars($row['APEPATERNO'], ENT_QUOTES) . "'
                            data-apem='" . htmlspecialchars($row['APEMATERNO'], ENT_QUOTES) . "'
                            data-iddoc='" . $row['IDTIPO_DOCUMENTO'] . "'
                            data-numdoc='" . htmlspecialchars($row['DOC_IDENTIDAD'], ENT_QUOTES) . "'
                            data-iddist='" . $row['IDDISTRITO'] . "'
                            data-dir='" . htmlspecialchars($row['DIRECCION'] ?? '', ENT_QUOTES) . "'
                            data-cel='" . htmlspecialchars($row['CELULAR'] ?? '', ENT_QUOTES) . "'
                            data-correo='" . htmlspecialchars($row['CORREO'] ?? '', ENT_QUOTES) . "'
                            data-fecnac='" . ($row['FEC_NACIMIENTO'] ? $row['FEC_NACIMIENTO']->format('Y-m-d') : '') . "'
                            data-ecivil='" . $row['EST_CIVIL'] . "'
                            data-estado='" . $row['ESTADO'] . "'
                            title='Editar'>
                            <i class='bi bi-pencil-square'></i>
                          </button>";
                          
                    // BOTONES ACTIVAR/DESACTIVAR
                    if ($row['ESTADO'] == '1') {
                        $onclick_js = "return confirm('¿Seguro que deseas DESACTIVAR a " . $nombre_completo . "?');";
                        echo "<a href='backend/gestionar_persona.php?accion=desactivar&id=" . $row['IDPERSONA'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-delete' onclick=\"" . $onclick_js . "\"><i class='bi bi-trash'></i></a>";
                    } else {
                        $onclick_js = "return confirm('¿Seguro que deseas ACTIVAR a " . $nombre_completo . "?');";
                        echo "<a href='backend/gestionar_persona.php?accion=activar&id=" . $row['IDPERSONA'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-activate' onclick=\"" . $onclick_js . "\"><i class='bi bi-check-circle'></i></a>";
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
        $url_base = "personas.php" . (!empty($url_params) ? "?" . $url_params : "");
        include 'includes/paginador.php'; 
        ?>
    </div>
</div>

<!-- MODAL: CREAR PERSONA -->
<div class="modal fade" id="modalCrearPersona" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_persona.php" method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Nueva Persona</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Nombres:</label><input type="text" name="nombres" class="form-control" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Apellido P.:</label><input type="text" name="apepaterno" class="form-control" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Apellido M.:</label><input type="text" name="apematerno" class="form-control" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo Doc:</label>
                            <select name="idtipo_documento" class="form-select" required>
                                <?php foreach ($tipos_documento as $doc) echo "<option value='{$doc['IDTIPO_DOCUMENTO']}'>{$doc['NOMDOCUMENTO']}</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3"><label class="form-label">Número Doc:</label><input type="text" name="doc_identidad" class="form-control" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Celular:</label><input type="text" name="celular" class="form-control" pattern="[0-9]{9}"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Correo:</label><input type="email" name="correo" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Distrito:</label>
                            <select name="iddistrito" class="form-select" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($distritos as $d) echo "<option value='{$d['IDDISTRITO']}'>{$d['NOMPROVINCIA']} - {$d['NOMDISTRITO']}</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3"><label class="form-label">Dirección:</label><input type="text" name="direccion" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Fecha Nac:</label><input type="date" name="fec_nacimiento" class="form-control"></div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado Civil:</label>
                            <select name="est_civil" class="form-select">
                                <option value="S">Soltero(a)</option><option value="C">Casado(a)</option>
                                <option value="V">Viudo(a)</option><option value="D">Divorciado(a)</option>
                            </select>
                        </div>
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

<!-- MODAL: EDITAR PERSONA -->
<div class="modal fade" id="modalEditarPersona" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_persona.php" method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="idpersona" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Persona</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Mismos campos que Crear, con IDs para JS -->
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Nombres:</label><input type="text" name="nombres" id="edit_nombres" class="form-control" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Apellido P.:</label><input type="text" name="apepaterno" id="edit_apepaterno" class="form-control" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Apellido M.:</label><input type="text" name="apematerno" id="edit_apematerno" class="form-control" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo Doc:</label>
                            <select name="idtipo_documento" id="edit_iddoc" class="form-select" required>
                                <?php foreach ($tipos_documento as $doc) echo "<option value='{$doc['IDTIPO_DOCUMENTO']}'>{$doc['NOMDOCUMENTO']}</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3"><label class="form-label">Número Doc:</label><input type="text" name="doc_identidad" id="edit_numdoc" class="form-control" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Celular:</label><input type="text" name="celular" id="edit_cel" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Correo:</label><input type="email" name="correo" id="edit_correo" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Distrito:</label>
                            <select name="iddistrito" id="edit_iddist" class="form-select" required>
                                <?php foreach ($distritos as $d) echo "<option value='{$d['IDDISTRITO']}'>{$d['NOMPROVINCIA']} - {$d['NOMDISTRITO']}</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3"><label class="form-label">Dirección:</label><input type="text" name="direccion" id="edit_dir" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Fecha Nac:</label><input type="date" name="fec_nacimiento" id="edit_fecnac" class="form-control"></div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Estado Civil:</label>
                            <select name="est_civil" id="edit_ecivil" class="form-select">
                                <option value="S">Soltero(a)</option><option value="C">Casado(a)</option>
                                <option value="V">Viudo(a)</option><option value="D">Divorciado(a)</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Estado:</label>
                            <select name="estado" id="edit_estado" class="form-select" required>
                                <option value="1">Activo</option><option value="0">Inactivo</option>
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
    const modalEditar = document.getElementById('modalEditarPersona');
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        modalEditar.querySelector('#edit_id').value = btn.getAttribute('data-id');
        modalEditar.querySelector('#edit_nombres').value = btn.getAttribute('data-nombres');
        modalEditar.querySelector('#edit_apepaterno').value = btn.getAttribute('data-apep');
        modalEditar.querySelector('#edit_apematerno').value = btn.getAttribute('data-apem');
        modalEditar.querySelector('#edit_iddoc').value = btn.getAttribute('data-iddoc');
        modalEditar.querySelector('#edit_numdoc').value = btn.getAttribute('data-numdoc');
        modalEditar.querySelector('#edit_iddist').value = btn.getAttribute('data-iddist');
        modalEditar.querySelector('#edit_dir').value = btn.getAttribute('data-dir');
        modalEditar.querySelector('#edit_cel').value = btn.getAttribute('data-cel');
        modalEditar.querySelector('#edit_correo').value = btn.getAttribute('data-correo');
        modalEditar.querySelector('#edit_fecnac').value = btn.getAttribute('data-fecnac');
        modalEditar.querySelector('#edit_ecivil').value = btn.getAttribute('data-ecivil');
        modalEditar.querySelector('#edit_estado').value = btn.getAttribute('data-estado');
    });
});
</script>

<?php include 'includes/footer.php'; ?>