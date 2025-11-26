<?php
// 1. Seguridad, Header y Conexión
include 'includes/seguridad.php'; 
include 'includes/header.php';
include 'includes/conexion.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="clientes.php">Clientes</a></li>
        <li class="breadcrumb-item active">Crear Cliente (Empresa)</li>
    </ol>
</nav>

<div class="form-page-header"> <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="form-page-title"> <i class="bi bi-building-add me-2"></i>
                Crear Nuevo Cliente (Empresa)
            </h1>
            <p class="form-page-subtitle"> Completa los datos de la empresa.
            </p>
        </div>
        <a href="clientes.php?pagina=1" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver al Listado
        </a>
    </div>
</div>

<div class="form-card"> <form action="backend/gestionar_cliente.php" method="POST">
        <input type="hidden" name="accion" value="crear_empresa">
        
        <div class="form-section">
            <h5 class="section-title">
                <i class="bi bi-building me-2"></i>
                Datos de la Empresa
            </h5>
            
            <div class="mb-3">
                <label for="razon_social" class="form-label required">Razón Social:</label>
                <input type="text" id="razon_social" name="razon_social" class="form-control" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ruc" class="form-label required">RUC (11 dígitos):</label>
                    <input type="text" id="ruc" name="ruc" class="form-control" required pattern="[0-9]{11}" title="Debe contener 11 dígitos numéricos">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="telefono" class="form-label">Teléfono (9 dígitos):</label>
                    <input type="text" id="telefono" name="telefono" class="form-control" pattern="[0-9]{9}" title="Debe contener 9 dígitos numéricos">
                </div>
            </div>

            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección:</label>
                <input type="text" id="direccion" name="direccion" class="form-control">
            </div>
        </div>

        <div class="form-actions"> <a href="clientes.php?pagina=1" class="btn btn-cancel">
                <i class="bi bi-x-circle me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-save">
                <i class="bi bi-save me-2"></i>Guardar Empresa
            </button>
        </div>
    </form>
</div>

<link rel="stylesheet" href="css/formularios.css?v=1.0">

<?php include 'includes/footer.php'; ?>