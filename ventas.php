<?php
// 1. Seguridad, Header y Conexión
include 'includes/seguridad.php'; 
include 'includes/header.php';
include 'includes/conexion.php';

// 2. --- Cargar Datos para los Dropdowns ---

// (A) Clientes: Concatenamos Nombre y Documento para facilitar la búsqueda visual
$sql_clientes = "SELECT c.IDCLIENTE, 
                        COALESCE(p.NOMBRES + ' ' + p.APEPATERNO, e.RAZON_SOCIAL) AS NombreCliente,
                        CASE WHEN p.IDPERSONA IS NOT NULL THEN 'DNI: ' + p.DOC_IDENTIDAD ELSE 'RUC: ' + e.RUC END AS Documento
                 FROM CLIENTE c
                 LEFT JOIN PERSONA p ON c.IDPERSONA = p.IDPERSONA
                 LEFT JOIN EMPRESA e ON c.IDEMPRESA = e.IDEMPRESA
                 WHERE c.ESTADO = '1' 
                 ORDER BY NombreCliente";
$stmt_clientes = sqlsrv_query($conn, $sql_clientes);
$clientes = [];
while ($row = sqlsrv_fetch_array($stmt_clientes, SQLSRV_FETCH_ASSOC)) $clientes[] = $row;

// (B) Productos: Solo activos y con stock positivo
$sql_productos = "
    SELECT 
        p.IDPRODUCTO, p.NOMPRODUCTO, p.PRECIO AS PRECIO_ORIGINAL, p.STOCK, p.IMAGEN_URL,
        pr.PORCENTAJE_DESC
    FROM PRODUCTO p
    LEFT JOIN PROMOCION pr ON p.IDPRODUCTO = pr.IDPRODUCTO 
                          AND pr.ESTADO = '1' AND GETDATE() BETWEEN pr.FECHA_INICIO AND pr.FECHA_FIN
    WHERE p.ESTADO = '1' AND p.STOCK > 0 
    ORDER BY p.NOMPRODUCTO";
$stmt_productos = sqlsrv_query($conn, $sql_productos);
$productos = [];
while ($row = sqlsrv_fetch_array($stmt_productos, SQLSRV_FETCH_ASSOC)) {
    $precio_original = (float)$row['PRECIO_ORIGINAL'];
    $porcentaje_desc = (float)$row['PORCENTAJE_DESC'];
    $precio_final = $precio_original;
    $display_promo = "";
    
    if ($porcentaje_desc > 0) {
        $precio_final = $precio_original * (1 - ($porcentaje_desc / 100));
        $display_promo = " [OFERTA -" . number_format($porcentaje_desc, 0) . "%]";
    }
    
    $row['precio_final_calculado'] = $precio_final;
    $row['precio_original_display'] = number_format($precio_original, 2);
    $row['display_promo_texto'] = $display_promo;
    $productos[] = $row;
}

// (C) Métodos de Pago
$sql_metodos = "SELECT IDMETODOPAGO, NOMMETODO FROM METODO_PAGO WHERE ESTADO = '1' ORDER BY NOMMETODO";
$stmt_metodos = sqlsrv_query($conn, $sql_metodos);
$metodos_pago = [];
while ($row = sqlsrv_fetch_array($stmt_metodos, SQLSRV_FETCH_ASSOC)) $metodos_pago[] = $row;
?>

<!-- Header del POS -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-0"><i class="bi bi-cart3 text-primary me-2"></i> Punto de Venta</h1>
        <p class="text-muted small mb-0">Registra una nueva venta en el sistema.</p>
    </div>
    <a href="listado_ventas.php" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-clock-history me-1"></i> Historial
    </a>
</div>

<!-- Mensajes -->
<?php
if (isset($_SESSION['mensaje_venta'])) {
    echo '<div class="alert alert-success alert-dismissible fade show custom-alert mb-4"><i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['mensaje_venta'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['mensaje_venta']);
}
if (isset($_SESSION['error_venta'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error_venta'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['error_venta']);
}
?>

<form id="formVenta" action="backend/registrar_venta.php" method="POST" onsubmit="return validarVenta()">
    <div class="row g-4">
        
        <!-- COLUMNA IZQUIERDA: OPERACIONES -->
        <div class="col-lg-8">
            
            <!-- 1. Datos de Cabecera -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-person-vcard me-2"></i>Datos del Cliente</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="idCliente" class="form-label small fw-bold text-muted">Cliente</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                <select id="idCliente" name="idCliente" class="form-select border-start-0 ps-0" required>
                                    <option value="">-- Seleccionar Cliente --</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?php echo $cliente['IDCLIENTE']; ?>">
                                            <?php echo htmlspecialchars($cliente['NombreCliente'] . ' (' . $cliente['Documento'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label for="idMetodoPago" class="form-label small fw-bold text-muted">Pago</label>
                            <select id="idMetodoPago" name="idMetodoPago" class="form-select" required>
                                <?php foreach ($metodos_pago as $metodo): ?>
                                    <option value="<?php echo $metodo['IDMETODOPAGO']; ?>">
                                        <?php echo htmlspecialchars($metodo['NOMMETODO']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Selector de Productos -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center" 
                     style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-basket me-2"></i>Agregar Productos</h6>
                </div>
                <div class="card-body bg-light">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted">Buscar Producto</label>
                            <!-- Autocomplete="off" para mejor experiencia en lectores de códigos de barra si se usaran -->
                            <select id="selectProducto" class="form-select form-select-lg shadow-none border-primary">
                                <option value="">-- Seleccione o busque producto --</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option value="<?php echo $producto['IDPRODUCTO']; ?>"
                                            data-precio="<?php echo $producto['precio_final_calculado']; ?>"
                                            data-stock="<?php echo $producto['STOCK']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($producto['NOMPRODUCTO']); ?>">
                                        <?php echo htmlspecialchars($producto['NOMPRODUCTO']); ?> 
                                        | S/ <?php echo number_format($producto['precio_final_calculado'], 2); ?>
                                        | Stock: <?php echo $producto['STOCK']; ?>
                                        <?php echo $producto['display_promo_texto']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label small fw-bold text-muted">Cant.</label>
                            <input type="number" id="cantidadProducto" value="1" min="1" class="form-control form-control-lg text-center fw-bold">
                        </div>
                        <div class="col-md-2 col-6 d-grid">
                            <button type="button" id="btnAgregar" class="btn btn-warning btn-lg text-white fw-bold shadow-sm">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Tabla de Detalle -->
            <div class="card shadow-sm border-0" style="min-height: 300px;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light border-bottom">
                                <tr>
                                    <th class="ps-4 py-3 text-muted small text-uppercase">Producto</th>
                                    <th class="text-center py-3 text-muted small text-uppercase">Cant.</th>
                                    <th class="text-end py-3 text-muted small text-uppercase">Precio</th>
                                    <th class="text-end py-3 text-muted small text-uppercase">Subtotal</th>
                                    <th class="text-center pe-4 py-3" style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="tablaCarritoBody">
                                <!-- JS rellenará esto -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: TICKET -->
        <div class="col-lg-4">
            <div class="card shadow-lg border-0 sticky-top" style="top: 20px; border-radius: 16px; overflow: hidden;">
                <div class="card-header text-center py-4 bg-white border-bottom-0">
                    <h5 class="text-uppercase text-muted small ls-1 mb-1">Total a Pagar</h5>
                    <h1 class="display-4 fw-bold text-primary mb-0">S/ <span id="totalVenta">0.00</span></h1>
                </div>
                <div class="card-body bg-light border-top">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-bold">S/ <span id="subtotalVenta">0.00</span></span>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="text-muted">IGV (18%)</span>
                        <span class="fw-bold">S/ <span id="igvVenta">0.00</span></span>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" id="btnGuardarVenta" class="btn btn-success btn-lg py-3 fw-bold shadow-sm" disabled>
                            <i class="bi bi-check-circle-fill me-2"></i> CONFIRMAR VENTA
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="if(confirm('¿Vaciar carrito?')) location.reload()">
                            <i class="bi bi-x-circle me-2"></i> Cancelar
                        </button>
                    </div>
                </div>
                <div class="card-footer bg-white text-center py-3 text-muted small">
                    <i class="bi bi-shield-lock me-1"></i> Transacción segura
                </div>
            </div>
        </div>

    </div>

    <!-- Campos Ocultos -->
    <input type="hidden" name="subtotal_final" id="subtotal_final" value="0">
    <input type="hidden" name="igv_final" id="igv_final" value="0">
    <input type="hidden" name="total_final" id="total_final" value="0">
    <input type="hidden" name="carrito_json" id="carrito_json" value="[]">
</form>

<script>
    // ==========================================
    // LÓGICA DEL PUNTO DE VENTA (JS)
    // ==========================================
    let carrito = []; 
    const IGV_TASA = 0.18; 
    
    // Elementos del DOM
    const dom = {
        selectProducto: document.getElementById('selectProducto'),
        cantidadInput: document.getElementById('cantidadProducto'),
        btnAgregar: document.getElementById('btnAgregar'),
        tablaBody: document.getElementById('tablaCarritoBody'),
        btnGuardar: document.getElementById('btnGuardarVenta'),
        totales: {
            sub: document.getElementById('subtotalVenta'),
            igv: document.getElementById('igvVenta'),
            total: document.getElementById('totalVenta')
        },
        inputs: {
            sub: document.getElementById('subtotal_final'),
            igv: document.getElementById('igv_final'),
            total: document.getElementById('total_final'),
            json: document.getElementById('carrito_json'),
            cliente: document.getElementById('idCliente')
        }
    };
    
    // Inicialización
    dom.selectProducto.focus();
    renderizarCarrito();

    // Event Listeners
    dom.btnAgregar.addEventListener('click', agregarProducto);
    
    // Permitir agregar con Enter desde el input de cantidad
    dom.cantidadInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            agregarProducto();
        }
    });

    function agregarProducto() {
        const selectedOption = dom.selectProducto.options[dom.selectProducto.selectedIndex];
        const idProducto = selectedOption.value;
        
        if (!idProducto) { 
            alert("⚠️ Por favor, seleccione un producto."); 
            dom.selectProducto.focus();
            return; 
        }
        
        const cantidad = parseInt(dom.cantidadInput.value);
        if (isNaN(cantidad) || cantidad <= 0) { 
            alert("⚠️ La cantidad debe ser mayor a 0."); 
            dom.cantidadInput.focus();
            return; 
        }
        
        const precio = parseFloat(selectedOption.getAttribute('data-precio'));
        const stockMaximo = parseInt(selectedOption.getAttribute('data-stock'));
        const nombre = selectedOption.getAttribute('data-nombre'); // Usamos el nombre limpio del data attribute
        
        // Verificar si ya existe para sumar cantidad y validar stock total
        const itemExistente = carrito.find(item => item.id === idProducto);
        const cantidadActualEnCarrito = itemExistente ? itemExistente.cantidad : 0;
        
        if (cantidad + cantidadActualEnCarrito > stockMaximo) { 
            alert(`⛔ Stock insuficiente.\nDisponibles: ${stockMaximo}\nEn carrito: ${cantidadActualEnCarrito}\nIntentas agregar: ${cantidad}`); 
            return; 
        }
        
        if (itemExistente) {
            itemExistente.cantidad += cantidad;
            itemExistente.subtotal = itemExistente.cantidad * itemExistente.precio;
        } else {
            carrito.push({ 
                id: idProducto, 
                nombre: nombre, 
                cantidad: cantidad, 
                precio: precio, 
                stock: stockMaximo, 
                subtotal: cantidad * precio 
            });
        }
        
        // Resetear UI para siguiente producto
        dom.selectProducto.value = "";
        dom.cantidadInput.value = 1;
        dom.selectProducto.focus(); // Volver el foco al buscador
        
        renderizarCarrito();
        actualizarTotales();
    }

    function renderizarCarrito() {
        dom.tablaBody.innerHTML = ''; 
        
        if (carrito.length === 0) {
            dom.tablaBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-cart-x display-4 d-block mb-3 opacity-25"></i>
                        <p class="mb-0">El carrito está vacío</p>
                    </td>
                </tr>`;
            dom.btnGuardar.setAttribute('disabled', 'true');
            return;
        }
        
        dom.btnGuardar.removeAttribute('disabled');

        // Formateador de moneda para la vista
        const formatter = new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' });

        carrito.forEach((item, index) => {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td class="ps-4 py-3">
                    <div class="fw-bold text-dark">${item.nombre}</div>
                    <div class="small text-muted d-none d-sm-block" style="font-size: 0.75rem;">COD: ${item.id}</div>
                </td>
                <td class="text-center py-3">
                    <span class="badge bg-white text-dark border px-3 py-2 rounded-pill">${item.cantidad}</span>
                </td>
                <td class="text-end py-3 text-muted">${formatter.format(item.precio)}</td>
                <td class="text-end py-3 fw-bold text-primary">${formatter.format(item.subtotal)}</td>
                <td class="text-center pe-4 py-3">
                    <button type='button' onclick='eliminarItem(${index})' 
                            class='btn btn-link text-danger p-0' title="Quitar">
                        <i class="bi bi-trash-fill fs-5"></i>
                    </button>
                </td>
            `;
            dom.tablaBody.appendChild(fila);
        });
    }

    function eliminarItem(index) {
        carrito.splice(index, 1); 
        renderizarCarrito();
        actualizarTotales();
    }

    function actualizarTotales() {
        let subtotalVenta = 0;
        carrito.forEach(item => { subtotalVenta += item.subtotal; });
        
        const igvVenta = subtotalVenta * IGV_TASA;
        const totalVenta = subtotalVenta + igvVenta;
        
        // Actualizar Vista (Ticket)
        dom.totales.sub.textContent = subtotalVenta.toFixed(2);
        dom.totales.igv.textContent = igvVenta.toFixed(2);
        dom.totales.total.textContent = totalVenta.toFixed(2);
        
        // Actualizar Inputs Ocultos (Backend)
        dom.inputs.sub.value = subtotalVenta.toFixed(2);
        dom.inputs.igv.value = igvVenta.toFixed(2);
        dom.inputs.total.value = totalVenta.toFixed(2);
    }

    function validarVenta() {
        if (carrito.length === 0) {
            alert("⚠️ El carrito está vacío.");
            return false;
        }
        
        if (!dom.inputs.cliente.value) {
            alert("⚠️ Debe seleccionar un cliente.");
            dom.inputs.cliente.focus();
            return false;
        }

        if(!confirm("¿Confirma procesar esta venta por S/ " + dom.totales.total.textContent + "?")) {
             return false;
        }

        // Serializar carrito
        dom.inputs.json.value = JSON.stringify(carrito);
        return true;
    }
</script>

<?php include 'includes/footer.php'; ?>