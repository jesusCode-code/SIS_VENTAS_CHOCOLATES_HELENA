<?php
// 1. Seguridad y Conexión
include 'includes/seguridad.php';
include 'includes/header.php';
include 'includes/conexion.php';

// 2. Validar ID de Cliente
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: clientes.php?pagina=1"); exit;
}
$idcliente = $_GET['id'];

// 3. Cargar Datos del Cliente y Persona
$sql_data = "SELECT c.IDCLIENTE, c.ESTADO AS ESTADO_CLIENTE, p.* FROM CLIENTE c
             JOIN PERSONA p ON c.IDPERSONA = p.IDPERSONA
             WHERE c.IDCLIENTE = ?";
$params = array($idcliente);
$stmt_data = sqlsrv_query($conn, $sql_data, $params);
if ($stmt_data === false) { die(print_r(sqlsrv_errors(), true)); }
$cliente = sqlsrv_fetch_array($stmt_data, SQLSRV_FETCH_ASSOC);

if (!$cliente) {
    $_SESSION['error_cliente'] = "Error: El cliente no existe.";
    header("Location: clientes.php?pagina=1"); exit;
}
$idpersona = $cliente['IDPERSONA'];

// 4. Cargar Tipos de Documento
$sql_docs = "SELECT IDTIPO_DOCUMENTO, NOMDOCUMENTO FROM TIPO_DOCUMENTO WHERE ESTADO = '1' ORDER BY NOMDOCUMENTO";
$stmt_docs = sqlsrv_query($conn, $sql_docs);
$tipos_documento = [];
while ($row = sqlsrv_fetch_array($stmt_docs, SQLSRV_FETCH_ASSOC)) $tipos_documento[] = $row;

// 5. Cargar Distritos
$sql_dist = "SELECT d.IDDISTRITO, d.NOMDISTRITO, p.NOMPROVINCIA 
             FROM DISTRITO d
             JOIN PROVINCIA p ON d.IDPROVINCIA = p.IDPROVINCIA
             WHERE d.ESTADO = '1' AND p.ESTADO = '1'
             ORDER BY p.NOMPROVINCIA, d.NOMDISTRITO";
$stmt_dist = sqlsrv_query($conn, $sql_dist);
$distritos = [];
while ($row = sqlsrv_fetch_array($stmt_dist, SQLSRV_FETCH_ASSOC)) $distritos[] = $row;
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="clientes.php">Clientes</a></li>
        <li class="breadcrumb-item active">Editar Cliente (Persona)</li>
    </ol>
</nav>

<div class="form-page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="form-page-title">
                <i class="bi bi-pencil-square me-2"></i>
                Editar Cliente (Persona)
            </h1>
            <p class="form-page-subtitle">
                Modificando a: <strong><?php echo htmlspecialchars($cliente['NOMBRES'] . ' ' . $cliente['APEPATERNO']); ?></strong>
            </p>
        </div>
        <a href="clientes.php?pagina=1" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver al Listado
        </a>
    </div>
</div>

<div class="form-card">
    <form action="backend/gestionar_cliente.php" method="POST">
        <input type="hidden" name="accion" value="editar_persona">
        <input type="hidden" name="idcliente" value="<?php echo $idcliente; ?>">
        <input type="hidden" name="idpersona" value="<?php echo $idpersona; ?>">
        
        <div class="form-section">
            <h5 class="section-title">
                <i class="bi bi-person-circle me-2"></i>
                Datos Personales
            </h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="nombres" class="form-label required">Nombres:</label>
                    <input type="text" id="nombres" name="nombres" class="form-control" required 
                           value="<?php echo htmlspecialchars($cliente['NOMBRES']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="apepaterno" class="form-label required">Apellido Paterno:</label>
                    <input type="text" id="apepaterno" name="apepaterno" class="form-control" required
                           value="<?php echo htmlspecialchars($cliente['APEPATERNO']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="apematerno" class="form-label required">Apellido Materno:</label>
                    <input type="text" id="apematerno" name="apematerno" class="form-control" required
                           value="<?php echo htmlspecialchars($cliente['APEMATERNO']); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fec_nacimiento" class="form-label">Fecha de Nacimiento:</label>
                    <input type="date" id="fec_nacimiento" name="fec_nacimiento" class="form-control"
                           value="<?php echo ($cliente['FEC_NACIMIENTO']) ? $cliente['FEC_NACIMIENTO']->format('Y-m-d') : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="est_civil" class="form-label">Estado Civil:</label>
                    <select id="est_civil" name="est_civil" class="form-select">
                        <option value="S" <?php echo ($cliente['EST_CIVIL'] == 'S') ? 'selected' : ''; ?>>Soltero(a)</option>
                        <option value="C" <?php echo ($cliente['EST_CIVIL'] == 'C') ? 'selected' : ''; ?>>Casado(a)</option>
                        <option value="V" <?php echo ($cliente['EST_CIVIL'] == 'V') ? 'selected' : ''; ?>>Viudo(a)</option>
                        <option value="D" <?php echo ($cliente['EST_CIVIL'] == 'D') ? 'selected' : ''; ?>>Divorciado(a)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h5 class="section-title">
                <i class="bi bi-person-vcard me-2"></i>
                Identificación y Contacto
            </h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="idtipo_documento" class="form-label required">Tipo de Documento:</label>
                    <select id="idtipo_documento" name="idtipo_documento" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($tipos_documento as $doc): ?>
                        <option value="<?php echo $doc['IDTIPO_DOCUMENTO']; ?>" <?php echo ($doc['IDTIPO_DOCUMENTO'] == $cliente['IDTIPO_DOCUMENTO']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($doc['NOMDOCUMENTO']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="doc_identidad" class="form-label required">N° de Documento:</label>
                    <input type="text" id="doc_identidad" name="doc_identidad" class="form-control" required
                           value="<?php echo htmlspecialchars($cliente['DOC_IDENTIDAD']); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="celular" class="form-label">Celular (9 dígitos):</label>
                    <input type="text" id="celular" name="celular" class="form-control" pattern="[0-9]{9}" 
                           value="<?php echo htmlspecialchars($cliente['CELULAR']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="correo" class="form-label">Correo Electrónico:</label>
                    <input type="email" id="correo" name="correo" class="form-control"
                           value="<?php echo htmlspecialchars($cliente['CORREO']); ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h5 class="section-title">
                <i class="bi bi-geo-alt me-2"></i>
                Ubicación y Estado
            </h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="iddistrito" class="form-label required">Distrito:</label>
                    <select id="iddistrito" name="iddistrito" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($distritos as $dist): ?>
                        <option value="<?php echo $dist['IDDISTRITO']; ?>" <?php echo ($dist['IDDISTRITO'] == $cliente['IDDISTRITO']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dist['NOMPROVINCIA'] . ' - ' . $dist['NOMDISTRITO']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="direccion" class="form-label">Dirección:</label>
                    <input type="text" id="direccion" name="direccion" class="form-control"
                           value="<?php echo htmlspecialchars($cliente['DIRECCION']); ?>">
                </div>
            </div>
            <div class="mb-3" style="max-width: 300px;">
                <label for="estado_cliente" class="form-label required">Estado del Cliente:</label>
                <select id="estado_cliente" name="estado_cliente" class="form-select" required>
                    <option value="1" <?php echo ($cliente['ESTADO_CLIENTE'] == '1') ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo ($cliente['ESTADO_CLIENTE'] == '0') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <a href="clientes.php?pagina=1" class="btn btn-cancel">
                <i class="bi bi-x-circle me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-save">
                <i class="bi bi-save me-2"></i>Actualizar Cliente
            </button>
        </div>
    </form>
</div>

<link rel="stylesheet" href="css/formularios.css?v=1.0">

<?php include 'includes/footer.php'; ?>