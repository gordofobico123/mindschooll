<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario está autenticado y es un ADMINISTRADOR
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'admin') {
    $_SESSION['mensaje_error'] = "No tienes permisos para acceder a esta página.";
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

$mensaje_exito = '';
$mensaje_error = '';

// Lógica para procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger y limpiar los datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = trim($_POST['rol'] ?? '');

    // Validaciones
    if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($rol)) {
        $mensaje_error = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "Formato de correo electrónico inválido.";
    } elseif (strlen($password) < 6) { // Requisito mínimo de longitud de contraseña
        $mensaje_error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif (!in_array($rol, ['admin', 'profesor', 'alumno', 'padre'])) {
        $mensaje_error = "Rol de usuario inválido. Selecciona uno de la lista.";
    } else {
        // Verificar si el correo electrónico ya existe en la base de datos
        $stmt_check_email = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        if ($stmt_check_email) {
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();
            if ($stmt_check_email->num_rows > 0) {
                $mensaje_error = "El correo electrónico '{$email}' ya está registrado.";
            }
            $stmt_check_email->close();
        } else {
            $mensaje_error = "Error al preparar la verificación de email: " . $conexion->error;
        }

        // Si no hay errores, proceder con la inserción
        if (empty($mensaje_error)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hashear la contraseña

            $stmt_insert = $conexion->prepare("INSERT INTO usuarios (nombre, apellido, email, password, rol) VALUES (?, ?, ?, ?, ?)");
            if ($stmt_insert) {
                $stmt_insert->bind_param("sssss", $nombre, $apellido, $email, $hashed_password, $rol);

                if ($stmt_insert->execute()) {
                    $mensaje_exito = "Usuario '{$nombre} {$apellido}' creado con éxito.";
                    // Opcional: Limpiar los campos del formulario después del éxito
                    $_POST = array(); // Esto borra los datos del formulario si se recarga la página
                } else {
                    $mensaje_error = "Error al crear el usuario: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                $mensaje_error = "Error al preparar la consulta de inserción: " . $conexion->error;
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
    <title>Crear Nuevo Usuario - MindSchool</title>
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
            background-color: #28a745; /* Color verde para "crear" */
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
            background-color: #218838;
            transform: translateY(-2px);
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

        <h1>Crear Nuevo Usuario</h1>

        <?php if ($mensaje_exito): ?>
            <p class="mensaje-exito"><?php echo $mensaje_exito; ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
                <small>La contraseña debe tener al menos 6 caracteres.</small>
            </div>
            <div class="form-group">
                <label for="rol">Rol:</label>
                <select id="rol" name="rol" required>
                    <option value="">Selecciona un rol</option>
                    <option value="admin" <?php echo (($_POST['rol'] ?? '') == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                    <option value="profesor" <?php echo (($_POST['rol'] ?? '') == 'profesor') ? 'selected' : ''; ?>>Profesor</option>
                    <option value="alumno" <?php echo (($_POST['rol'] ?? '') == 'alumno') ? 'selected' : ''; ?>>Alumno</option>
                    <option value="padre" <?php echo (($_POST['rol'] ?? '') == 'padre') ? 'selected' : ''; ?>>Padre</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">Crear Usuario</button>
        </form>
    </div>
</body>
</html>