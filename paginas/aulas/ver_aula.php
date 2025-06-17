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

$aula = null;
$alumnos_aula = [];
$cursos_aula = [];
$mensaje_error = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_aula = (int)$_GET['id'];

    // 1. Obtener detalles del aula
    $sql_aula = "SELECT a.id_aula, a.nombre_aula, a.descripcion, a.codigo_invitacion, a.fecha_creacion,
                        a.id_profesor, u.nombre AS nombre_profesor, u.apellido AS apellido_profesor
                 FROM aulas a
                 INNER JOIN usuarios u ON a.id_profesor = u.id_usuario
                 WHERE a.id_aula = ?";

    $stmt_aula = $conexion->prepare($sql_aula);
    $stmt_aula->bind_param("i", $id_aula);
    $stmt_aula->execute();
    $resultado_aula = $stmt_aula->get_result();

    if ($resultado_aula->num_rows > 0) {
        $aula = $resultado_aula->fetch_assoc();

        // Verificar si el profesor actual tiene permiso para ver esta aula
        if ($rol_usuario === 'profesor' && $aula['id_profesor'] != $id_usuario_sesion) {
            $_SESSION['mensaje_error'] = "No tienes permiso para ver esta aula.";
            header("Location: " . RUTA_BASE . "paginas/aulas/listar_aulas.php");
            exit();
        }

        // 2. Obtener alumnos inscritos en esta aula
        $sql_alumnos = "SELECT ua.id_usuario, ua.nombre, ua.apellido, ua.email
                        FROM aula_alumnos aa
                        INNER JOIN usuarios ua ON aa.id_alumno = ua.id_usuario
                        WHERE aa.id_aula = ?";
        $stmt_alumnos = $conexion->prepare($sql_alumnos);
        $stmt_alumnos->bind_param("i", $id_aula);
        $stmt_alumnos->execute();
        $resultado_alumnos = $stmt_alumnos->get_result();
        while ($fila_alumno = $resultado_alumnos->fetch_assoc()) {
            $alumnos_aula[] = $fila_alumno;
        }
        $stmt_alumnos->close();

        // 3. Obtener cursos asignados a esta aula
        $sql_cursos = "SELECT c.id_curso, c.nombre_curso, c.nivel_dificultad, c.categoria
                       FROM aula_cursos ac
                       INNER JOIN cursos c ON ac.id_curso = c.id_curso
                       WHERE ac.id_aula = ?";
        $stmt_cursos = $conexion->prepare($sql_cursos);
        $stmt_cursos->bind_param("i", $id_aula);
        $stmt_cursos->execute();
        $resultado_cursos = $stmt_cursos->get_result();
        while ($fila_curso = $resultado_cursos->fetch_assoc()) {
            $cursos_aula[] = $fila_curso;
        }
        $stmt_cursos->close();

    } else {
        $mensaje_error = "Aula no encontrada.";
    }
    $stmt_aula->close();

} else {
    $mensaje_error = "ID de aula no especificado o inválido.";
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Aula: <?php echo htmlspecialchars($aula['nombre_aula'] ?? 'Aula no encontrada'); ?> - MindSchool</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background-color: #f0f2f5; color: #333; }
        .container { max-width: 900px; margin: 20px auto; padding: 30px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #0056b3; font-size: 2.8em; margin-bottom: 10px; }
        .header p { color: #666; font-size: 1.1em; }
        .navegacion { margin-bottom: 30px; text-align: center; display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; }
        .navegacion a { text-decoration: none; color: #007bff; padding: 10px 20px; border: 1px solid #007bff; border-radius: 25px; transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease; font-weight: 500; white-space: nowrap; }
        .navegacion a:hover { background-color: #007bff; color: white; transform: translateY(-2px); }
        
        .info-section {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
        }
        .info-section h2 { color: #007bff; margin-top: 0; margin-bottom: 15px; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        .info-section p { margin: 8px 0; line-height: 1.6; }
        .info-section strong { color: #003b7a; }
        .codigo-invitacion {
            font-family: 'Courier New', Courier, monospace;
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            color: #495057;
            display: inline-block;
            margin-left: 10px;
        }

        .list-section {
            margin-bottom: 25px;
        }
        .list-section h3 { color: #28a745; margin-bottom: 15px; border-bottom: 2px solid #28a745; padding-bottom: 5px; }
        .list-section ul { list-style: none; padding: 0; }
        .list-section li {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 12px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .list-section li:hover {
            transform: translateY(-2px);
        }
        .list-section li span {
            font-weight: 500;
            color: #333;
        }
        .list-section li .detail {
            color: #666;
            font-size: 0.9em;
        }
        .list-section .acciones {
            display: flex;
            gap: 8px;
        }
        .list-section .acciones a, .list-section .acciones button {
            padding: 6px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.8em;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }
        .list-section .acciones .btn-remove { background-color: #dc3545; color: white; }
        .list-section .acciones .btn-remove:hover { background-color: #c82333; }
        .no-items {
            text-align: center;
            color: #888;
            font-style: italic;
            padding: 20px;
            background-color: #fefefe;
            border: 1px dashed #e9ecef;
            border-radius: 8px;
        }
        .mensaje-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="navegacion">
            <a href="<?php echo RUTA_BASE; ?>paginas/aulas/listar_aulas.php">Volver a Aulas</a>
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">Panel de Control</a>
            <a href="<?php echo RUTA_BASE; ?>cerrar_sesion.php">Cerrar Sesión</a>
        </div>

        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo $mensaje_error; ?></p>
        <?php elseif ($aula): ?>
            <div class="header">
                <h1>Aula: <?php echo htmlspecialchars($aula['nombre_aula']); ?></h1>
                <p><?php echo nl2br(htmlspecialchars($aula['descripcion'] ?? 'No hay descripción para esta aula.')); ?></p>
            </div>

            <div class="info-section">
                <h2>Información del Aula</h2>
                <p><strong>ID del Aula:</strong> <?php echo htmlspecialchars($aula['id_aula']); ?></p>
                <p>
                    <strong>Profesor Creador:</strong> 
                    <?php echo htmlspecialchars($aula['nombre_profesor'] . ' ' . $aula['apellido_profesor']); ?>
                </p>
                <p>
                    <strong>Código de Invitación:</strong> 
                    <span class="codigo-invitacion"><?php echo htmlspecialchars($aula['codigo_invitacion'] ?? 'N/A'); ?></span> 
                    (Comparte este código para invitar alumnos)
                </p>
                <p><strong>Fecha de Creación:</strong> <?php echo date('d/m/Y H:i', strtotime($aula['fecha_creacion'])); ?></p>
            </div>

            <div class="list-section">
                <h3>Alumnos Inscritos en el Aula (<?php echo count($alumnos_aula); ?>)</h3>
                <?php if (!empty($alumnos_aula)): ?>
                    <ul>
                        <?php foreach ($alumnos_aula as $alumno): ?>
                            <li>
                                <span><?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido']); ?></span>
                                <span class="detail">(<?php echo htmlspecialchars($alumno['email']); ?>)</span>
                                </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-items">No hay alumnos inscritos en esta aula aún. ¡Invita a algunos!</p>
                <?php endif; ?>
            </div>

            <div class="list-section">
                <h3>Cursos Asignados al Aula (<?php echo count($cursos_aula); ?>)</h3>
                <?php if (!empty($cursos_aula)): ?>
                    <ul>
                        <?php foreach ($cursos_aula as $curso): ?>
                            <li>
                                <span><?php echo htmlspecialchars($curso['nombre_curso']); ?></span>
                                <span class="detail">(Nivel: <?php echo htmlspecialchars($curso['nivel_dificultad']); ?>, Categoría: <?php echo htmlspecialchars($curso['categoria']); ?>)</span>
                                </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-items">No hay cursos asignados a esta aula. ¡Asigna algunos!</p>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>