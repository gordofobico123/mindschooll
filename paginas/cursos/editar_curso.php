<?php
// Iniciar sesión y cargar archivos de configuración y base de datos
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol_usuario'] !== 'profesor' && $_SESSION['rol_usuario'] !== 'admin')) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$rol_usuario = $_SESSION['rol_usuario'];
$id_usuario = $_SESSION['id_usuario'];
$id_curso = $_GET['id'] ?? null;
$curso = null;
$profesores_disponibles = [];
$mensaje_exito = '';
$mensaje_error = '';

// Directorio para imágenes de portada
$directorio_imagenes = realpath(__DIR__ . '/../../imagenes_cursos/') . DIRECTORY_SEPARATOR;
if (!is_dir($directorio_imagenes)) {
    mkdir($directorio_imagenes, 0777, true);
}

// Obtener datos del curso a editar
if (!$id_curso || !is_numeric($id_curso)) {
    $mensaje_error = "ID de curso no válido para edición.";
} else {
    $stmt = $conexion->prepare("SELECT * FROM cursos WHERE id_curso = ?");
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $curso = $res->fetch_assoc();
        // Solo el profesor dueño o el admin puede editar
        if ($rol_usuario === 'profesor' && $curso['id_profesor'] != $id_usuario) {
            $_SESSION['mensaje_error'] = "No tienes permiso para editar este curso.";
            header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php");
            exit();
        }
    } else {
        $mensaje_error = "Curso no encontrado.";
    }
    $stmt->close();
}

// Obtener lista de profesores (solo para admin)
if ($rol_usuario === 'admin') {
    $sql_profesores = "SELECT id_usuario, nombre, apellido FROM usuarios WHERE rol_usuario = 'profesor' ORDER BY nombre ASC";
    $res_profesores = $conexion->query($sql_profesores);
    while ($fila = $res_profesores->fetch_assoc()) {
        $profesores_disponibles[] = $fila;
    }
}

// Procesar formulario de edición
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_curso_editar'])) {
    $nombre_curso = trim($_POST['nombre_curso'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $nivel_dificultad = $_POST['nivel_dificultad'] ?? '';
    $categoria = trim($_POST['categoria'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0.0);
    $estado = $_POST['estado'] ?? 'en_edicion';
    $id_profesor_asignado = ($rol_usuario === 'admin') ? (int)($_POST['id_profesor_asignado'] ?? 0) : $id_usuario;
    $imagen_portada_actual = $_POST['imagen_portada_actual'] ?? '';
    $imagen_portada = $imagen_portada_actual;

    // Validaciones básicas
    if (empty($nombre_curso) || empty($descripcion) || empty($nivel_dificultad) || empty($categoria) || $precio < 0 || empty($estado) || $id_profesor_asignado <= 0) {
        $mensaje_error = "Todos los campos obligatorios deben ser completados.";
    } else {
        // Subida de nueva imagen de portada si corresponde
        if (isset($_FILES['imagen_portada']) && $_FILES['imagen_portada']['error'] == UPLOAD_ERR_OK) {
            $nombre_archivo = basename($_FILES["imagen_portada"]["name"]);
            $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
            $nombre_unico = uniqid('curso_') . '.' . $extension;
            $ruta_destino = $directorio_imagenes . $nombre_unico;
            $tipo_archivo = strtolower($extension);

            $es_imagen = getimagesize($_FILES["imagen_portada"]["tmp_name"]);
            if ($es_imagen && in_array($tipo_archivo, ['jpg', 'jpeg', 'png', 'gif'])) {
                if ($_FILES["imagen_portada"]["size"] <= 5000000) { // 5MB
                    if (move_uploaded_file($_FILES["imagen_portada"]["tmp_name"], $ruta_destino)) {
                        $imagen_portada = $nombre_unico;
                        // Eliminar imagen anterior si existe y no es la predeterminada
                        if (!empty($imagen_portada_actual) && file_exists($directorio_imagenes . $imagen_portada_actual)) {
                            unlink($directorio_imagenes . $imagen_portada_actual);
                        }
                    } else {
                        $mensaje_error = "Error al subir la nueva imagen.";
                    }
                } else {
                    $mensaje_error = "La imagen es demasiado grande (máx. 5MB).";
                }
            } else {
                $mensaje_error = "Solo se permiten imágenes JPG, JPEG, PNG o GIF.";
            }
        }

        // Actualizar curso en la base de datos
        if (empty($mensaje_error)) {
            $stmt_update = $conexion->prepare("UPDATE cursos SET nombre_curso = ?, descripcion = ?, nivel_dificultad = ?, categoria = ?, precio = ?, estado = ?, id_profesor = ?, imagen_portada = ? WHERE id_curso = ?");
            $stmt_update->bind_param("ssssdsisi", $nombre_curso, $descripcion, $nivel_dificultad, $categoria, $precio, $estado, $id_profesor_asignado, $imagen_portada, $id_curso);
            if ($stmt_update->execute()) {
                $_SESSION['mensaje_exito'] = "Curso actualizado con éxito.";
                header("Location: " . RUTA_BASE . "paginas/cursos/editar_curso.php?id=" . $id_curso);
                exit();
            } else {
                $mensaje_error = "Error al actualizar el curso: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    }
}

// Obtener módulos y lecciones del curso
$modulos = [];
if ($curso) {
    $stmt_modulos = $conexion->prepare("SELECT id_modulo, nombre_modulo, descripcion_modulo, orden FROM modulos_curso WHERE id_curso = ? ORDER BY orden ASC");
    $stmt_modulos->bind_param("i", $id_curso);
    $stmt_modulos->execute();
    $res_modulos = $stmt_modulos->get_result();
    while ($modulo = $res_modulos->fetch_assoc()) {
        $stmt_lecciones = $conexion->prepare("SELECT id_leccion, nombre_leccion, tipo, contenido_texto, url_recurso, orden FROM lecciones WHERE id_modulo = ? ORDER BY orden ASC");
        $stmt_lecciones->bind_param("i", $modulo['id_modulo']);
        $stmt_lecciones->execute();
        $res_lecciones = $stmt_lecciones->get_result();
        $modulo['lecciones'] = [];
        while ($leccion = $res_lecciones->fetch_assoc()) {
            $modulo['lecciones'][] = $leccion;
        }
        $stmt_lecciones->close();
        $modulos[] = $modulo;
    }
    $stmt_modulos->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Curso: <?php echo htmlspecialchars($curso['nombre_curso'] ?? 'Cargando...'); ?> - MindSchool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Arial, sans-serif; color: #222; margin: 0; padding: 0; }
        .contenedor { max-width: 900px; margin: 32px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 32px 24px; }
        h1 { color: #3f51b5; margin-bottom: 24px; text-align: center; }
        .mensaje-exito, .mensaje-error { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 1em; font-weight: bold; text-align: center; }
        .mensaje-exito { background: #e8f5e9; color: #388e3c; border: 1px solid #c8e6c9; }
        .mensaje-error { background: #fff3f3; color: #d32f2f; border: 1px solid #f8d7da; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group textarea, .form-group select {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 1em;
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group input[type="file"] { border: 1px solid #ddd; padding: 10px; border-radius: 6px; background-color: #f9f9f9; width: auto; }
        .current-image { margin-top: 10px; margin-bottom: 15px; text-align: center; }
        .current-image img { max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .current-image p { font-size: 0.9em; color: #666; margin-top: 5px; }
        .btn-submit { background-color: #3f51b5; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 1em; transition: background-color 0.3s ease; display: inline-block; margin-top: 10px; }
        .btn-submit:hover { background-color: #283593; }
        .section-title { color: #3f51b5; border-bottom: 2px solid #3f51b5; padding-bottom: 5px; margin-top: 30px; margin-bottom: 20px; }
        .modulo-item { background-color: #e9f2f9; border: 1px solid #cce5ff; border-radius: 8px; margin-bottom: 15px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .modulo-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .modulo-header h3 { margin: 0; color: #3f51b5; }
        .lecciones-list { list-style: none; padding: 0; margin-top: 15px; border-top: 1px dashed #cce5ff; padding-top: 15px; }
        .leccion-item { background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 6px; margin-bottom: 8px; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .leccion-info span { font-weight: 500; }
        .leccion-info small { color: #666; font-size: 0.85em; margin-left: 10px; }
        .leccion-actions a { padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.85em; transition: background-color 0.2s ease; }
        .leccion-actions .editar-leccion { background-color: #ffc107; color: #333; }
        .leccion-actions .editar-leccion:hover { background-color: #e0a800; }
        .leccion-actions .eliminar-leccion { background-color: #dc3545; color: white; }
        .leccion-actions .eliminar-leccion:hover { background-color: #c82333; }
        .no-content { text-align: center; color: #888; font-style: italic; padding: 20px; background-color: #fefefe; border: 1px dashed #e9ecef; border-radius: 8px; margin-top: 15px; }
        .btn-add-content { background-color: #388e3c; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 0.9em; transition: background-color 0.3s ease; display: inline-block; margin-left: 10px; }
        .btn-add-content:hover { background-color: #256029; }
    </style>
</head>
<body>
    <div class="contenedor">
        <h1>Editar Curso: <?php echo htmlspecialchars($curso['nombre_curso'] ?? 'Error'); ?></h1>
        <?php if ($mensaje_exito): ?>
            <div class="mensaje-exito"><?php echo $mensaje_exito; ?></div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="mensaje-error"><?php echo $mensaje_error; ?></div>
        <?php endif; ?>

        <?php if ($curso): ?>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_curso_editar" value="<?php echo htmlspecialchars($curso['id_curso']); ?>">
                <input type="hidden" name="imagen_portada_actual" value="<?php echo htmlspecialchars($curso['imagen_portada']); ?>">

                <div class="form-group">
                    <label for="nombre_curso">Nombre del Curso:</label>
                    <input type="text" id="nombre_curso" name="nombre_curso" value="<?php echo htmlspecialchars($curso['nombre_curso']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($curso['descripcion']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="nivel_dificultad">Nivel de Dificultad:</label>
                    <select id="nivel_dificultad" name="nivel_dificultad" required>
                        <option value="principiante" <?php echo ($curso['nivel_dificultad'] == 'principiante') ? 'selected' : ''; ?>>Principiante</option>
                        <option value="intermedio" <?php echo ($curso['nivel_dificultad'] == 'intermedio') ? 'selected' : ''; ?>>Intermedio</option>
                        <option value="avanzado" <?php echo ($curso['nivel_dificultad'] == 'avanzado') ? 'selected' : ''; ?>>Avanzado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <input type="text" id="categoria" name="categoria" value="<?php echo htmlspecialchars($curso['categoria']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="precio">Precio:</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?php echo htmlspecialchars($curso['precio']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="estado">Estado del Curso:</label>
                    <select id="estado" name="estado" required>
                        <option value="activo" <?php echo ($curso['estado'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo ($curso['estado'] == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                        <option value="en_edicion" <?php echo ($curso['estado'] == 'en_edicion') ? 'selected' : ''; ?>>En Edición</option>
                    </select>
                </div>
                <?php if ($rol_usuario === 'admin'): ?>
                <div class="form-group">
                    <label for="id_profesor_asignado">Profesor Asignado:</label>
                    <select id="id_profesor_asignado" name="id_profesor_asignado" required>
                        <?php if (empty($profesores_disponibles)): ?>
                            <option value="">No hay profesores disponibles</option>
                        <?php else: ?>
                            <?php foreach ($profesores_disponibles as $profesor): ?>
                                <option value="<?php echo htmlspecialchars($profesor['id_usuario']); ?>"
                                    <?php echo ($curso['id_profesor'] == $profesor['id_usuario']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Imagen de Portada Actual:</label>
                    <?php if (!empty($curso['imagen_portada']) && file_exists($directorio_imagenes . $curso['imagen_portada'])): ?>
                        <div class="current-image">
                            <img src="<?php echo RUTA_BASE . 'imagenes_cursos/' . htmlspecialchars($curso['imagen_portada']); ?>" alt="Portada del Curso">
                            <p><?php echo htmlspecialchars($curso['imagen_portada']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="imagen_portada">Cambiar Imagen de Portada:</label>
                    <input type="file" id="imagen_portada" name="imagen_portada" accept="image/*">
                </div>
                <button type="submit" class="btn-submit">Guardar Cambios</button>
            </form>

            <h2 class="section-title">Módulos del Curso</h2>
            <?php if (!empty($modulos)): ?>
                <?php foreach ($modulos as $modulo): ?>
                    <div class="modulo-item">
                        <div class="modulo-header">
                            <h3><?php echo htmlspecialchars($modulo['nombre_modulo']); ?></h3>
                            <a href="<?php echo RUTA_BASE; ?>paginas/cursos/editar_modulo.php?id_modulo=<?php echo $modulo['id_modulo']; ?>" class="btn-add-content">Editar Módulo</a>
                        </div>
                        <p><?php echo htmlspecialchars($modulo['descripcion_modulo']); ?></p>
                        <strong>Lecciones:</strong>
                        <?php if (!empty($modulo['lecciones'])): ?>
                            <ul class="lecciones-list">
                                <?php foreach ($modulo['lecciones'] as $leccion): ?>
                                    <li class="leccion-item">
                                        <div class="leccion-info">
                                            <span><?php echo htmlspecialchars($leccion['nombre_leccion']); ?></span>
                                            <small>(<?php echo htmlspecialchars($leccion['tipo']); ?>)</small>
                                        </div>
                                        <div class="leccion-actions">
                                            <a href="<?php echo RUTA_BASE; ?>paginas/cursos/editar_leccion.php?id_leccion=<?php echo $leccion['id_leccion']; ?>" class="editar-leccion">Editar</a>
                                            <a href="<?php echo RUTA_BASE; ?>paginas/cursos/eliminar_leccion.php?id_leccion=<?php echo $leccion['id_leccion']; ?>&id_modulo=<?php echo $modulo['id_modulo']; ?>&id_curso=<?php echo $curso['id_curso']; ?>" class="eliminar-leccion" onclick="return confirm('¿Seguro que deseas eliminar esta lección?');">Eliminar</a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="no-content">No hay lecciones en este módulo.</div>
                        <?php endif; ?>
                        <a href="<?php echo RUTA_BASE; ?>paginas/cursos/agregar_leccion.php?id_modulo=<?php echo $modulo['id_modulo']; ?>&id_curso=<?php echo $curso['id_curso']; ?>" class="btn-add-content">Agregar Lección</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-content">Este curso aún no tiene módulos.</div>
            <?php endif; ?>
            <a href="<?php echo RUTA_BASE; ?>paginas/cursos/agregar_modulo.php?id_curso=<?php echo $curso['id_curso']; ?>" class="btn-add-content">Agregar Módulo</a>
        <?php else: ?>
            <div class="mensaje-error">No se pudo cargar la información del curso para editar.</div>
        <?php endif; ?>
    </div>
</body>
</html>