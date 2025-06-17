<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$mensaje_exito = '';
$mensaje_error = '';

// Obtener lista de usuarios (excepto el actual)
$usuarios = $conexion->query("SELECT id_usuario, nombre, apellido, rol_usuario FROM usuarios WHERE id_usuario != $id_usuario");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destinatario_id = intval($_POST['destinatario_id']);
    $mensaje = trim($_POST['mensaje']);
    if ($destinatario_id && $mensaje !== '') {
        $stmt = $conexion->prepare("INSERT INTO mensajes (remitente_id, destinatario_id, mensaje) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id_usuario, $destinatario_id, $mensaje);
        if ($stmt->execute()) {
            $mensaje_exito = "Mensaje enviado correctamente.";
        } else {
            $mensaje_error = "Error al enviar el mensaje.";
        }
        $stmt->close();
    } else {
        $mensaje_error = "Selecciona un destinatario y escribe un mensaje.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Enviar Mensaje</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
        .contenedor-mensajeria {
            max-width: 500px;
            margin: 32px auto;
            padding: 40px 32px 32px 32px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            width: 100%;
        }
        h1 {
            color: #388e3c;
            text-align: center;
        }
        .btn-volver {
            display: inline-block;
            padding: 8px 18px;
            background: #ececec;
            color: #444;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 20px;
            transition: background 0.3s;
        }
        .btn-volver:hover {
            background: #d6d6d6;
        }
        .mensaje-exito {
            background: #e8f5e9;
            color: #388e3c;
            border: 1px solid #c8e6c9;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        .mensaje-error {
            background: #fff3f3;
            color: #d32f2f;
            border: 1px solid #f8d7da;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 6px;
            font-weight: 500;
        }
        select, textarea {
            margin-bottom: 16px;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1em;
        }
        button[type="submit"] {
            background: #388e3c;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        button[type="submit"]:hover {
            background: #2e7031;
        }
    </style>
</head>
<body>
    <div class="contenedor-mensajeria">
        <h1><i class="fas fa-paper-plane"></i> Enviar Mensaje</h1>
        <a href="bandeja.php" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver a la bandeja</a>
        <?php if ($mensaje_exito): ?>
            <div class="mensaje-exito"> <?php echo $mensaje_exito; ?> </div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="mensaje-error"> <?php echo $mensaje_error; ?> </div>
        <?php endif; ?>
        <form method="POST">
            <label for="destinatario_id">Destinatario:</label>
            <select name="destinatario_id" id="destinatario_id" required>
                <option value="">Selecciona un usuario</option>
                <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                    <option value="<?php echo $usuario['id_usuario']; ?>">
                        <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido'] . ' (' . $usuario['rol_usuario'] . ')'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <label for="mensaje">Mensaje:</label>
            <textarea name="mensaje" id="mensaje" rows="5" required></textarea>
            <button type="submit"><i class="fas fa-paper-plane"></i> Enviar</button>
        </form>
    </div>
</body>
</html>