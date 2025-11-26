<?php
// 1. INICIAMOS LA SESIÓN ANTES DE CUALQUIER HTML
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Seguridad del Cliente
if (!isset($_SESSION['cliente_logueado']) || $_SESSION['cliente_logueado'] !== true) { 
    header("Location: login.php");
    exit;
}

// 3. Incluimos el header PÚBLICO y la conexión
include 'includes/public_header.php';
include 'includes/conexion.php'; 

// 4. Obtenemos los IDs del Cliente de la sesión
$idcliente_actual = $_SESSION['idcliente'];
$idusuario_actual = $_SESSION['idusuario_cliente'];

// 5. Lógica de Pestañas (Secciones)
$seccion_actual = $_GET['seccion'] ?? 'historial'; 
$tab_historial = ($seccion_actual == 'historial') ? 'active' : '';
$tab_datos = ($seccion_actual == 'datos') ? 'active' : '';
$tab_clave = ($seccion_actual == 'clave') ? 'active' : '';
$tab_valorar = ($seccion_actual == 'valorar') ? 'active' : '';

if (!empty($_POST)) { 
    if (isset($_POST['idpersona'])) $tab_datos = 'active';
    if (isset($_POST['clave_actual'])) $tab_clave = 'active';
}
if (isset($_SESSION['mensaje_val_cliente']) || isset($_SESSION['error_val_cliente'])) {
    $tab_valorar = 'active';
    $tab_historial = ''; 
}
if (empty($tab_historial) && empty($tab_datos) && empty($tab_clave) && empty($tab_valorar)) {
    $tab_historial = 'active';
}

// LÓGICA DE LA PESTAÑA "MIS DATOS"
$sql_get_persona = "SELECT IDPERSONA FROM USUARIO WHERE IDUSUARIO = ?";
$stmt_get_persona = sqlsrv_query($conn, $sql_get_persona, array($idusuario_actual));
$idpersona_actual = sqlsrv_fetch_array($stmt_get_persona, SQLSRV_FETCH_ASSOC)['IDPERSONA'];

$sql_persona = "SELECT * FROM PERSONA WHERE IDPERSONA = ?";
$stmt_persona = sqlsrv_query($conn, $sql_persona, array($idpersona_actual));
$datos_persona = sqlsrv_fetch_array($stmt_persona, SQLSRV_FETCH_ASSOC);

$sql_dist = "SELECT d.IDDISTRITO, d.NOMDISTRITO, p.NOMPROVINCIA 
             FROM DISTRITO d
             JOIN PROVINCIA p ON d.IDPROVINCIA = p.IDPROVINCIA
             WHERE d.ESTADO = '1' AND p.ESTADO = '1'
             ORDER BY p.NOMPROVINCIA, d.NOMDISTRITO";
$stmt_dist = sqlsrv_query($conn, $sql_dist);
$distritos = [];
while ($row_dist = sqlsrv_fetch_array($stmt_dist, SQLSRV_FETCH_ASSOC)) $distritos[] = $row_dist;

$sql_docs = "SELECT IDTIPO_DOCUMENTO, NOMDOCUMENTO FROM TIPO_DOCUMENTO WHERE ESTADO = '1' ORDER BY NOMDOCUMENTO";
$stmt_docs = sqlsrv_query($conn, $sql_docs);
$tipos_documento = [];
while ($row_doc = sqlsrv_fetch_array($stmt_docs, SQLSRV_FETCH_ASSOC)) $tipos_documento[] = $row_doc;
?>

<!-- Hero Header de Cuenta -->
<div class="account-hero mb-5">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="welcome-section">
                <div class="welcome-icon">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div class="welcome-content">
                    <h1 class="welcome-title">¡Hola, <?php echo htmlspecialchars($_SESSION['nombre_cliente']); ?>! 👋</h1>
                    <p class="welcome-text">Gestiona tu cuenta, revisa tus pedidos y actualiza tu información personal</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 text-lg-end">
            <a href="cliente_logout.php" class="btn btn-logout">
                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
            </a>
        </div>
    </div>
</div>

<!-- Mensajes de Feedback -->
<div class="messages-container mb-4">
    <?php
    if (isset($_SESSION['mensaje_cuenta'])) {
        echo '<div class="alert alert-success alert-dismissible fade show custom-alert" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_cuenta'] . 
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        unset($_SESSION['mensaje_cuenta']);
    }
    if (isset($_SESSION['error_cuenta'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show custom-alert" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_cuenta'] . 
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        unset($_SESSION['error_cuenta']);
    }
    if (isset($_SESSION['mensaje_val_cliente'])) {
        echo '<div class="alert alert-success alert-dismissible fade show custom-alert" role="alert">
                <i class="bi bi-star-fill me-2"></i>' . $_SESSION['mensaje_val_cliente'] . 
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        unset($_SESSION['mensaje_val_cliente']);
    }
    if (isset($_SESSION['error_val_cliente'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show custom-alert" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_val_cliente'] . 
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        unset($_SESSION['error_val_cliente']);
    }
    ?>
</div>

<!-- Contenido Principal con Tabs -->
<div class="row g-4">
    <!-- Sidebar de Navegación -->
    <div class="col-lg-3">
        <div class="account-sidebar">
            <div class="sidebar-header">
                <h5><i class="bi bi-grid-3x3-gap me-2"></i>Mi Panel</h5>
            </div>
            <div class="nav flex-column" id="v-pills-tab" role="tablist">
                <button class="nav-link account-nav-link <?php echo $tab_historial; ?>" 
                        id="v-pills-historial-tab" 
                        data-bs-toggle="pill" 
                        data-bs-target="#v-pills-historial" 
                        type="button" role="tab">
                    <i class="bi bi-clock-history me-2"></i>
                    <span>Historial de Compras</span>
                </button>
                <button class="nav-link account-nav-link <?php echo $tab_datos; ?>" 
                        id="v-pills-datos-tab" 
                        data-bs-toggle="pill" 
                        data-bs-target="#v-pills-datos" 
                        type="button" role="tab">
                    <i class="bi bi-person-lines-fill me-2"></i>
                    <span>Mis Datos Personales</span>
                </button>
                <button class="nav-link account-nav-link <?php echo $tab_clave; ?>" 
                        id="v-pills-clave-tab" 
                        data-bs-toggle="pill" 
                        data-bs-target="#v-pills-clave" 
                        type="button" role="tab">
                    <i class="bi bi-shield-lock me-2"></i>
                    <span>Cambiar Contraseña</span>
                </button>
                <button class="nav-link account-nav-link <?php echo $tab_valorar; ?>" 
                        id="v-pills-valorar-tab" 
                        data-bs-toggle="pill" 
                        data-bs-target="#v-pills-valorar" 
                        type="button" role="tab">
                    <i class="bi bi-star-fill me-2"></i>
                    <span>Valorar Productos</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Contenido de las Pestañas -->
    <div class="col-lg-9">
        <div class="tab-content" id="v-pills-tabContent">
            
            <!-- TAB: HISTORIAL DE COMPRAS -->
            <div class="tab-pane fade <?php echo $tab_historial == 'active' ? 'show active' : ''; ?>" 
                 id="v-pills-historial" role="tabpanel">
                <?php 
                if($tab_historial == 'active') {
                    // Lógica de paginación e historial
                    $registros_por_pagina = 10;
                    $pagina_actual = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
                    $offset = ($pagina_actual - 1) * $registros_por_pagina;
                    $sql_total = "SELECT COUNT(IDVENTA) AS total FROM VENTA WHERE IDCLIENTE = ?";
                    $stmt_total = sqlsrv_query($conn, $sql_total, array($idcliente_actual));
                    $total_registros = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC)['total'];
                    $total_paginas = ceil($total_registros / $registros_por_pagina);
                    $sql_ventas = "SELECT v.IDVENTA, v.FECHAVENTA, v.SUBTOTAL, v.IGV, v.TOTAL, v.ESTADO,
                                    COALESCE(b.SERIE + '-' + b.NUMERO, f.SERIE + '-' + f.NUMERO) AS NumeroComprobante,
                                    CASE WHEN f.IDFACTURA IS NOT NULL THEN 'Factura' WHEN b.IDBOLETA IS NOT NULL THEN 'Boleta' ELSE 'Pendiente' END AS TipoComprobante
                                    FROM VENTA v LEFT JOIN BOLETA b ON v.IDVENTA = b.IDVENTA LEFT JOIN FACTURA f ON v.IDVENTA = f.IDVENTA
                                    WHERE v.IDCLIENTE = ? ORDER BY v.FECHAVENTA DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
                    $params_ventas = array($idcliente_actual, $offset, $registros_por_pagina);
                    $stmt_ventas = sqlsrv_query($conn, $sql_ventas, $params_ventas);
                    if ($stmt_ventas === false) { die(print_r(sqlsrv_errors(), true)); }
                    $ventas = []; $ids_de_ventas = [];
                    while ($row = sqlsrv_fetch_array($stmt_ventas, SQLSRV_FETCH_ASSOC)) { $ventas[] = $row; $ids_de_ventas[] = $row['IDVENTA']; }
                    $detalles = [];
                    if (count($ids_de_ventas) > 0) {
                        $placeholders = implode(',', array_fill(0, count($ids_de_ventas), '?'));
                        $sql_detalle = "SELECT dv.IDVENTA, dv.CANTIDAD, p.NOMPRODUCTO, dv.PRECIO_UNITARIO, dv.SUBTOTAL
                                        FROM DETALLE_VENTA dv INNER JOIN PRODUCTO p ON dv.IDPRODUCTO = p.IDPRODUCTO
                                        WHERE dv.IDVENTA IN ($placeholders) ORDER BY p.NOMPRODUCTO ASC";
                        $stmt_detalle = sqlsrv_query($conn, $sql_detalle, $ids_de_ventas);
                        if ($stmt_detalle === false) { die(print_r(sqlsrv_errors(), true)); }
                        while ($row_det = sqlsrv_fetch_array($stmt_detalle, SQLSRV_FETCH_ASSOC)) { $detalles[ $row_det['IDVENTA'] ][] = $row_det; }
                    }
                    include 'includes/mi_cuenta_historial.php'; 
                }
                ?>
            </div>

            <!-- TAB: MIS DATOS PERSONALES -->
            <div class="tab-pane fade <?php echo $tab_datos == 'active' ? 'show active' : ''; ?>" 
                 id="v-pills-datos" role="tabpanel">
                <div class="content-card">
                    <div class="card-header-custom">
                        <h5><i class="bi bi-person-lines-fill me-2"></i>Mis Datos Personales</h5>
                        <p class="text-muted mb-0">Actualiza tu información personal y de contacto</p>
                    </div>
                    <div class="card-body-custom">
                        <form action="backend/actualizar_mis_datos.php" method="POST">
                            <input type="hidden" name="idpersona" value="<?php echo $idpersona_actual; ?>">
                            
                            <div class="form-section">
                                <h6 class="section-title">
                                    <i class="bi bi-person-badge me-2"></i>Información Personal
                                </h6>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Nombres</label>
                                        <input type="text" name="nombres" class="form-control form-control-modern" 
                                               value="<?php echo htmlspecialchars($datos_persona['NOMBRES']); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Apellido Paterno</label>
                                        <input type="text" name="apepaterno" class="form-control form-control-modern" 
                                               value="<?php echo htmlspecialchars($datos_persona['APEPATERNO']); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Apellido Materno</label>
                                        <input type="text" name="apematerno" class="form-control form-control-modern" 
                                               value="<?php echo htmlspecialchars($datos_persona['APEMATERNO']); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Fecha de Nacimiento</label>
                                        <input type="date" name="fec_nacimiento" class="form-control form-control-modern" 
                                               value="<?php echo ($datos_persona['FEC_NACIMIENTO']) ? $datos_persona['FEC_NACIMIENTO']->format('Y-m-d') : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Estado Civil</label>
                                        <select name="est_civil" class="form-select form-control-modern">
                                            <option value="S" <?php echo ($datos_persona['EST_CIVIL'] == 'S') ? 'selected' : ''; ?>>Soltero(a)</option>
                                            <option value="C" <?php echo ($datos_persona['EST_CIVIL'] == 'C') ? 'selected' : ''; ?>>Casado(a)</option>
                                            <option value="V" <?php echo ($datos_persona['EST_CIVIL'] == 'V') ? 'selected' : ''; ?>>Viudo(a)</option>
                                            <option value="D" <?php echo ($datos_persona['EST_CIVIL'] == 'D') ? 'selected' : ''; ?>>Divorciado(a)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6 class="section-title">
                                    <i class="bi bi-card-text me-2"></i>Documento de Identidad
                                </h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tipo de Documento</label>
                                        <select name="idtipo_documento" class="form-select form-control-modern" required>
                                            <?php foreach ($tipos_documento as $doc): ?>
                                            <option value="<?php echo $doc['IDTIPO_DOCUMENTO']; ?>" 
                                                    <?php echo ($doc['IDTIPO_DOCUMENTO'] == $datos_persona['IDTIPO_DOCUMENTO']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($doc['NOMDOCUMENTO']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">N° de Documento</label>
                                        <input type="text" name="doc_identidad" class="form-control form-control-modern" 
                                               value="<?php echo htmlspecialchars($datos_persona['DOC_IDENTIDAD']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6 class="section-title">
                                    <i class="bi bi-envelope-at me-2"></i>Datos de Contacto
                                </h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Celular (9 dígitos)</label>
                                        <input type="text" name="celular" class="form-control form-control-modern" 
                                               pattern="[0-9]{9}" value="<?php echo htmlspecialchars($datos_persona['CELULAR']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Correo Electrónico</label>
                                        <input type="email" name="correo" class="form-control form-control-modern" 
                                               value="<?php echo htmlspecialchars($datos_persona['CORREO']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6 class="section-title">
                                    <i class="bi bi-geo-alt me-2"></i>Ubicación
                                </h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Distrito</label>
                                        <select name="iddistrito" class="form-select form-control-modern" required>
                                            <option value="">-- Seleccione --</option>
                                            <?php foreach ($distritos as $dist): ?>
                                            <option value="<?php echo $dist['IDDISTRITO']; ?>" 
                                                    <?php echo ($dist['IDDISTRITO'] == $datos_persona['IDDISTRITO']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dist['NOMPROVINCIA'] . ' - ' . $dist['NOMDISTRITO']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Dirección</label>
                                        <input type="text" name="direccion" class="form-control form-control-modern" 
                                               value="<?php echo htmlspecialchars($datos_persona['DIRECCION']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-save">
                                    <i class="bi bi-check-circle me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TAB: CAMBIAR CONTRASEÑA -->
            <div class="tab-pane fade <?php echo $tab_clave == 'active' ? 'show active' : ''; ?>" 
                 id="v-pills-clave" role="tabpanel">
                <div class="content-card">
                    <div class="card-header-custom">
                        <h5><i class="bi bi-shield-lock me-2"></i>Cambiar Contraseña</h5>
                        <p class="text-muted mb-0">Actualiza tu contraseña para mantener tu cuenta segura</p>
                    </div>
                    <div class="card-body-custom">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <form action="backend/actualizar_mi_clave.php" method="POST">
                                    <div class="mb-4">
                                        <label for="clave_actual" class="form-label">Contraseña Actual</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" id="clave_actual" name="clave_actual" 
                                                   class="form-control form-control-modern" required>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label for="clave_nueva" class="form-label">Contraseña Nueva</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                                            <input type="password" id="clave_nueva" name="clave_nueva" 
                                                   class="form-control form-control-modern" required>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label for="clave_confirmar" class="form-label">Confirmar Contraseña Nueva</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-check2-square"></i></span>
                                            <input type="password" id="clave_confirmar" name="clave_confirmar" 
                                                   class="form-control form-control-modern" required>
                                        </div>
                                    </div>
                                    <div class="alert alert-info-custom">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Tip de seguridad:</strong> Usa una contraseña de al menos 8 caracteres con letras y números.
                                    </div>
                                    <button type="submit" class="btn btn-save w-100">
                                        <i class="bi bi-shield-check me-2"></i>Actualizar Contraseña
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: VALORAR PRODUCTOS -->
            <div class="tab-pane fade <?php echo $tab_valorar == 'active' ? 'show active' : ''; ?>" 
                 id="v-pills-valorar" role="tabpanel">
                <?php
                $sql_pendientes = "
                    SELECT DISTINCT p.IDPRODUCTO, p.NOMPRODUCTO
                    FROM DETALLE_VENTA dv
                    JOIN VENTA v ON dv.IDVENTA = v.IDVENTA
                    JOIN PRODUCTO p ON dv.IDPRODUCTO = p.IDPRODUCTO
                    LEFT JOIN VALORACION val ON p.IDPRODUCTO = val.IDPRODUCTO AND val.IDCLIENTE = v.IDCLIENTE
                    WHERE v.IDCLIENTE = ? AND val.IDVALORACION IS NULL";
                
                $stmt_pendientes = sqlsrv_query($conn, $sql_pendientes, array($idcliente_actual));
                if ($stmt_pendientes === false) { die(print_r(sqlsrv_errors(), true)); }
                
                $productos_pendientes = [];
                while ($row = sqlsrv_fetch_array($stmt_pendientes, SQLSRV_FETCH_ASSOC)) {
                    $productos_pendientes[] = $row;
                }
                ?>
                <div class="content-card">
                    <div class="card-header-custom">
                        <h5><i class="bi bi-star-fill me-2"></i>Valorar Productos</h5>
                        <p class="text-muted mb-0">Tu opinión nos ayuda a mejorar y ayuda a otros clientes</p>
                    </div>
                    <div class="card-body-custom">
                        <?php if (count($productos_pendientes) == 0): ?>
                            <div class="empty-state">
                                <i class="bi bi-check-circle"></i>
                                <h4>¡Todo al día!</h4>
                                <p>Ya has valorado todos los productos que has comprado. ¡Gracias por tu feedback!</p>
                            </div>
                        <?php else: ?>
                            <p class="mb-4">Tienes <strong><?php echo count($productos_pendientes); ?></strong> producto(s) pendiente(s) de valorar.</p>
                            
                            <?php foreach ($productos_pendientes as $producto): ?>
                            <div class="rating-card mb-4">
                                <form action="backend/enviar_valoracion.php" method="POST">
                                    <input type="hidden" name="idproducto" value="<?php echo $producto['IDPRODUCTO']; ?>">
                                    
                                    <div class="rating-product-name">
                                        <i class="bi bi-box-seam me-2"></i>
                                        <?php echo htmlspecialchars($producto['NOMPRODUCTO']); ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tu Puntuación:</label>
                                        <div class="rating-stars">
                                            <input type="radio" id="star5-<?php echo $producto['IDPRODUCTO']; ?>" name="puntuacion" value="5" required>
                                            <label for="star5-<?php echo $producto['IDPRODUCTO']; ?>">⭐</label>
                                            <input type="radio" id="star4-<?php echo $producto['IDPRODUCTO']; ?>" name="puntuacion" value="4">
                                            <label for="star4-<?php echo $producto['IDPRODUCTO']; ?>">⭐</label>
                                            <input type="radio" id="star3-<?php echo $producto['IDPRODUCTO']; ?>" name="puntuacion" value="3">
                                            <label for="star3-<?php echo $producto['IDPRODUCTO']; ?>">⭐</label>
                                            <input type="radio" id="star2-<?php echo $producto['IDPRODUCTO']; ?>" name="puntuacion" value="2">
                                            <label for="star2-<?php echo $producto['IDPRODUCTO']; ?>">⭐</label>
                                            <input type="radio" id="star1-<?php echo $producto['IDPRODUCTO']; ?>" name="puntuacion" value="1">
                                            <label for="star1-<?php echo $producto['IDPRODUCTO']; ?>">⭐</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="comentario-<?php echo $producto['IDPRODUCTO']; ?>" class="form-label">Tu Comentario:</label>
                                        <textarea id="comentario-<?php echo $producto['IDPRODUCTO']; ?>" 
                                                  name="comentario" 
                                                  class="form-control form-control-modern" 
                                                  rows="3" 
                                                  maxlength="150" 
                                                  placeholder="Cuéntanos tu experiencia con este producto (opcional)"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-submit-rating">
                                        <i class="bi bi-send me-2"></i>Enviar Valoración
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<link rel="stylesheet" href="css/mi_cuenta.css">



<?php include 'includes/public_footer.php'; ?>