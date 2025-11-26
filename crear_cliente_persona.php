<?php
// 1. Seguridad, Header y Conexión
include 'includes/seguridad.php'; 
include 'includes/header.php';
include 'includes/conexion.php';

// 2. Cargar Tipos de Documento
$sql_docs = "SELECT IDTIPO_DOCUMENTO, NOMDOCUMENTO FROM TIPO_DOCUMENTO WHERE ESTADO = '1' ORDER BY NOMDOCUMENTO";
$stmt_docs = sqlsrv_query($conn, $sql_docs);
$tipos_documento = [];
while ($row = sqlsrv_fetch_array($stmt_docs, SQLSRV_FETCH_ASSOC)) {
    $tipos_documento[] = $row;
}

// 3. Cargar Distritos
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
    <h1 class="h2">Crear Nuevo Cliente (Persona)</h1>
    <p>Completa los datos personales del nuevo cliente.</p>
</div>

<div class="card shadow-sm" style="max-width: 900px; margin: auto;">
    <div class="card-body">
        <form action="backend/gestionar_cliente.php" method="POST">
            <input type="hidden" name="accion" value="crear_persona">
            
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

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fec_nacimiento" class="form-label">Fecha de Nacimiento:</label>
                    <input type="date" id="fec_nacimiento" name="fec_nacimiento" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="est_civil" class="form-label">Estado Civil:</label>
                    <select id="est_civil" name="est_civil" class="form-select">
                        <option value="S">Soltero(a)</option>
                        <option value="C">Casado(a)</option>
                        <option value="V">Viudo(a)</option>
                        <option value="D">Divorciado(a)</option>
                    </select>
                </div>
            </div>

            <hr class="my-4">
            <h5 class="text-primary mb-3">2. Datos de Identificación y Contacto</h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="idtipo_documento" class="form-label">Tipo de Documento:</label>
                    <select id="idtipo_documento" name="idtipo_documento" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($tipos_documento as $doc): ?>
                        <option value="<?php echo $doc['IDTIPO_DOCUMENTO']; ?>"><?php echo htmlspecialchars($doc['NOMDOCUMENTO']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="doc_identidad" class="form-label">N° de Documento:</label>
                    <input type="text" id="doc_identidad" name="doc_identidad" class="form-control" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="celular" class="form-label">Celular (9 dígitos):</label>
                    <input type="text" id="celular" name="celular" class="form-control" pattern="[0-9]{9}" title="Debe contener 9 dígitos numéricos">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="correo" class="form-label">Correo Electrónico:</label>
                    <input type="email" id="correo" name="correo" class="form-control">
                </div>
            </div>

            <hr class="my-4">
            <h5 class="text-primary mb-3">3. Datos de Ubicación</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="iddistrito" class="form-label">Distrito:</label>
                    <select id="iddistrito" name="iddistrito" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($distritos as $dist): ?>
                        <option value="<?php echo $dist['IDDISTRITO']; ?>">
                            <?php echo htmlspecialchars($dist['NOMPROVINCIA'] . ' - ' . $dist['NOMDISTRITO']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="direccion" class="form-label">Dirección:</label>
                    <input type="text" id="direccion" name="direccion" class="form-control">
                </div>
            </div>

            <div class="text-end mt-4">
                <a href="clientes.php?pagina=1" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>