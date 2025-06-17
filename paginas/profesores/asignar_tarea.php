<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario está autenticado y es un profesor
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'profesor') {
    $_SESSION['mensaje_error'] = "Acceso denegado. Solo los profesores pueden asignar tareas.";
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

$id_profesor = $_SESSION['id_usuario'];
$mensaje_exito = '';
$mensaje_error = '';

// Obtener los cursos del profesor
$sql = "SELECT id_curso, nombre_curso FROM cursos WHERE id_profesor = ? AND estado = 'activo' ORDER BY nombre_curso";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_profesor);
$stmt->execute();
$resultado = $stmt->get_result();
$cursos = [];
while ($fila = $resultado->fetch_assoc()) {
    $cursos[] = $fila;
}
$stmt->close();

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_curso = intval($_POST['id_curso'] ?? 0);
    $titulo_tarea = trim($_POST['titulo_tarea'] ?? '');
    $descripcion_tarea = trim($_POST['descripcion_tarea'] ?? '');
    $fecha_vencimiento = trim($_POST['fecha_vencimiento'] ?? '');

    // Validaciones
    if (empty($id_curso) || empty($titulo_tarea) || empty($descripcion_tarea) || empty($fecha_vencimiento)) {
        $mensaje_error = "Todos los campos son obligatorios.";
    } elseif (!in_array($id_curso, array_column($cursos, 'id_curso'))) {
        $mensaje_error = "El curso seleccionado no es válido o no pertenece a su cuenta.";
    } else {
        // Insertar la tarea en la base de datos
        $sql_insert = "INSERT INTO tareas (id_curso, titulo, descripcion, fecha_vencimiento) VALUES (?, ?, ?, ?)";
        $stmt_insert = $conexion->prepare($sql_insert);
        if ($stmt_insert === false) {
            die("Error al preparar la inserción de tarea: " . $conexion->error);
        }
        $stmt_insert->bind_param("isss", $id_curso, $titulo_tarea, $descripcion_tarea, $fecha_vencimiento);

        if ($stmt_insert->execute()) {
            $mensaje_exito = "Tarea asignada correctamente.";
            // Limpiar los campos del formulario después del éxito
            $titulo_tarea = '';
            $descripcion_tarea = '';
            $fecha_vencimiento = '';
        } else {
            $mensaje_error = "Error al asignar la tarea: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }
}

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Tarea - MindSchool</title>
    <link rel="stylesheet" href="<?php echo RUTA_BASE; ?>public/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            max-width: 600px;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: 90%;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"],
        textarea,
        select,
        input[type="date"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ddd;
            box-sizing: border-box;
            font-size: 1em;
        }
        textarea {
            resize: vertical;
        }
        button {
            margin-top: 25px;
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #0056b3;
        }
        .mensaje-exito {
            color: green;
            background-color: #e6ffe6;
            border: 1px solid #a3e6a3;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 15px;
        }
        .mensaje-error {
            color: red;
            background-color: #ffe6e6;
            border: 1px solid #e6a3a3;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Asignar Nueva Tarea</h2>
        <?php if ($mensaje_exito): ?>
            <div class="mensaje-exito"><?php echo $mensaje_exito; ?></div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="mensaje-error"><?php echo $mensaje_error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <label for="id_curso">Seleccionar Curso:</label>
            <select id="id_curso" name="id_curso" required>
                <option value="">Selecciona un curso</option>
                <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo htmlspecialchars($curso['id_curso']); ?>"><?php echo htmlspecialchars($curso['nombre_curso']); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="titulo_tarea">Título de la Tarea:</label>
            <input type="text" id="titulo_tarea" name="titulo_tarea" value="<?php echo htmlspecialchars($titulo_tarea ?? ''); ?>" required>

            <label for="descripcion_tarea">Descripción de la Tarea:</label>
            <textarea id="descripcion_tarea" name="descripcion_tarea" rows="6" required><?php echo htmlspecialchars($descripcion_tarea ?? ''); ?></textarea>

            <label for="fecha_vencimiento">Fecha de Vencimiento:</label>
            <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" value="<?php echo htmlspecialchars($fecha_vencimiento ?? ''); ?>" required>

            <button type="submit">Asignar Tarea</button>
        </form>
    </div>
</body>
</html>