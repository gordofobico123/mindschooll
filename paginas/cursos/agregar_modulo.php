<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$rol_usuario = $_SESSION['rol_usuario'];
$id_usuario_sesion = $_SESSION['id_usuario'];

$id_curso = $_GET['id_curso'] ?? null;
$curso_existente = null;
$mensaje_exito = '';
$mensaje_error = '';

// Verificar que se haya proporcionado un ID de curso válido
if (!$id_curso || !is_numeric($id_curso)) {
    $_SESSION['mensaje_error'] = "ID de curso no válido para añadir módulo.";
    header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php");
    exit();
}

// Obtener detalles del curso para verificar permisos
$stmt_curso = $conexion->prepare("SELECT id_curso, nombre_curso, id_profesor FROM cursos WHERE id_curso = ?");
$stmt_curso->bind_param("i", $id_curso);
$stmt_curso->execute();
$resultado_curso = $stmt_curso->get_result();

if ($resultado_curso->num_rows === 1) {
    $curso_existente = $resultado_curso->fetch_assoc();

    // Verificar permisos: solo admin o el profesor asignado al curso
    if ($rol_usuario !== 'admin' && ($rol_usuario === 'profesor' && $curso_existente['id_profesor'] !== $id_usuario_sesion)) {
        $_SESSION['mensaje_error'] = "No tienes permiso para añadir módulos a este curso.";
        header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php");
        exit();
    }
} else {
    $_SESSION['mensaje_error'] = "Curso no encontrado para añadir módulo.";
    header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php");
    exit();
}
$stmt_curso->close();

// Lógica para manejar el envío del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo_modulo = trim($_POST['titulo']);
    $descripcion_modulo = trim($_POST['descripcion']);
    $orden_modulo = filter_var($_POST['orden'], FILTER_VALIDATE_INT);

    if (empty($titulo_modulo) || $orden_modulo === false || $orden_modulo < 1) {
        $mensaje_error = "El título y el orden del módulo son obligatorios. El orden debe ser un número entero positivo.";
    } else {
        // Insertar el nuevo módulo
        $stmt_insert = $conexion->prepare("INSERT INTO modulos (id_curso, titulo, descripcion, `orden`, fecha_publicacion) VALUES (?, ?, ?, ?, NOW())");
        $stmt_insert->bind_param("isss", $id_curso, $titulo_modulo, $descripcion_modulo, $orden_modulo);

        if ($stmt_insert->execute()) {
            $_SESSION['mensaje_exito'] = "Módulo '{$titulo_modulo}' añadido con éxito al curso '{$curso_existente['nombre_curso']}'.";
            header("Location: " . RUTA_BASE . "paginas/cursos/editar_curso.php?id=" . $id_curso);
            exit();
        } else {
            $mensaje_error = "Error al añadir el módulo: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }
}

// Cerrar la conexión a la base de datos
if (isset($conexion) && $conexion instanceof mysqli) {
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Módulo a Curso - MindSchool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f7f6; color: #333; }
        .container { max-width: 700px; margin: 0 auto; padding: 25px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #0056b3; margin-bottom: 30px; }
        h2 { text-align: center; color: #0056b3; margin-bottom: 20px; font-size: 1.5em; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .btn-submit {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            display: block;
            width: 100%;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .btn-submit:hover {
            background-color: #218838;
        }
        .navegacion { margin-bottom: 25px; text-align: center; }
        .navegacion a {
            margin: 0 10px;
            text-decoration: none;
            color: #007bff;
            padding: 8px 15px;
            border: 1px solid #007bff;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
        }
        .navegacion a:hover {
            background-color: #007bff;
            color: white;
        }
        .mensaje-exito { color: green; font-weight: bold; text-align: center; margin-bottom: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; }
        .mensaje-error { color: red; font-weight: bold; text-align: center; margin-bottom: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="navegacion">
            <?php if ($curso_existente): ?>
                <a href="<?php echo RUTA_BASE; ?>paginas/cursos/editar_curso.php?id=<?php echo $id_curso; ?>">Volver a Editar Curso</a>
            <?php endif; ?>
            <a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php">Volver a Cursos</a>
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">Panel de Control</a>
            <a href="<?php echo RUTA_BASE; ?>cerrar_sesion.php">Cerrar Sesión</a>
        </div>

        <a href="<?php echo RUTA_BASE; ?>paginas/cursos/agregar_modulo.php?id_curso=<?php echo $curso_existente['id_curso']; ?>" class="btn-submit" style="background-color: #007bff; text-align: center;">Añadir Nuevo Módulo</a>
        <?php if ($curso_existente): ?>
            <h2>Para el Curso: "<?php echo htmlspecialchars($curso_existente['nombre_curso']); ?>"</h2>
        <?php endif; ?>

        <?php
        if ($mensaje_exito) {
            echo "<p class='mensaje-exito'>" . $mensaje_exito . "</p>";
        }
        if ($mensaje_error) {
            echo "<p class='mensaje-error'>" . $mensaje_error . "</p>";
        }
        ?>

        <form action="<?php echo RUTA_BASE; ?>paginas/cursos/agregar_modulo.php?id_curso=<?php echo $id_curso; ?>" method="POST">
            <div class="form-group">
                <label for="titulo">Título del Módulo:</label>
                <input type="text" id="titulo" name="titulo" required>
            </div>
            <div class="form-group">
                <label for="descripcion">Descripción del Módulo (Opcional):</label>
                <textarea id="descripcion" name="descripcion"></textarea>
            </div>
            <div class="form-group">
                <label for="orden">Orden del Módulo:</label>
                <input type="number" id="orden" name="orden" min="1" value="1" required>
                <small>Define la posición del módulo en el curso (ej. 1, 2, 3...).</small>
            </div>
            <button type="submit" class="btn-submit">Añadir Módulo</button>
        </form>
    </div>
</body>
</html>