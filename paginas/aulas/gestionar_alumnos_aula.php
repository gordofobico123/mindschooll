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
$alumnos_aula = []; // Alumnos ya en el aula
$alumnos_disponibles = []; // Alumnos que pueden ser añadidos
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
            $_SESSION['mensaje_error'] = "No tienes permiso para gestionar los alumnos de esta aula.";
            header("Location: " . RUTA_BASE . "paginas/aulas/listar_aulas.php");
            exit();
        }

        // 2. Lógica para añadir un alumno
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'agregar_alumno') {
            $id_alumno_a_agregar = $_POST['id_alumno_a_agregar'] ?? null;

            if ($id_alumno_a_agregar && is_numeric($id_alumno_a_agregar)) {
                // Verificar que el alumno sea realmente un 'alumno'
                $stmt_check_rol = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usuario = ? AND rol = 'alumno'");
                $stmt_check_rol->bind_param("i", $id_alumno_a_agregar);
                $stmt_check_rol->execute();
                $stmt_check_rol->bind_result($es_alumno);
                $stmt_check_rol->fetch();
                $stmt_check_rol->close();

                if ($es_alumno > 0) {
                    // Verificar si el alumno ya está en esta aula
                    $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM aula_alumnos WHERE id_aula = ? AND id_alumno = ?");
                    $stmt_check->bind_param("ii", $id_aula, $id_alumno_a_agregar);
                    $stmt_check->execute();
                    $stmt_check->bind_result($count);
                    $stmt_check->fetch();
                    $stmt_check->close();

                    if ($count == 0) {
                        $stmt_insert = $conexion->prepare("INSERT INTO aula_alumnos (id_aula, id_alumno) VALUES (?, ?)");
                        $stmt_insert->bind_param("ii", $id_aula, $id_alumno_a_agregar);
                        if ($stmt_insert->execute()) {
                            $mensaje_exito = "Alumno añadido al aula con éxito.";
                        } else {
                            $mensaje_error = "Error al añadir alumno: " . $stmt_insert->error;
                        }
                        $stmt_insert->close();
                    } else {
                        $mensaje_error = "Este alumno ya está en el aula.";
                    }
                } else {
                    $mensaje_error = "El usuario seleccionado no es un alumno válido.";
                }
            } else {
                $mensaje_error = "Por favor, selecciona un alumno válido para añadir.";
            }
        }

        // 3. Lógica para eliminar un alumno
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'eliminar_alumno') {
            $id_alumno_a_eliminar = $_POST['id_alumno_a_eliminar'] ?? null;

            if ($id_alumno_a_eliminar && is_numeric($id_alumno_a_eliminar)) {
                $stmt_delete = $conexion->prepare("DELETE FROM aula_alumnos WHERE id_aula = ? AND id_alumno = ?");
                $stmt_delete->bind_param("ii", $id_aula, $id_alumno_a_eliminar);
                if ($stmt_delete->execute()) {
                    $mensaje_exito = "Alumno eliminado del aula con éxito.";
                } else {
                    $mensaje_error = "Error al eliminar alumno: " . $stmt_delete->error;
                }
                $stmt_delete->close();
            } else {
                $mensaje_error = "ID de alumno no especificado o inválido para eliminar.";
            }
        }

        // 4. Obtener alumnos actualmente en esta aula
        $sql_alumnos_aula = "SELECT aa.id_aula_alumno, u.id_usuario, u.nombre, u.apellido, u.email
                             FROM aula_alumnos aa
                             INNER JOIN usuarios u ON aa.id_alumno = u.id_usuario
                             WHERE aa.id_aula = ?
                             ORDER BY u.apellido, u.nombre";
        $stmt_alumnos_aula = $conexion->prepare($sql_alumnos_aula);
        $stmt_alumnos_aula->bind_param("i", $id_aula);
        $stmt_alumnos_aula->execute();
        $resultado_alumnos_aula = $stmt_alumnos_aula->get_result();
        while ($fila = $resultado_alumnos_aula->fetch_assoc()) {
            $alumnos_aula[] = $fila;
        }
        $stmt_alumnos_aula->close();

        // 5. Obtener todos los usuarios con rol 'alumno' que NO están en esta aula
        $sql_alumnos_disponibles = "SELECT id_usuario, nombre, apellido, email
                                    FROM usuarios
                                    WHERE rol = 'alumno'
                                    AND id_usuario NOT IN (SELECT id_alumno FROM aula_alumnos WHERE id_aula = ?)
                                    ORDER BY apellido, nombre";
        $stmt_alumnos_disponibles = $conexion->prepare($sql_alumnos_disponibles);
        $stmt_alumnos_disponibles->bind_param("i", $id_aula);
        $stmt_alumnos_disponibles->execute();
        $resultado_alumnos_disponibles = $stmt_alumnos_disponibles->get_result();
        while ($fila = $resultado_alumnos_disponibles->fetch_assoc()) {
            $alumnos_disponibles[] = $fila;
        }
        $stmt_alumnos_disponibles->close();

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
    <title>Gestionar Alumnos del Aula: <?php echo htmlspecialchars($aula['nombre_aula'] ?? 'Error'); ?> - MindSchool</title>
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
            max-height: 300px; /* Para hacerla scrollable si hay muchos alumnos */
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
            flex-grow: 1; /* Permite que el select ocupe el espacio disponible */
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

        <h1>Gestionar Alumnos del Aula: <?php echo htmlspecialchars($aula['nombre_aula'] ?? 'Error'); ?></h1>

        <?php if ($mensaje_exito): ?>
            <p class="mensaje-exito"><?php echo $mensaje_exito; ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <?php if (!$aula): ?>
            <p class="mensaje-error">No se pudo cargar la información del aula.</p>
        <?php else: ?>

            <h2 class="section-title">Alumnos Actuales en esta Aula</h2>
            <div class="list-container">
                <?php if (!empty($alumnos_aula)): ?>
                    <ul>
                        <?php foreach ($alumnos_aula as $alumno): ?>
                            <li>
                                <div class="list-item-info">
                                    <span><?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido']); ?></span>
                                    <small>(<?php echo htmlspecialchars($alumno['email']); ?>)</small>
                                </div>
                                <form action="" method="POST" class="actions-form">
                                    <input type="hidden" name="accion" value="eliminar_alumno">
                                    <input type="hidden" name="id_alumno_a_eliminar" value="<?php echo htmlspecialchars($alumno['id_usuario']); ?>">
                                    <button type="submit" class="btn-quitar" onclick="return confirm('¿Estás seguro de que quieres eliminar a este alumno del aula?');">Quitar</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-items">No hay alumnos inscritos en esta aula.</p>
                <?php endif; ?>
            </div>

            <h2 class="section-title">Añadir Alumnos a esta Aula</h2>
            <div class="list-container">
                <?php if (!empty($alumnos_disponibles)): ?>
                    <form action="" method="POST" class="actions-form">
                        <input type="hidden" name="accion" value="agregar_alumno">
                        <label for="id_alumno_a_agregar" class="sr-only">Seleccionar Alumno:</label>
                        <select id="id_alumno_a_agregar" name="id_alumno_a_agregar" required>
                            <option value="">-- Selecciona un alumno --</option>
                            <?php foreach ($alumnos_disponibles as $alumno): ?>
                                <option value="<?php echo htmlspecialchars($alumno['id_usuario']); ?>">
                                    <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido'] . ' (' . $alumno['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-agregar">Añadir Alumno</button>
                    </form>
                <?php else: ?>
                    <p class="no-items">No hay alumnos disponibles para añadir o todos ya están en esta aula.</p>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>