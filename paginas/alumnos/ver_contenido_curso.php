<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// 1. Verificar autenticación y rol (solo alumnos deben acceder a esta página directamente)
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'alumno') {
    $_SESSION['mensaje_error'] = "Acceso denegado. Solo los alumnos pueden ver el contenido de los cursos.";
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

$id_alumno_sesion = $_SESSION['id_usuario'];
$id_curso = $_GET['id_curso'] ?? null;
$curso = null;
$modulos_con_lecciones = [];
$mensaje_error = '';

// Array para almacenar los IDs de las lecciones que el alumno ha completado
$lecciones_completadas_ids = [];

// 2. Validar ID del curso
if (!$id_curso || !is_numeric($id_curso)) {
    $mensaje_error = "ID de curso no válido. Por favor, selecciona un curso.";
} else {
    // 3. Verificar si el alumno está inscrito en este curso
    $stmt_check_inscripcion = $conexion->prepare("SELECT id_inscripcion FROM inscripciones WHERE id_alumno = ? AND id_curso = ? AND estado_inscripcion = 'activo'");
    $stmt_check_inscripcion->bind_param("ii", $id_alumno_sesion, $id_curso);
    $stmt_check_inscripcion->execute();
    $resultado_inscripcion = $stmt_check_inscripcion->get_result();

    if ($resultado_inscripcion->num_rows === 0) {
        // Si el alumno no está inscrito en el curso, redirigir
        $_SESSION['mensaje_error'] = "No estás inscrito en este curso o tu inscripción no está activa.";
        header("Location: " . RUTA_BASE . "paginas/alumnos/mis_cursos.php");
        exit();
    }

    // 4. Obtener detalles del curso
    $stmt_curso = $conexion->prepare("SELECT id_curso, nombre_curso, descripcion, imagen_portada FROM cursos WHERE id_curso = ?");
    $stmt_curso->bind_param("i", $id_curso);
    $stmt_curso->execute();
    $resultado_curso = $stmt_curso->get_result();
    if ($resultado_curso->num_rows > 0) {
        $curso = $resultado_curso->fetch_assoc();
    } else {
        $mensaje_error = "El curso no se encontró.";
    }
    $stmt_curso->close();

    // 5. Obtener los módulos y sus lecciones para el curso
    $sql_modulos_lecciones = "SELECT
                                m.id_modulo,
                                m.titulo AS titulo_modulo,
                                m.descripcion AS descripcion_modulo,
                                l.id_leccion,
                                l.titulo AS titulo_leccion
                            FROM
                                modulos m
                            LEFT JOIN
                                lecciones l ON m.id_modulo = l.id_modulo
                            WHERE
                                m.id_curso = ?
                            ORDER BY
                                m.orden, l.orden";

    $stmt_modulos_lecciones = $conexion->prepare($sql_modulos_lecciones);
    $stmt_modulos_lecciones->bind_param("i", $id_curso);
    $stmt_modulos_lecciones->execute();
    $resultado_modulos_lecciones = $stmt_modulos_lecciones->get_result();

    // Organizar los resultados en una estructura anidada: Curso -> Módulos -> Lecciones
    while ($fila = $resultado_modulos_lecciones->fetch_assoc()) {
        $id_modulo = $fila['id_modulo'];
        if (!isset($modulos_con_lecciones[$id_modulo])) {
            $modulos_con_lecciones[$id_modulo] = [
                'id_modulo' => $id_modulo,
                'titulo_modulo' => $fila['titulo_modulo'],
                'descripcion_modulo' => $fila['descripcion_modulo'],
                'lecciones' => []
            ];
        }
        if ($fila['id_leccion']) { // Asegurarse de que haya una lección
            $modulos_con_lecciones[$id_modulo]['lecciones'][] = [
                'id_leccion' => $fila['id_leccion'],
                'titulo_leccion' => $fila['titulo_leccion']
            ];
        }
    }
    $stmt_modulos_lecciones->close();

    // 6. Obtener todas las lecciones completadas por este alumno para este curso
    // Esto se hace para poder mostrar el ícono de "completado" junto a cada lección
    $sql_lecciones_completadas = "SELECT pa.id_leccion
                                    FROM progreso_alumno pa
                                    JOIN lecciones l ON pa.id_leccion = l.id_leccion
                                    JOIN modulos m ON l.id_modulo = m.id_modulo
                                    WHERE pa.id_alumno = ? AND m.id_curso = ?";
    $stmt_completadas = $conexion->prepare($sql_lecciones_completadas);
    $stmt_completadas->bind_param("ii", $id_alumno_sesion, $id_curso);
    $stmt_completadas->execute();
    $resultado_completadas = $stmt_completadas->get_result();
    while ($fila_completada = $resultado_completadas->fetch_assoc()) {
        $lecciones_completadas_ids[] = $fila_completada['id_leccion'];
    }
    $stmt_completadas->close();
}

// Cerrar conexión
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contenido del Curso: <?php echo htmlspecialchars($curso['nombre_curso'] ?? 'Curso no encontrado'); ?> - AmindSchool</title>
    <link rel="stylesheet" href="<?php echo RUTA_BASE; ?>public/css/style.css">
    <style>
        /* Estilos básicos para este ejemplo */
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
            margin-bottom: 20px;
            text-align: center;
        }
        .course-info {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .course-info img {
            max-width: 200px;
            height: auto;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .course-info h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .course-info p {
            color: #555;
            line-height: 1.6;
        }
        .modulo-container {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden; /* Para contener los bordes */
        }
        .modulo-header {
            background-color: #f8f8f8;
            padding: 15px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            border-bottom: 1px solid #eee;
            color: #007bff;
        }
        .modulo-header:hover {
            background-color: #eef;
        }
        .modulo-header .arrow {
            transition: transform 0.3s ease;
        }
        .modulo-header.collapsed .arrow {
            transform: rotate(-90deg);
        }
        .lecciones-lista {
            list-style: none;
            padding: 0;
            margin: 0;
            /* Oculto por defecto, se mostrará con JS */
            display: none;
        }
        .lecciones-lista.active {
            display: block; /* Muestra cuando se activa */
        }
        .leccion-item {
            padding: 12px 20px;
            border-bottom: 1px solid #eee;
            background-color: #fdfdfd;
            display: flex;
            align-items: center;
        }
        .leccion-item:last-child {
            border-bottom: none;
        }
        .leccion-item a {
            color: #333;
            text-decoration: none;
            flex-grow: 1; /* Permite que el enlace ocupe el espacio */
            display: flex;
            align-items: center;
        }
        .leccion-item a:hover {
            color: #0056b3;
            background-color: #e6f7ff;
        }
        .icono-completado {
            color: #28a745; /* Verde para completado */
            font-weight: bold;
            margin-right: 8px;
            font-size: 1.2em;
        }
        .mensaje-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
        .no-content {
            padding: 20px;
            text-align: center;
            color: #888;
        }
        .back-link {
            display: block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?php echo RUTA_BASE; ?>paginas/alumnos/mis_cursos.php" class="back-link">← Volver a Mis Cursos</a>

        <h1>Contenido del Curso: <?php echo htmlspecialchars($curso['nombre_curso'] ?? 'Curso no encontrado'); ?></h1>

        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <?php if ($curso && !$mensaje_error): ?>
            <div class="course-info">
                <?php
                    $ruta_imagen_curso = !empty($curso['imagen_portada']) ? RUTA_BASE . 'imagenes_cursos/' . htmlspecialchars($curso['imagen_portada']) : '';
                    $ruta_imagen_default = RUTA_BASE . 'public/img/default_course.png'; // Asegúrate de tener una imagen por defecto
                    $imagen_a_mostrar = (file_exists($ruta_imagen_curso) && !is_dir($ruta_imagen_curso))
                                        ? $ruta_imagen_curso
                                        : $ruta_imagen_default;
                ?>
                <img src="<?php echo $imagen_a_mostrar; ?>" alt="Portada del Curso" class="course-imagen-detalle">
                <h2><?php echo htmlspecialchars($curso['nombre_curso']); ?></h2>
                <p><?php echo nl2br(htmlspecialchars($curso['descripcion'])); ?></p>
            </div>

            <?php if (!empty($modulos_con_lecciones)): ?>
                <?php foreach ($modulos_con_lecciones as $modulo): ?>
                    <div class="modulo-container">
                        <div class="modulo-header" data-toggle-target="#modulo-<?php echo htmlspecialchars($modulo['id_modulo']); ?>">
                            <span><?php echo htmlspecialchars($modulo['titulo_modulo']); ?></span>
                            <span class="arrow">▼</span>
                        </div>
                        <ul class="lecciones-lista" id="modulo-<?php echo htmlspecialchars($modulo['id_modulo']); ?>">
                            <?php if (!empty($modulo['lecciones'])): ?>
                                <?php foreach ($modulo['lecciones'] as $leccion): ?>
                                    <li class="leccion-item">
                                        <a href="<?php echo RUTA_BASE; ?>paginas/alumnos/ver_leccion.php?id_leccion=<?php echo htmlspecialchars($leccion['id_leccion']); ?>">
                                            <?php if (in_array($leccion['id_leccion'], $lecciones_completadas_ids)): ?>
                                                <span class="icono-completado">✓</span>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($leccion['titulo_leccion']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="leccion-item no-content">No hay lecciones en este módulo.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-content">Este curso aún no tiene módulos o lecciones.</div>
            <?php endif; ?>
        <?php else: ?>
            <p class="mensaje-error">No se pudo cargar la información del curso o el ID es inválido.</p>
        <?php endif; ?>
    </div>

    <script>
        // JavaScript para colapsar/expandir los módulos
        document.querySelectorAll('.modulo-header').forEach(header => {
            header.addEventListener('click', function() {
                const targetId = this.dataset.toggleTarget;
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    this.classList.toggle('collapsed');
                    
                    // Toggle visibility
                    if (targetElement.style.display === 'block') {
                        targetElement.style.display = 'none';
                    } else {
                        targetElement.style.display = 'block';
                    }
                }
            });
        });

        // Asegurarse de que las lecciones estén ocultas por defecto
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.lecciones-lista').forEach(list => {
                list.style.display = 'none'; // Ocultar todas las listas de lecciones al cargar
            });
            document.querySelectorAll('.modulo-header').forEach(header => {
                header.classList.add('collapsed'); // Añadir clase 'collapsed' por defecto
            });
        });
    </script>
</body>
</html>