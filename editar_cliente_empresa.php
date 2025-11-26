<?php
// 1. Seguridad, Header y Conexión
include 'includes/seguridad.php'; 
include 'includes/header.php';
include 'includes/conexion.php';

// 2. Validar ID de Cliente
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: clientes.php?pagina=1"); exit;
}
$idcliente = $_GET['id'];

// 3. Cargar Datos del Cliente y Empresa
$sql_data = "SELECT c.IDCLIENTE, c.ESTADO AS ESTADO_CLIENTE, e.* FROM CLIENTE c
             JOIN EMPRESA e ON c.IDEMPRESA = e.IDEMPRESA
             WHERE c.IDCLIENTE = ?";
$params = array($idcliente);
$stmt_data = sqlsrv_query($conn, $sql_data, $params);
if ($stmt_data === false) { die(print_r(sqlsrv_errors(), true)); }
$cliente = sqlsrv_fetch_array($stmt_data, SQLSRV_FETCH_ASSOC);

if (!$cliente) {
    $_SESSION['error_cliente'] = "Error: El cliente no existe.";
    header("Location: clientes.php?pagina=1"); exit;
}
$idempresa = $cliente['IDEMPRESA'];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="clientes.php">Clientes</a></li>
        <li class="breadcrumb-item active">Editar Cliente (Empresa)</li>
    </ol>
</nav>

<div class="form-page-header"> <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="form-page-title"> <i class="bi bi-pencil-square me-2"></i>
                Editar Cliente (Empresa)
            </h1>
            <p class="form-page-subtitle"> Modificando: <strong><?php echo htmlspecialchars($cliente['RAZON_SOCIAL']); ?></strong>
            </p>
        </div>
        <a href="clientes.php?pagina=1" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver al Listado
        </a>
    </div>
</div>

<div class="form-card"> <form action="backend/gestionar_cliente.php" method="POST">
        <input type="hidden" name="accion" value="editar_empresa">
        <input type="hidden" name="idcliente" value="<?php echo $idcliente; ?>">
        <input type="hidden" name="idempresa" value="<?php echo $idempresa; ?>">
        
        <div class="form-section">
            <h5 class="section-title">
                <i class="bi bi-building me-2"></i>
                Datos de la Empresa
            </h5>
            
            <div class="mb-3">
                <label for="razon_social" class="form-label required">Razón Social:</label>
                <input type="text" id="razon_social" name="razon_social" class="form-control" required
                       value="<?php echo htmlspecialchars($cliente['RAZON_SOCIAL']); ?>">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ruc" class="form-label required">RUC (11 dígitos):</label>
                    <input type="text" id="ruc" name="ruc" class="form-control" required pattern="[0-9]{11}"
                           value="<?php echo htmlspecialchars($cliente['RUC']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="telefono" class="form-label">Teléfono (9 dígitos):</label>
                    <input type="text" id="telefono" name="telefono" class="form-control" pattern="[0-9]{9}"
                           value="<?php echo htmlspecialchars($cliente['TELEFONO']); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección:</label>
                <input type="text" id="direccion" name="direccion" class="form-control"
                       value="<?php echo htmlspecialchars($cliente['DIRECCION']); ?>">
            </div>
            
            <div class="mb-3" style="max-width: 300px;">
                <label for="estado_cliente" class="form-label required">Estado del Cliente:</label>
                <select id="estado_cliente" name="estado_cliente" class="form-select" required>
                    <option value="1" <?php echo ($cliente['ESTADO_CLIENTE'] == '1') ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo ($cliente['ESTADO_CLIENTE'] == '0') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
        </div>

        <div class="form-actions"> <a href="clientes.php?pagina=1" class="btn btn-cancel">
                <i class="bi bi-x-circle me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-save">
                <i class="bi bi-save me-2"></i>Actualizar Empresa
            </button>
        </div>
    </form>
</div>

<link rel="stylesheet" href="css/formularios.css?v=1.0">

<?php include 'includes/footer.php'; ?>