<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// 1. Verificar autenticación y rol
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['mensaje_error'] = "Debes iniciar sesión para inscribirte a un curso.";
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$id_usuario_sesion = $_SESSION['id_usuario'];
$rol_usuario_sesion = $_SESSION['rol_usuario'];

// Solo los alumnos pueden inscribirse a cursos de esta manera
if ($rol_usuario_sesion !== 'alumno') {
    $_SESSION['mensaje_error'] = "Solo los alumnos pueden inscribirse a cursos.";
    header("Location: " . RUTA_BASE . "dashboard.php"); // Redirigir a su dashboard si no es alumno
    exit();
}

$id_curso = $_GET['id'] ?? null; // Obtener el ID del curso de la URL

if (!$id_curso || !is_numeric($id_curso)) {
    $_SESSION['mensaje_error'] = "ID de curso no válido para inscripción.";
    header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php"); // Redirigir al listado de cursos
    exit();
}

// Iniciar una transacción para asegurar la integridad de los datos
$conexion->begin_transaction();

try {
    // 2. Verificar que el curso exista y esté activo
    $stmt_curso = $conexion->prepare("SELECT id_curso, nombre_curso, precio, estado FROM cursos WHERE id_curso = ?");
    if (!$stmt_curso) {
        throw new Exception("Error al preparar la consulta del curso: " . $conexion->error);
    }
    $stmt_curso->bind_param("i", $id_curso);
    $stmt_curso->execute();
    $resultado_curso = $stmt_curso->get_result();

    if ($resultado_curso->num_rows === 0) {
        throw new Exception("El curso al que intentas inscribirte no existe.");
    }
    $curso = $resultado_curso->fetch_assoc();
    $stmt_curso->close();

    if ($curso['estado'] !== 'activo') {
        throw new Exception("No puedes inscribirte a este curso. Su estado actual es: " . htmlspecialchars($curso['estado']) . ".");
    }

    // 3. Verificar que el alumno no esté ya inscrito en este curso
    // El id_usuario_sesion es el mismo que el id_alumno, ya que 'alumnos.id_alumno' es FK a 'usuarios.id_usuario'
    $stmt_inscripcion_existente = $conexion->prepare("SELECT id_inscripcion FROM inscripciones WHERE id_alumno = ? AND id_curso = ?");
    if (!$stmt_inscripcion_existente) {
        throw new Exception("Error al preparar la consulta de inscripción existente: " . $conexion->error);
    }
    $stmt_inscripcion_existente->bind_param("ii", $id_usuario_sesion, $id_curso);
    $stmt_inscripcion_existente->execute();
    $resultado_inscripcion_existente = $stmt_inscripcion_existente->get_result();

    if ($resultado_inscripcion_existente->num_rows > 0) {
        throw new Exception("Ya estás inscrito en el curso '" . htmlspecialchars($curso['nombre_curso']) . "'.");
    }
    $stmt_inscripcion_existente->close();

    // 4. Realizar la inscripción
    // La tabla `inscripciones` registra que un `id_alumno` se inscribió a un `id_curso`.
    // La columna `precio` en `cursos` es informativa. Si el `precio` es > 0, aquí iría una lógica de pasarela de pago real.
    // Para este MVP, asumiremos que si el precio es > 0, el "pago" se simula al momento de la inscripción.
    $fecha_inscripcion = date('Y-m-d H:i:s');
    $estado_inscripcion = 'activa'; // Otros estados podrían ser 'completada', 'abandonada', 'pendiente_pago'

    $stmt_insertar_inscripcion = $conexion->prepare("INSERT INTO inscripciones (id_alumno, id_curso, fecha_inscripcion, estado_inscripcion) VALUES (?, ?, ?, ?)");
    if (!$stmt_insertar_inscripcion) {
        throw new Exception("Error al preparar la inserción de la inscripción: " . $conexion->error);
    }
    $stmt_insertar_inscripcion->bind_param("iiss", $id_usuario_sesion, $id_curso, $fecha_inscripcion, $estado_inscripcion);

    if (!$stmt_insertar_inscripcion->execute()) {
        throw new Exception("Error al registrar la inscripción: " . $stmt_insertar_inscripcion->error);
    }
    $stmt_insertar_inscripcion->close();

    $conexion->commit(); // Confirmar la transacción
    $_SESSION['mensaje_exito'] = "¡Felicitaciones! Te has inscrito en el curso '" . htmlspecialchars($curso['nombre_curso']) . "' con éxito.";

} catch (Exception $e) {
    $conexion->rollback(); // Revertir la transacción en caso de error
    $_SESSION['mensaje_error'] = "Error al inscribirte en el curso: " . $e->getMessage();
}

$conexion->close();

// Redirigir siempre a la página de detalle del curso para ver el mensaje
header("Location: " . RUTA_BASE . "paginas/cursos/detalle_curso.php?id=" . $id_curso);
exit();
?>