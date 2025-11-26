<?php
session_start();

// 1. Inicializar el carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// 2. Definir la página a la que regresaremos
$pagina_retorno = $_POST['pagina_anterior'] ?? '../carrito.php'; // Por defecto, vuelve al carrito

// 3. Validar que sea POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $accion = $_POST['accion'] ?? 'agregar';
    $idproducto = (int)($_POST['idproducto'] ?? 0);

    // --- ACCIÓN: AGREGAR (Desde producto_detalle.php) ---
    if ($accion == 'agregar' && $idproducto > 0) {
        $cantidad = (int)($_POST['cantidad'] ?? 1);
        
        // Datos del producto (los pasamos desde el formulario)
        $nombre = $_POST['nombre_producto'];
        $precio = (float)$_POST['precio_final'];
        $imagen = $_POST['imagen_url'];

        if (isset($_SESSION['carrito'][$idproducto])) {
            $_SESSION['carrito'][$idproducto]['cantidad'] += $cantidad;
        } else {
            $_SESSION['carrito'][$idproducto] = [
                'id' => $idproducto,
                'nombre' => $nombre,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'imagen' => $imagen
            ];
        }
        
        $_SESSION['mensaje_carrito'] = "¡Producto '" . htmlspecialchars($nombre) . "' añadido al carrito!";
    }

    // --- ACCIÓN: ACTUALIZAR (Desde carrito.php) ---
    elseif ($accion == 'actualizar' && $idproducto > 0) {
        $cantidad = (int)($_POST['cantidad'] ?? 1);
        
        if (isset($_SESSION['carrito'][$idproducto])) {
            if ($cantidad > 0) {
                $_SESSION['carrito'][$idproducto]['cantidad'] = $cantidad;
            } else {
                // Si la cantidad es 0 o menos, lo eliminamos
                unset($_SESSION['carrito'][$idproducto]);
            }
        }
        $_SESSION['mensaje_carrito'] = "Carrito actualizado.";
    }

    // --- ACCIÓN: ELIMINAR (Desde carrito.php) ---
    elseif ($accion == 'eliminar' && $idproducto > 0) {
        if (isset($_SESSION['carrito'][$idproducto])) {
            unset($_SESSION['carrito'][$idproducto]);
        }
        $_SESSION['mensaje_carrito'] = "Producto eliminado del carrito.";
    }

}

// 4. Redirigir de vuelta
header("Location: " . $pagina_retorno);
exit;
?>