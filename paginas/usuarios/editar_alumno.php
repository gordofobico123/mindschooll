<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario está autenticado y es un ADMINISTRADOR
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'admin') {
    // Redirigir si no tiene permisos
    $_SESSION['mensaje_error'] = "No tienes permisos para acceder a esta página.";
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

$id_usuario_a_editar = $_GET['id'] ?? null; // Obtener el ID del usuario de la URL
$usuario_existente = null; // Variable para almacenar los datos del usuario a editar
$mensaje_exito = '';
$mensaje_error = '';

// --- Lógica para obtener el usuario a editar ---
if (!$id_usuario_a_editar || !is_numeric($id_usuario_a_editar)) {
    $mensaje_error = "ID de usuario no válido o no proporcionado para edición.";
} else {
    $id_usuario_a_editar = (int)$id_usuario_a_editar; // Asegurar que sea un entero

    // Preparar y ejecutar la consulta para obtener los datos del usuario
    $stmt_usuario = $conexion->prepare("SELECT id_usuario, nombre, apellido, email, rol FROM usuarios WHERE id_usuario = ?");
    if ($stmt_usuario) {
        $stmt_usuario->bind_param("i", $id_usuario_a_editar);
        $stmt_usuario->execute();
        $resultado_usuario = $stmt_usuario->get_result();

        if ($resultado_usuario->num_rows > 0) {
            $usuario_existente = $resultado_usuario->fetch_assoc();
        } else {
            $mensaje_error = "No se encontró un usuario con el ID: " . htmlspecialchars($id_usuario_a_editar) . ".";
        }
        $stmt_usuario->close();
    } else {
        $mensaje_error = "Error al preparar la consulta para obtener usuario: " . $conexion->error;
    }
}

// --- Lógica para procesar la actualización del usuario ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $usuario_existente) {
    // Recoger y limpiar los datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = trim($_POST['rol'] ?? '');
    $password_nueva = $_POST['password_nueva'] ?? '';

    // Validaciones
    if (empty($nombre) || empty($apellido) || empty($email) || empty($rol)) {
        $mensaje_error = "Todos los campos obligatorios (Nombre, Apellido, Email, Rol) deben ser llenados.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "Formato de correo electrónico inválido.";
    } elseif (!in_array($rol, ['admin', 'profesor', 'alumno', 'padre'])) {
        $mensaje_error = "Rol de usuario inválido. Selecciona uno de la lista.";
    } else {
        // Verificar si el nuevo email ya existe para otro usuario (excluyendo al usuario actual)
        $stmt_check_email = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
        if ($stmt_check_email) {
            $stmt_check_email->bind_param("si", $email, $id_usuario_a_editar);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();
            if ($stmt_check_email->num_rows > 0) {
                $mensaje_error = "El correo electrónico '{$email}' ya está registrado por otro usuario.";
            }
            $stmt_check_email->close();
        } else {
            $mensaje_error = "Error al preparar la verificación de email: " . $conexion->error;
        }

        // Si no hay errores hasta ahora, proceder con la actualización
        if (empty($mensaje_error)) {
            $sql_update = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol = ?";
            $params = [$nombre, $apellido, $email, $rol];
            $types = "ssss";

            // Si se proporciona una nueva contraseña, añadirla a la consulta
            if (!empty($password_nueva)) {
                $hashed_password = password_hash($password_nueva, PASSWORD_DEFAULT);
                $sql_update .= ", password = ?";
                $params[] = $hashed_password;
                $types .= "s";
            }

            $sql_update .= " WHERE id_usuario = ?";
            $params[] = $id_usuario_a_editar;
            $types .= "i";

            $stmt_update = $conexion->prepare($sql_update);
            if ($stmt_update) {
                $stmt_update->bind_param($types, ...$params);

                if ($stmt_update->execute()) {
                    $mensaje_exito = "Usuario '{$nombre} {$apellido}' (ID: {$id_usuario_a_editar}) actualizado con éxito.";
                    
                    // Si el usuario editado es el mismo que está logueado, actualizar la sesión
                    if ($_SESSION['id_usuario'] == $id_usuario_a_editar) {
                        $_SESSION['nombre_usuario'] = $nombre . ' ' . $apellido; // Actualizar nombre completo en sesión
                        $_SESSION['rol_usuario'] = $rol; // Actualizar rol en sesión
                    }
                    
                    // Volver a cargar el usuario desde la DB para mostrar los datos actualizados en el formulario
                    $stmt_usuario_recargar = $conexion->prepare("SELECT id_usuario, nombre, apellido, email, rol FROM usuarios WHERE id_usuario = ?");
                    if ($stmt_usuario_recargar) {
                        $stmt_usuario_recargar->bind_param("i", $id_usuario_a_editar);
                        $stmt_usuario_recargar->execute();
                        $resultado_recarga = $stmt_usuario_recargar->get_result();
                        if ($resultado_recarga->num_rows > 0) {
                            $usuario_existente = $resultado_recarga->fetch_assoc();
                        }
                        $stmt_usuario_recargar->close();
                    }
                } else {
                    $mensaje_error = "Error al actualizar el usuario: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $mensaje_error = "Error al preparar la consulta de actualización: " . $conexion->error;
            }
        }
    }
}
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - MindSchool</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background-color: #f4f7f6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 30px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #0056b3; margin-bottom: 30px; font-size: 2.2em; font-weight: 600; }
        .navegacion { margin-bottom: 30px; text-align: center; display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; }
        .navegacion a { text-decoration: none; color: #007bff; padding: 10px 20px; border: 1px solid #007bff; border-radius: 25px; transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease; font-weight: 500; white-space: nowrap; }
        .navegacion a:hover { background-color: #007bff; color: white; transform: translateY(-2px); }

        .mensaje-exito { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .mensaje-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            width: calc(100% - 22px);
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #007bff;
            outline: none;
        }
        .btn-submit {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            width: 100%;
            margin-top: 20px;
        }
        .btn-submit:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        .info-actual {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9em;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navegacion">
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">Panel de Control</a>
            <a href="<?php echo RUTA_BASE; ?>paginas/usuarios/listar_alumnos.php">Gestionar Usuarios</a>
            <a href="<?php echo RUTA_BASE; ?>paginas/usuarios/crear_usuario.php">Crear Nuevo Usuario</a>
            <a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php">Gestionar Cursos</a>
            <a href="<?php echo RUTA_BASE; ?>cerrar_sesion.php">Cerrar Sesión</a>
        </div>

        <h1>Editar Usuario</h1>

        <?php if ($mensaje_exito): ?>
            <p class="mensaje-exito"><?php echo $mensaje_exito; ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <?php if ($usuario_existente): // Asegúrate de que el usuario existe antes de mostrar el formulario ?>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario_existente['nombre']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="apellido">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($usuario_existente['apellido']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Correo Electrónico:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario_existente['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password_nueva">Nueva Contraseña (dejar en blanco para no cambiar):</label>
                    <input type="password" id="password_nueva" name="password_nueva">
                    <small>Si no deseas cambiar la contraseña, deja este campo en blanco.</small>
                </div>
                <div class="form-group">
                    <label for="rol">Rol:</label>
                    <select id="rol" name="rol" required>
                        <option value="admin" <?php echo ($usuario_existente['rol'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                        <option value="profesor" <?php echo ($usuario_existente['rol'] == 'profesor') ? 'selected' : ''; ?>>Profesor</option>
                        <option value="alumno" <?php echo ($usuario_existente['rol'] == 'alumno') ? 'selected' : ''; ?>>Alumno</option>
                        <option value="padre" <?php echo ($usuario_existente['rol'] == 'padre') ? 'selected' : ''; ?>>Padre</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Guardar Cambios</button>
            </form>
        <?php else: ?>
            <p class="mensaje-error">No se pudo cargar la información del usuario para edición. Por favor, verifica el ID.</p>
        <?php endif; ?>
    </div>
</body>
</html>