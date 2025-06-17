<?php
session_start();
// Incluimos la base de datos y la configuración
include_once '../../includes/db.php'; 
include_once '../../includes/config.php'; 

$mensaje_error = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo_electronico = $_POST['email'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    if (empty($correo_electronico) || empty($contrasena)) {
        $mensaje_error = "Por favor, ingresa tu correo electrónico y contraseña.";
    } else {
        $stmt = $conexion->prepare("SELECT id_usuario, nombre, apellido, password_hash, rol FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $correo_electronico);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows == 1) {
            $usuario = $resultado->fetch_assoc();
            if (password_verify($contrasena, $usuario['password_hash'])) {
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre_usuario'] = $usuario['nombre'] . " " . $usuario['apellido'];
                $_SESSION['rol_usuario'] = $usuario['rol'];
                header("Location: " . RUTA_BASE . "dashboard.php"); 
                exit();
            } else {
                $mensaje_error = "Contraseña incorrecta. Inténtalo de nuevo.";
            }
        } else {
            $mensaje_error = "No se encontró una cuenta con ese correo electrónico.";
        }
        $stmt->close();
    }
}
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - MindSchool</title>
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
        .contenedor-login {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 40px 32px 32px 32px;
            max-width: 350px;
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
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            margin: 8px 0 18px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: #f5f5f5;
            font-size: 1rem;
            transition: border 0.2s;
        }
        input[type="email"]:focus, input[type="password"]:focus {
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
        .mensaje-error {
            color: #d32f2f;
            background: #fff3f3;
            border: 1px solid #f8d7da;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 18px;
            text-align: center;
        }
        .registro {
            text-align: center;
            margin-top: 18px;
        }
        .registro a {
            color: #444;
            text-decoration: underline;
            transition: color 0.2s;
        }
        .registro a:hover {
            color: #888;
        }
    </style>
</head>
<body>
    <div class="contenedor-login">
        <h1>Iniciar Sesión en MindSchool</h1>
        <?php
        // Mostrar mensaje de error si existe
        if ($mensaje_error) {
            echo "<div class='mensaje-error'>$mensaje_error</div>";
        }
        ?>
        <!-- Formulario de inicio de sesión -->
        <form action="<?php echo RUTA_BASE; ?>paginas/autenticacion/login.php" method="POST">
            <label for="email">Correo Electrónico:</label><br>
            <input type="email" id="email" name="email" required><br>

            <label for="contrasena">Contraseña:</label><br>
            <input type="password" id="contrasena" name="contrasena" required><br>

            <button type="submit">Iniciar Sesión</button>
        </form>
        <div class="registro">
            ¿No tienes una cuenta? <a href="<?php echo RUTA_BASE; ?>paginas/autenticacion/registro.php">Regístrate aquí</a>
        </div>
        <div class="registro">
    <a href="<?php echo RUTA_BASE; ?>paginas/autenticacion/olvide_contrasena.php">¿Olvidaste tu contraseña?</a>
</div>
    </div>
</body>
</html>