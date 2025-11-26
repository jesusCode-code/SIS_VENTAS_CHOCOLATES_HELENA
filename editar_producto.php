<?php
// Seguridad y Conexión
include 'includes/seguridad.php';
include 'includes/header.php';
include 'includes/conexion.php';

// Validar ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: productos.php");
    exit;
}
$idproducto = $_GET['id'];

// Obtener datos del producto
$sql_producto = "SELECT * FROM PRODUCTO WHERE IDPRODUCTO = ?";
$params = array($idproducto);
$stmt = sqlsrv_query($conn, $sql_producto, $params);
if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
$producto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$producto) {
    $_SESSION['error_producto'] = "Error: El producto no existe.";
    header("Location: productos.php");
    exit;
}

// Obtener categorías
$sql_categorias = "SELECT IDCATEGORIA, NOMCATEGORIA FROM CATEGORIA_PRODUCTO WHERE ESTADO = '1' ORDER BY NOMCATEGORIA";
$stmt_categorias = sqlsrv_query($conn, $sql_categorias);
$categorias = [];
if ($stmt_categorias !== false) {
    while ($row_cat = sqlsrv_fetch_array($stmt_categorias, SQLSRV_FETCH_ASSOC)) {
        $categorias[] = $row_cat;
    }
}
?>

<!-- Breadcrumb y Header -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="productos.php">Productos</a></li>
        <li class="breadcrumb-item active">Editar Producto</li>
    </ol>
</nav>

<div class="page-header-edit">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title-edit">
                <i class="bi bi-pencil-square me-2"></i>
                Editar Producto
            </h1>
            <p class="page-subtitle-edit">
                Modificando: <strong><?php echo htmlspecialchars($producto['NOMPRODUCTO']); ?></strong>
            </p>
        </div>
        <a href="productos.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<!-- Formulario de Edición -->
<div class="edit-card">
    <form action="backend/gestionar_producto.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="idproducto" value="<?php echo $producto['IDPRODUCTO']; ?>">
        <input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($producto['IMAGEN_URL']); ?>">
        
        <!-- Sección: Información Básica -->
        <div class="form-section">
            <h5 class="section-title">
                <i class="bi bi-info-circle me-2"></i>
                Información Básica
            </h5>
            
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="nomproducto" class="form-label required">
                        Nombre del Producto
                    </label>
                    <input type="text" 
                           id="nomproducto" 
                           name="nomproducto" 
                           class="form-control-edit"
                           value="<?php echo htmlspecialchars($producto['NOMPRODUCTO']); ?>" 
                           required
                           placeholder="Ej: Chocotejas Premium">
                </div>

                <div class="col-md-4 mb-3">
                    <label for="idcategoria" class="form-label required">
                        Categoría
                    </label>
                    <select id="idcategoria" name="idcategoria" class="form-select-edit" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['IDCATEGORIA']; ?>" 
                                    <?php echo ($categoria['IDCATEGORIA'] == $producto['IDCATEGORIA']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['NOMCATEGORIA']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">
                    Descripción
                </label>
                <textarea id="descripcion" 
                          name="descripcion" 
                          class="form-control-edit" 
                          rows="4"
                          placeholder="Descripción detallada del producto..."><?php echo htmlspecialchars($producto['DESCRIPCION']); ?></textarea>
            </div>
        </div>

        <!-- Sección: Precio y Stock -->
        <div class="form-section">
            <h5 class="section-title">
                <i class="bi bi-cash-coin me-2"></i>
                Precio y Disponibilidad
            </h5>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="precio" class="form-label required">
                        Precio (S/)
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" 
                               id="precio" 
                               name="precio" 
                               class="form-control-edit" 
                               step="0.01" 
                               min="0" 
                               required
                               value="<?php echo number_format($producto['PRECIO'], 2, '.', ''); ?>"
                               placeholder="0.00">
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="stock" class="form-label required">
                        Stock
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-box-seam"></i>
                        </span>
                        <input type="number" 
                               id="stock" 
                               name="stock" 
                               class="form-control-edit" 
                               min="0" 
                               required
                               value="<?php echo $producto['STOCK']; ?>"
                               placeholder="0">
                    </div>
                    <?php if ($producto['STOCK'] <= 10): ?>
                        <small class="text-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Stock bajo
                        </small>
                    <?php endif; ?>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="estado" class="form-label required">
                        Estado
                    </label>
                    <select id="estado" name="estado" class="form-select-edit" required>
                        <option value="1" <?php echo ($producto['ESTADO'] == '1') ? 'selected' : ''; ?>>
                            Activo
                        </option>
                        <option value="0" <?php echo ($producto['ESTADO'] == '0') ? 'selected' : ''; ?>>
                            Inactivo
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sección: Imagen -->
        <div class="form-section">
            <h5 class="section-title">
                <i class="bi bi-image me-2"></i>
                Imagen del Producto
            </h5>
            
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Imagen Actual</label>
                    <div class="current-image-container">
                        <?php 
                        $imagen_url = "img/" . htmlspecialchars($producto['IMAGEN_URL']);
                        if (empty($producto['IMAGEN_URL']) || !file_exists($imagen_url)) {
                            $imagen_url = "https://www.lapurita.com/cdn/shop/collections/LAPURITA4229.jpg?v=1724384944&width=320";
                        } else {
                            $imagen_url .= "?v=" . time();
                        }
                        ?>
                        <img src="<?php echo $imagen_url; ?>" 
                             alt="Imagen Actual" 
                             class="current-image"
                             id="imagePreview">
                        <div class="image-overlay">
                            <i class="bi bi-eye"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <label for="imagen" class="form-label">
                        Cambiar Imagen (Opcional)
                    </label>
                    <input class="form-control-edit" 
                           type="file" 
                           id="imagen" 
                           name="imagen" 
                           accept="image/jpeg, image/png"
                           onchange="previewImage(this)">
                    <div class="form-help">
                        <i class="bi bi-info-circle me-2"></i>
                        <div>
                            <strong>Recomendaciones:</strong>
                            <ul class="mb-0">
                                <li>Formatos permitidos: JPG, PNG</li>
                                <li>Tamaño recomendado: 500x500 px</li>
                                <li>Peso máximo: 2 MB</li>
                                <li>Solo sube un archivo si deseas cambiar la imagen actual</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="form-actions">
            <a href="productos.php" class="btn btn-cancel">
                <i class="bi bi-x-circle me-2"></i>
                Cancelar
            </a>
            <button type="submit" class="btn btn-save">
                <i class="bi bi-save me-2"></i>
                Guardar Cambios
            </button>
        </div>
    </form>
</div>

<link rel="stylesheet" href="css/edit_productos.css">

<script>
// Preview de imagen al seleccionar archivo
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Validación antes de enviar
document.querySelector('form').addEventListener('submit', function(e) {
    const precio = parseFloat(document.getElementById('precio').value);
    const stock = parseInt(document.getElementById('stock').value);
    
    if (precio < 0) {
        e.preventDefault();
        alert('El precio no puede ser negativo');
        return false;
    }
    
    if (stock < 0) {
        e.preventDefault();
        alert('El stock no puede ser negativo');
        return false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>