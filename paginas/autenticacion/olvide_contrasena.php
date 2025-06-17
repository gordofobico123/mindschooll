<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

$mensaje_exito = '';
$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    if ($correo !== '') {
        $stmt = $conexion->prepare("SELECT id_usuario, nombre FROM usuarios WHERE email = ?");
         $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($usuario = $resultado->fetch_assoc()) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $conexion->query("INSERT INTO recuperacion_contrasena (id_usuario, token, expira) VALUES ({$usuario['id_usuario']}, '$token', '$expira')");
            // Enviar correo
            require '../../vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USUARIO;
            $mail->Password = SMTP_CONTRASENA;
            $mail->SMTPSecure = 'tls';
            $mail->Port = SMTP_PORT;
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NOMBRE);
            $mail->addAddress($correo, $usuario['nombre']);
            $mail->Subject = 'Recuperación de contraseña - MindSchool';
            $enlace = RUTA_BASE . "paginas/autenticacion/restablecer_contrasena.php?token=$token";
            $mail->Body = "Hola {$usuario['nombre']},\n\nPara restablecer tu contraseña haz clic en el siguiente enlace:\n$enlace\n\nSi no solicitaste este cambio, ignora este correo.";
            if ($mail->send()) {
                $mensaje_exito = 'Se ha enviado un correo con instrucciones para restablecer tu contraseña.';
            } else {
                $mensaje_error = 'No se pudo enviar el correo. Error: ' . $mail->ErrorInfo;
            }   
        } else {
            $mensaje_error = 'No se encontró una cuenta con ese correo.';
        }
        $stmt->close();
    } else {
        $mensaje_error = 'Por favor ingresa tu correo.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Arial, sans-serif; }
        .contenedor-recuperar { max-width: 400px; margin: 60px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 32px; }
        h1 { text-align: center; color: #388e3c; }
        .mensaje-exito, .mensaje-error { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 1em; font-weight: bold; text-align: center; }
        .mensaje-exito { background: #e8f5e9; color: #388e3c; border: 1px solid #c8e6c9; }
        .mensaje-error { background: #fff3f3; color: #d32f2f; border: 1px solid #f8d7da; }
        label { font-weight: bold; }
        input[type="email"] { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; margin-bottom: 15px; }
        button { background: #388e3c; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; }
        button:hover { background: #2e7031; }
    </style>
</head>
<body>
    <div class="contenedor-recuperar">
        <h1>Recuperar Contraseña</h1>
        <?php if ($mensaje_exito): ?>
            <div class="mensaje-exito"><?php echo $mensaje_exito; ?></div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="mensaje-error"><?php echo $mensaje_error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="correo">Correo electrónico:</label>
            <input type="email" name="correo" id="correo" required>
            <button type="submit"><i class="fas fa-envelope"></i> Enviar instrucciones</button>
        </form>
        <div style="text-align:center;margin-top:20px;">
            <a href="login.php">Volver al inicio de sesión</a>
        </div>
    </div>
</body>
</html>