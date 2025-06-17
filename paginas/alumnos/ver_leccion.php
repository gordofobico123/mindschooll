<?php
// Iniciar sesión para acceder a las variables de sesión
session_start();

// Incluir archivos de conexión a la base de datos y configuración
// Asegúrate de que las rutas sean correctas según tu estructura de carpetas
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Redirigir si el usuario no está autenticado o no es un alumno
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'alumno') {
    $_SESSION['mensaje_error'] = "Acceso denegado. Solo los alumnos pueden ver las lecciones.";
    header("Location: " . RUTA_BASE . "dashboard.php"); // Redirige al dashboard o login
    exit();
}

$id_alumno_sesion = $_SESSION['id_usuario']; // ID del alumno logueado
$id_leccion = $_GET['id_leccion'] ?? null; // Obtener el ID de la lección de la URL
$leccion = null; // Variable para almacenar los datos de la lección
$mensaje_error = ''; // Mensaje de error
$mensaje_exito = ''; // Mensaje de éxito

// Si existe un mensaje de sesión de éxito o error, lo mostramos y limpiamos
if (isset($_SESSION['mensaje_exito_leccion'])) {
    $mensaje_exito = $_SESSION['mensaje_exito_leccion'];
    unset($_SESSION['mensaje_exito_leccion']);
}
if (isset($_SESSION['mensaje_error_leccion'])) {
    $mensaje_error = $_SESSION['mensaje_error_leccion'];
    unset($_SESSION['mensaje_error_leccion']);
}

// 1. Validar que se recibió un ID de lección válido
if (!$id_leccion || !is_numeric($id_leccion)) {
    $mensaje_error = "ID de lección no válido. Por favor, selecciona una lección.";
} else {
    // 2. Obtener los detalles de la lección y del módulo/curso al que pertenece
    // Realizamos un LEFT JOIN para obtener información de curso y módulo
    // y verificar si el alumno está inscrito en ese curso.
    $sql_leccion = "SELECT
                        l.id_leccion,
                        l.titulo AS titulo_leccion,
                        l.descripcion AS descripcion_leccion,
                        l.tipo_recurso,
                        l.contenido_texto,
                        l.url_recurso_externo,
                        l.ruta_archivo,
                        m.id_modulo,
                        m.titulo AS titulo_modulo,
                        c.id_curso,
                        c.nombre_curso
                    FROM
                        lecciones l
                    JOIN
                        modulos m ON l.id_modulo = m.id_modulo
                    JOIN
                        cursos c ON m.id_curso = c.id_curso
                    WHERE
                        l.id_leccion = ?";

    $stmt_leccion = $conexion->prepare($sql_leccion);
    $stmt_leccion->bind_param("i", $id_leccion);
    $stmt_leccion->execute();
    $resultado_leccion = $stmt_leccion->get_result();

    if ($resultado_leccion->num_rows > 0) {
        $leccion = $resultado_leccion->fetch_assoc();
        $id_curso_leccion = $leccion['id_curso'];

        // 3. Verificar que el alumno esté inscrito en el curso de esta lección
        $stmt_check_inscripcion = $conexion->prepare("SELECT id_inscripcion FROM inscripciones WHERE id_alumno = ? AND id_curso = ? AND estado_inscripcion = 'activo'");
        $stmt_check_inscripcion->bind_param("ii", $id_alumno_sesion, $id_curso_leccion);
        $stmt_check_inscripcion->execute();
        $resultado_inscripcion = $stmt_check_inscripcion->get_result();

        if ($resultado_inscripcion->num_rows === 0) {
            // Si el alumno no está inscrito en el curso, redirigir
            $_SESSION['mensaje_error'] = "No estás inscrito en el curso al que pertenece esta lección.";
            header("Location: " . RUTA_BASE . "paginas/alumnos/mis_cursos.php");
            exit();
        }

        // 4. Lógica para marcar lección como completada
        // Esto se puede hacer con un formulario POST o AJAX
        // Aquí usaremos un formulario POST simple
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['marcar_completada'])) {
            // Verificar si la lección ya está marcada como completada para este alumno
            $stmt_check_progreso = $conexion->prepare("SELECT id_progreso FROM progreso_alumno WHERE id_alumno = ? AND id_leccion = ?");
            $stmt_check_progreso->bind_param("ii", $id_alumno_sesion, $id_leccion);
            $stmt_check_progreso->execute();
            $resultado_progreso = $stmt_check_progreso->get_result();

            if ($resultado_progreso->num_rows === 0) {
                // Si no está completada, insertarla en progreso_alumno
                $stmt_insert_progreso = $conexion->prepare("INSERT INTO progreso_alumno (id_alumno, id_leccion, fecha_completado) VALUES (?, ?, NOW())");
                $stmt_insert_progreso->bind_param("ii", $id_alumno_sesion, $id_leccion);
                if ($stmt_insert_progreso->execute()) {
                    $mensaje_exito = "¡Lección marcada como completada con éxito!";
                    // Opcional: Redirigir para evitar reenvío del formulario
                    // header("Location: " . RUTA_BASE . "paginas/alumnos/ver_leccion.php?id_leccion=" . $id_leccion);
                    // exit();
                } else {
                    $mensaje_error = "Error al marcar la lección como completada: " . $conexion->error;
                }
            } else {
                $mensaje_error = "Esta lección ya ha sido marcada como completada.";
            }
        }
        // Verificar si la lección ya está completada para mostrar el botón adecuado
        $leccion_completada = false;
        $stmt_check_progreso = $conexion->prepare("SELECT id_progreso FROM progreso_alumno WHERE id_alumno = ? AND id_leccion = ?");
        $stmt_check_progreso->bind_param("ii", $id_alumno_sesion, $id_leccion);
        $stmt_check_progreso->execute();
        $resultado_progreso = $stmt_check_progreso->get_result();
        if ($resultado_progreso->num_rows > 0) {
            $leccion_completada = true;
        }

    } else {
        $mensaje_error = "La lección solicitada no existe o no se pudo cargar.";
    }
    $stmt_leccion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lección: <?php echo htmlspecialchars($leccion['titulo_leccion'] ?? 'No encontrada'); ?> - AmindSchool</title>
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
        .leccion-navegacion {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .leccion-navegacion a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .leccion-navegacion a:hover {
            text-decoration: underline;
        }
        .leccion-info h2 {
            color: #333;
            margin-top: 0;
        }
        .leccion-info p {
            line-height: 1.6;
        }
        .leccion-contenido {
            margin-top: 20px;
            padding: 20px;
            background-color: #e9f7fe;
            border-left: 5px solid #007bff;
            border-radius: 5px;
        }
        .leccion-contenido img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 15px 0;
        }
        .leccion-contenido video, .leccion-contenido audio {
            width: 100%;
            display: block;
            margin: 15px 0;
        }
        .mensaje-exito {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            text-align: center;
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
        .btn-completar {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 20px;
            text-decoration: none; /* Para que funcione si se usa como enlace */
        }
        .btn-completar:hover {
            background-color: #218838;
        }
        .btn-completado {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .btn-completado:hover {
            background-color: #5a6268;
        }
        .resource-download {
            display: block;
            margin-top: 15px;
            background-color: #17a2b8;
            color: white;
            padding: 10px 15px;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
        }
        .resource-download:hover {
            background-color: #138496;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Lección: <?php echo htmlspecialchars($leccion['titulo_leccion'] ?? 'No encontrada'); ?></h1>

        <?php if ($mensaje_exito): ?>
            <p class="mensaje-exito"><?php echo $mensaje_exito; ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <?php if ($leccion): ?>
            <div class="leccion-navegacion">
                <a href="<?php echo RUTA_BASE; ?>paginas/alumnos/ver_contenido_curso.php?id_curso=<?php echo htmlspecialchars($leccion['id_curso']); ?>">
                    &larr; Volver al Curso: <?php echo htmlspecialchars($leccion['nombre_curso']); ?>
                </a>
            </div>

            <div class="leccion-info">
                <h2><?php echo htmlspecialchars($leccion['titulo_leccion']); ?></h2>
                <p><strong>Módulo:</strong> <?php echo htmlspecialchars($leccion['titulo_modulo']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($leccion['descripcion_leccion'])); ?></p>

                <div class="leccion-contenido">
                    <?php
                    // Renderizar el contenido de la lección según su tipo de recurso
                    switch ($leccion['tipo_recurso']) {
                        case 'texto':
                            echo '<p>' . nl2br(htmlspecialchars($leccion['contenido_texto'])) . '</p>';
                            break;
                        case 'video':
                            if (!empty($leccion['url_recurso_externo'])) {
                                // Soporte básico para YouTube, Vimeo. Puedes expandirlo.
                                // Esto es solo un ejemplo, para producción se necesitaría parseo más robusto
                                $url = htmlspecialchars($leccion['url_recurso_externo']);
                                if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
                                    // Extraer ID de YouTube
                                    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $match);
                                    if (isset($match[1])) {
                                        echo '<iframe width="100%" height="450" src="https://www.youtube.com/embed/' . $match[1] . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                                    } else {
                                        echo '<p class="mensaje-error">URL de video de YouTube no válida.</p>';
                                    }
                                } elseif (strpos($url, 'vimeo.com') !== false) {
                                    // Extraer ID de Vimeo
                                    preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]+\/)?videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)/', $url, $match);
                                    if (isset($match[1])) {
                                        echo '<iframe src="https://player.vimeo.com/video/' . $match[1] . '" width="100%" height="450" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>';
                                    } else {
                                        echo '<p class="mensaje-error">URL de video de Vimeo no válida.</p>';
                                    }
                                } else {
                                    // Si no es YouTube/Vimeo, intentar con un reproductor HTML5 si es un archivo local
                                    echo '<video controls src="' . RUTA_BASE . 'recursos_lecciones/' . htmlspecialchars($leccion['ruta_archivo']) . '">Tu navegador no soporta el elemento de video.</video>';
                                }
                            } elseif (!empty($leccion['ruta_archivo'])) {
                                echo '<video controls src="' . RUTA_BASE . 'recursos_lecciones/' . htmlspecialchars($leccion['ruta_archivo']) . '">Tu navegador no soporta el elemento de video.</video>';
                            } else {
                                echo '<p class="mensaje-error">No se encontró una URL o archivo de video para esta lección.</p>';
                            }
                            break;
                        case 'documento':
                            if (!empty($leccion['url_recurso_externo'])) {
                                echo '<p><a href="' . htmlspecialchars($leccion['url_recurso_externo']) . '" target="_blank" class="resource-download">Ver Documento Externo</a></p>';
                            } elseif (!empty($leccion['ruta_archivo'])) {
                                $ruta_completa = RUTA_BASE . 'recursos_lecciones/' . htmlspecialchars($leccion['ruta_archivo']);
                                // Para visualizar PDF en el navegador (si el navegador lo soporta)
                                if (strpos($leccion['ruta_archivo'], '.pdf') !== false) {
                                    echo '<iframe src="' . $ruta_completa . '" width="100%" height="600px" style="border:none;"></iframe>';
                                }
                                echo '<p><a href="' . $ruta_completa . '" target="_blank" class="resource-download" download>Descargar Documento</a></p>';
                            } else {
                                echo '<p class="mensaje-error">No se encontró una URL o archivo de documento para esta lección.</p>';
                            }
                            break;
                        case 'audio':
                            if (!empty($leccion['url_recurso_externo'])) {
                                echo '<p><a href="' . htmlspecialchars($leccion['url_recurso_externo']) . '" target="_blank" class="resource-download">Escuchar Audio Externo</a></p>';
                            } elseif (!empty($leccion['ruta_archivo'])) {
                                echo '<audio controls src="' . RUTA_BASE . 'recursos_lecciones/' . htmlspecialchars($leccion['ruta_archivo']) . '">Tu navegador no soporta el elemento de audio.</audio>';
                                echo '<p><a href="' . RUTA_BASE . 'recursos_lecciones/' . htmlspecialchars($leccion['ruta_archivo']) . '" target="_blank" class="resource-download" download>Descargar Audio</a></p>';
                            } else {
                                echo '<p class="mensaje-error">No se encontró una URL o archivo de audio para esta lección.</p>';
                            }
                            break;
                        default:
                            echo '<p class="mensaje-error">Tipo de recurso no soportado o no definido para esta lección.</p>';
                            break;
                    }
                    ?>
                </div>

                <form action="<?php echo RUTA_BASE; ?>paginas/alumnos/ver_leccion.php?id_leccion=<?php echo htmlspecialchars($leccion['id_leccion']); ?>" method="POST">
                    <input type="hidden" name="marcar_completada" value="1">
                    <?php if ($leccion_completada): ?>
                        <button type="button" class="btn-completar btn-completado" disabled>Lección Completada</button>
                    <?php else: ?>
                        <button type="submit" class="btn-completar">Marcar como Completada</button>
                    <?php endif; ?>
                </form>

            </div>
        <?php else: ?>
            <p class="mensaje-error">No se pudo cargar la información de la lección.</p>
        <?php endif; ?>
    </div>
</body>
</html>