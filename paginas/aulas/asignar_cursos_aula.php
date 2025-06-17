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
$id_usuario_sesion = $_SESSION['id_usuario']; // ID del profesor o admin en sesión

$aula = null;
$cursos_aula = []; // Cursos ya asignados al aula
$cursos_disponibles = []; // Cursos que pueden ser asignados
$mensaje_exito = '';
$mensaje_error = '';

// Obtener ID del aula desde la URL
if (isset($_GET['id_aula']) && is_numeric($_GET['id_aula'])) {
    $id_aula = (int)$_GET['id_aula'];

    // 1. Obtener detalles del aula y verificar permisos
    $sql_aula = "SELECT id_aula, nombre_aula, id_profesor FROM aulas WHERE id_aula = ?";
    $stmt_aula = $conexion->prepare($sql_aula);
    $stmt_aula->bind_param("i", $id_aula);
    $stmt_aula->execute();
    $resultado_aula = $stmt_aula->get_result();

    if ($resultado_aula->num_rows > 0) {
        $aula = $resultado_aula->fetch_assoc();

        // Verificar si el profesor actual tiene permiso para gestionar esta aula
        if ($rol_usuario === 'profesor' && $aula['id_profesor'] != $id_usuario_sesion) {
            $_SESSION['mensaje_error'] = "No tienes permiso para gestionar los cursos de esta aula.";
            header("Location: " . RUTA_BASE . "paginas/aulas/listar_aulas.php");
            exit();
        }

        // 2. Lógica para asignar un curso
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'asignar_curso') {
            $id_curso_a_asignar = $_POST['id_curso_a_asignar'] ?? null;

            if ($id_curso_a_asignar && is_numeric($id_curso_a_asignar)) {
                // Opcional: Verificar que el curso pertenezca a este profesor (si rol es profesor)
                // Esto es una medida de seguridad extra
                if ($rol_usuario === 'profesor') {
                    $stmt_check_curso_propio = $conexion->prepare("SELECT COUNT(*) FROM cursos WHERE id_curso = ? AND id_profesor = ?");
                    $stmt_check_curso_propio->bind_param("ii", $id_curso_a_asignar, $id_usuario_sesion);
                    $stmt_check_curso_propio->execute();
                    $stmt_check_curso_propio->bind_result($es_curso_propio);
                    $stmt_check_curso_propio->fetch();
                    $stmt_check_curso_propio->close();

                    if ($es_curso_propio == 0) {
                        $mensaje_error = "No tienes permiso para asignar este curso o el curso no existe.";
                        // Limpiar para que no se siga el resto del POST si hay error de permiso
                        $id_curso_a_asignar = null; 
                    }
                }

                if ($id_curso_a_asignar) { // Si pasó la verificación de permiso o es admin
                    // Verificar si el curso ya está asignado a esta aula
                    $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM aula_cursos WHERE id_aula = ? AND id_curso = ?");
                    $stmt_check->bind_param("ii", $id_aula, $id_curso_a_asignar);
                    $stmt_check->execute();
                    $stmt_check->bind_result($count);
                    $stmt_check->fetch();
                    $stmt_check->close();

                    if ($count == 0) {
                        $stmt_insert = $conexion->prepare("INSERT INTO aula_cursos (id_aula, id_curso) VALUES (?, ?)");
                        $stmt_insert->bind_param("ii", $id_aula, $id_curso_a_asignar);
                        if ($stmt_insert->execute()) {
                            $mensaje_exito = "Curso asignado al aula con éxito.";
                        } else {
                            $mensaje_error = "Error al asignar curso: " . $stmt_insert->error;
                        }
                        $stmt_insert->close();
                    } else {
                        $mensaje_error = "Este curso ya está asignado a esta aula.";
                    }
                }
            } else {
                $mensaje_error = "Por favor, selecciona un curso válido para asignar.";
            }
        }

        // 3. Lógica para desasignar un curso
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'desasignar_curso') {
            $id_curso_a_desasignar = $_POST['id_curso_a_desasignar'] ?? null;

            if ($id_curso_a_desasignar && is_numeric($id_curso_a_desasignar)) {
                 // Opcional: Verificar que el curso pertenezca a este profesor (si rol es profesor)
                if ($rol_usuario === 'profesor') {
                    $stmt_check_curso_propio = $conexion->prepare("SELECT COUNT(*) FROM cursos WHERE id_curso = ? AND id_profesor = ?");
                    $stmt_check_curso_propio->bind_param("ii", $id_curso_a_desasignar, $id_usuario_sesion);
                    $stmt_check_curso_propio->execute();
                    $stmt_check_curso_propio->bind_result($es_curso_propio);
                    $stmt_check_curso_propio->fetch();
                    $stmt_check_curso_propio->close();

                    if ($es_curso_propio == 0) {
                        $mensaje_error = "No tienes permiso para desasignar este curso o el curso no existe.";
                        // Limpiar para que no se siga el resto del POST si hay error de permiso
                        $id_curso_a_desasignar = null; 
                    }
                }

                if ($id_curso_a_desasignar) { // Si pasó la verificación de permiso o es admin
                    $stmt_delete = $conexion->prepare("DELETE FROM aula_cursos WHERE id_aula = ? AND id_curso = ?");
                    $stmt_delete->bind_param("ii", $id_aula, $id_curso_a_desasignar);
                    if ($stmt_delete->execute()) {
                        $mensaje_exito = "Curso desasignado del aula con éxito.";
                    } else {
                        $mensaje_error = "Error al desasignar curso: " . $stmt_delete->error;
                    }
                    $stmt_delete->close();
                }
            } else {
                $mensaje_error = "ID de curso no especificado o inválido para desasignar.";
            }
        }

        // 4. Obtener cursos actualmente asignados a esta aula
        $sql_cursos_aula = "SELECT c.id_curso, c.nombre_curso, c.nivel_dificultad, c.categoria
                             FROM aula_cursos ac
                             INNER JOIN cursos c ON ac.id_curso = c.id_curso
                             WHERE ac.id_aula = ?
                             ORDER BY c.nombre_curso";
        $stmt_cursos_aula = $conexion->prepare($sql_cursos_aula);
        $stmt_cursos_aula->bind_param("i", $id_aula);
        $stmt_cursos_aula->execute();
        $resultado_cursos_aula = $stmt_cursos_aula->get_result();
        while ($fila = $resultado_cursos_aula->fetch_assoc()) {
            $cursos_aula[] = $fila;
        }
        $stmt_cursos_aula->close();

        // 5. Obtener cursos disponibles (solo del profesor actual si es profesor, o todos si es admin)
        // que NO están ya asignados a esta aula
        $sql_cursos_disponibles = "SELECT id_curso, nombre_curso, nivel_dificultad, categoria
                                    FROM cursos
                                    WHERE id_curso NOT IN (SELECT id_curso FROM aula_cursos WHERE id_aula = ?)";
        
        $params_disp = [$id_aula];
        $types_disp = "i";

        if ($rol_usuario === 'profesor') {
            $sql_cursos_disponibles .= " AND id_profesor = ?";
            $params_disp[] = $id_usuario_sesion;
            $types_disp .= "i";
        }
        $sql_cursos_disponibles .= " ORDER BY nombre_curso";

        $stmt_cursos_disponibles = $conexion->prepare($sql_cursos_disponibles);
        $stmt_cursos_disponibles->bind_param($types_disp, ...$params_disp);
        $stmt_cursos_disponibles->execute();
        $resultado_cursos_disponibles = $stmt_cursos_disponibles->get_result();
        while ($fila = $resultado_cursos_disponibles->fetch_assoc()) {
            $cursos_disponibles[] = $fila;
        }
        $stmt_cursos_disponibles->close();

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
    <title>Asignar Cursos al Aula: <?php echo htmlspecialchars($aula['nombre_aula'] ?? 'Error'); ?> - MindSchool</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background-color: #f0f2f5; color: #333; }
        .container { max-width: 900px; margin: 20px auto; padding: 30px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #0056b3; margin-bottom: 30px; font-size: 2.5em; font-weight: 600; }
        .navegacion { margin-bottom: 30px; text-align: center; display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; }
        .navegacion a { text-decoration: none; color: #007bff; padding: 10px 20px; border: 1px solid #007bff; border-radius: 25px; transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease; font-weight: 500; white-space: nowrap; }
        .navegacion a:hover { background-color: #007bff; color: white; transform: translateY(-2px); }
        .mensaje-exito { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .mensaje-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }

        .section-title {
            color: #0056b3;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .list-container {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background-color: #fcfcfc;
            margin-bottom: 25px;
        }

        .list-container ul {
            list-style: none;
            padding: 0;
            max-height: 300px; /* Para hacerla scrollable si hay muchos cursos */
            overflow-y: auto;
        }

        .list-container li {
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 8px;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .list-container li:last-child { margin-bottom: 0; }

        .list-item-info span {
            font-weight: 500;
            color: #333;
        }
        .list-item-info small {
            color: #666;
            font-size: 0.9em;
            margin-left: 10px;
        }

        .actions-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .actions-form select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            flex-grow: 1;
        }
        .actions-form button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .btn-agregar { background-color: #28a745; color: white; }
        .btn-agregar:hover { background-color: #218838; }
        .btn-quitar { background-color: #dc3545; color: white; }
        .btn-quitar:hover { background-color: #c82333; }
        
        .no-items { text-align: center; color: #888; font-style: italic; padding: 20px; background-color: #fefefe; border: 1px dashed #e9ecef; border-radius: 8px; margin-top: 15px; }

    </style>
</head>
<body>
    <div class="container">
        <div class="navegacion">
            <a href="<?php echo RUTA_BASE; ?>paginas/aulas/listar_aulas.php">Volver a Aulas</a>
            <a href="<?php echo RUTA_BASE; ?>paginas/aulas/ver_aula.php?id=<?php echo htmlspecialchars($id_aula); ?>">Ver Detalles del Aula</a>
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">Panel de Control</a>
            <a href="<?php echo RUTA_BASE; ?>cerrar_sesion.php">Cerrar Sesión</a>
        </div>

        <h1>Asignar Cursos al Aula: <?php echo htmlspecialchars($aula['nombre_aula'] ?? 'Error'); ?></h1>

        <?php if ($mensaje_exito): ?>
            <p class="mensaje-exito"><?php echo $mensaje_exito; ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <?php if (!$aula): ?>
            <p class="mensaje-error">No se pudo cargar la información del aula.</p>
        <?php else: ?>

            <h2 class="section-title">Cursos Actuales en esta Aula</h2>
            <div class="list-container">
                <?php if (!empty($cursos_aula)): ?>
                    <ul>
                        <?php foreach ($cursos_aula as $curso): ?>
                            <li>
                                <div class="list-item-info">
                                    <span><?php echo htmlspecialchars($curso['nombre_curso']); ?></span>
                                    <small>(Nivel: <?php echo htmlspecialchars($curso['nivel_dificultad']); ?>, Categoría: <?php echo htmlspecialchars($curso['categoria']); ?>)</small>
                                </div>
                                <form action="" method="POST" class="actions-form">
                                    <input type="hidden" name="accion" value="desasignar_curso">
                                    <input type="hidden" name="id_curso_a_desasignar" value="<?php echo htmlspecialchars($curso['id_curso']); ?>">
                                    <button type="submit" class="btn-quitar" onclick="return confirm('¿Estás seguro de que quieres desasignar este curso del aula?');">Quitar</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-items">No hay cursos asignados a esta aula.</p>
                <?php endif; ?>
            </div>

            <h2 class="section-title">Asignar Cursos a esta Aula</h2>
            <div class="list-container">
                <?php if (!empty($cursos_disponibles)): ?>
                    <form action="" method="POST" class="actions-form">
                        <input type="hidden" name="accion" value="asignar_curso">
                        <label for="id_curso_a_asignar" class="sr-only">Seleccionar Curso:</label>
                        <select id="id_curso_a_asignar" name="id_curso_a_asignar" required>
                            <option value="">-- Selecciona un curso --</option>
                            <?php foreach ($cursos_disponibles as $curso): ?>
                                <option value="<?php echo htmlspecialchars($curso['id_curso']); ?>">
                                    <?php echo htmlspecialchars($curso['nombre_curso'] . ' (Nivel: ' . $curso['nivel_dificultad'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-agregar">Asignar Curso</button>
                    </form>
                <?php else: ?>
                    <p class="no-items">No hay cursos disponibles para asignar o todos tus cursos ya están asignados a esta aula (o eres admin y no hay más cursos).</p>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>