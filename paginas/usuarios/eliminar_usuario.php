<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario está autenticado y es un ADMINISTRADOR
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'admin') {
    $_SESSION['mensaje_error'] = "No tienes permisos para realizar esta acción.";
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

$id_usuario_a_eliminar = $_GET['id'] ?? null;
$mensaje_exito = '';
$mensaje_error = '';

if (!$id_usuario_a_eliminar || !is_numeric($id_usuario_a_eliminar)) {
    $_SESSION['mensaje_error'] = "ID de usuario no válido o no proporcionado para eliminar.";
    header("Location: " . RUTA_BASE . "paginas/usuarios/listar_alumnos.php");
    exit();
}

$id_usuario_a_eliminar = (int)$id_usuario_a_eliminar;

// Seguridad adicional: No permitir que un administrador se elimine a sí mismo
if ($id_usuario_a_eliminar == $_SESSION['id_usuario']) {
    $_SESSION['mensaje_error'] = "No puedes eliminar tu propia cuenta de administrador.";
    header("Location: " . RUTA_BASE . "paginas/usuarios/listar_alumnos.php");
    exit();
}

// Iniciar una transacción para asegurar la integridad de los datos
$conexion->begin_transaction();

try {
    // 1. Obtener el rol del usuario que se va a eliminar
    $rol_usuario_a_eliminar = '';
    $stmt_get_rol = $conexion->prepare("SELECT rol FROM usuarios WHERE id_usuario = ?");
    if ($stmt_get_rol) {
        $stmt_get_rol->bind_param("i", $id_usuario_a_eliminar);
        $stmt_get_rol->execute();
        $resultado_rol = $stmt_get_rol->get_result();
        if ($fila_rol = $resultado_rol->fetch_assoc()) {
            $rol_usuario_a_eliminar = $fila_rol['rol'];
        }
        $stmt_get_rol->close();
    } else {
        throw new Exception("Error al obtener el rol del usuario: " . $conexion->error);
    }

    if (empty($rol_usuario_a_eliminar)) {
        throw new Exception("Usuario no encontrado o rol no definido.");
    }

    // 2. Manejo de Claves Foráneas (Foreign Keys) según el rol del usuario a eliminar
    // NOTA: Gracias a ON DELETE CASCADE en tu SQL, muchas eliminaciones se propagarán automáticamente
    // Sin embargo, es buena práctica entender el flujo y realizar eliminaciones explícitas si CASCADE no aplica o si hay lógica compleja.

    switch ($rol_usuario_a_eliminar) {
        case 'alumno':
            // Si el usuario es un alumno, eliminar de la tabla 'alumnos'.
            // Esto, a su vez, por ON DELETE CASCADE en `inscripciones` y `padres_alumnos`
            // debería eliminar las inscripciones y las relaciones padre-alumno.
            $stmt_delete_alumno = $conexion->prepare("DELETE FROM alumnos WHERE id_alumno = ?");
            if ($stmt_delete_alumno) {
                $stmt_delete_alumno->bind_param("i", $id_usuario_a_eliminar);
                $stmt_delete_alumno->execute();
                $stmt_delete_alumno->close();
            } else {
                throw new Exception("Error al eliminar el registro de alumno: " . $conexion->error);
            }
            break;

        case 'profesor':
            // Si el usuario es un profesor, eliminar de la tabla 'profesores'.
            // Según tu SQL, la FK de `cursos` a `profesores` no tiene ON DELETE CASCADE.
            // Entonces, si un profesor es eliminado, ¿qué pasa con sus cursos?
            // Actualmente tu `cursos` tiene `id_profesor` como FK a `profesores`.
            // Opción 1: Asignar sus cursos a NULL (si la columna `id_profesor` en `cursos` lo permite)
            // Opción 2: Asignar sus cursos a un profesor genérico/admin
            // Opción 3: Eliminar los cursos (¡PELIGROSO!)
            // Según el SQL que me diste, `id_profesor` en `cursos` NO tiene `ON DELETE SET NULL` ni `ON DELETE CASCADE`.
            // Esto significa que si eliminas un `profesor` DE LA TABLA `profesores`,
            // y hay cursos que hacen referencia a él, la eliminación fallará si la FK no permite NULL y no es CASCADE.
            // Sin embargo, tu `eliminar_usuario.php` actual ya tiene:
            // "UPDATE cursos SET id_profesor = NULL WHERE id_profesor = ?"
            // Esto es una buena solución si `id_profesor` en `cursos` permite NULL.
            // Si no lo permite, necesitarías cambiar la definición de la columna en `cursos`.

            $stmt_update_cursos = $conexion->prepare("UPDATE cursos SET id_profesor = NULL WHERE id_profesor = ?");
            if ($stmt_update_cursos) {
                $stmt_update_cursos->bind_param("i", $id_usuario_a_eliminar);
                $stmt_update_cursos->execute();
                $stmt_update_cursos->close();
            } else {
                throw new Exception("Error al desvincular cursos del profesor: " . $conexion->error);
            }

            // Ahora eliminar el registro de profesores
            $stmt_delete_profesor = $conexion->prepare("DELETE FROM profesores WHERE id_profesor = ?");
            if ($stmt_delete_profesor) {
                $stmt_delete_profesor->bind_param("i", $id_usuario_a_eliminar);
                $stmt_delete_profesor->execute();
                $stmt_delete_profesor->close();
            } else {
                throw new Exception("Error al eliminar el registro de profesor: " . $conexion->error);
            }
            break;

        case 'padre':
            // Si el usuario es un padre, eliminar de la tabla 'padres'.
            // Esto, por ON DELETE CASCADE en `padres_alumnos` debería eliminar las relaciones padre-alumno.
            $stmt_delete_padre = $conexion->prepare("DELETE FROM padres WHERE id_padre = ?");
            if ($stmt_delete_padre) {
                $stmt_delete_padre->bind_param("i", $id_usuario_a_eliminar);
                $stmt_delete_padre->execute();
                $stmt_delete_padre->close();
            } else {
                throw new Exception("Error al eliminar el registro de padre: " . $conexion->error);
            }
            break;

        // Si hay otros roles que tienen tablas específicas, añádelos aquí.
        // Por ejemplo, si 'admin' tuviera una tabla `administradores`.
    }

    // Paso 3: Eliminar al usuario de la tabla 'usuarios'
    // Esta es la última eliminación, ya que otras tablas referencian a 'usuarios'.
    $stmt_eliminar_usuario = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    if ($stmt_eliminar_usuario) {
        $stmt_eliminar_usuario->bind_param("i", $id_usuario_a_eliminar);
        if ($stmt_eliminar_usuario->execute()) {
            if ($stmt_eliminar_usuario->affected_rows > 0) {
                $conexion->commit(); // Confirmar la transacción
                $_SESSION['mensaje_exito'] = "Usuario eliminado con éxito.";
            } else {
                $conexion->rollback(); // Revertir la transacción si no se eliminó nada
                $_SESSION['mensaje_error'] = "El usuario no pudo ser eliminado. Posiblemente ya no existe.";
            }
        } else {
            throw new Exception("Error al ejecutar la eliminación del usuario: " . $stmt_eliminar_usuario->error);
        }
        $stmt_eliminar_usuario->close();
    } else {
        throw new Exception("Error al preparar la eliminación del usuario: " . $conexion->error);
    }

} catch (Exception $e) {
    $conexion->rollback(); // Revertir todos los cambios si algo falla
    $_SESSION['mensaje_error'] = "Error al eliminar el usuario: " . $e->getMessage();
}

$conexion->close();

// Redirigir de vuelta a la lista de usuarios
header("Location: " . RUTA_BASE . "paginas/usuarios/listar_alumnos.php");
exit();
?>