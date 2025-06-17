<?php
// Configuración de la base de datos
$servidor = "localhost"; // Generalmente es 'localhost' para XAMPP
$usuario_db = "root";    // Usuario por defecto de XAMPP
$contrasena_db = "";     // Contraseña por defecto de XAMPP, a menudo vacía
$nombre_db = "mindschool"; // El nombre de tu base de datos

// Crear conexión
$conexion = new mysqli($servidor, $usuario_db, $contrasena_db, $nombre_db);

// Verificar la conexión
if ($conexion->connect_error) {
    // Si hay un error, lo mostramos y terminamos la ejecución
    die("Error de conexión a la base de datos: " . $conexion->connect_error);
}

// Establecer el conjunto de caracteres a UTF-8 para evitar problemas con tildes y ñ
$conexion->set_charset("utf8");

?>