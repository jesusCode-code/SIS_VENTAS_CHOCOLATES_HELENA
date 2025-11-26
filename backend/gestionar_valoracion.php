<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones GET (Aprobar/Ocultar)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion'])) {
    
    $accion = $_GET['accion'];
    $id = $_GET['id'];
    $pagina_actual = $_GET['pagina'] ?? 1; 
    $search_term = $_GET['search'] ?? '';
    $search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
    $redirect_url = "../valoraciones.php?pagina=" . $pagina_actual . $search_url_param;
    $nuevo_estado = '';
    $mensaje = '';

    if ($accion == 'aprobar') {
        $nuevo_estado = '1'; // Activo/Aprobado
        $mensaje = 'aprobada';
    }
    
    if ($accion == 'ocultar') {
        $nuevo_estado = '0'; // Inactivo/Pendiente/Oculto
        $mensaje = 'ocultada';
    }

    if ($nuevo_estado !== '') {
        $sql = "UPDATE VALORACION SET ESTADO = ? WHERE IDVALORACION = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_val'] = "Error al cambiar el estado de la valoración.";
        } else {
            $_SESSION['mensaje_val'] = "Valoración " . $mensaje . " correctamente.";
        }
    }
    
    header("Location: ../valoraciones.php?pagina=" . $pagina_actual);
    exit;
}
?>