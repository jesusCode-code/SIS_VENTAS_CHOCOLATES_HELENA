<?php
include 'includes/public_header.php';
include 'includes/conexion.php';

// CONSULTA 1: PROMOCIONES
$sql_promos_activas = "
    SELECT TOP 3 
        pr.NOMPROMOCION, pr.DESCRIPCION, pr.PORCENTAJE_DESC, 
        p.NOMPRODUCTO, p.IDPRODUCTO
    FROM PROMOCION pr
    INNER JOIN PRODUCTO p ON pr.IDPRODUCTO = p.IDPRODUCTO
    WHERE pr.ESTADO = '1' AND GETDATE() BETWEEN pr.FECHA_INICIO AND pr.FECHA_FIN
    ORDER BY pr.PORCENTAJE_DESC DESC";

$stmt_promos = sqlsrv_query($conn, $sql_promos_activas);
$promociones = [];
if ($stmt_promos) {
    while ($row = sqlsrv_fetch_array($stmt_promos, SQLSRV_FETCH_ASSOC)) {
        $promociones[] = $row;
    }
}

// CONSULTA 2: NOVEDADES
$sql_novedades = "
    SELECT TOP 4 
        p.IDPRODUCTO, p.NOMPRODUCTO, p.PRECIO AS PRECIO_ORIGINAL, p.IMAGEN_URL,
        pr.PORCENTAJE_DESC
    FROM PRODUCTO p
    LEFT JOIN PROMOCION pr ON p.IDPRODUCTO = pr.IDPRODUCTO 
                          AND pr.ESTADO = '1' 
                          AND GETDATE() BETWEEN pr.FECHA_INICIO AND pr.FECHA_FIN
    WHERE p.ESTADO = '1' AND p.STOCK > 0
    ORDER BY p.IDPRODUCTO DESC";

$stmt_novedades = sqlsrv_query($conn, $sql_novedades);
$novedades = [];
if ($stmt_novedades) {
    while ($row = sqlsrv_fetch_array($stmt_novedades, SQLSRV_FETCH_ASSOC)) {
        $precio_original = (float)$row['PRECIO_ORIGINAL'];
        $porcentaje_desc = (float)$row['PORCENTAJE_DESC'];
        $row['precio_final'] = $precio_original;
        $row['es_oferta'] = $porcentaje_desc > 0;
        if ($row['es_oferta']) {
            $row['precio_final'] = $precio_original * (1 - ($porcentaje_desc / 100));
        }
        $novedades[] = $row;
    }
}

// CONSULTA 3: MEJORES VALORACIONES
$sql_valoraciones = "
    SELECT TOP 3 v.PUNTUACION, v.COMENTARIO,
           COALESCE(per.NOMBRES, 'Cliente Anónimo') AS NombreCliente
    FROM VALORACION v
    JOIN CLIENTE c ON v.IDCLIENTE = c.IDCLIENTE
    LEFT JOIN PERSONA per ON c.IDPERSONA = per.IDPERSONA
    WHERE v.ESTADO = '1' AND v.PUNTUACION >= 4
    ORDER BY v.FECHA_VALORACION DESC";

$stmt_valoraciones = sqlsrv_query($conn, $sql_valoraciones);
$valoraciones = [];
if ($stmt_valoraciones) {
    while ($row = sqlsrv_fetch_array($stmt_valoraciones, SQLSRV_FETCH_ASSOC)) {
        $valoraciones[] = $row;
    }
}
?>

<!-- Hero Section -->
<div class="hero-section mb-5">
    <div class="row align-items-center g-4">
        <div class="col-lg-6">
            <div class="hero-content">
                <span class="hero-badge">Desde 1975</span>
                <h1 class="hero-title">Chocolates<br><span class="text-gradient">Helena</span></h1>
                <p class="hero-description">La tradición del sabor iqueño en cada bocado. Descubre nuestros chocolates artesanales, tejas y bombones elaborados con pasión y dedicación.</p>
                <div class="hero-buttons">
                    <a href="tienda.php" class="btn btn-hero-primary">
                        <i class="bi bi-shop me-2"></i>Ver Catálogo
                    </a>
                    <a href="#novedades" class="btn btn-hero-secondary">
                        <i class="bi bi-stars me-2"></i>Novedades
                    </a>
                    <a href="#nuestra_historia" class="btn btn-hero-secondary">
                        <i class="bi bi-stars me-2"></i>Nuestra Historia
                    </a>
                </div>
                <div class="hero-stats mt-4">
                    <div class="stat-item">
                        <h3>50+</h3>
                        <p>Años de Tradición</p>
                    </div>
                    <div class="stat-item">
                        <h3>6</h3>
                        <p>Tiendas</p>
                    </div>
                    <div class="stat-item">
                        <h3>100%</h3>
                        <p>Artesanal</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="hero-image-container">
                <img src="https://static.mercadonegro.pe/wp-content/uploads/2019/09/22194435/helena-chocolates-y-tejas.jpg" class="hero-image" alt="Chocolates Helena">
                <div class="hero-decoration"></div>
            </div>
        </div>
    </div>
</div>

<!-- Promociones Destacadas -->
<?php if (count($promociones) > 0): ?>
    <section class="promo-section mb-5">
        <div class="section-header text-center mb-4">
            <span class="section-badge">Ofertas Especiales</span>
            <h2 class="section-title">🎉 Promociones Destacadas</h2>
            <p class="section-description">Aprovecha nuestras mejores ofertas por tiempo limitado</p>
        </div>

        <div id="promoCarousel" class="carousel slide promo-carousel" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <?php foreach ($promociones as $index => $promo): ?>
                    <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="<?php echo $index; ?>"
                        class="<?php echo ($index == 0) ? 'active' : ''; ?>"></button>
                <?php endforeach; ?>
            </div>

            <div class="carousel-inner">
                <?php foreach ($promociones as $index => $promo): ?>
                    <div class="carousel-item <?php echo ($index == 0) ? 'active' : ''; ?>" data-bs-interval="5000">
                        <div class="promo-card">
                            <div class="promo-badge">
                                <span class="discount"><?php echo number_format($promo['PORCENTAJE_DESC'], 0); ?>%</span>
                                <span class="label">OFF</span>
                            </div>
                            <div class="promo-content">
                                <h3 class="promo-title"><?php echo htmlspecialchars($promo['NOMPROMOCION']); ?></h3>
                                <p class="promo-description"><?php echo htmlspecialchars($promo['DESCRIPCION']); ?></p>
                                <p class="promo-product">
                                    <i class="bi bi-gift me-2"></i>Aplica a:
                                    <strong><?php echo htmlspecialchars($promo['NOMPRODUCTO']); ?></strong>
                                </p>
                                <a href="producto_detalle.php?id=<?php echo $promo['IDPRODUCTO']; ?>" class="btn btn-promo">
                                    Ver Oferta <i class="bi bi-arrow-right ms-2"></i>
                                </a>
                            </div>
                            <div class="promo-decoration"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    </section>
<?php endif; ?>

<!-- Categorías -->
<section class="categories-section mb-5">
    <div class="section-header text-center mb-4">
        <span class="section-badge">Explora</span>
        <h2 class="section-title">Nuestras Colecciones</h2>
        <p class="section-description">Descubre la variedad de productos que tenemos para ti</p>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <a href="tienda.php?categoria_id=1" class="category-card">
                <div class="category-image">
                    <img src="https://www.lapurita.com/cdn/shop/collections/LAPURITA4229.jpg?v=1724384944&width=320" alt="Tejas">
                    <div class="category-overlay"></div>
                </div>
                <div class="category-content">
                    <h3>Tejas</h3>
                    <p>Tradicionales y deliciosas</p>
                    <span class="category-btn">Explorar <i class="bi bi-arrow-right"></i></span>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="tienda.php?categoria_id=2" class="category-card">
                <div class="category-image">
                    <img src="https://www.lapurita.com/cdn/shop/collections/LAPURITA4229.jpg?v=1724384944&width=320" alt="Bombones">
                    <div class="category-overlay"></div>
                </div>
                <div class="category-content">
                    <h3>Bombones</h3>
                    <p>Rellenos exquisitos</p>
                    <span class="category-btn">Explorar <i class="bi bi-arrow-right"></i></span>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="tienda.php?categoria_id=3" class="category-card">
                <div class="category-image">
                    <img src="https://www.lapurita.com/cdn/shop/collections/LAPURITA4229.jpg?v=1724384944&width=320" alt="Chocolates">
                    <div class="category-overlay"></div>
                </div>
                <div class="category-content">
                    <h3>Chocolates</h3>
                    <p>Premium y artesanales</p>
                    <span class="category-btn">Explorar <i class="bi bi-arrow-right"></i></span>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- Novedades -->
<?php if (count($novedades) > 0): ?>
    <section id="novedades" class="products-section mb-5">
        <div class="section-header text-center mb-4">
            <span class="section-badge">Nuevos Productos</span>
            <h2 class="section-title">✨ Novedades</h2>
            <p class="section-description">Los últimos productos añadidos a nuestro catálogo</p>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($novedades as $producto): ?>
                <?php
                $imagen_url = "img/" . htmlspecialchars($producto['IMAGEN_URL']);
                if (empty($producto['IMAGEN_URL']) || !file_exists($imagen_url)) {
                    $imagen_url = "https://www.lapurita.com/cdn/shop/collections/LAPURITA4229.jpg?v=1724384944&width=320";
                } else {
                    $imagen_url .= "?v=" . time();
                }
                ?>
                <div class="col">
                    <div class="product-card">
                        <?php if ($producto['es_oferta']): ?>
                            <div class="product-badge">
                                <?php echo number_format($producto['PORCENTAJE_DESC'], 0); ?>% OFF
                            </div>
                        <?php endif; ?>
                        <div class="product-image">
                            <img src="<?php echo $imagen_url; ?>" alt="<?php echo htmlspecialchars($producto['NOMPRODUCTO']); ?>">
                            <div class="product-overlay">
                                <a href="producto_detalle.php?id=<?php echo $producto['IDPRODUCTO']; ?>" class="btn-quick-view">
                                    <i class="bi bi-eye"></i> Ver Detalles
                                </a>
                            </div>
                        </div>
                        <div class="product-info">
                            <h6 class="product-name"><?php echo htmlspecialchars($producto['NOMPRODUCTO']); ?></h6>
                            <div class="product-price">
                                <?php if ($producto['es_oferta']): ?>
                                    <span class="price-sale">S/ <?php echo number_format($producto['precio_final'], 2); ?></span>
                                    <span class="price-original">S/ <?php echo number_format($producto['PRECIO_ORIGINAL'], 2); ?></span>
                                <?php else: ?>
                                    <span class="price-current">S/ <?php echo number_format($producto['precio_final'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- Valoraciones -->
<?php if (count($valoraciones) > 0): ?>
    <section class="reviews-section mb-5">
        <div class="section-header text-center mb-4">
            <span class="section-badge">Testimonios</span>
            <h2 class="section-title">💬 Lo que dicen nuestros clientes</h2>
            <p class="section-description">La satisfacción de nuestros clientes es nuestra mejor recompensa</p>
        </div>

        <div class="row g-4">
            <?php foreach ($valoraciones as $val): ?>
                <div class="col-md-4">
                    <div class="review-card">
                        <div class="review-stars">
                            <?php for ($i = 0; $i < $val['PUNTUACION']; $i++): ?>
                                <i class="bi bi-star-fill"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="review-comment">"<?php echo htmlspecialchars($val['COMENTARIO']); ?>"</p>
                        <div class="review-author">
                            <div class="author-avatar">
                                <?php echo strtoupper(substr($val['NombreCliente'], 0, 1)); ?>
                            </div>
                            <div class="author-info">
                                <h6><?php echo htmlspecialchars($val['NombreCliente']); ?></h6>
                                <small>Cliente Verificado</small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- Nuestra Historia -->
<section id="nuestra_historia" class="about-section mb-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="about-card">
                <h2 class="about-title">Nuestra Historia</h2>
                <div class="about-content">
                    <p><strong>Elena Soler</strong>, una mujer emprendedora, dinámica y creativa descubrió la forma perfecta de consentir a quienes la rodeaban creando unas deliciosas chocotejas, un dulce tradicional de la soleada y tranquila ciudad de Ica.</p>

                    <p>Chocolates Helena se estableció en <strong>1975</strong> en la cocina de Elena Soler. Empezó ofreciendo el dulce tradicional iqueño más famoso: las tejas. Tuvo gran acogida, logrando establecer una fábrica en Ica y crecer el portafolio de productos.</p>

                    <p>Actualmente, contamos con <strong>4 tiendas en Lima y 2 en Ica</strong>. Desde el 2018, pasamos a llamarnos Helena Chocolates, iniciando grandes cambios con nuevos destinos y productos.</p>

                    <div class="mission-vision">
                        <div class="mv-item">
                            <div class="mv-icon"><i class="bi bi-bullseye"></i></div>
                            <h4>Misión</h4>
                            <p>Proveer los mejores productos de chocolate con el mejor servicio al cliente y a precios competitivos.</p>
                        </div>
                        <div class="mv-item">
                            <div class="mv-icon"><i class="bi bi-telescope"></i></div>
                            <h4>Visión</h4>
                            <p>Convertirse en la empresa más importante de exportación de chocolates gourmet desde Perú.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="cta-card">
                <div class="cta-icon">
                    <i class="bi bi-chat-dots-fill"></i>
                </div>
                <h3>¿Tienes alguna pregunta?</h3>
                <p>Estamos aquí para ayudarte. Contáctanos o explora nuestro catálogo.</p>
                <a href="tienda.php" class="btn btn-cta-primary">
                    <i class="bi bi-shop me-2"></i>Ir a la Tienda
                </a>
                <a href="https://chat.whatsapp.com/H7UkFVMRxOcAC0Nu61SxO0" class="btn btn-cta-secondary" target="_blank">
                    <i class="bi bi-envelope me-2"></i>Contáctanos
                </a>
            </div>
        </div>
    </div>
</section>

<link rel="stylesheet" href="css/index.css">

<?php include 'includes/public_footer.php'; ?>