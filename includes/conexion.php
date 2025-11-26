<?php

// Configuración de la conexión a SQL Server
$serverName = "DESKTOP-MC1FKRQ"; // ej: "localhost" o "MI-PC\SQLEXPRESS"
$connectionOptions = array(
    // ¡Actualizado con el nombre de tu BD!
    "Database" => "SIS_VENTAS_CHOCOLATES_HELENA_V1", 
    "Uid" => "sa",       
    "PWD" => "123"
);

// Conectar
$conn = sqlsrv_connect($serverName, $connectionOptions); 

if ($conn === false) {
    // Si la conexión falla, muestra el error y detiene la ejecución
    die(print_r(sqlsrv_errors(), true)); 
}

?>