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
                    e.RAZON_SOCIAL LIKE ? 
                    OR (p.NOMBRES + ' ' + p.APEPATERNO) LIKE ?
                    OR ec.CARGO_CONTACTO LIKE ?
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
$sql_total = "SELECT COUNT(ec.IDCONTACTO) AS total 
              FROM EMPRESA_CONTACTO ec
              JOIN PERSONA p ON ec.IDPERSONA = p.IDPERSONA
              JOIN EMPRESA e ON ec.IDEMPRESA = e.IDEMPRESA
              WHERE 1=1" . $search_sql; 
              
$stmt_total = sqlsrv_query($conn, $sql_total, $params_search);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// ==================================================
// BLOQUE DE ESTADÍSTICAS (KPIs)
// ==================================================
$stats = [];
$stats['total_contactos'] = $total_registros; 

$sql_emp = "SELECT COUNT(DISTINCT IDEMPRESA) as total FROM EMPRESA_CONTACTO WHERE ESTADO = '1'";
$stmt_emp = sqlsrv_query($conn, $sql_emp);
$stats['total_empresas_con_contacto'] = sqlsrv_fetch_array($stmt_emp, SQLSRV_FETCH_ASSOC)['total'];

$sql_per = "SELECT COUNT(DISTINCT IDPERSONA) as total FROM EMPRESA_CONTACTO WHERE ESTADO = '1'";
$stmt_per = sqlsrv_query($conn, $sql_per);
$stats['total_personas_contacto'] = sqlsrv_fetch_array($stmt_per, SQLSRV_FETCH_ASSOC)['total'];

// ==================================================
// Lógica para OBTENER LISTA
// ==================================================
$sql = "SELECT 
            ec.IDCONTACTO, ec.CARGO_CONTACTO, ec.ESTADO,
            p.NOMBRES, p.APEPATERNO,
            e.RAZON_SOCIAL
        FROM EMPRESA_CONTACTO ec
        JOIN PERSONA p ON ec.IDPERSONA = p.IDPERSONA
        JOIN EMPRESA e ON ec.IDEMPRESA = e.IDEMPRESA
        WHERE 1=1 " . $search_sql . "
        ORDER BY e.RAZON_SOCIAL, p.APEPATERNO
        OFFSET ? ROWS 
        FETCH NEXT ? ROWS ONLY";
                  
$params_paginacion = array_merge($params_search, [$offset, $registros_por_pagina]);
$stmt = sqlsrv_query($conn, $sql, $params_paginacion);
if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }

// 7. Lógica para OBTENER PERSONAS (para el modal)
$sql_personas = "SELECT IDPERSONA, NOMBRES, APEPATERNO, DOC_IDENTIDAD FROM PERSONA WHERE ESTADO = '1' ORDER BY APEPATERNO";
$stmt_personas = sqlsrv_query($conn, $sql_personas);
$personas = [];
while ($row_p = sqlsrv_fetch_array($stmt_personas, SQLSRV_FETCH_ASSOC)) $personas[] = $row_p;

// 8. Lógica para OBTENER EMPRESAS (para el modal)
$sql_empresas = "SELECT IDEMPRESA, RAZON_SOCIAL, RUC FROM EMPRESA WHERE ESTADO = '1' ORDER BY RAZON_SOCIAL";
$stmt_empresas = sqlsrv_query($conn, $sql_empresas);
$empresas = [];
while ($row_e = sqlsrv_fetch_array($stmt_empresas, SQLSRV_FETCH_ASSOC)) $empresas[] = $row_e;
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="page-title"><i class="bi bi-person-lines-fill me-2"></i> Contactos de Empresa</h1>
            <p class="page-description">Vincula a una persona como contacto oficial de una empresa.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearContacto">
                <i class="bi bi-plus-circle me-2"></i>Añadir Contacto
            </button>
        </div>
    </div>
</div>

<?php
if (isset($_SESSION['mensaje_contacto_emp'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert">
            <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_contacto_emp'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['mensaje_contacto_emp']);
}
if (isset($_SESSION['error_contacto_emp'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_contacto_emp'] . 
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_contacto_emp']);
}
?>

<div class="row g-3 mb-4">
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-primary-soft"><i class="bi bi-person-lines-fill"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_contactos']; ?></h3>
                <p>Vínculos (Filtrados)</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-success-soft"><i class="bi bi-building"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_empresas_con_contacto']; ?></h3>
                <p>Empresas con Contacto</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card-mini">
            <div class="stat-icon-mini bg-info-soft"><i class="bi bi-people"></i></div>
            <div class="stat-content-mini">
                <h3><?php echo $stats['total_personas_contacto']; ?></h3>
                <p>Personas de Contacto</p>
            </div>
        </div>
    </div>
</div>

<div class="search-card mb-4">
    <form action="contactos_empresa.php" method="GET" class="search-form">
        <div class="search-input-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input" 
                   value="<?php echo htmlspecialchars($search_term); ?>"
                   placeholder="Buscar por empresa, persona o cargo...">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="contactos_empresa.php" class="btn-clear-search" title="Limpiar búsqueda"><i class="bi bi-x-circle"></i></a>
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
        <h5><i class="bi bi-list-ul me-2"></i> Listado de Contactos</h5>
        <span class="pagination-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table data-table mb-0">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Persona (Contacto)</th>
                    <th>Cargo del Contacto</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros == 0) {
                    $mensaje_vacio = !empty($search_term) ? "No se encontraron contactos con el término '" . htmlspecialchars($search_term) . "'." : "No hay contactos registrados.";
                    echo "<tr><td colspan='5' class='text-center p-4 text-muted'>$mensaje_vacio</td></tr>";
                }
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $estado = ($row['ESTADO'] == '1') ? '<span class="status-badge status-active"><i class="bi bi-check-circle"></i> Activo</span>' : '<span class="status-badge status-inactive"><i class="bi bi-x-circle"></i> Inactivo</span>';

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['RAZON_SOCIAL']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['APEPATERNO'] . ' ' . $row['NOMBRES']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['CARGO_CONTACTO']) . "</td>";
                    echo "<td>" . $estado . "</td>";
                    
                    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
                    echo "<td class='action-buttons'>";
                    
                    // ===================================
                    // BOTÓN EDITAR (ABRE EL MODAL)
                    // ===================================
                    echo "<button type='button' 
                                 class='btn-action btn-edit' 
                                 data-bs-toggle='modal' 
                                 data-bs-target='#modalEditarContacto'
                                 data-id='" . $row['IDCONTACTO'] . "'
                                 data-empresa='" . htmlspecialchars($row['RAZON_SOCIAL'], ENT_QUOTES) . "'
                                 data-persona='" . htmlspecialchars($row['APEPATERNO'] . ' ' . $row['NOMBRES'], ENT_QUOTES) . "'
                                 data-cargo='" . htmlspecialchars($row['CARGO_CONTACTO'], ENT_QUOTES) . "'
                                 data-estado='" . $row['ESTADO'] . "'
                                 title='Editar'>
                              <i class='bi bi-pencil-square'></i>
                           </button>";
                           
                    if ($row['ESTADO'] == '1') {
                        $onclick_js = "return confirm('¿Seguro que deseas DESACTIVAR este vínculo?');";
                        echo "<a href='backend/gestionar_contacto_empresa.php?accion=desactivar&id=" . $row['IDCONTACTO'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
                               class='btn-action btn-delete' onclick=\"" . $onclick_js . "\" title='Desactivar'><i class='bi bi-trash'></i></a>";
                    } else {
                        $onclick_js = "return confirm('¿Seguro que deseas ACTIVAR este vínculo?');";
                        echo "<a href='backend/gestionar_contacto_empresa.php?accion=activar&id=" . $row['IDCONTACTO'] . "&pagina=" . $pagina_actual . $search_url_param . "' 
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
        $url_base = "contactos_empresa.php" . (!empty($url_params) ? "?" . $url_params : "");
        include 'includes/paginador.php'; 
        ?>
    </div>
</div>

<div class="modal fade" id="modalCrearContacto" tabindex="-1" aria-labelledby="modalCrearLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_contacto_empresa.php" method="POST">
                <input type="hidden" name="accion" value="crear">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearLabel">
                        <i class="bi bi-plus-circle me-2"></i>Añadir Nuevo Contacto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="idempresa" class="form-label"><i class="bi bi-building me-2"></i>Empresa:</label>
                        <select id="idempresa" name="idempresa" class="form-select" required>
                            <option value="">-- Seleccione Empresa --</option>
                            <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp['IDEMPRESA']; ?>"><?php echo htmlspecialchars($emp['RAZON_SOCIAL']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="idpersona" class="form-label"><i class="bi bi-person me-2"></i>Persona (Contacto):</label>
                        <select id="idpersona" name="idpersona" class="form-select" required>
                            <option value="">-- Seleccione Persona --</option>
                            <?php foreach ($personas as $per): ?>
                            <option value="<?php echo $per['IDPERSONA']; ?>"><?php echo htmlspecialchars($per['APEPATERNO'] . ' ' . $per['NOMBRES']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cargo_contacto" class="form-label"><i class="bi bi-briefcase me-2"></i>Cargo del Contacto:</label>
                        <input type="text" id="cargo_contacto" name="cargo_contacto" class="form-control" placeholder="Ej: Gerente de Compras" required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Vincular Contacto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarContacto" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="backend/gestionar_contacto_empresa.php" method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="idcontacto" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">
                        <i class="bi bi-pencil-square me-2"></i>Editar Contacto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Empresa:</label>
                        <input type="text" id="edit_empresa_nombre" class="form-control" disabled readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Persona (Contacto):</label>
                        <input type="text" id="edit_persona_nombre" class="form-control" disabled readonly>
                    </div>

                    <div class="mb-3">
                        <label for="edit_cargo" class="form-label"><i class="bi bi-briefcase me-2"></i>Cargo del Contacto:</label>
                        <input type="text" id="edit_cargo" name="cargo_contacto" class="form-control" required>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEditar = document.getElementById('modalEditarContacto');
    
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        const id = button.getAttribute('data-id');
        const empresa = button.getAttribute('data-empresa');
        const persona = button.getAttribute('data-persona');
        const cargo = button.getAttribute('data-cargo');
        const estado = button.getAttribute('data-estado');
        
        modalEditar.querySelector('#edit_id').value = id;
        modalEditar.querySelector('#edit_empresa_nombre').value = empresa;
        modalEditar.querySelector('#edit_persona_nombre').value = persona;
        modalEditar.querySelector('#edit_cargo').value = cargo;
        modalEditar.querySelector('#edit_estado').value = estado;
    });
});
</script>

<?php include 'includes/footer.php'; ?>