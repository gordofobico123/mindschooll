<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario está autenticado y es un profesor o administrador
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol_usuario'] !== 'profesor' && $_SESSION['rol_usuario'] !== 'admin')) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$rol_usuario = $_SESSION['rol_usuario'];
$id_usuario_sesion = $_SESSION['id_usuario'];

$aulas = [];
$mensaje_exito = '';
$mensaje_error = '';

// Obtener mensajes de sesión si existen
if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']);
}
if (isset($_SESSION['mensaje_error'])) {
    $mensaje_error = $_SESSION['mensaje_error'];
    unset($_SESSION['mensaje_error']);
}

// Lógica para obtener las aulas
// Administradores ven todas las aulas.
// Profesores solo ven las aulas que han creado.
$sql = "SELECT a.id_aula, a.nombre_aula, a.descripcion, a.codigo_invitacion, a.fecha_creacion,
a.id_profesor, -- ¡AÑADIDO ESTO!
u.nombre AS nombre_profesor, u.apellido AS apellido_profesor
FROM aulas a
INNER JOIN usuarios u ON a.id_profesor = u.id_usuario";

$params = [];
$types = "";

if ($rol_usuario === 'profesor') {
    $sql .= " WHERE a.id_profesor = ?";
    $params[] = $id_usuario_sesion;
    $types = "i";
}

$sql .= " ORDER BY a.nombre_aula ASC";

$stmt = $conexion->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if ($stmt->execute()) {
    $resultado = $stmt->get_result();
    while ($fila = $resultado->fetch_assoc()) {
        $aulas[] = $fila;
    }
} else {
    $mensaje_error = "Error al cargar las aulas: " . $stmt->error;
}
$stmt->close();

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Aulas - MindSchool</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background-color: #f0f2f5; color: #333; }
        .container { max-width: 1200px; margin: 20px auto; padding: 30px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #0056b3; margin-bottom: 30px; font-size: 2.5em; font-weight: 600; }
        .navegacion { margin-bottom: 30px; text-align: center; display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; }
        .navegacion a { text-decoration: none; color: #007bff; padding: 10px 20px; border: 1px solid #007bff; border-radius: 25px; transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease; font-weight: 500; white-space: nowrap; }
        .navegacion a:hover { background-color: #007bff; color: white; transform: translateY(-2px); }
        .mensaje-exito { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .mensaje-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .no-aulas { text-align: center; color: #666; font-size: 1.2em; padding: 50px; background-color: #fcfcfc; border: 1px dashed #e0e0e0; border-radius: 8px; margin-top: 30px; }

        /* Estilo de la tabla de aulas */
        .aulas-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden; /* Asegura que los bordes redondeados sean visibles */
        }
        .aulas-table th, .aulas-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .aulas-table th {
            background-color: #0056b3;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        .aulas-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .aulas-table tbody tr:hover {
            background-color: #eef;
        }
        .aulas-table .acciones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-start;
        }
        .acciones a {
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
            white-space: nowrap;
        }
        .acciones .ver-aula { background-color: #007bff; color: white; }
        .acciones .ver-aula:hover { background-color: #0056b3; transform: translateY(-2px); }
        .acciones .gestionar-alumnos { background-color: #6f42c1; color: white; } /* Púrpura */
        .acciones .gestionar-alumnos:hover { background-color: #5a359e; transform: translateY(-2px); }
        .acciones .asignar-cursos { background-color: #20c997; color: white; } /* Turquesa */
        .acciones .asignar-cursos:hover { background-color: #1aa079; transform: translateY(-2px); }
        .acciones .editar { background-color: #ffc107; color: #333; }
        .acciones .editar:hover { background-color: #e0a800; transform: translateY(-2px); }
        .acciones .eliminar { background-color: #dc3545; color: white; }
        .acciones .eliminar:hover { background-color: #c82333; transform: translateY(-2px); }

        .codigo-invitacion {
            font-family: 'Courier New', Courier, monospace;
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navegacion">
            <a href="<?php echo RUTA_BASE; ?>paginas/aulas/crear_aula.php">Crear Nueva Aula</a>
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">Panel de Control</a>
            <a href="<?php echo RUTA_BASE; ?>cerrar_sesion.php">Cerrar Sesión</a>
        </div>

        <h1>Gestión de Aulas</h1>

        <?php if ($mensaje_exito): ?>
            <p class="mensaje-exito"><?php echo $mensaje_exito; ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <?php if (!empty($aulas)): ?>
            <table class="aulas-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Aula</th>
                        <th>Profesor</th>
                        <th>Código Invitación</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aulas as $aula): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($aula['id_aula']); ?></td>
                            <td><?php echo htmlspecialchars($aula['nombre_aula']); ?></td>
                            <td><?php echo htmlspecialchars($aula['nombre_profesor'] . ' ' . $aula['apellido_profesor']); ?></td>
                            <td><span class="codigo-invitacion"><?php echo htmlspecialchars($aula['codigo_invitacion'] ?? 'N/A'); ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($aula['fecha_creacion'])); ?></td>
                            <td class="acciones">
                                <a href="<?php echo RUTA_BASE; ?>paginas/aulas/ver_aula.php?id=<?php echo htmlspecialchars($aula['id_aula']); ?>" class="ver-aula">Ver Aula</a>
                                <a href="<?php echo RUTA_BASE; ?>paginas/aulas/gestionar_alumnos_aula.php?id_aula=<?php echo htmlspecialchars($aula['id_aula']); ?>" class="gestionar-alumnos">Gestionar Alumnos</a>
                                <a href="<?php echo RUTA_BASE; ?>paginas/aulas/asignar_cursos_aula.php?id_aula=<?php echo htmlspecialchars($aula['id_aula']); ?>" class="asignar-cursos">Asignar Cursos</a>
                                
                                <?php 
                                // Opciones de edición/eliminación solo para administradores o el profesor dueño del aula
                                $es_profesor_propio = ($rol_usuario === 'profesor' && $aula['id_profesor'] == $id_usuario_sesion);
                                ?>
                                <?php if ($rol_usuario === 'admin' || $es_profesor_propio): ?>
                                    <a href="<?php echo RUTA_BASE; ?>paginas/aulas/editar_aula.php?id=<?php echo htmlspecialchars($aula['id_aula']); ?>" class="editar">Editar</a>
                                    <a href="<?php echo RUTA_BASE; ?>paginas/aulas/eliminar_aula.php?id=<?php echo htmlspecialchars($aula['id_aula']); ?>" class="eliminar" onclick="return confirm('¿Estás seguro de que quieres eliminar esta aula? Se desvincularán todos los alumnos y cursos asociados.');">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-aulas">No tienes aulas creadas todavía. ¡Crea una para empezar a organizar tus clases!</p>
        <?php endif; ?>
    </div>
</body>
</html>