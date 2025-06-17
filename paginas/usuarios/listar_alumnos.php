<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario está autenticado y es un ADMINISTRADOR
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'admin') {
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

$usuarios = []; // Usamos 'usuarios' para ser más genérico
$mensaje_exito = '';
$mensaje_error = '';

// Obtener mensajes de sesión si existen (para feedback de crear, editar, eliminar)
if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']); // Borrar el mensaje después de mostrarlo
}
if (isset($_SESSION['mensaje_error'])) {
    $mensaje_error = $_SESSION['mensaje_error'];
    unset($_SESSION['mensaje_error']); // Borrar el mensaje después de mostrarlo
}

// Lógica para obtener todos los usuarios
$sql = "SELECT id_usuario, nombre, apellido, email, rol FROM usuarios ORDER BY rol, apellido, nombre";
$resultado = $conexion->query($sql);

if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $usuarios[] = $fila;
    }
    $resultado->free();
} else {
    $mensaje_error = "Error al cargar los usuarios: " . $conexion->error;
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - MindSchool</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background-color: #f4f7f6; color: #333; }
        .container { max-width: 900px; margin: 20px auto; padding: 30px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #0056b3; margin-bottom: 30px; font-size: 2.2em; font-weight: 600; }
        .navegacion { margin-bottom: 30px; text-align: center; display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; }
        .navegacion a { text-decoration: none; color: #007bff; padding: 10px 20px; border: 1px solid #007bff; border-radius: 25px; transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease; font-weight: 500; white-space: nowrap; }
        .navegacion a:hover { background-color: #007bff; color: white; transform: translateY(-2px); }

        .mensaje-exito { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .mensaje-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; border: 1px solid #e0e0e0; text-align: left; }
        th { background-color: #eef5ff; color: #0056b3; font-weight: 600; text-transform: uppercase; }
        tr:nth-child(even) { background-color: #f8fbfd; }
        tr:hover { background-color: #eef0f3; }

        .acciones a {
            display: inline-block;
            margin: 0 5px;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 500;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .acciones .editar { background-color: #ffc107; color: #333; }
        .acciones .editar:hover { background-color: #e0a800; color: white; }
        .acciones .eliminar { background-color: #dc3545; color: white; }
        .acciones .eliminar:hover { background-color: #c82333; }
        .no-usuarios { text-align: center; color: #666; padding: 30px; border: 1px dashed #ccc; border-radius: 8px; margin-top: 20px; }
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

        <h1>Lista de Usuarios</h1>

        <?php if ($mensaje_exito): ?>
            <p class="mensaje-exito"><?php echo $mensaje_exito; ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <?php if (!empty($usuarios)): ?>
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
                            <td><?php echo htmlspecialchars($usuario['id_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($usuario['rol'])); ?></td>
                            <td class="acciones">
                                <a href="<?php echo RUTA_BASE; ?>paginas/usuarios/editar_alumno.php?id=<?php echo htmlspecialchars($usuario['id_usuario']); ?>" class="editar">Editar</a>
                                <a href="<?php echo RUTA_BASE; ?>paginas/usuarios/eliminar_usuario.php?id=<?php echo htmlspecialchars($usuario['id_usuario']); ?>" class="eliminar" onclick="return confirm('¿Estás seguro de que quieres eliminar a este usuario (<?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>)? Esta acción es irreversible.');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-usuarios">No hay usuarios registrados en el sistema.</p>
        <?php endif; ?>
    </div>
</body>
</html>