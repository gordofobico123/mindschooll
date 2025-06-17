<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario está autenticado y es un profesor o administrador
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol_usuario'] !== 'profesor' && $_SESSION['rol_usuario'] !== 'admin')) {
    $_SESSION['mensaje_error'] = "Acceso denegado. Debes ser profesor o administrador para eliminar aulas.";
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$rol_usuario = $_SESSION['rol_usuario'];
$id_usuario_sesion = $_SESSION['id_usuario'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_aula = (int)$_GET['id'];

    // Iniciar una transacción para asegurar la integridad de los datos
    $conexion->begin_transaction();

    try {
        // 1. Obtener el id_profesor del aula para verificar permisos
        $stmt_check_owner = $conexion->prepare("SELECT id_profesor FROM aulas WHERE id_aula = ?");
        $stmt_check_owner->bind_param("i", $id_aula);
        $stmt_check_owner->execute();
        $stmt_check_owner->bind_result($id_profesor_aula);
        $stmt_check_owner->fetch();
        $stmt_check_owner->close();

        // Verificar si el profesor actual tiene permiso para eliminar esta aula
        if ($rol_usuario === 'profesor' && $id_profesor_aula != $id_usuario_sesion) {
            $conexion->rollback(); // Revertir cualquier cambio (aunque no se haya hecho nada todavía)
            $_SESSION['mensaje_error'] = "No tienes permiso para eliminar esta aula.";
            header("Location: " . RUTA_BASE . "paginas/aulas/listar_aulas.php");
            exit();
        }

        // 2. Eliminar registros relacionados en aula_alumnos
        $stmt_alumnos = $conexion->prepare("DELETE FROM aula_alumnos WHERE id_aula = ?");
        $stmt_alumnos->bind_param("i", $id_aula);
        $stmt_alumnos->execute();
        $stmt_alumnos->close();

        // 3. Eliminar registros relacionados en aula_cursos
        $stmt_cursos = $conexion->prepare("DELETE FROM aula_cursos WHERE id_aula = ?");
        $stmt_cursos->bind_param("i", $id_aula);
        $stmt_cursos->execute();
        $stmt_cursos->close();

        // 4. Finalmente, eliminar el aula de la tabla principal 'aulas'
        $stmt_aula = $conexion->prepare("DELETE FROM aulas WHERE id_aula = ?");
        $stmt_aula->bind_param("i", $id_aula);
        
        if ($stmt_aula->execute()) {
            $conexion->commit(); // Confirmar la transacción
            $_SESSION['mensaje_exito'] = "Aula eliminada con éxito (ID: $id_aula).";
        } else {
            throw new Exception("Error al eliminar el aula principal: " . $stmt_aula->error);
        }
        $stmt_aula->close();

    } catch (Exception $e) {
        $conexion->rollback(); // Revertir todos los cambios si algo falla
        $_SESSION['mensaje_error'] = "Error al eliminar el aula: " . $e->getMessage();
    }
    
    $conexion->close();

} else {
    $_SESSION['mensaje_error'] = "ID de aula no especificado o inválido para eliminar.";
}

header("Location: " . RUTA_BASE . "paginas/aulas/listar_aulas.php");
exit();
?>