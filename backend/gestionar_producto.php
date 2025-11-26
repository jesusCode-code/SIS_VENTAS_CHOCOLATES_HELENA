<?php
// 1. Incluimos seguridad (sesión) y conexión ($conn)
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// ==================================================
// FUNCIÓN AUXILIAR PARA PROCESAR LA IMAGEN
// ==================================================
/**
 * Procesa, valida y mueve un archivo de imagen subido.
 * @param array $file La variable $_FILES['imagen']
 * @param string $directorio_destino Ruta donde se guardará (ej: '../img/')
 * @return string|null El nombre único del archivo si fue exitoso, o null si falló.
 */
function procesar_subida_imagen($file, $directorio_destino) {
    // Verifica si se subió un archivo y si no hay errores de PHP
    if (isset($file) && $file['error'] == UPLOAD_ERR_OK) {
        
        // Validar tipo de archivo (solo JPG y PNG)
        $tipos_permitidos = ['jpg', 'jpeg', 'png'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $tipos_permitidos)) {
            $_SESSION['error_producto'] = "Error: Solo se permiten archivos JPG o PNG.";
            return null;
        }

        // Crear un nombre de archivo único para evitar sobrescribir
        $nombre_unico = time() . '_' . uniqid() . '.' . $extension;
        $ruta_completa = $directorio_destino . $nombre_unico;

        // Mueve el archivo a nuestro directorio 'img/'
        if (move_uploaded_file($file['tmp_name'], $ruta_completa)) {
            return $nombre_unico; // Éxito: Devuelve el nuevo nombre
        } else {
            $_SESSION['error_producto'] = "Error: No se pudo mover el archivo subido.";
            return null;
        }
    }
    return null; // No se subió archivo nuevo
}

// Directorio de destino (subir un nivel desde 'backend' a 'img')
$directorio_img = "../img/";

// 2. Lógica para peticiones POST (Formularios de Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR PRODUCTO ---
    if ($accion == 'crear') {
        
        // 1. Procesa la imagen subida (si existe)
        $nombre_imagen = procesar_subida_imagen($_FILES['imagen'], $directorio_img);

        // 2. Prepara parámetros para la BD
        $params = [
            $_POST['idcategoria'],
            $_POST['nomproducto'],
            $_POST['descripcion'],
            $_POST['precio'],
            $_POST['stock'],
            '1', // Estado (siempre 'Activo' al crear)
            $nombre_imagen // IMAGEN_URL (o null si no se subió)
        ];
        
        // 3. Consulta SQL (ACTUALIZADA con IMAGEN_URL)
        $sql = "INSERT INTO PRODUCTO (IDCATEGORIA, NOMPRODUCTO, DESCRIPCION, PRECIO, STOCK, ESTADO, IMAGEN_URL) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_producto'] = "Error al guardar el producto: " . print_r(sqlsrv_errors(), true);
        } else {
            // No sobrescribe el error si la subida de imagen falló
            if (!isset($_SESSION['error_producto'])) { 
                $_SESSION['mensaje_producto'] = "¡Producto '" . htmlspecialchars($_POST['nomproducto']) . "' guardado!";
            }
        }
        header("Location: ../productos.php?pagina=1");
        exit;
    }

    // --- ACCIÓN: EDITAR PRODUCTO ---
    if ($accion == 'editar') {
        
        $idproducto = $_POST['idproducto'];
        $imagen_actual = $_POST['imagen_actual']; // Nombre de la imagen vieja (desde input hidden)

        // 1. Procesa la imagen (si se subió una nueva)
        $nombre_imagen_nueva = procesar_subida_imagen($_FILES['imagen'], $directorio_img);
        
        $nombre_imagen_final = $imagen_actual; // Por defecto, mantenemos la imagen vieja

        if ($nombre_imagen_nueva !== null) {
            // Si SÍ se subió una nueva, la usamos
            $nombre_imagen_final = $nombre_imagen_nueva;
            
            // Borra la imagen vieja del servidor ('img/') para ahorrar espacio
            if (!empty($imagen_actual) && file_exists($directorio_img . $imagen_actual)) {
                unlink($directorio_img . $imagen_actual);
            }
        }
        
        // 2. Prepara parámetros para la BD
        $params = [
            $_POST['idcategoria'],
            $_POST['nomproducto'],
            $_POST['descripcion'],
            $_POST['precio'],
            $_POST['stock'],
            $_POST['estado'],
            $nombre_imagen_final, // El nombre de la imagen (nueva o la vieja)
            $idproducto // WHERE
        ];

        // 3. Consulta SQL (ACTUALIZADA con IMAGEN_URL)
        $sql = "UPDATE PRODUCTO SET
                    IDCATEGORIA = ?, NOMPRODUCTO = ?, DESCRIPCION = ?,
                    PRECIO = ?, STOCK = ?, ESTADO = ?, IMAGEN_URL = ?
                WHERE IDPRODUCTO = ?";
        
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_producto'] = "Error al actualizar el producto: " . print_r(sqlsrv_errors(), true);
        } else {
            if (!isset($_SESSION['error_producto'])) {
                $_SESSION['mensaje_producto'] = "¡Producto '" . htmlspecialchars($_POST['nomproducto']) . "' actualizado!";
            }
        }
        header("Location: ../productos.php?pagina=1");
        exit;
    }

}

// 3. Lógica para peticiones GET (Botones de Activar/Desactivar)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion'])) {
    
    $accion = $_GET['accion'];
    $id = $_GET['id'];
    
    // ===================================
    // LÓGICA DE REDIRECCIÓN (Mantiene la página y el filtro de búsqueda)
    // ===================================
    $pagina_actual = $_GET['pagina'] ?? 1; 
    $search_term = $_GET['search'] ?? '';
    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
    $redirect_url = "../productos.php?pagina=" . $pagina_actual . $search_url_param;
    // ===================================

    $nuevo_estado = '';
    $mensaje = '';

    // Asigna el nuevo estado basado en la acción
    if ($accion == 'eliminar') { // 'eliminar' es el borrado lógico
        $nuevo_estado = '0';
        $mensaje = 'desactivado';
    }
    
    if ($accion == 'activar') {
        $nuevo_estado = '1';
        $mensaje = 'activado';
    }

    // Si se definió un nuevo estado, ejecuta la actualización
    if ($nuevo_estado !== '') {
        $sql = "UPDATE PRODUCTO SET ESTADO = ? WHERE IDPRODUCTO = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_producto'] = "Error al cambiar el estado.";
        } else {
            $_SESSION['mensaje_producto'] = "Producto " . $mensaje . " correctamente.";
        }
    }
    
    // Redirige al usuario de vuelta a la lista (manteniendo su página y filtro)
    header("Location: " . $redirect_url); 
    exit;
}
?>