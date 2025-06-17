<?php
session_start();
// Incluir el archivo de configuración para usar RUTA_BASE
include_once 'includes/config.php';

// Redirigir a login si no hay sesión iniciada
if (!isset($_SESSION['id_usuario'])) {
    // Redirigir a la página de login dentro de la carpeta de autenticación
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php"); 
    exit();
} else {
    // Si ya hay sesión iniciada, redirigir al dashboard
    header("Location: " . RUTA_BASE . "dashboard.php"); 
    exit();
}
?>