<?php
session_start();
// Incluimos la base de datos y la configuración
include_once '../../includes/db.php'; 
include_once '../../includes/config.php'; 

$mensaje_exito = "";
$mensaje_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = htmlspecialchars($_POST['nombre'] ?? '');
    $apellido = htmlspecialchars($_POST['apellido'] ?? '');
    $correo_electronico = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
    $rol = htmlspecialchars($_POST['rol'] ?? '');

    if (empty($nombre) || empty($apellido) || empty($correo_electronico) || empty($contrasena) || empty($confirmar_contrasena) || empty($rol)) {
        $mensaje_error = "Todos los campos son obligatorios.";
    } elseif (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "El formato del correo electrónico no es válido.";
    } elseif ($contrasena !== $confirmar_contrasena) {
        $mensaje_error = "Las contraseñas no coinciden.";
    } elseif (strlen($contrasena) < 6) {
        $mensaje_error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        if ($conexion->connect_error) {
            $mensaje_error = "Error de conexión a la base de datos: " . $conexion->connect_error;
        } else {
            $stmt_check = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt_check->bind_param("s", $correo_electronico);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $mensaje_error = "El correo electrónico ya está registrado. Intenta iniciar sesión.";
            } else {
                $password_hash = password_hash($contrasena, PASSWORD_DEFAULT);

                $stmt_usuario = $conexion->prepare("INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, fecha_registro, estado) VALUES (?, ?, ?, ?, ?, NOW(), 'activo')");
                $stmt_usuario->bind_param("sssss", $nombre, $apellido, $correo_electronico, $password_hash, $rol);

                if ($stmt_usuario->execute()) {
                    $id_nuevo_usuario = $stmt_usuario->insert_id;

                    $stmt_rol = null;
                    switch ($rol) {
                        case 'alumno':
                            $stmt_rol = $conexion->prepare("INSERT INTO alumnos (id_alumno) VALUES (?)");
                            $stmt_rol->bind_param("i", $id_nuevo_usuario);
                            break;
                        case 'profesor':
                            $stmt_rol = $conexion->prepare("INSERT INTO profesores (id_profesor) VALUES (?)");
                            $stmt_rol->bind_param("i", $id_nuevo_usuario);
                            break;
                        case 'padre':
                            $stmt_rol = $conexion->prepare("INSERT INTO padres (id_padre) VALUES (?)");
                            $stmt_rol->bind_param("i", $id_nuevo_usuario);
                            break;
                    }

                    if ($stmt_rol && $stmt_rol->execute()) {
                        $mensaje_exito = "¡Registro exitoso! Ya puedes iniciar sesión.";
                    } else {
                        $mensaje_error = "Error al completar el registro específico del rol.";
                        $conexion->query("DELETE FROM usuarios WHERE id_usuario = $id_nuevo_usuario");
                    }
                    if ($stmt_rol) $stmt_rol->close();

                } else {
                    $mensaje_error = "Error al registrar el usuario: " . $stmt_usuario->error;
                }
                $stmt_usuario->close();
            }
            $stmt_check->close();
        }
    }
}
if (isset($conexion) && $conexion instanceof mysqli) {
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - MindSchool</title>
    <!-- Estilos personalizados para un diseño moderno en blanco y gris -->
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .registro-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 40px 32px 32px 32px;
            max-width: 400px;
            width: 100%;
            margin: 32px 0;
        }
        h1 {
            text-align: center;
            margin-bottom: 24px;
            color: #444;
            font-weight: 600;
        }
        label {
            font-weight: 500;
            color: #555;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px 12px;
            margin: 8px 0 18px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: #f5f5f5;
            font-size: 1rem;
            transition: border 0.2s;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            border: 1.5px solid #888;
            outline: none;
            background: #fff;
        }
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #e0e0e0;
            color: #222;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button[type="submit"]:hover {
            background: #bdbdbd;
        }
        .mensaje-exito {
            color: #388e3c;
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 18px;
            text-align: center;
            font-weight: 600;
        }
        .mensaje-error {
            color: #d32f2f;
            background: #fff3f3;
            border: 1px solid #f8d7da;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 18px;
            text-align: center;
            font-weight: 600;
        }
        .login-link {
            text-align: center;
            margin-top: 18px;
        }
        .login-link a {
            color: #444;
            text-decoration: underline;
            transition: color 0.2s;
        }
        .login-link a:hover {
            color: #888;
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <h1>Regístrate en MindSchool</h1>
        <?php
        // Mostrar mensajes de éxito o error si existen
        if ($mensaje_exito) {
            echo "<div class='mensaje-exito'>$mensaje_exito</div>";
        }
        if ($mensaje_error) {
            echo "<div class='mensaje-error'>$mensaje_error</div>";
        }
        ?>
        <!-- Formulario de registro -->
        <form action="<?php echo RUTA_BASE; ?>paginas/autenticacion/registro.php" method="POST">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">

            <label for="apellido">Apellido:</label>
            <input type="text" id="apellido" name="apellido" required value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>">

            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" required>

            <label for="confirmar_contrasena">Confirmar Contraseña:</label>
            <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>

            <label for="rol">Soy:</label>
            <select id="rol" name="rol" required>
                <option value="">Selecciona tu rol</option>
                <option value="alumno" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'alumno') ? 'selected' : ''; ?>>Alumno</option>
                <option value="profesor" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'profesor') ? 'selected' : ''; ?>>Profesor</option>
                <option value="padre" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'padre') ? 'selected' : ''; ?>>Padre/Tutor</option>
            </select>

            <button type="submit">Registrarse</button>
        </form>
        <div class="login-link">
            ¿Ya tienes una cuenta? <a href="<?php echo RUTA_BASE; ?>paginas/autenticacion/login.php">Inicia Sesión</a>
        </div>
    </div>
</body>
</html>