<?php
// Usamos el header PÚBLICO
include 'includes/public_header.php';
include 'includes/conexion.php'; 

// (Cargamos distritos para el formulario)
$sql_dist = "SELECT d.IDDISTRITO, d.NOMDISTRITO, p.NOMPROVINCIA 
             FROM DISTRITO d
             JOIN PROVINCIA p ON d.IDPROVINCIA = p.IDPROVINCIA
             WHERE d.ESTADO = '1' AND p.ESTADO = '1'
             ORDER BY p.NOMPROVINCIA, d.NOMDISTRITO";
$stmt_dist = sqlsrv_query($conn, $sql_dist);
$distritos = [];
while ($row = sqlsrv_fetch_array($stmt_dist, SQLSRV_FETCH_ASSOC)) {
    $distritos[] = $row;
}
?>

<div class="mb-4 pb-2 border-bottom">
    <h1 class="h2">Crear una Cuenta</h1>
    <p>Regístrate para comprar más rápido y ver tu historial de pedidos.</p>
</div>

<?php
if (isset($_SESSION['mensaje_registro'])) {
    echo '<div class="alert alert-success">' . $_SESSION['mensaje_registro'] . '</div>';
    unset($_SESSION['mensaje_registro']);
}
if (isset($_SESSION['error_registro'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_registro'] . '</div>';
    unset($_SESSION['error_registro']);
}
?>

<div class="card shadow-sm" style="max-width: 900px; margin: auto;">
    <div class="card-body p-4 p-md-5">
        <form id="formRegistro" action="backend/registrar_cliente.php" method="POST">
            
            <h5 class="text-primary mb-3">1. Datos Personales</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="nombres" class="form-label">Nombres:</label>
                    <input type="text" id="nombres" name="nombres" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="apepaterno" class="form-label">Apellido Paterno:</label>
                    <input type="text" id="apepaterno" name="apepaterno" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="apematerno" class="form-label">Apellido Materno:</label>
                    <input type="text" id="apematerno" name="apematerno" class="form-control" required>
                </div>
            </div>

            <hr class="my-4">
            <h5 class="text-primary mb-3">2. Datos de Contacto y Documento</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="doc_identidad" class="form-label">DNI:</label>
                    <input type="text" id="doc_identidad" name="doc_identidad" class="form-control" required pattern="[0-9]{8}" title="Debe contener 8 dígitos numéricos">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="celular" class="form-label">Celular (9 dígitos):</label>
                    <input type="text" id="celular" name="celular" class="form-control" pattern="[0-9]{9}" title="Debe contener 9 dígitos numéricos">
                </div>
            </div>
            <div class="mb-3">
                <label for="correo" class="form-label">Correo Electrónico:</label>
                <input type="email" id="correo" name="correo" class="form-control" required>
            </div>
            
            <hr class="my-4">
            <h5 class="text-primary mb-3">3. Datos de Cuenta (Login)</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="logeo" class="form-label">Nombre de Usuario (Logeo):</label>
                    <input type="text" id="logeo" name="logeo" class="form-control" required placeholder="Ej: jtenorio">
                </div>
                 <div class="col-md-6 mb-3">
                    <label for="idtipo_documento" class="form-label">Tipo de Documento:</label>
                    <select id="idtipo_documento" name="idtipo_documento" class="form-select" required>
                        <option value="1">DNI</option> </select>
                </div>
            </div>
             <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="clave" class="form-label">Contraseña:</label>
                    <input type="password" id="clave" name="clave" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="clave_confirm" class="form-label">Confirmar Contraseña:</label>
                    <input type="password" id="clave_confirm" name="clave_confirm" class="form-control" required>
                </div>
            </div>

            <div class="text-end mt-4">
                <a href="login.php" class="btn btn-secondary me-2">Ya tengo cuenta (Login)</a>
                <button type="submit" class="btn btn-primary">Crear Cuenta</button>
            </div>
        </form>
    </div>
</div>

<script>
// Pequeña validación de contraseña en el frontend
document.getElementById('formRegistro').addEventListener('submit', function(event) {
    var clave = document.getElementById('clave').value;
    var clave_confirm = document.getElementById('clave_confirm').value;
    
    if (clave !== clave_confirm) {
        alert("Error: Las contraseñas no coinciden.");
        event.preventDefault(); // Detiene el envío del formulario
    }
});
</script>

<?php include 'includes/public_footer.php'; ?>