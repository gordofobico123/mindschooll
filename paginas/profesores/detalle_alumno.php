<?php
// Iniciar sesión y cargar archivos de configuración y base de datos
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar autenticación y permisos (solo profesor)
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'profesor') {
    $_SESSION['mensaje_error'] = "Acceso denegado. No tienes permiso para ver detalles de alumnos.";
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

$id_profesor_sesion = $_SESSION['id_usuario']; // ID del profesor logueado

$id_alumno = $_GET['id_alumno'] ?? null;
$alumno = null;
$cursos_inscritos = [];
$mensaje_error = '';
$mensaje_exito = '';

// Obtener mensajes de sesión si existen
if (isset($_SESSION['mensaje_exito_alumno_detalle'])) {
    $mensaje_exito = $_SESSION['mensaje_exito_alumno_detalle'];
    unset($_SESSION['mensaje_exito_alumno_detalle']);
}
if (isset($_SESSION['mensaje_error_alumno_detalle'])) {
    $mensaje_error = $_SESSION['mensaje_error_alumno_detalle'];
    unset($_SESSION['mensaje_error_alumno_detalle']);
}

// Validar ID de alumno
if (!$id_alumno || !is_numeric($id_alumno)) {
    $mensaje_error = "ID de alumno no válido.";
} else {
    // 1. Obtener detalles básicos del alumno
    // Se verifica que el alumno exista y tenga el rol 'alumno'.
    $sql_alumno = "SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.fecha_registro,
                          a.nivel_educativo, a.institucion_anterior, a.necesidades_especiales
                   FROM usuarios u
                   JOIN alumnos a ON u.id_usuario = a.id_alumno
                   WHERE u.id_usuario = ? AND u.rol = 'alumno'";
    $stmt_alumno = $conexion->prepare($sql_alumno);
    $stmt_alumno->bind_param("i", $id_alumno);
    $stmt_alumno->execute();
    $resultado_alumno = $stmt_alumno->get_result();

    if ($resultado_alumno->num_rows > 0) {
        $alumno = $resultado_alumno->fetch_assoc();

        // 2. Obtener los cursos en los que el alumno está inscrito Y que este profesor imparte
$sql_cursos = "SELECT
i.id_inscripcion,
c.id_curso,
c.nombre_curso,
c.imagen_portada,
c.estado AS estado_curso,
i.fecha_inscripcion,
i.estado_inscripcion,
COUNT(DISTINCT l.id_leccion) AS total_lecciones,
COUNT(DISTINCT pa.id_leccion) AS lecciones_completadas,
CONCAT(prof_u.nombre, ' ', prof_u.apellido) AS nombre_profesor_completo
FROM
inscripciones i
JOIN
cursos c ON i.id_curso = c.id_curso
LEFT JOIN
modulos m ON c.id_curso = m.id_curso
LEFT JOIN
lecciones l ON m.id_modulo = l.id_modulo
LEFT JOIN
progreso_alumno pa ON i.id_inscripcion = pa.id_inscripcion AND l.id_leccion = pa.id_leccion -- ¡Línea corregida aquí!
JOIN
usuarios prof_u ON c.id_profesor = prof_u.id_usuario
WHERE
i.id_alumno = ? AND c.id_profesor = ?
GROUP BY i.id_inscripcion, c.id_curso, c.nombre_curso, c.imagen_portada, c.estado, i.fecha_inscripcion, i.estado_inscripcion, nombre_profesor_completo
ORDER BY i.fecha_inscripcion DESC";

        $stmt_cursos = $conexion->prepare($sql_cursos);
        $stmt_cursos->bind_param("ii", $id_alumno, $id_profesor_sesion);
        $stmt_cursos->execute();
        $resultado_cursos = $stmt_cursos->get_result();
        while ($fila_curso = $resultado_cursos->fetch_assoc()) {
            // Calcular porcentaje de progreso
            $fila_curso['porcentaje_progreso'] = ($fila_curso['total_lecciones'] > 0) ?
                                                ($fila_curso['lecciones_completadas'] / $fila_curso['total_lecciones']) * 100 : 0;
            $cursos_inscritos[] = $fila_curso;
        }
        $stmt_cursos->close();

    } else {
        $mensaje_error = "El alumno no se encontró o no es un alumno registrado.";
    }
    $stmt_alumno->close();
}

// Lógica para cambiar estado de inscripción (Solo si el rol lo permite y el curso es del profesor)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion_inscripcion'])) {
    $id_inscripcion_accion = $_POST['id_inscripcion'] ?? null;
    $nuevo_estado = $_POST['nuevo_estado'] ?? null;
    $id_curso_afectado = $_POST['id_curso'] ?? null; // ID del curso al que pertenece la inscripción

    if ($id_inscripcion_accion && $nuevo_estado && $id_curso_afectado) {
        // Verificar que el curso pertenezca a este profesor
        $stmt_check_profesor_curso = $conexion->prepare("SELECT id_curso FROM cursos WHERE id_curso = ? AND id_profesor = ?");
        $stmt_check_profesor_curso->bind_param("ii", $id_curso_afectado, $id_profesor_sesion);
        $stmt_check_profesor_curso->execute();
        $resultado_check = $stmt_check_profesor_curso->get_result();

        if ($resultado_check->num_rows > 0) { // Si el curso pertenece a este profesor
            // Validar que el nuevo estado sea uno permitido
            $estados_permitidos = ['activo', 'inactivo', 'completado']; // "completado" si lo usas para inscripciones
            if (in_array($nuevo_estado, $estados_permitidos)) {
                $stmt_update_inscripcion = $conexion->prepare("UPDATE inscripciones SET estado_inscripcion = ? WHERE id_inscripcion = ?");
                $stmt_update_inscripcion->bind_param("si", $nuevo_estado, $id_inscripcion_accion);
                if ($stmt_update_inscripcion->execute()) {
                    $_SESSION['mensaje_exito_alumno_detalle'] = "Estado de inscripción actualizado a '" . htmlspecialchars($nuevo_estado) . "' con éxito.";
                } else {
                    $_SESSION['mensaje_error_alumno_detalle'] = "Error al actualizar la inscripción: " . $conexion->error;
                }
            } else {
                $_SESSION['mensaje_error_alumno_detalle'] = "Estado de inscripción no válido.";
            }
        } else {
            $_SESSION['mensaje_error_alumno_detalle'] = "No tienes permiso para modificar esta inscripción (el curso no te pertenece).";
        }
        $stmt_check_profesor_curso->close();
    } else {
        $_SESSION['mensaje_error_alumno_detalle'] = "Datos incompletos para la acción de inscripción.";
    }
    // Redirigir para evitar reenvío de formulario
    header("Location: " . RUTA_BASE . "paginas/profesores/detalle_alumno.php?id_alumno=" . $id_alumno);
    exit();
}


$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Alumno: <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido'] ?? 'No encontrado'); ?> - AmindSchool</title>
    <link rel="stylesheet" href="<?php echo RUTA_BASE; ?>public/css/style.css">
    <style>
        /* Estilos básicos para este ejemplo, preferiblemente en style.css */
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
            max-width: 900px;
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
        .alumno-info, .cursos-inscritos {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            background-color: #fefefe;
        }
        .alumno-info h2, .cursos-inscritos h2 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .alumno-info p {
            margin: 8px 0;
            font-size: 1.1em;
        }
        .alumno-info strong {
            color: #333;
        }
        .cursos-lista {
            list-style: none;
            padding: 0;
        }
        .curso-card {
            display: flex;
            align-items: center;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .curso-card img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        .curso-details {
            flex-grow: 1;
        }
        .curso-details h3 {
            margin-top: 0;
            margin-bottom: 5px;
            color: #0056b3;
        }
        .curso-details p {
            margin: 3px 0;
            font-size: 0.95em;
            color: #666;
        }
        .barra-progreso-container {
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 5px;
            height: 10px;
            margin-top: 8px;
            overflow: hidden;
        }
        .barra-progreso {
            height: 100%;
            background-color: #28a745; /* Verde para el progreso */
            border-radius: 5px;
            text-align: center;
            color: white;
            font-size: 0.8em;
            transition: width 0.5s ease-in-out;
        }
        .progreso-porcentaje {
            font-weight: bold;
            color: #28a745;
            margin-top: 5px;
            display: block;
        }
        .curso-acciones {
            margin-left: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .curso-acciones form {
            margin: 0;
        }
        .curso-acciones button {
            background-color: #007bff;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        .curso-acciones button:hover {
            background-color: #0056b3;
        }
        .curso-acciones .btn-desinscribir {
            background-color: #dc3545;
        }
        .curso-acciones .btn-desinscribir:hover {
            background-color: #c82333;
        }
        .curso-acciones .btn-activar {
            background-color: #ffc107;
            color: #333;
        }
        .curso-acciones .btn-activar:hover {
            background-color: #e0a800;
        }
        .curso-acciones .btn-ver-contenido {
            background-color: #17a2b8;
        }
        .curso-acciones .btn-ver-contenido:hover {
            background-color: #138496;
        }
        .no-cursos-inscritos {
            text-align: center;
            padding: 20px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?php echo RUTA_BASE; ?>paginas/profesores/listar_alumnos.php" class="back-link">← Volver a Mis Alumnos</a>

        <h1>Detalles del Alumno</h1>

        <?php if ($mensaje_exito): ?>
            <p class="message exito"><?php echo $mensaje_exito; ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="message error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <?php if ($alumno): ?>
            <div class="alumno-info">
                <h2>Información General</h2>
                <p><strong>Nombre Completo:</strong> <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($alumno['email']); ?></p>
                <p><strong>Fecha de Registro:</strong> <?php echo date('d/m/Y', strtotime($alumno['fecha_registro'])); ?></p>
                <p><strong>Nivel Educativo:</strong> <?php echo htmlspecialchars($alumno['nivel_educativo'] ?? 'N/A'); ?></p>
                <p><strong>Institución Anterior:</strong> <?php echo htmlspecialchars($alumno['institucion_anterior'] ?? 'N/A'); ?></p>
                <p><strong>Necesidades Especiales:</strong> <?php echo nl2br(htmlspecialchars($alumno['necesidades_especiales'] ?? 'Ninguna')); ?></p>
            </div>

            <div class="cursos-inscritos">
                <h2>Cursos Inscritos (Impartidos por mí)</h2>
                <?php if (!empty($cursos_inscritos)): ?>
                    <ul class="cursos-lista">
                        <?php foreach ($cursos_inscritos as $curso): ?>
                            <li class="curso-card">
                                <?php
                                    $ruta_imagen_curso = !empty($curso['imagen_portada']) ? RUTA_BASE . 'imagenes_cursos/' . htmlspecialchars($curso['imagen_portada']) : '';
                                    $ruta_imagen_default = RUTA_BASE . 'public/img/default_course.png'; // Asegúrate de tener una imagen por defecto
                                    $imagen_a_mostrar = (file_exists($ruta_imagen_curso) && !is_dir($ruta_imagen_curso))
                                                        ? $ruta_imagen_curso
                                                        : $ruta_imagen_default;
                                ?>
                                <img src="<?php echo $imagen_a_mostrar; ?>" alt="Portada del Curso">
                                <div class="curso-details">
                                    <h3><?php echo htmlspecialchars($curso['nombre_curso']); ?></h3>
                                    <p>Profesor: <?php echo htmlspecialchars($curso['nombre_profesor_completo']); ?></p>
                                    <p>Fecha de Inscripción: <?php echo date('d/m/Y', strtotime($curso['fecha_inscripcion'])); ?></p>
                                    <p>Estado de Inscripción: <strong><?php echo htmlspecialchars(ucfirst($curso['estado_inscripcion'])); ?></strong></p>
                                    <p>Estado del Curso: <?php echo htmlspecialchars(ucfirst($curso['estado_curso'])); ?></p>

                                    <div class="barra-progreso-container">
                                        <div class="barra-progreso" style="width: <?php echo round($curso['porcentaje_progreso']); ?>%;"></div>
                                    </div>
                                    <span class="progreso-porcentaje"><?php echo round($curso['porcentaje_progreso']); ?>% Completado</span>
                                </div>
                                <div class="curso-acciones">
                                    <a href="<?php echo RUTA_BASE; ?>paginas/alumnos/ver_contenido_curso.php?id_curso=<?php echo htmlspecialchars($curso['id_curso']); ?>" class="btn-ver-contenido">Ver Contenido</a>
                                    
                                    <?php if ($curso['estado_inscripcion'] === 'activo'): ?>
                                        <form action="" method="POST" onsubmit="return confirm('¿Seguro que deseas desinscribir a este alumno de este curso?');">
                                            <input type="hidden" name="accion_inscripcion" value="cambiar_estado">
                                            <input type="hidden" name="id_inscripcion" value="<?php echo htmlspecialchars($curso['id_inscripcion']); ?>">
                                            <input type="hidden" name="id_curso" value="<?php echo htmlspecialchars($curso['id_curso']); ?>">
                                            <input type="hidden" name="nuevo_estado" value="inactivo">
                                            <button type="submit" class="btn-desinscribir">Desinscribir</button>
                                        </form>
                                    <?php elseif ($curso['estado_inscripcion'] === 'inactivo'): ?>
                                        <form action="" method="POST" onsubmit="return confirm('¿Seguro que deseas activar la inscripción de este alumno a este curso?');">
                                            <input type="hidden" name="accion_inscripcion" value="cambiar_estado">
                                            <input type="hidden" name="id_inscripcion" value="<?php echo htmlspecialchars($curso['id_inscripcion']); ?>">
                                            <input type="hidden" name="id_curso" value="<?php echo htmlspecialchars($curso['id_curso']); ?>">
                                            <input type="hidden" name="nuevo_estado" value="activo">
                                            <button type="submit" class="btn-activar">Activar Inscripción</button>
                                        </form>
                                    <?php endif; ?>
                                    </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-cursos-inscritos">Este alumno no está inscrito en ninguno de tus cursos.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="message error">No se pudo cargar la información del alumno o el ID es inválido.</p>
        <?php endif; ?>
    </div>
</body>
</html>