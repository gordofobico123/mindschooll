<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// 1. Verificar autenticación y rol
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'alumno') {
    $_SESSION['mensaje_error'] = "Acceso denegado. Solo los alumnos pueden ver sus cursos inscritos.";
    header("Location: " . RUTA_BASE . "dashboard.php"); // Redirigir si no es alumno o no está logueado
    exit();
}

$id_alumno_sesion = $_SESSION['id_usuario']; // El id_usuario es el id_alumno para este rol
$cursos_inscritos = [];
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

// 2. Obtener los cursos en los que el alumno está inscrito
$sql_inscripciones = "SELECT
                        c.id_curso,
                        c.nombre_curso,
                        c.descripcion,
                        c.imagen_portada,
                        c.nivel_dificultad,
                        c.categoria,
                        c.precio,
                        c.estado AS estado_curso, -- Alias para evitar conflicto con estado_inscripcion
                        i.fecha_inscripcion,
                        i.estado_inscripcion,
                        CONCAT(u.nombre, ' ', u.apellido) AS nombre_profesor_completo
                      FROM inscripciones i
                      JOIN cursos c ON i.id_curso = c.id_curso
                      LEFT JOIN usuarios u ON c.id_profesor = u.id_usuario
                      WHERE i.id_alumno = ?
                      ORDER BY i.fecha_inscripcion DESC";

$stmt_inscripciones = $conexion->prepare($sql_inscripciones);
$stmt_inscripciones->bind_param("i", $id_alumno_sesion);
$stmt_inscripciones->execute();
$resultado_inscripciones = $stmt_inscripciones->get_result();

if ($resultado_inscripciones->num_rows > 0) {
    while ($fila = $resultado_inscripciones->fetch_assoc()) {
        $cursos_inscritos[] = $fila;
    }
}
$stmt_inscripciones->close();
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cursos - MindSchool</title>
    <link rel="stylesheet" href="<?php echo RUTA_BASE; ?>css/style.css">
    <style>
        /* Estilos específicos para esta página (ajustados para coherencia) */
        .contenedor-cursos {
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-section h1 {
            margin: 0;
            color: #0056b3;
            font-size: 2.5em;
        }

        .btn-regresar {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d; /* Color gris */
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.2s ease;
        }

        .btn-regresar:hover {
            background-color: #5a6268; /* Gris más oscuro al pasar el ratón */
        }

        .mensaje-exito, .mensaje-error {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .mensaje-exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensaje-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .grid-cursos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .curso-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .curso-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .curso-imagen-card {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }

        .curso-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .curso-info h3 {
            margin-top: 0;
            color: #0056b3;
            font-size: 1.6em;
            margin-bottom: 10px;
        }

        .curso-info p {
            margin-bottom: 8px;
            color: #555;
            line-height: 1.5;
        }

        .curso-info .profesor,
        .curso-info .fecha-inscripcion,
        .curso-info .estado-inscripcion {
            font-weight: bold;
            color: #444;
        }
        
        .curso-info .fecha-inscripcion {
            font-size: 0.95em;
            color: #666;
        }

        .curso-info .estado-inscripcion {
            font-size: 0.95em;
            color: #007bff; /* Un color distintivo para el estado */
        }


        .descripcion-corta {
            font-size: 0.9em;
            color: #666;
            flex-grow: 1;
        }

        .acciones-curso {
            padding: 15px 20px;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center; /* Centrar el botón "Ver Contenido" */
            gap: 10px;
            border-top: 1px solid #eee;
        }

        .acciones-curso a {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: #fff;
            font-weight: bold;
            text-align: center;
            transition: background-color 0.2s ease;
            white-space: nowrap;
        }

        .acciones-curso .ver-contenido {
            background-color:rgb(19, 4, 101); /* Verde para ver contenido */
        }

        .acciones-curso .ver-contenido:hover {
            background-color:rgb(61, 61, 61);
        }

        .no-cursos {
            text-align: center;
            color: #666;
            font-size: 1.2em;
            padding: 50px 0;
        }

        .no-cursos a {
            color: #007bff;
            text-decoration: none;
        }

        .no-cursos a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="contenedor-cursos">
        <div class="header-section">
            <h1>Mis Cursos Inscritos</h1>
            <a href="<?php echo RUTA_BASE; ?>dashboard.php" class="btn-regresar">Regresar al Panel de Control</a>
        </div>

        <?php if ($mensaje_exito): ?>
            <p class="mensaje-exito"><?php echo htmlspecialchars($mensaje_exito); ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo htmlspecialchars($mensaje_error); ?></p>
        <?php endif; ?>

        <?php if (!empty($cursos_inscritos)): ?>
            <div class="grid-cursos">
                <?php foreach ($cursos_inscritos as $curso): ?>
                    <div class="curso-card">
    <?php
    // Construye la ruta completa de la imagen de portada
    // Si imagen_portada es una ruta relativa a RUTA_BASE, ya está bien.
    // Si solo guarda el nombre del archivo, necesitarás la ruta completa.
    $ruta_imagen = RUTA_BASE . 'imagenes_cursos/' . htmlspecialchars($curso['imagen_portada']);
    $ruta_imagen_default = RUTA_BASE . 'imagenes_cursos/default_course.png'; // Asegúrate de que esta imagen exista

    // Verifica si el curso tiene una imagen de portada y si el archivo existe
    // Considera que si la imagen está en la DB, solo necesitas verificar si el campo no está vacío.
    // Si el campo solo tiene el nombre del archivo, la ruta completa es necesaria.
    $imagen_a_mostrar = (!empty($curso['imagen_portada']) && file_exists(__DIR__ . '/../../imagenes_cursos/' . $curso['imagen_portada']))
                        ? $ruta_imagen
                        : $ruta_imagen_default;
    ?>
    <img src="<?php echo $imagen_a_mostrar; ?>" alt="Portada del Curso" class="curso-imagen-card">
    <div class="curso-info">
        <h3><?php echo htmlspecialchars($curso['nombre_curso']); ?></h3>
        <p class="profesor">Profesor: <?php echo htmlspecialchars($curso['nombre_profesor_completo'] ?? 'N/A'); ?></p>
        <p class="fecha-inscripcion">Inscrito el: <?php echo date('d/m/Y', strtotime($curso['fecha_inscripcion'])); ?></p>
        <p class="estado-inscripcion">Estado Inscripción: <?php echo htmlspecialchars(ucfirst($curso['estado_inscripcion'])); ?></p>
        <p class="estado-curso">Estado Curso: <?php echo htmlspecialchars(ucfirst($curso['estado_curso'])); ?></p>
        <p class="descripcion-corta"><?php echo nl2br(substr(htmlspecialchars($curso['descripcion']), 0, 100)); ?>...</p>
        <div class="acciones-curso">
            <a href="<?php echo RUTA_BASE; ?>paginas/alumnos/ver_contenido_curso.php?id_curso=<?php echo htmlspecialchars($curso['id_curso']); ?>" class="ver-contenido">Ver Contenido</a>
        </div>
    </div>
</div>
<?php //endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-cursos">Aún no te has inscrito a ningún curso. ¡Explora nuestros <a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php">cursos disponibles</a>!</p>
        <?php endif; ?>
    </div>
</body>
</html>