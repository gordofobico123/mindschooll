<?php
session_start(); // Inicia la sesión

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la sesión completamente, borre también la cookie de sesión.
// Nota: Esto destruirá la sesión, y no solo los datos de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión.
session_destroy();

// Redirigir al usuario a la página de inicio de sesión o a la página principal
// Asegúrate de que RUTA_BASE esté definido en config.php y que la ruta sea correcta
include_once '../../includes/config.php'; // Necesario para RUTA_BASE
header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php"); // Redirigir al login
exit();
?>