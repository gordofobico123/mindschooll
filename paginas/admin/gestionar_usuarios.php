<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario es admin, si no, redirigir al dashboard
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'admin') {
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

$mensaje_exito = '';
$mensaje_error = '';

// Procesar eliminación de usuario si se recibe el parámetro
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id_usuario_eliminar = intval($_GET['eliminar']);
    // Evitar que el admin se elimine a sí mismo
    if ($id_usuario_eliminar == $_SESSION['id_usuario']) {
        $mensaje_error = 'No puedes eliminar tu propio usuario.';
    } else {
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario_eliminar);
        if ($stmt->execute()) {
            $mensaje_exito = 'Usuario eliminado correctamente.';
        } else {
            $mensaje_error = 'Error al eliminar el usuario.';
        }
        $stmt->close();
    }
}

// Obtener lista de usuarios
$usuarios = [];
$resultado = $conexion->query("SELECT id_usuario, nombre, apellido, email, rol FROM usuarios ORDER BY nombre, apellido");
if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $usuarios[] = $fila;
    }
}
$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - MindSchool</title>
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
        .container {
            max-width: 1000px;
            margin: 32px auto;
            padding: 40px 32px 32px 32px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            width: 100%;
        }
        .navegacion {
            margin-bottom: 25px;
            text-align: center;
        }
        .navegacion a {
            margin: 0 10px;
            text-decoration: none;
            color: #007bff;
            padding: 8px 15px;
            border: 1px solid #007bff;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
            font-weight: 500;
        }
        .navegacion a:hover {
            background-color: #007bff;
            color: white;
        }
        h1 {
            text-align: center;
            color: #444;
            margin-bottom: 30px;
            font-size: 2em;
            font-weight: 600;
        }
        .mensaje-exito, .mensaje-error {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 1em;
            font-weight: bold;
            text-align: center;
        }
        .mensaje-exito {
            background: #e8f5e9;
            color: #388e3c;
            border: 1px solid #c8e6c9;
        }
        .mensaje-error {
            background: #fff3f3;
            color: #d32f2f;
            border: 1px solid #f8d7da;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #f5f5f5;
            border-radius: 12px;
            overflow: hidden;
        }
        th, td {
            padding: 14px 10px;
            text-align: left;
        }
        th {
            background: #ececec;
            color: #333;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
        tr:hover {
            background: #e0e0e0;
        }
        .acciones {
            display: flex;
            gap: 10px;
        }
        .btn-editar, .btn-eliminar {
            padding: 7px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: background 0.3s, color 0.3s;
        }
        .btn-editar {
            background: #e8f5e9;
            color: #388e3c;
        }
        .btn-editar:hover {
            background: #c8e6c9;
        }
        .btn-eliminar {
            background: #fff3f3;
            color: #d32f2f;
        }
        .btn-eliminar:hover {
            background: #f8d7da;
        }
        @media (max-width: 700px) {
            .container {
                padding: 18px 6px 18px 6px;
            }
            table, th, td {
                font-size: 0.95em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navegacion">
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">Panel de Control</a>
            <a href="<?php echo RUTA_BASE; ?>cerrar_sesion.php">Cerrar Sesión</a>
        </div>
        <h1>Gestión de Usuarios</h1>
        <?php
        if ($mensaje_exito) {
            echo "<div class='mensaje-exito'>" . $mensaje_exito . "</div>";
        }
        if ($mensaje_error) {
            echo "<div class='mensaje-error'>" . $mensaje_error . "</div>";
        }
        ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo $usuario['id_usuario']; ?></td>
                        <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['apellido']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($usuario['rol'])); ?></td>
                        <td class="acciones">
                            <a href="<?php echo RUTA_BASE; ?>paginas/usuarios/editar_alumno.php?id=<?php echo $usuario['id_usuario']; ?>" class="btn-editar"><i class="fas fa-edit"></i> Editar</a>
                            <a href="gestionar_usuarios.php?eliminar=<?php echo $usuario['id_usuario']; ?>" class="btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar este usuario?');"><i class="fas fa-trash-alt"></i> Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>