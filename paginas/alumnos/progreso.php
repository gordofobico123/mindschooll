<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Asegúrate de que solo los alumnos puedan acceder a esta página
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'alumno') {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$id_alumno = $_SESSION['id_usuario'];
$progreso_cursos = []; // Aquí almacenaremos el progreso por curso
$mensaje = "";
$tipo_mensaje = "";

try {
    // Consulta para obtener los cursos en los que está inscrito el alumno y su progreso general
    // Necesitamos unir usuarios, inscripciones, cursos y progreso_alumno para obtener la información completa.
    $stmt = $conexion->prepare("
        SELECT
            c.id_curso,
            c.titulo AS curso_titulo,
            c.descripcion AS curso_descripcion,
            COUNT(DISTINCT l.id_leccion) AS total_lecciones_curso,
            SUM(CASE WHEN pa.estado = 'completado' THEN 1 ELSE 0 END) AS lecciones_completadas_curso
        FROM
            inscripciones i
        JOIN
            cursos c ON i.id_curso = c.id_curso
        LEFT JOIN
            lecciones l ON c.id_curso = l.id_curso
        LEFT JOIN
            progreso_alumno pa ON i.id_inscripcion = pa.id_inscripcion AND l.id_leccion = pa.id_leccion
        WHERE
            i.id_alumno = ?
        GROUP BY
            c.id_curso, c.titulo, c.descripcion
        ORDER BY
            c.titulo;
    ");
    $stmt->bind_param("i", $id_alumno);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        while ($curso = $resultado->fetch_assoc()) {
            $curso_id = $curso['id_curso'];
            $total_lecciones = $curso['total_lecciones_curso'];
            $lecciones_completadas = $curso['lecciones_completadas_curso'];

            // Calcular el porcentaje de progreso para el curso
            $porcentaje_progreso = ($total_lecciones > 0) ? round(($lecciones_completadas / $total_lecciones) * 100) : 0;

            $progreso_cursos[$curso_id] = [
                'titulo' => $curso['curso_titulo'],
                'descripcion' => $curso['curso_descripcion'],
                'total_lecciones' => $total_lecciones,
                'lecciones_completadas' => $lecciones_completadas,
                'porcentaje_progreso' => $porcentaje_progreso,
                'lecciones_detalle' => [] // Aquí almacenaremos el detalle de cada lección
            ];
        }
        $resultado->free(); // Liberar el resultado de la primera consulta
    } else {
        $mensaje = "No estás inscrito en ningún curso o no hay progreso registrado.";
        $tipo_mensaje = "info";
    }

    // Para cada curso, obtener el detalle de las lecciones
    foreach ($progreso_cursos as $curso_id => &$curso_data) { // Usar '&' para modificar el array directamente
        $stmt_lecciones = $conexion->prepare("
            SELECT
                l.id_leccion,
                l.titulo AS leccion_titulo,
                l.descripcion AS leccion_descripcion,
                pa.estado AS estado_leccion,
                pa.fecha_completado
            FROM
                lecciones l
            LEFT JOIN
                inscripciones i ON l.id_curso = i.id_curso
            LEFT JOIN
                progreso_alumno pa ON i.id_inscripcion = pa.id_inscripcion AND l.id_leccion = pa.id_leccion
            WHERE
                l.id_curso = ? AND i.id_alumno = ?
            ORDER BY
                l.orden;
        ");
        $stmt_lecciones->bind_param("ii", $curso_id, $id_alumno);
        $stmt_lecciones->execute();
        $resultado_lecciones = $stmt_lecciones->get_result();

        while ($leccion = $resultado_lecciones->fetch_assoc()) {
            $curso_data['lecciones_detalle'][] = $leccion;
        }
        $resultado_lecciones->free();
    }
    unset($curso_data); // Romper la referencia al último elemento
    
} catch (Exception $e) {
    $mensaje = "Error al cargar el progreso: " . $e->getMessage();
    $tipo_mensaje = "error";
    error_log("Error en progreso_alumno.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Progreso - MindSchool</title>
    <link rel="stylesheet" href="<?php echo RUTA_BASE; ?>css/estilos.css"> <style>
        /* Estilos específicos para esta página */
        body { font-family: 'Arial', sans-serif; background-color: #f4f7f6; color: #333; margin: 0; padding-top: 60px; }
        .contenedor-principal { max-width: 1000px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        .mensaje { padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .mensaje-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .mensaje-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .curso-card { background-color: #f9f9f9; border: 1px solid #eee; border-radius: 6px; margin-bottom: 25px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .curso-card h2 { color: #34495e; margin-top: 0; margin-bottom: 10px; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px; }
        .progreso-barra { background-color: #e0e0e0; border-radius: 5px; height: 20px; margin-top: 15px; overflow: hidden; }
        .progreso-fill { height: 100%; background-color: #28a745; border-radius: 5px; text-align: center; color: white; font-weight: bold; line-height: 20px; transition: width 0.5s ease-in-out; }
        .leccion-list { list-style: none; padding: 0; margin-top: 15px; }
        .leccion-item { background-color: #ffffff; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; padding: 12px 15px; display: flex; align-items: center; justify-content: space-between; }
        .leccion-item.completado { background-color: #e6ffe6; border-color: #c3e6cb; }
        .leccion-item.pendiente { background-color: #fff3e6; border-color: #ffe0b3; }
        .leccion-item .titulo-leccion { font-weight: bold; flex-grow: 1; }
        .leccion-item .estado-leccion {
            font-size: 0.9em;
            padding: 4px 8px;
            border-radius: 3px;
            color: white;
            min-width: 80px;
            text-align: center;
        }
        .leccion-item .estado-leccion.completado { background-color: #28a745; }
        .leccion-item .estado-leccion.pendiente { background-color: #ffc107; }
        .leccion-item .estado-leccion.vacio { background-color: #6c757d; } /* Para lecciones sin registro de progreso */

        /* Estilos para el encabezado (si usas un encabezado incluido) */
        header { background-color: #3498db; color: white; padding: 15px 0; text-align: center; position: fixed; width: 100%; top: 0; left: 0; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        header nav ul { list-style: none; padding: 0; margin: 0; display: flex; justify-content: center; }
        header nav ul li { margin: 0 15px; }
        header nav ul li a { color: white; text-decoration: none; font-weight: bold; }
        header nav ul li a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; // Incluye tu encabezado aquí ?>

    <div class="contenedor-principal">
        <h1>Mi Progreso en Cursos</h1>

        <?php if ($mensaje): ?>
            <div class="mensaje mensaje-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($progreso_cursos) && empty($mensaje)): ?>
            <div class="mensaje mensaje-info">
                No estás inscrito en ningún curso o aún no hay lecciones asignadas a tus cursos.
            </div>
        <?php endif; ?>

        <?php foreach ($progreso_cursos as $curso): ?>
            <div class="curso-card">
                <h2><?php echo htmlspecialchars($curso['titulo']); ?></h2>
                <p><?php echo htmlspecialchars($curso['descripcion']); ?></p>

                <p>Progreso General: (<?php echo $curso['lecciones_completadas']; ?> / <?php echo $curso['total_lecciones']; ?> lecciones completadas)</p>
                <div class="progreso-barra">
                    <div class="progreso-fill" style="width: <?php echo $curso['porcentaje_progreso']; ?>%;">
                        <?php echo $curso['porcentaje_progreso']; ?>%
                    </div>
                </div>

                <h3>Detalle de Lecciones:</h3>
                <?php if (!empty($curso['lecciones_detalle'])): ?>
                    <ul class="leccion-list">
                        <?php foreach ($curso['lecciones_detalle'] as $leccion): ?>
                            <?php
                                $estado_class = 'vacio'; // Por defecto, si no hay registro de progreso
                                $estado_texto = 'Sin iniciar';
                                if (!empty($leccion['estado_leccion'])) {
                                    $estado_class = $leccion['estado_leccion'];
                                    $estado_texto = ucfirst($leccion['estado_leccion']);
                                }
                            ?>
                            <li class="leccion-item <?php echo $estado_class; ?>">
                                <span class="titulo-leccion"><?php echo htmlspecialchars($leccion['leccion_titulo']); ?></span>
                                <span class="estado-leccion <?php echo $estado_class; ?>">
                                    <?php echo $estado_texto; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No hay lecciones registradas para este curso.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php include '../../includes/footer.php'; // Incluye tu pie de página si tienes uno ?>
</body>
</html>