<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar autenticación y permisos (solo profesor o admin)
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol_usuario'] !== 'profesor' && $_SESSION['rol_usuario'] !== 'admin')) {
    $_SESSION['mensaje_error'] = "Acceso denegado. No tienes permiso para calificar tareas.";
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

$id_profesor_sesion = $_SESSION['id_usuario'];
$id_entrega = $_GET['id_entrega'] ?? null;

$entrega = null;
$mensaje_exito = '';
$mensaje_error = '';

// Directorio para las entregas de archivos
$upload_dir = realpath(__DIR__ . '/../../entregas_tareas/') . DIRECTORY_SEPARATOR;

// Obtener mensajes de sesión si existen
if (isset($_SESSION['mensaje_exito_calificacion'])) {
    $mensaje_exito = $_SESSION['mensaje_exito_calificacion'];
    unset($_SESSION['mensaje_exito_calificacion']);
}
if (isset($_SESSION['mensaje_error_calificacion'])) {
    $mensaje_error = $_SESSION['mensaje_error_calificacion'];
    unset($_SESSION['mensaje_error_calificacion']);
}

// 1. Obtener detalles de la entrega y la tarea
if (!$id_entrega || !is_numeric($id_entrega)) {
    $mensaje_error = "ID de entrega no válido.";
} else {
    $sql = "SELECT
                et.id_entrega,
                et.id_tarea,
                et.id_alumno,
                et.contenido_texto,
                et.url_entrega,
                et.archivo_entrega,
                et.fecha_entrega AS fecha_entrega_alumno,
                et.calificacion,
                et.comentarios_profesor,
                et.estado_entrega,
                t.titulo AS titulo_tarea,
                t.descripcion AS descripcion_tarea,
                t.fecha_asignacion,
                t.fecha_entrega AS fecha_limite_tarea,
                t.tipo_tarea,
                t.id_profesor AS id_profesor_tarea,
                c.nombre_curso,
                CONCAT(u_alumno.nombre, ' ', u_alumno.apellido) AS nombre_alumno_completo
            FROM
                entregas_tarea et
            JOIN
                tareas t ON et.id_tarea = t.id_tarea
            JOIN
                cursos c ON t.id_curso = c.id_curso
            JOIN
                usuarios u_alumno ON et.id_alumno = u_alumno.id_usuario
            WHERE
                et.id_entrega = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_entrega);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $entrega = $resultado->fetch_assoc();

        // Verificar que el profesor tiene permiso para calificar esta tarea (solo si es profesor)
        if ($_SESSION['rol_usuario'] === 'profesor' && $entrega['id_profesor_tarea'] != $id_profesor_sesion) {
            $_SESSION['mensaje_error'] = "No tienes permiso para calificar esta tarea.";
            header("Location: " . RUTA_BASE . "dashboard.php"); // Redirigir a un lugar seguro
            exit();
        }
    } else {
        $mensaje_error = "Entrega no encontrada.";
    }
    $stmt->close();
}

// 2. Lógica para manejar el envío del formulario de calificación
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion_calificar']) && $entrega) {
    $calificacion = filter_var($_POST['calificacion'] ?? '', FILTER_VALIDATE_FLOAT);
    $comentarios = trim($_POST['comentarios_profesor'] ?? '');

    // Validar calificación (ej. entre 0 y 100, o 0-10, según tu escala)
    // Asumiremos una escala de 0 a 100 por ahora. Ajusta según tus necesidades.
    if ($calificacion === false || $calificacion < 0 || $calificacion > 100) {
        $_SESSION['mensaje_error_calificacion'] = "La calificación debe ser un número entre 0 y 100.";
    } else {
        $estado_entrega_nuevo = 'calificado'; // Una vez calificada, cambia el estado

        $stmt_update = $conexion->prepare("UPDATE entregas_tarea SET calificacion = ?, comentarios_profesor = ?, estado_entrega = ? WHERE id_entrega = ?");
        $stmt_update->bind_param("dssi", $calificacion, $comentarios, $estado_entrega_nuevo, $id_entrega);

        if ($stmt_update->execute()) {
            $_SESSION['mensaje_exito_calificacion'] = "Tarea calificada exitosamente.";
        } else {
            $_SESSION['mensaje_error_calificacion'] = "Error al guardar la calificación: " . $conexion->error;
        }
        $stmt_update->close();
    }
    header("Location: " . RUTA_BASE . "paginas/profesores/calificar_tarea.php?id_entrega=" . $id_entrega);
    exit();
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificar Tarea: <?php echo htmlspecialchars($entrega['titulo_tarea'] ?? 'No encontrada'); ?> - AmindSchool</title>
    <link rel="stylesheet" href="<?php echo RUTA_BASE; ?>public/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 20px auto;
        }
        h1 {
            color: #0056b3;
            margin-bottom: 25px;
            text-align: center;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        .message.exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .tarea-info, .entrega-detalle, .calificacion-form {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            background-color: #fefefe;
        }
        .tarea-info h2, .entrega-detalle h2, .calificacion-form h2 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .tarea-info p, .entrega-detalle p {
            margin: 8px 0;
            font-size: 1.1em;
        }
        .tarea-info strong, .entrega-detalle strong {
            color: #333;
        }
        .estado-entrega {
            font-weight: bold;
        }
        .estado-pendiente { color: #ffc107; }
        .estado-entregado { color: #17a2b8; }
        .estado-calificado { color: #28a745; }
        .estado-retrasado { color: #dc3545; }
        .calificacion-actual {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .btn-submit {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #218838;
        }
        .btn-descargar {
            background-color: #007bff;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
            display: inline-block;
            margin-top: 10px;
        }
        .btn-descargar:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($entrega): ?>
            <a href="<?php echo RUTA_BASE; ?>paginas/profesores/detalle_alumno.php?id_alumno=<?php echo htmlspecialchars($entrega['id_alumno']); ?>" class="back-link">← Volver a Detalles de <?php echo htmlspecialchars($entrega['nombre_alumno_completo']); ?></a>
        <?php else: ?>
            <a href="<?php echo RUTA_BASE; ?>dashboard.php" class="back-link">← Volver al Dashboard</a>
        <?php endif; ?>

        <h1>Calificar Tarea</h1>

        <?php if ($mensaje_exito): ?>
            <p class="message exito"><?php echo $mensaje_exito; ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="message error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <?php if ($entrega): ?>
            <div class="tarea-info">
                <h2>Tarea: <?php echo htmlspecialchars($entrega['titulo_tarea']); ?></h2>
                <p><strong>Alumno:</strong> <?php echo htmlspecialchars($entrega['nombre_alumno_completo']); ?></p>
                <p><strong>Curso:</strong> <?php echo htmlspecialchars($entrega['nombre_curso']); ?></p>
                <p><strong>Descripción de la Tarea:</strong> <?php echo nl2br(htmlspecialchars($entrega['descripcion_tarea'])); ?></p>
                <p><strong>Asignada el:</strong> <?php echo date('d/m/Y H:i', strtotime($entrega['fecha_asignacion'])); ?></p>
                <p><strong>Fecha Límite de Entrega:</strong> <?php echo date('d/m/Y H:i', strtotime($entrega['fecha_limite_tarea'])); ?></p>
                <p><strong>Tipo de Entrega Esperada:</strong> <?php echo htmlspecialchars(ucfirst($entrega['tipo_tarea'])); ?></p>
            </div>

            <div class="entrega-detalle">
                <h2>Detalles de la Entrega del Alumno</h2>
                <p><strong>Estado de Entrega:</strong>
                    <span class="estado-entrega estado-<?php echo htmlspecialchars($entrega['estado_entrega']); ?>">
                        <?php echo htmlspecialchars(ucfirst($entrega['estado_entrega'])); ?>
                    </span>
                </p>
                <?php if ($entrega['fecha_entrega_alumno']): ?>
                    <p><strong>Fecha de Entrega del Alumno:</strong> <?php echo date('d/m/Y H:i', strtotime($entrega['fecha_entrega_alumno'])); ?></p>
                <?php else: ?>
                    <p><strong>Fecha de Entrega del Alumno:</strong> Aún no entregada.</p>
                <?php endif; ?>

                <?php if ($entrega['estado_entrega'] !== 'pendiente'): // Mostrar contenido solo si hay entrega ?>
                    <?php if ($entrega['tipo_tarea'] === 'texto' && !empty($entrega['contenido_texto'])): ?>
                        <p><strong>Contenido de Texto Entregado:</strong></p>
                        <div style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; max-height: 200px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;">
                            <?php echo nl2br(htmlspecialchars($entrega['contenido_texto'])); ?>
                        </div>
                    <?php elseif ($entrega['tipo_tarea'] === 'enlace' && !empty($entrega['url_entrega'])): ?>
                        <p><strong>Enlace Entregado:</strong> <a href="<?php echo htmlspecialchars($entrega['url_entrega']); ?>" target="_blank"><?php echo htmlspecialchars($entrega['url_entrega']); ?></a></p>
                    <?php elseif ($entrega['tipo_tarea'] === 'documento' && !empty($entrega['archivo_entrega'])): ?>
                        <p><strong>Archivo Entregado:</strong>
                            <a href="<?php echo RUTA_BASE . 'entregas_tareas/' . htmlspecialchars($entrega['archivo_entrega']); ?>" target="_blank" class="btn-descargar" download>Descargar Archivo</a>
                            <small> (<?php echo htmlspecialchars($entrega['archivo_entrega']); ?>)</small>
                        </p>
                    <?php else: ?>
                        <p>El alumno aún no ha proporcionado contenido para esta tarea, o el tipo de entrega no requiere contenido visible aquí.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>El alumno aún no ha entregado esta tarea.</p>
                <?php endif; ?>
            </div>

            <div class="calificacion-form">
                <h2>Calificar y Comentar</h2>
                <?php if ($entrega['calificacion'] !== null): ?>
                    <p class="calificacion-actual">Calificación Actual: <strong><?php echo htmlspecialchars($entrega['calificacion']); ?></strong></p>
                <?php endif; ?>

                <form action="" method="POST">
                    <input type="hidden" name="accion_calificar" value="guardar">
                    <div class="form-group">
                        <label for="calificacion">Calificación (0-100):</label>
                        <input type="number" id="calificacion" name="calificacion" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($entrega['calificacion'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="comentarios_profesor">Comentarios para el Alumno (Opcional):</label>
                        <textarea id="comentarios_profesor" name="comentarios_profesor"><?php echo htmlspecialchars($entrega['comentarios_profesor'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Guardar Calificación y Comentarios</button>
                </form>
            </div>

        <?php else: ?>
            <p class="message error">No se pudo cargar la información de la entrega de la tarea.</p>
        <?php endif; ?>
    </div>
</body>
</html>