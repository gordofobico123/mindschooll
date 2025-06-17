<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario está autenticado y es un profesor o administrador
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol_usuario'] !== 'profesor' && $_SESSION['rol_usuario'] !== 'admin')) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$id_profesor_actual = $_SESSION['id_usuario'];
$rol_usuario = $_SESSION['rol_usuario'];

$mensaje_exito = '';
$mensaje_error = '';

// Función para generar un código de invitación aleatorio
function generarCodigoInvitacion($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_aula = trim($_POST['nombre_aula'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    // Validar campos
    if (empty($nombre_aula)) {
        $mensaje_error = "El nombre del aula es obligatorio.";
    } else {
        // Generar un código de invitación único
        $codigo_invitacion = generarCodigoInvitacion();
        $codigo_unico = false;
        $intentos = 0;
        // Intentar generar un código único hasta 5 veces (para evitar colisiones muy improbables)
        while (!$codigo_unico && $intentos < 5) {
            $stmt_check_code = $conexion->prepare("SELECT id_aula FROM aulas WHERE codigo_invitacion = ?");
            $stmt_check_code->bind_param("s", $codigo_invitacion);
            $stmt_check_code->execute();
            $stmt_check_code->store_result();
            if ($stmt_check_code->num_rows == 0) {
                $codigo_unico = true;
            } else {
                $codigo_invitacion = generarCodigoInvitacion(); // Generar uno nuevo
            }
            $stmt_check_code->close();
            $intentos++;
        }

        if (!$codigo_unico) {
            $mensaje_error = "No se pudo generar un código de invitación único. Inténtalo de nuevo.";
        } else {
            // Insertar el aula en la base de datos
            $stmt = $conexion->prepare("INSERT INTO aulas (nombre_aula, descripcion, id_profesor, codigo_invitacion) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $nombre_aula, $descripcion, $id_profesor_actual, $codigo_invitacion);

            if ($stmt->execute()) {
                $mensaje_exito = "Aula '" . htmlspecialchars($nombre_aula) . "' creada con éxito. Código de invitación: <strong>" . htmlspecialchars($codigo_invitacion) . "</strong>";
                // Limpiar los campos del formulario después del éxito
                $nombre_aula = '';
                $descripcion = '';
            } else {
                $mensaje_error = "Error al crear el aula: " . $stmt->error;
            }
            $stmt->close();
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
    <title>Crear Aula - MindSchool</title>
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
            background-color: #28a745;
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
            background-color: #218838;
            transform: translateY(-2px);
        }
        .mensaje-exito { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .mensaje-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="navegacion">
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">Panel de Control</a>
            <a href="<?php echo RUTA_BASE; ?>cerrar_sesion.php">Cerrar Sesión</a>
        </div>

        <h1>Crear Nueva Aula</h1>

        <?php if ($mensaje_exito): ?>
            <p class="mensaje-exito"><?php echo $mensaje_exito; ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <form action="<?php echo RUTA_BASE; ?>paginas/aulas/crear_aula.php" method="POST">
            <div class="form-group">
                <label for="nombre_aula">Nombre del Aula:</label>
                <input type="text" id="nombre_aula" name="nombre_aula" value="<?php echo htmlspecialchars($nombre_aula ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción del Aula (Opcional):</label>
                <textarea id="descripcion" name="descripcion"><?php echo htmlspecialchars($descripcion ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn-submit">Crear Aula</button>
        </form>
    </div>
</body>
</html>