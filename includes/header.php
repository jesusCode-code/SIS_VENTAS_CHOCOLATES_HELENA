<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- CONTROL DE ACCESO VISUAL ---
$rol_actual = $_SESSION['rol'] ?? '';
$esAdmin = ($rol_actual === 'Administrador');
$esVendedor = ($rol_actual === 'Vendedor');

// --- FUNCIONES DE AYUDA ---
function isActive($pageName)
{
    return basename($_SERVER['PHP_SELF']) === $pageName ? 'active' : '';
}
function isExpanded($pagesArray)
{
    return in_array(basename($_SERVER['PHP_SELF']), $pagesArray) ? 'true' : 'false';
}
function showCollapse($pagesArray)
{
    return in_array(basename($_SERVER['PHP_SELF']), $pagesArray) ? 'show' : '';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Chocolates Helena</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍫</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/custom-layout.css?v=4.3">
</head>

<body>

    <nav class="navbar mobile-navbar">
        <div class="container-fluid">
            <button class="btn btn-toggle text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                <i class="bi bi-list fs-4"></i>
            </button>
            <span class="navbar-brand mb-0 h1 text-white">🍫 Chocolates Helena</span>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start offcanvas-lg sidebar-nav d-flex flex-column" tabindex="-1" id="sidebarOffcanvas">

        <div class="sidebar-header">
            <div>
                <h2>🍫 Helena</h2>
                <p><?php echo $esAdmin ? 'ADMINISTRACIÓN' : 'PUNTO DE VENTA'; ?></p>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none position-absolute top-0 end-0 m-3" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body p-0 flex-grow-1 overflow-y-auto">
            <nav>
                <ul class="nav flex-column">

                    <li class="nav-item">
                        <a class="nav-link" href="index.php" target="_blank">
                            <i class="bi bi-shop-window"></i> <span>Ver Tienda Pública</span>
                        </a>
                    </li>

                    <?php if (isset($_SESSION['logueado']) && $_SESSION['logueado'] === true): ?>

                        <hr class="mx-3 my-2 border-secondary opacity-25">

                        <!-- Dashboard (Visible para todos) -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> <span>Panel Principal</span>
                            </a>
                        </li>

                        <!-- 1. VENTAS (Visible para todos: Admin y Vendedor) -->
                        <?php $pagesVentas = ['ventas.php', 'listado_ventas.php', 'comprobantes.php']; ?>
                        <li class="nav-item">
                            <a class="nav-link sidebar-link <?php echo isExpanded($pagesVentas) === 'true' ? '' : 'collapsed'; ?>"
                                data-bs-toggle="collapse" href="#menu-ventas" role="button"
                                aria-expanded="<?php echo isExpanded($pagesVentas); ?>">
                                <span class="d-flex align-items-center gap-2"><i class="bi bi-cart3"></i> Ventas</span>
                            </a>
                            <ul class="menu-items collapse <?php echo showCollapse($pagesVentas); ?>" id="menu-ventas">
                                <li><a class="<?php echo isActive('ventas.php'); ?>" href="ventas.php">Nueva Venta</a></li>
                                <li><a class="<?php echo isActive('listado_ventas.php'); ?>" href="listado_ventas.php">Historial</a></li>
                                <li><a class="<?php echo isActive('comprobantes.php'); ?>" href="comprobantes.php">Comprobantes</a></li>
                            </ul>
                        </li>

                        <!-- 2. PERSONAS  -->
                        <?php $pagesPersonas = ['personas.php', 'clientes.php', 'empresas.php', 'empleados.php', 'contactos_empresa.php']; ?>
                        <li class="nav-item">
                            <a class="nav-link sidebar-link <?php echo isExpanded($pagesPersonas) === 'true' ? '' : 'collapsed'; ?>"
                                data-bs-toggle="collapse" href="#menu-personas" role="button"
                                aria-expanded="<?php echo isExpanded($pagesPersonas); ?>">
                                <span class="d-flex align-items-center gap-2"><i class="bi bi-people"></i> Directorio</span>
                            </a>
                            <ul class="menu-items collapse <?php echo showCollapse($pagesPersonas); ?>" id="menu-personas">
                                <li><a class="<?php echo isActive('clientes.php'); ?>" href="clientes.php">Clientes</a></li>
                                <li><a class="<?php echo isActive('empresas.php'); ?>" href="empresas.php">Empresas</a></li>
                                <li><a class="<?php echo isActive('contactos_empresa.php'); ?>" href="contactos_empresa.php">Contactos</a></li>
                                <li><a class="<?php echo isActive('personas.php'); ?>" href="personas.php">Personas Naturales</a></li>

                                <?php if ($esAdmin): // Solo Admin ve empleados 
                                ?>
                                    <li><a class="<?php echo isActive('empleados.php'); ?>" href="empleados.php">Empleados</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>

                        <!-- MENÚS SOLO PARA ADMINISTRADOR -->
                        <?php if ($esAdmin): ?>

                            <!-- 3. INVENTARIO (Solo Admin edita productos) -->
                            <?php $pagesInv = ['productos.php', 'categorias.php', 'promociones.php', 'valoraciones.php']; ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo isExpanded($pagesInv) === 'true' ? '' : 'collapsed'; ?>"
                                    data-bs-toggle="collapse" href="#menu-inventario" role="button"
                                    aria-expanded="<?php echo isExpanded($pagesInv); ?>">
                                    <span class="d-flex align-items-center gap-2"><i class="bi bi-box-seam"></i> Inventario</span>
                                </a>
                                <ul class="menu-items collapse <?php echo showCollapse($pagesInv); ?>" id="menu-inventario">
                                    <li><a class="<?php echo isActive('productos.php'); ?>" href="productos.php">Productos</a></li>
                                    <li><a class="<?php echo isActive('categorias.php'); ?>" href="categorias.php">Categorías</a></li>
                                    <li><a class="<?php echo isActive('promociones.php'); ?>" href="promociones.php">Promociones</a></li>
                                    <li><a class="<?php echo isActive('valoraciones.php'); ?>" href="valoraciones.php">Valoraciones</a></li>
                                </ul>
                            </li>

                            <!-- 4. REPORTES -->


                            <li class="nav-item">
                                <a class="nav-link collapsed d-flex justify-content-between align-items-center"
                                    href="#"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#submenuReportes"
                                    aria-expanded="false"
                                    style="border-left: 4px solid #ffc107; background: rgba(255, 193, 7, 0.05);">

                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-bar-chart-line-fill text-warning"></i>
                                        <span class="fw-semibold">Reportes BI</span>
                                    </div>
                                    <i class="bi bi-chevron-down small transition-icon"></i>
                                </a>

                                <div id="submenuReportes" class="collapse" style="background-color: rgba(0,0,0,0.2);">
                                    <ul class="nav flex-column py-2">

                                        <li class="nav-item">
                                            <a class="nav-link ps-4 py-2 small d-flex align-items-center gap-2 text-white-50 hover-light" href="reportes.php?id=1">
                                                <i class="bi bi-speedometer2 text-info"></i> Resumen General
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a class="nav-link ps-4 py-2 small d-flex align-items-center gap-2 text-white-50 hover-light" href="reportes.php?id=2">
                                                <i class="bi bi-box-seam text-success"></i> Productos Top
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a class="nav-link ps-4 py-2 small d-flex align-items-center gap-2 text-white-50 hover-light" href="reportes.php?id=3">
                                                <i class="bi bi-people-fill text-primary"></i> Clientes VIP
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a class="nav-link ps-4 py-2 small d-flex align-items-center gap-2 text-white-50 hover-light" href="reportes.php?id=4">
                                                <i class="bi bi-person-badge text-danger"></i> Tendencias
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a class="nav-link ps-4 py-2 small d-flex align-items-center gap-2 text-white-50 hover-light" href="reportes.php?id=5">
                                                <i class="bi bi-graph-up-arrow text-warning"></i> Inteligencia de Rentabilidad
                                            </a>
                                        </li>

                                    </ul>
                                </div>
                            </li>
                            <!-- SECCIÓN CONFIGURACIÓN -->
                            <li class="nav-item mt-3 mb-1 px-3">
                                <div class="border-top border-secondary opacity-25 mb-2"></div>
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Configuración</small>
                            </li>

                            <!-- 5. FINANZAS -->
                            <?php $pagesFinanzas = ['metodos_pago.php']; ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo isExpanded($pagesFinanzas) === 'true' ? '' : 'collapsed'; ?>"
                                    data-bs-toggle="collapse" href="#menu-finanzas" role="button"
                                    aria-expanded="<?php echo isExpanded($pagesFinanzas); ?>">
                                    <span class="d-flex align-items-center gap-2"><i class="bi bi-wallet2"></i> Finanzas</span>
                                </a>
                                <ul class="menu-items collapse <?php echo showCollapse($pagesFinanzas); ?>" id="menu-finanzas">
                                    <li><a class="<?php echo isActive('metodos_pago.php'); ?>" href="metodos_pago.php">Métodos de Pago</a></li>
                                </ul>
                            </li>

                            <!-- 6. DOCUMENTOS -->
                            <?php $pagesDocs = ['tipos_documento.php']; ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo isExpanded($pagesDocs) === 'true' ? '' : 'collapsed'; ?>"
                                    data-bs-toggle="collapse" href="#menu-docs" role="button"
                                    aria-expanded="<?php echo isExpanded($pagesDocs); ?>">
                                    <span class="d-flex align-items-center gap-2"><i class="bi bi-file-earmark-text"></i> Documentos</span>
                                </a>
                                <ul class="menu-items collapse <?php echo showCollapse($pagesDocs); ?>" id="menu-docs">
                                    <li><a class="<?php echo isActive('tipos_documento.php'); ?>" href="tipos_documento.php">Tipos de Doc.</a></li>
                                </ul>
                            </li>

                            <!-- 7. UBICACIÓN -->
                            <?php $pagesGeo = ['departamentos.php', 'provincias.php', 'distritos.php']; ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo isExpanded($pagesGeo) === 'true' ? '' : 'collapsed'; ?>"
                                    data-bs-toggle="collapse" href="#menu-ubicacion" role="button"
                                    aria-expanded="<?php echo isExpanded($pagesGeo); ?>">
                                    <span class="d-flex align-items-center gap-2"><i class="bi bi-geo-alt"></i> Ubicación</span>
                                </a>
                                <ul class="menu-items collapse <?php echo showCollapse($pagesGeo); ?>" id="menu-ubicacion">
                                    <li><a class="<?php echo isActive('departamentos.php'); ?>" href="departamentos.php">Departamentos</a></li>
                                    <li><a class="<?php echo isActive('provincias.php'); ?>" href="provincias.php">Provincias</a></li>
                                    <li><a class="<?php echo isActive('distritos.php'); ?>" href="distritos.php">Distritos</a></li>
                                </ul>
                            </li>

                            <!-- 8. SISTEMA -->
                            <?php $pagesSistema = ['usuarios.php', 'roles.php', 'cargos.php', 'contratos.php']; ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo isExpanded($pagesSistema) === 'true' ? '' : 'collapsed'; ?>"
                                    data-bs-toggle="collapse" href="#menu-sistema" role="button"
                                    aria-expanded="<?php echo isExpanded($pagesSistema); ?>">
                                    <span class="d-flex align-items-center gap-2"><i class="bi bi-shield-lock"></i> Sistema</span>
                                </a>
                                <ul class="menu-items collapse <?php echo showCollapse($pagesSistema); ?>" id="menu-sistema">
                                    <li><a class="<?php echo isActive('usuarios.php'); ?>" href="usuarios.php">Usuarios</a></li>
                                    <li><a class="<?php echo isActive('roles.php'); ?>" href="roles.php">Roles</a></li>
                                    <li><a class="<?php echo isActive('cargos.php'); ?>" href="cargos.php">Cargos Laborales</a></li>
                                    <li><a class="<?php echo isActive('contratos.php'); ?>" href="contratos.php">Contratos</a></li>
                                </ul>
                            </li>

                        <?php endif; 
                        ?>

                        <!-- Cerrar Sesión -->
                        <li class="nav-item mt-4 mb-4">
                            <a class="nav-link logout-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> <span>Cerrar Sesión</span>
                            </a>
                        </li>

                    <?php else: ?>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-key"></i> <span>Iniciar Sesión</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>

        <!-- Footer Sidebar -->
        <?php if (isset($_SESSION['logueado']) && $_SESSION['logueado'] === true): ?>
            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="bi bi-person-circle"></i>
                    <div class="text-center w-100">
                        <strong class="d-block text-truncate" style="max-width: 160px; margin: 0 auto;"><?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Usuario'); ?></strong>
                        <small class="opacity-75 text-uppercase" style="font-size: 0.7rem;"><?php echo htmlspecialchars($_SESSION['rol'] ?? 'Invitado'); ?></small>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <main class="main-content">

        <div class="container-fluid content-body">
