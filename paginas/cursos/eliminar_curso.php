<?php
session_start();
include_once '../../includes/db.php'; // Ruta para la conexión a la DB
include_once '../../includes/config.php'; // Ruta para el archivo de configuración

// Verificar si el usuario está autenticado y tiene el rol permitido para eliminar
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol_usuario'] != 'profesor' && $_SESSION['rol_usuario'] != 'admin')) {
    $_SESSION['mensaje_error'] = "No tienes permiso para realizar esta acción.";
    header("Location: " . RUTA_BASE . "dashboard.php"); // Redirigir al dashboard si no tiene permisos
    exit();
}

$id_curso = null;

// Verificar que la solicitud sea POST (más seguro para eliminaciones)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_curso = $_POST['id'] ?? null; // Obtener el ID del curso de la solicitud POST
} else {
    // Si se intenta acceder directamente por GET, redirigir con un mensaje de error
    $_SESSION['mensaje_error'] = "Acceso no autorizado para eliminar directamente.";
    header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php");
    exit();
}

// Validar que el ID del curso sea numérico y no esté vacío
if (!$id_curso || !is_numeric($id_curso)) {
    $_SESSION['mensaje_error'] = "ID de curso no válido para eliminar.";
    header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php");
    exit();
}

// Preparar y ejecutar la consulta DELETE
$stmt = $conexion->prepare("DELETE FROM cursos WHERE id_curso = ?");
$stmt->bind_param("i", $id_curso);

if ($stmt->execute()) {
    $_SESSION['mensaje_exito'] = "Curso eliminado exitosamente.";
} else {
    // Manejar errores de la base de datos
    $_SESSION['mensaje_error'] = "Error al eliminar el curso: " . $conexion->error;
}

$stmt->close();
$conexion->close();

// Redirigir de vuelta a la lista de cursos después de la operación
header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php");
exit();
?>