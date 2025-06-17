<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Mensajes recibidos
$stmt_recibidos = $conexion->prepare("SELECT m.*, u.nombre AS remitente_nombre, u.apellido AS remitente_apellido FROM mensajes m JOIN usuarios u ON m.remitente_id = u.id_usuario WHERE m.destinatario_id = ? ORDER BY m.fecha_envio DESC");
$stmt_recibidos->bind_param("i", $id_usuario);
$stmt_recibidos->execute();
$recibidos = $stmt_recibidos->get_result();

// Mensajes enviados
$stmt_enviados = $conexion->prepare("SELECT m.*, u.nombre AS destinatario_nombre, u.apellido AS destinatario_apellido FROM mensajes m JOIN usuarios u ON m.destinatario_id = u.id_usuario WHERE m.remitente_id = ? ORDER BY m.fecha_envio DESC");
$stmt_enviados->bind_param("i", $id_usuario);
$stmt_enviados->execute();
$enviados = $stmt_enviados->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bandeja de Mensajes</title>
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
            max-width: 700px;
            margin: 32px auto;
            padding: 40px 32px 32px 32px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            width: 100%;
        }
        h1, h2 {
            color: #388e3c;
            text-align: center;
        }
        .btn-accion { /* Estilo general para botones */
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        .btn-nuevo { /* Estilo específico para "Redactar nuevo mensaje" */
            background: #e8f5e9;
            color: #388e3c;
            margin-bottom: 20px;
        }
        .btn-nuevo:hover {
            background: #c8e6c9;
        }
        .btn-regresar { /* Nuevo estilo para el botón de regresar */
            background: #e0e0e0;
            color: #222;
            margin-bottom: 20px;
            margin-right: 10px; /* Espacio si hay otro botón al lado */
        }
        .btn-regresar:hover {
            background: #bdbdbd;
        }
        .acciones-mensajeria { /* Contenedor para los botones de acción */
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            gap: 10px; /* Espacio entre los botones */
        }
        ul.lista-mensajes {
            list-style: none;
            padding: 0;
        }
        ul.lista-mensajes li {
            background: #f5f5f5;
            margin-bottom: 10px;
            padding: 16px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        ul.lista-mensajes li a {
            color: #222;
            text-decoration: none;
            font-weight: 500;
            flex: 1;
        }
        ul.lista-mensajes li strong {
            color: #d32f2f;
            margin-left: 10px;
        }
        .icono-sobre {
            color: #388e3c;
            margin-right: 10px;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <div class="contenedor-mensajeria">
        <h1><i class="fas fa-envelope icono-sobre"></i> Bandeja de Entrada</h1>
        <div class="acciones-mensajeria">
            <a href="<?php echo RUTA_BASE; ?>dashboard.php" class="btn-accion btn-regresar"><i class="fas fa-arrow-left"></i> Regresar al Dashboard</a>
            <a href="enviar.php" class="btn-accion btn-nuevo"><i class="fas fa-plus"></i> Redactar nuevo mensaje</a>
        </div>
        <h2>Recibidos</h2>
        <ul class="lista-mensajes">
            <?php while ($mensaje = $recibidos->fetch_assoc()): ?>
                <li>
                    <a href="ver.php?id=<?php echo $mensaje['id_mensaje']; ?>">
                        <i class="fas fa-user icono-sobre"></i>
                        De: <?php echo htmlspecialchars($mensaje['remitente_nombre'] . ' ' . $mensaje['remitente_apellido']); ?> - <?php echo htmlspecialchars($mensaje['fecha_envio']); ?>
                    </a>
                    <?php if (!$mensaje['leido']): ?> <strong>(No leído)</strong> <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
        <h2>Enviados</h2>
        <ul class="lista-mensajes">
            <?php while ($mensaje = $enviados->fetch_assoc()): ?>
                <li>
                    <a href="ver.php?id=<?php echo $mensaje['id_mensaje']; ?>">
                        <i class="fas fa-user icono-sobre"></i>
                        Para: <?php echo htmlspecialchars($mensaje['destinatario_nombre'] . ' ' . $mensaje['destinatario_apellido']); ?> - <?php echo htmlspecialchars($mensaje['fecha_envio']); ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
</body>
</html>