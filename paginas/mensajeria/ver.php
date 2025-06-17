<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$id_mensaje = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conexion->prepare("SELECT m.*, u1.nombre AS remitente_nombre, u1.apellido AS remitente_apellido, u2.nombre AS destinatario_nombre, u2.apellido AS destinatario_apellido FROM mensajes m JOIN usuarios u1 ON m.remitente_id = u1.id_usuario JOIN usuarios u2 ON m.destinatario_id = u2.id_usuario WHERE m.id_mensaje = ? AND (m.remitente_id = ? OR m.destinatario_id = ?)");
$stmt->bind_param("iii", $id_mensaje, $id_usuario, $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
$mensaje = $res->fetch_assoc();

if ($mensaje && $mensaje['destinatario_id'] == $id_usuario && !$mensaje['leido']) {
    $conexion->query("UPDATE mensajes SET leido = 1 WHERE id_mensaje = $id_mensaje");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Mensaje</title>
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
        h2, h3 {
            color: #388e3c;
            margin-bottom: 8px;
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
        .mensaje-contenido {
            background: #f5f5f5;
            padding: 18px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 15px;
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
    </style>
</head>
<body>
    <div class="contenedor-mensajeria">
        <a href="bandeja.php" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver a la bandeja</a>
        <?php if ($mensaje): ?>
            <h2>De: <?php echo htmlspecialchars($mensaje['remitente_nombre'] . ' ' . $mensaje['remitente_apellido']); ?></h2>
            <h3>Para: <?php echo htmlspecialchars($mensaje['destinatario_nombre'] . ' ' . $mensaje['destinatario_apellido']); ?></h3>
            <p><strong>Fecha:</strong> <?php echo htmlspecialchars($mensaje['fecha_envio']); ?></p>
            <hr>
            <p><?php echo nl2br(htmlspecialchars($mensaje['mensaje'])); ?></p>
        <?php else: ?>
            <p>Mensaje no encontrado o no tienes permiso para verlo.</p>
        <?php endif; ?>
    </div>
</body>
</html>