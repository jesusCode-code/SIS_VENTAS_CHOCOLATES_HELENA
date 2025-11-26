<?php
// 1. Iniciamos la sesión
session_start();

// 2. Control de seguridad
// Si la variable de sesión 'logueado' no existe o no es true,
// lo redirigimos al login.
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header("Location: login.php");
    exit; // Detenemos la ejecución
}
// Si la sesión es válida, el script continúa.
?>