<?php
// 1. Seguridad, Header y Conexión
include 'includes/seguridad.php'; 
include 'includes/header.php';
include 'includes/conexion.php';

// ==================================================
// ¡¡¡CONSULTA CORREGIDA!!!
// Buscamos personas que NO estén ya en la tabla EMPLEADO
// ==================================================
$sql_personas = "
    SELECT p.IDPERSONA, p.NOMBRES, p.APEPATERNO, p.DOC_IDENTIDAD
    FROM PERSONA p
    LEFT JOIN EMPLEADO e ON p.IDPERSONA = e.IDPERSONA
    WHERE e.IDEMPLEADO IS NULL AND p.ESTADO = '1' -- Que no exista en Empleados
    ORDER BY p.APEPATERNO, p.NOMBRES";
$stmt_personas = sqlsrv_query($conn, $sql_personas);
$personas_libres = [];
while ($row = sqlsrv_fetch_array($stmt_personas, SQLSRV_FETCH_ASSOC)) $personas_libres[] = $row;

// (El resto de las consultas para cargos, contratos y roles no cambian)
// 3. Cargar Cargos
$sql_cargos = "SELECT IDCARGO, NOMCARGO FROM CARGO WHERE ESTADO = '1' ORDER BY NOMCARGO";
$stmt_cargos = sqlsrv_query($conn, $sql_cargos);
$cargos = [];
while ($row = sqlsrv_fetch_array($stmt_cargos, SQLSRV_FETCH_ASSOC)) $cargos[] = $row;

// 4. Cargar Contratos
$sql_contratos = "SELECT IDCONTRATO, NOMCONTRATO FROM CONTRATO WHERE ESTADO = '1' ORDER BY NOMCONTRATO";
$stmt_contratos = sqlsrv_query($conn, $sql_contratos);
$contratos = [];
while ($row = sqlsrv_fetch_array($stmt_contratos, SQLSRV_FETCH_ASSOC)) $contratos[] = $row;

// 5. Cargar Tipos de Usuario (Roles)
$sql_tipos = "SELECT IDTIPO_USUARIO, NOMUSUARIO FROM TIPO_USUARIO WHERE ESTADO = '1' ORDER BY NOMUSUARIO";
$stmt_tipos = sqlsrv_query($conn, $sql_tipos);
$tipos_usuario = [];
while ($row = sqlsrv_fetch_array($stmt_tipos, SQLSRV_FETCH_ASSOC)) $tipos_usuario[] = $row;

?>

<div class="mb-4 pb-2 border-bottom">
    <h1 class="h2">Registrar Nuevo Empleado</h1>
    <p>Completa los datos para registrar un nuevo miembro del personal.</p>
</div>

<div class="card shadow-sm" style="max-width: 800px; margin: auto;">
    <div class="card-body">
        <form action="backend/gestionar_empleado.php" method="POST">
            <input type="hidden" name="accion" value="crear_empleado">
            
            <h5 class="text-primary mb-3">1. Datos del Empleado</h5>
            
            <div class="mb-3">
                <label for="idpersona" class="form-label">Persona (Empleado):</label>
                <select id="idpersona" name="idpersona" class="form-select" required>
                    <option value="">-- Seleccione una persona --</option>
                    <?php foreach ($personas_libres as $p): ?>
                    <option value="<?php echo $p['IDPERSONA']; ?>">
                        <?php echo htmlspecialchars($p['APEPATERNO'] . ' ' . $p['NOMBRES'] . ' (' . $p['DOC_IDENTIDAD'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (count($personas_libres) == 0): ?>
                    <small class="text-danger d-block mt-1">No hay personas registradas que no sean ya empleados.</small>
                <?php endif; ?>
                <small class="form-text">Si la persona no existe, <a href="crear_cliente_persona.php" target="_blank">regístrela aquí primero</a> y luego recargue esta página.</small>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="idcargo" class="form-label">Cargo:</label>
                    <select id="idcargo" name="idcargo" class="form-select" required>
                        <option value="">-- Seleccione cargo --</option>
                        <?php foreach ($cargos as $c): ?>
                        <option value="<?php echo $c['IDCARGO']; ?>"><?php echo htmlspecialchars($c['NOMCARGO']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="idcontrato" class="form-label">Tipo de Contrato:</label>
                    <select id="idcontrato" name="idcontrato" class="form-select" required>
                        <option value="">-- Seleccione contrato --</option>
                        <?php foreach ($contratos as $c): ?>
                        <option value="<?php echo $c['IDCONTRATO']; ?>"><?php echo htmlspecialchars($c['NOMCONTRATO']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="salario" class="form-label">Salario (S/):</label>
                    <input type="number" id="salario" name="salario" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fec_contratacion" class="form-label">Fecha de Contratación:</label>
                    <input type="date" id="fec_contratacion" name="fec_contratacion" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            
            <hr class="my-4">

            <h5 class="text-primary mb-3">2. Acceso al Sistema (Opcional)</h5>
            
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="crear_usuario" name="crear_usuario" value="1" onchange="toggleUsuario()">
                <label class="form-check-label fw-bold" for="crear_usuario">
                    Crear cuenta de usuario para este empleado
                </label>
            </div>

            <div id="campos_usuario" style="display: none;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="logeo" class="form-label">Usuario (Logeo):</label>
                        <input type="text" id="logeo" name="logeo" class="form-control" placeholder="Ej: jerez">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="clave" class="form-label">Contraseña:</label>
                        <input type="password" id="clave" name="clave" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="idtipo_usuario" class="form-label">Tipo de Usuario (Rol):</label>
                    <select id="idtipo_usuario" name="idtipo_usuario" class="form-select">
                        <option value="">-- Seleccione rol --</option>
                        <?php foreach ($tipos_usuario as $t): ?>
                        <option value="<?php echo $t['IDTIPO_USUARIO']; ?>"><?php echo htmlspecialchars($t['NOMUSUARIO']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="text-end mt-4">
                <a href="empleados.php?pagina=1" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Empleado</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleUsuario() {
    var checkbox = document.getElementById('crear_usuario');
    var campos = document.getElementById('campos_usuario');
    var inputs = campos.querySelectorAll('input, select');

    if (checkbox.checked) {
        campos.style.display = 'block';
        inputs.forEach(function(input) { input.required = true; });
    } else {
        campos.style.display = 'none';
        inputs.forEach(function(input) { input.required = false; });
    }
}
</script>

<?php include 'includes/footer.php'; ?>