<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario está autenticado y es un profesor o administrador
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol_usuario'] !== 'profesor' && $_SESSION['rol_usuario'] !== 'admin')) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$rol_usuario = $_SESSION['rol_usuario'];
$id_usuario_sesion = $_SESSION['id_usuario'];

$aula = null;
$mensaje_exito = '';
$mensaje_error = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_aula = (int)$_GET['id'];

    // Lógica para procesar el formulario de actualización
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nombre_aula = trim($_POST['nombre_aula'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        // Validar campos
        if (empty($nombre_aula)) {
            $mensaje_error = "El nombre del aula es obligatorio.";
        } else {
            // Verificar permisos antes de actualizar
            $stmt_check_owner = $conexion->prepare("SELECT id_profesor FROM aulas WHERE id_aula = ?");
            $stmt_check_owner->bind_param("i", $id_aula);
            $stmt_check_owner->execute();
            $stmt_check_owner->bind_result($id_profesor_aula);
            $stmt_check_owner->fetch();
            $stmt_check_owner->close();

            if ($rol_usuario === 'profesor' && $id_profesor_aula != $id_usuario_sesion) {
                $_SESSION['mensaje_error'] = "No tienes permiso para editar esta aula.";
                header("Location: " . RUTA_BASE . "paginas/aulas/listar_aulas.php");
                exit();
            }

            // Actualizar el aula en la base de datos
            $stmt = $conexion->prepare("UPDATE aulas SET nombre_aula = ?, descripcion = ? WHERE id_aula = ?");
            $stmt->bind_param("ssi", $nombre_aula, $descripcion, $id_aula);

            if ($stmt->execute()) {
                $mensaje_exito = "Aula actualizada con éxito.";
                // Recargar los datos del aula para mostrar los cambios
                // (no es estrictamente necesario aquí si siempre redirigimos, pero buena práctica)
            } else {
                $mensaje_error = "Error al actualizar el aula: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Obtener los datos actuales del aula para rellenar el formulario (o después de actualizar)
    $sql_aula = "SELECT id_aula, nombre_aula, descripcion, id_profesor FROM aulas WHERE id_aula = ?";
    $stmt_aula = $conexion->prepare($sql_aula);
    $stmt_aula->bind_param("i", $id_aula);
    $stmt_aula->execute();
    $resultado_aula = $stmt_aula->get_result();

    if ($resultado_aula->num_rows > 0) {
        $aula = $resultado_aula->fetch_assoc();

        // Verificar si el profesor actual tiene permiso para editar esta aula
        if ($rol_usuario === 'profesor' && $aula['id_profesor'] != $id_usuario_sesion) {
            $_SESSION['mensaje_error'] = "No tienes permiso para editar esta aula.";
            header("Location: " . RUTA_BASE . "paginas/aulas/listar_aulas.php");
            exit();
        }
    } else {
        $mensaje_error = "Aula no encontrada.";
    }
    $stmt_aula->close();

} else {
    $mensaje_error = "ID de aula no especificado o inválido.";
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Aula: <?php echo htmlspecialchars($aula['nombre_aula'] ?? 'Aula'); ?> - MindSchool</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background-color: #f0f2f5; color: #333; }
        .container { max-width: 800px; margin: 20px auto; padding: 30px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #0056b3; margin-bottom: 30px; font-size: 2.5em; font-weight: 600; }
        .navegacion { margin-bottom: 30px; text-align: center; display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; }
        .navegacion a { text-decoration: none; color: #007bff; padding: 10px 20px; border: 1px solid #007bff; border-radius: 25px; transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease; font-weight: 500; white-space: nowrap; }
        .navegacion a:hover { background-color: #007bff; color: white; transform: translateY(-2px); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .form-group input[type="text"],
        .form-group textarea {
            width: calc(100% - 22px); /* Ancho completo menos padding y borde */
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .btn-submit {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            display: block;
            width: 100%;
            margin-top: 30px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-submit:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        .mensaje-exito { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .mensaje-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="navegacion">
            <a href="<?php echo RUTA_BASE; ?>paginas/aulas/listar_aulas.php">Volver a Aulas</a>
            <?php if ($aula): // Solo mostrar si el aula existe y los permisos son correctos ?>
                <a href="<?php echo RUTA_BASE; ?>paginas/aulas/ver_aula.php?id=<?php echo htmlspecialchars($aula['id_aula']); ?>">Ver Aula</a>
            <?php endif; ?>
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">Panel de Control</a>
            <a href="<?php echo RUTA_BASE; ?>cerrar_sesion.php">Cerrar Sesión</a>
        </div>

        <h1>Editar Aula: <?php echo htmlspecialchars($aula['nombre_aula'] ?? 'Error'); ?></h1>

        <?php if ($mensaje_exito): ?>
            <p class="mensaje-exito"><?php echo $mensaje_exito; ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <?php if ($aula): ?>
            <form action="<?php echo RUTA_BASE; ?>paginas/aulas/editar_aula.php?id=<?php echo htmlspecialchars($aula['id_aula']); ?>" method="POST">
                <div class="form-group">
                    <label for="nombre_aula">Nombre del Aula:</label>
                    <input type="text" id="nombre_aula" name="nombre_aula" value="<?php echo htmlspecialchars($aula['nombre_aula']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción del Aula (Opcional):</label>
                    <textarea id="descripcion" name="descripcion"><?php echo htmlspecialchars($aula['descripcion'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">Guardar Cambios</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>