<?php
// Incluimos el header público y la conexión
include 'includes/public_header.php';
include 'includes/conexion.php';

// LÓGICA DE FILTRO DE CATEGORÍA
$categoria_id = null;
$where_categoria_sql = "";
$params_categoria = [];
$url_params = "";

if (isset($_GET['categoria_id']) && is_numeric($_GET['categoria_id'])) {
    $categoria_id = (int)$_GET['categoria_id'];
    $where_categoria_sql = " AND p.IDCATEGORIA = ? ";
    $params_categoria = [$categoria_id];
    $url_params = "&categoria_id=" . $categoria_id;
}

// Cargar TODAS las categorías
$sql_categorias = "SELECT IDCATEGORIA, NOMCATEGORIA FROM CATEGORIA_PRODUCTO WHERE ESTADO = '1' ORDER BY NOMCATEGORIA";
$stmt_cats = sqlsrv_query($conn, $sql_categorias);
$categorias_filtro = [];
while ($row_cat = sqlsrv_fetch_array($stmt_cats, SQLSRV_FETCH_ASSOC)) {
    $categorias_filtro[] = $row_cat;
}

// LÓGICA DE PAGINACIÓN
$registros_por_pagina = 12;
$pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Contar el TOTAL de productos
$sql_total = "SELECT COUNT(IDPRODUCTO) AS total FROM PRODUCTO p WHERE p.ESTADO = '1' AND p.STOCK > 0" . $where_categoria_sql;
$stmt_total = sqlsrv_query($conn, $sql_total, $params_categoria);
$total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener nombre de categoría actual
$nombre_categoria = "Todos los Productos";
if ($categoria_id) {
    $sql_cat_nombre = "SELECT NOMCATEGORIA FROM CATEGORIA_PRODUCTO WHERE IDCATEGORIA = ?";
    $stmt_cat_nombre = sqlsrv_query($conn, $sql_cat_nombre, [$categoria_id]);
    if ($row_cat_nombre = sqlsrv_fetch_array($stmt_cat_nombre, SQLSRV_FETCH_ASSOC)) {
        $nombre_categoria = $row_cat_nombre['NOMCATEGORIA'];
    }
}

// Obtener lista de productos
$sql_productos = "
    SELECT 
        p.IDPRODUCTO, p.NOMPRODUCTO, p.DESCRIPCION, p.PRECIO AS PRECIO_ORIGINAL, p.STOCK,
        p.IMAGEN_URL, 
        pr.PORCENTAJE_DESC,
        c.NOMCATEGORIA
    FROM PRODUCTO p
    LEFT JOIN PROMOCION pr ON p.IDPRODUCTO = pr.IDPRODUCTO 
                          AND pr.ESTADO = '1' 
                          AND GETDATE() BETWEEN pr.FECHA_INICIO AND pr.FECHA_FIN
    LEFT JOIN CATEGORIA_PRODUCTO c ON p.IDCATEGORIA = c.IDCATEGORIA
    WHERE 
        p.ESTADO = '1' AND p.STOCK > 0 " . $where_categoria_sql . "
    ORDER BY 
        p.NOMPRODUCTO ASC
    OFFSET ? ROWS 
    FETCH NEXT ? ROWS ONLY";

$params_productos = array_merge($params_categoria, [$offset, $registros_por_pagina]);
$stmt_productos = sqlsrv_query($conn, $sql_productos, $params_productos);
if ($stmt_productos === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!-- Hero de Tienda -->
<div class="shop-hero">
    <div class="hero-content-wrapper">
        <div class="hero-text">
            <h1 class="hero-title">
                <i class="bi bi-shop me-2"></i>
                Nuestra Tienda
            </h1>
            <p class="hero-description">Explora nuestro catálogo de productos artesanales de la más alta calidad</p>
        </div>
        <div class="hero-stats">
            <div class="stat-box">
                <i class="bi bi-box-seam"></i>
                <div>
                    <span class="stat-number"><?php echo $total_registros; ?></span>
                    <span class="stat-label">Productos</span>
                </div>
            </div>
            <div class="stat-box">
                <i class="bi bi-tags"></i>
                <div>
                    <span class="stat-number"><?php echo count($categorias_filtro); ?></span>
                    <span class="stat-label">Categorías</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="filters-section">
    <div class="filter-card">
        <div class="filter-header">
            <h5><i class="bi bi-funnel me-2"></i>Filtrar Productos</h5>
            <span class="results-count"><?php echo $total_registros; ?> productos encontrados</span>
        </div>

        <form action="tienda.php" method="GET" class="filter-form">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="categoria_id" class="form-label">
                        <i class="bi bi-grid-3x3-gap me-2"></i>Categoría
                    </label>
                    <select name="categoria_id" id="categoria_id" class="form-select form-select-modern">
                        <option value="">🍫 Todas las Categorías</option>
                        <?php foreach ($categorias_filtro as $cat): ?>
                            <option value="<?php echo $cat['IDCATEGORIA']; ?>"
                                <?php echo ($cat['IDCATEGORIA'] == $categoria_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['NOMCATEGORIA']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-filter">
                            <i class="bi bi-search me-2"></i>Filtrar
                        </button>
                        <a href="tienda.php" class="btn btn-clear">
                            <i class="bi bi-x-circle me-2"></i>Limpiar
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <?php if ($categoria_id): ?>
            <div class="active-filter">
                <span class="filter-label">Filtrando por:</span>
                <span class="filter-badge">
                    <?php echo htmlspecialchars($nombre_categoria); ?>
                    <a href="tienda.php" class="remove-filter">
                        <i class="bi bi-x"></i>
                    </a>
                </span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Grid de Productos -->
<?php if ($total_registros == 0): ?>
    <div class="empty-products">
        <div class="empty-icon">
            <i class="bi bi-basket"></i>
        </div>
        <h3>No hay productos disponibles</h3>
        <p>No se encontraron productos para esta categoría. Intenta con otro filtro.</p>
        <a href="tienda.php" class="btn btn-back-shop">
            <i class="bi bi-arrow-left me-2"></i>Ver todos los productos
        </a>
    </div>
<?php else: ?>
    <div class="products-grid">
        <?php while ($row = sqlsrv_fetch_array($stmt_productos, SQLSRV_FETCH_ASSOC)): ?>
            <?php
            $precio_original = (float)$row['PRECIO_ORIGINAL'];
            $porcentaje_desc = (float)$row['PORCENTAJE_DESC'];
            $precio_final = $precio_original;
            $es_oferta = $porcentaje_desc > 0;
            if ($es_oferta) {
                $precio_final = $precio_original * (1 - ($porcentaje_desc / 100));
            }

            $imagen_url = "img/" . htmlspecialchars($row['IMAGEN_URL']);
            if (empty($row['IMAGEN_URL']) || !file_exists($imagen_url)) {
                $imagen_url = "https://www.lapurita.com/cdn/shop/collections/LAPURITA4229.jpg?v=1724384944&width=320";
            } else {
                $imagen_url .= "?v=" . time();
            }
            ?>

            <div class="product-item">
                <div class="product-card-shop">
                    <!-- Badges -->
                    <div class="product-badges">
                        <?php if ($es_oferta): ?>
                            <span class="badge-discount">
                                -<?php echo number_format($porcentaje_desc, 0); ?>%
                            </span>
                        <?php endif; ?>
                        <?php if ($row['STOCK'] <= 5): ?>
                            <span class="badge-stock">
                                <i class="bi bi-exclamation-circle me-1"></i>Últimas unidades
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Imagen -->
                    <div class="product-image-wrapper">
                        <img src="<?php echo $imagen_url; ?>"
                            class="product-image"
                            alt="<?php echo htmlspecialchars($row['NOMPRODUCTO']); ?>">
                        <div class="product-overlay">
                            <a href="producto_detalle.php?id=<?php echo $row['IDPRODUCTO']; ?>"
                                class="btn-view-product">
                                <i class="bi bi-eye me-2"></i>Ver Detalles
                            </a>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="product-content">
                        <div class="product-category">
                            <i class="bi bi-tag me-1"></i>
                            <?php echo htmlspecialchars($row['NOMCATEGORIA']); ?>
                        </div>

                        <h5 class="product-name">
                            <?php echo htmlspecialchars($row['NOMPRODUCTO']); ?>
                        </h5>

                        <p class="product-description">
                            <?php
                            if (!empty($row['DESCRIPCION'])) {
                                echo htmlspecialchars(substr($row['DESCRIPCION'], 0, 80)) . '...';
                            } else {
                                echo "Delicioso producto artesanal elaborado con ingredientes de calidad.";
                            }
                            ?>
                        </p>

                        <!-- Stock -->
                        <div class="product-stock">
                            <i class="bi bi-box-seam me-1"></i>
                            <span>Stock: <?php echo $row['STOCK']; ?> unidades</span>
                        </div>

                        <!-- Precio -->
                        <div class="product-price-section">
                            <?php if ($es_oferta): ?>
                                <div class="price-group">
                                    <span class="price-current">S/ <?php echo number_format($precio_final, 2); ?></span>
                                    <span class="price-original">S/ <?php echo number_format($precio_original, 2); ?></span>
                                </div>
                                <div class="savings">
                                    Ahorras: S/ <?php echo number_format($precio_original - $precio_final, 2); ?>
                                </div>
                            <?php else: ?>
                                <span class="price-single">S/ <?php echo number_format($precio_final, 2); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Botón -->
                        <a href="producto_detalle.php?id=<?php echo $row['IDPRODUCTO']; ?>"
                            class="btn-add-cart">
                            <i class="bi bi-cart-plus me-2"></i>Ver Producto
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Paginación -->
    <div class="pagination-wrapper">
        <?php
        // Construimos la URL base con todos los filtros activos
        $url_parts = [];
        if ($categoria_id) {
            $url_parts[] = "categoria_id=" . $categoria_id;
        }
        $url_base = "tienda.php";
        if (!empty($url_parts)) {
            $url_base .= "?" . implode("&", $url_parts);
        }

        include 'includes/paginador.php';
        ?>
    </div>
<?php endif; ?>

<link rel="stylesheet" href="css/mi_tienda.css">

<?php include 'includes/public_footer.php'; ?>