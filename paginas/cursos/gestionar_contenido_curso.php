<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario está autenticado y tiene permisos de profesor o admin
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol_usuario'] !== 'profesor' && $_SESSION['rol_usuario'] !== 'admin')) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$rol_usuario = $_SESSION['rol_usuario'];
$id_usuario_sesion = $_SESSION['id_usuario'];

$curso = null;
$modulos = [];
$mensaje_exito = '';
$mensaje_error = '';

// Obtener el ID del curso desde la URL y validar
if (isset($_GET['id_curso']) && is_numeric($_GET['id_curso'])) {
    $id_curso = (int)$_GET['id_curso'];

    // Obtener detalles del curso y verificar permisos
    $sql_curso = "SELECT id_curso, nombre_curso, id_profesor FROM cursos WHERE id_curso = ?";
    $stmt_curso = $conexion->prepare($sql_curso);
    $stmt_curso->bind_param("i", $id_curso);
    $stmt_curso->execute();
    $resultado_curso = $stmt_curso->get_result();

    if ($resultado_curso->num_rows > 0) {
        $curso = $resultado_curso->fetch_assoc();

        // Solo el profesor dueño o el admin pueden gestionar el contenido
        if ($rol_usuario === 'profesor' && $curso['id_profesor'] != $id_usuario_sesion) {
            $_SESSION['mensaje_error'] = "No tienes permiso para gestionar el contenido de este curso.";
            header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php");
            exit();
        }

        // --- Añadir un módulo ---
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'agregar_modulo') {
            $nombre_modulo = trim($_POST['nombre_modulo'] ?? '');
            $descripcion_modulo = trim($_POST['descripcion_modulo'] ?? '');
            $orden_modulo = (int)($_POST['orden_modulo'] ?? 1);

            if (empty($nombre_modulo)) {
                $mensaje_error = "El nombre del módulo es obligatorio.";
            } else {
                $stmt_insert_modulo = $conexion->prepare("INSERT INTO modulos_curso (id_curso, nombre_modulo, descripcion_modulo, orden) VALUES (?, ?, ?, ?)");
                $stmt_insert_modulo->bind_param("issi", $id_curso, $nombre_modulo, $descripcion_modulo, $orden_modulo);
                if ($stmt_insert_modulo->execute()) {
                    $mensaje_exito = "Módulo añadido con éxito.";
                } else {
                    $mensaje_error = "Error al añadir módulo: " . $stmt_insert_modulo->error;
                }
                $stmt_insert_modulo->close();
            }
        }

        // --- Eliminar un módulo y sus lecciones ---
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'eliminar_modulo') {
            $id_modulo_a_eliminar = $_POST['id_modulo_a_eliminar'] ?? null;

            if ($id_modulo_a_eliminar && is_numeric($id_modulo_a_eliminar)) {
                $conexion->begin_transaction();
                try {
                    // Eliminar archivos asociados a las lecciones del módulo
                    $stmt_get_leccion_files = $conexion->prepare("SELECT url_recurso FROM lecciones WHERE id_modulo = ?");
                    $stmt_get_leccion_files->bind_param("i", $id_modulo_a_eliminar);
                    $stmt_get_leccion_files->execute();
                    $resultado_files = $stmt_get_leccion_files->get_result();
                    $files_to_delete = [];
                    while ($row = $resultado_files->fetch_assoc()) {
                        if (!empty($row['url_recurso']) && strpos($row['url_recurso'], RUTA_BASE . "uploads/") === 0) {
                            $files_to_delete[] = str_replace(RUTA_BASE, '../../', $row['url_recurso']);
                        }
                    }
                    $stmt_get_leccion_files->close();

                    // Eliminar lecciones del módulo
                    $stmt_del_lecciones = $conexion->prepare("DELETE FROM lecciones WHERE id_modulo = ?");
                    $stmt_del_lecciones->bind_param("i", $id_modulo_a_eliminar);
                    $stmt_del_lecciones->execute();
                    $stmt_del_lecciones->close();

                    // Eliminar el módulo
                    $stmt_del_modulo = $conexion->prepare("DELETE FROM modulos_curso WHERE id_modulo = ? AND id_curso = ?");
                    $stmt_del_modulo->bind_param("ii", $id_modulo_a_eliminar, $id_curso);
                    if ($stmt_del_modulo->execute() && $stmt_del_modulo->affected_rows > 0) {
                        $conexion->commit();
                        // Eliminar archivos físicos
                        foreach ($files_to_delete as $file_path) {
                            if (file_exists($file_path)) {
                                unlink($file_path);
                            }
                        }
                        $mensaje_exito = "Módulo y sus lecciones eliminados con éxito.";
                    } else {
                        $conexion->rollback();
                        $mensaje_error = "Módulo no encontrado o no pertenece a este curso.";
                    }
                    $stmt_del_modulo->close();
                } catch (Exception $e) {
                    $conexion->rollback();
                    $mensaje_error = "Error al eliminar módulo: " . $e->getMessage();
                }
            } else {
                $mensaje_error = "ID de módulo no especificado o inválido para eliminar.";
            }
        }

        // --- Añadir una lección ---
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'agregar_leccion') {
            $id_modulo_leccion = (int)($_POST['id_modulo_leccion'] ?? 0);
            $nombre_leccion = trim($_POST['nombre_leccion'] ?? '');
            $tipo_leccion = trim($_POST['tipo'] ?? '');
            $contenido_texto = trim($_POST['contenido_texto'] ?? '');
            $url_recurso = trim($_POST['url_recurso'] ?? '');
            $orden_leccion = (int)($_POST['orden_leccion'] ?? 1);

            if (empty($nombre_leccion) || empty($tipo_leccion)) {
                $mensaje_error = "El nombre y tipo de la lección son obligatorios.";
            } else {
                $ruta_archivo_subido = null;
                // Subida de archivo si corresponde
                if (isset($_FILES['archivo_leccion']) && $_FILES['archivo_leccion']['error'] == UPLOAD_ERR_OK) {
                    $directorio_uploads = "../../uploads/";
                    $directorio_curso = $directorio_uploads . $id_curso . '/';
                    if (!is_dir($directorio_curso)) {
                        mkdir($directorio_curso, 0777, true);
                    }

                    $nombre_archivo = basename($_FILES["archivo_leccion"]["name"]);
                    $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
                    $nombre_base = pathinfo($nombre_archivo, PATHINFO_FILENAME);
                    $nombre_unico = $nombre_base . '_' . uniqid() . '.' . $extension;

                    $ruta_destino = $directorio_curso . $nombre_unico;
                    $tipo_archivo = strtolower($extension);

                    $tipos_permitidos = array("jpg", "png", "jpeg", "gif", "pdf", "mp4", "webm", "ogg", "mp3");
                    if (!in_array($tipo_archivo, $tipos_permitidos)) {
                        $mensaje_error = "Solo se permiten archivos JPG, JPEG, PNG, GIF, PDF, MP4, WEBM, OGG, MP3.";
                    } elseif ($_FILES["archivo_leccion"]["size"] > 50000000) {
                        $mensaje_error = "El archivo es demasiado grande (máx. 50MB).";
                    } elseif (move_uploaded_file($_FILES["archivo_leccion"]["tmp_name"], $ruta_destino)) {
                        $ruta_archivo_subido = RUTA_BASE . "uploads/" . $id_curso . '/' . $nombre_unico;
                    } else {
                        $mensaje_error = "Error al subir el archivo.";
                    }
                }

                $final_url_recurso = $ruta_archivo_subido ?: (!empty($url_recurso) ? $url_recurso : null);

                if (!$mensaje_error) {
                    $stmt_insert_leccion = $conexion->prepare("INSERT INTO lecciones (id_modulo, nombre_leccion, tipo, contenido_texto, url_recurso, orden) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_insert_leccion->bind_param("issssi", $id_modulo_leccion, $nombre_leccion, $tipo_leccion, $contenido_texto, $final_url_recurso, $orden_leccion);
                    if ($stmt_insert_leccion->execute()) {
                        $mensaje_exito = "Lección añadida con éxito.";
                    } else {
                        $mensaje_error = "Error al añadir lección: " . $stmt_insert_leccion->error;
                    }
                    $stmt_insert_leccion->close();
                }
            }
        }

        // --- Eliminar una lección ---
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'eliminar_leccion') {
            $id_leccion_a_eliminar = $_POST['id_leccion_a_eliminar'] ?? null;

            if ($id_leccion_a_eliminar && is_numeric($id_leccion_a_eliminar)) {
                $stmt_check_leccion = $conexion->prepare("SELECT l.url_recurso FROM lecciones l JOIN modulos_curso mc ON l.id_modulo = mc.id_modulo WHERE l.id_leccion = ? AND mc.id_curso = ?");
                $stmt_check_leccion->bind_param("ii", $id_leccion_a_eliminar, $id_curso);
                $stmt_check_leccion->execute();
                $stmt_check_leccion->bind_result($url_recurso_a_eliminar);
                $stmt_check_leccion->fetch();
                $stmt_check_leccion->close();

                if ($url_recurso_a_eliminar !== null) {
                    if (strpos($url_recurso_a_eliminar, RUTA_BASE . "uploads/") === 0) {
                        $ruta_fisica_archivo = str_replace(RUTA_BASE, '../../', $url_recurso_a_eliminar);
                        if (file_exists($ruta_fisica_archivo)) {
                            unlink($ruta_fisica_archivo);
                        }
                    }

                    $stmt_delete_leccion = $conexion->prepare("DELETE FROM lecciones WHERE id_leccion = ?");
                    $stmt_delete_leccion->bind_param("i", $id_leccion_a_eliminar);
                    if ($stmt_delete_leccion->execute()) {
                        $mensaje_exito = "Lección eliminada con éxito.";
                    } else {
                        $mensaje_error = "Error al eliminar lección: " . $stmt_delete_leccion->error;
                    }
                    $stmt_delete_leccion->close();
                } else {
                    $mensaje_error = "Lección no encontrada o no pertenece a este curso.";
                }
            } else {
                $mensaje_error = "ID de lección no especificado o inválido para eliminar.";
            }
        }

        // --- Obtener módulos y lecciones ---
        $sql_modulos = "SELECT id_modulo, nombre_modulo, descripcion_modulo, orden FROM modulos_curso WHERE id_curso = ? ORDER BY orden ASC";
        $stmt_modulos = $conexion->prepare($sql_modulos);
        $stmt_modulos->bind_param("i", $id_curso);
        $stmt_modulos->execute();
        $resultado_modulos = $stmt_modulos->get_result();

        $modulos = [];
        while ($modulo = $resultado_modulos->fetch_assoc()) {
            $sql_lecciones = "SELECT id_leccion, nombre_leccion, tipo, contenido_texto, url_recurso, orden FROM lecciones WHERE id_modulo = ? ORDER BY orden ASC";
            $stmt_lecciones = $conexion->prepare($sql_lecciones);
            $stmt_lecciones->bind_param("i", $modulo['id_modulo']);
            $stmt_lecciones->execute();
            $resultado_lecciones = $stmt_lecciones->get_result();
            $modulo['lecciones'] = [];
            while ($leccion = $resultado_lecciones->fetch_assoc()) {
                $modulo['lecciones'][] = $leccion;
            }
            $stmt_lecciones->close();
            $modulos[] = $modulo;
        }
        $stmt_modulos->close();

    } else {
        $mensaje_error = "Curso no encontrado.";
    }
    $stmt_curso->close();

} else {
    $mensaje_error = "ID de curso no especificado o inválido.";
}
?>

<?php
if (isset($conexion) && $conexion instanceof mysqli) {
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Contenido del Curso</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Arial, sans-serif; color: #222; margin: 0; padding: 0; }
        .contenedor { max-width: 900px; margin: 32px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 32px 24px; }
        h1 { color: #3f51b5; margin-bottom: 24px; text-align: center; }
        .mensaje-exito, .mensaje-error { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 1em; font-weight: bold; text-align: center; }
        .mensaje-exito { background: #e8f5e9; color: #388e3c; border: 1px solid #c8e6c9; }
        .mensaje-error { background: #fff3f3; color: #d32f2f; border: 1px solid #f8d7da; }
        .modulo-card {
            background: #f1f8e9;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(60,60,60,0.07);
            margin-bottom: 22px;
            padding: 20px 18px;
            border-left: 6px solid #388e3c;
            transition: box-shadow 0.2s;
        }
        .modulo-card:hover {
            box-shadow: 0 4px 16px rgba(60,60,60,0.13);
        }
        .modulo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .modulo-header h3 {
            margin: 0;
            color: #388e3c;
            font-size: 1.25em;
        }
        .modulo-desc {
            color: #555;
            margin-bottom: 10px;
        }
        .leccion-list {
            list-style: none;
            padding: 0;
            margin: 0 0 10px 0;
        }
        .leccion-item {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 7px;
            margin-bottom: 8px;
            padding: 10px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .leccion-info {
            display: flex;
            flex-direction: column;
        }
        .leccion-nombre {
            font-weight: bold;
            color: #3f51b5;
        }
        .leccion-tipo {
            font-size: 0.95em;
            color: #666;
        }
        .leccion-actions button {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 4px 10px;
            cursor: pointer;
            font-size: 0.95em;
            margin-left: 8px;
        }
        .leccion-actions button:hover {
            background: #b71c1c;
        }
        .form-agregar-leccion, .form-agregar-modulo {
            background: #f9fbe7;
            border-radius: 8px;
            padding: 12px 14px;
            margin-top: 12px;
            margin-bottom: 10px;
            border: 1px solid #e0e0e0;
        }
        .form-agregar-leccion label, .form-agregar-modulo label {
            font-weight: 500;
            color: #333;
        }
        .form-agregar-leccion input, .form-agregar-leccion select,
        .form-agregar-modulo input {
            margin-bottom: 7px;
            padding: 5px 7px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 100%;
            font-size: 1em;
        }
        .form-agregar-leccion button, .form-agregar-modulo button {
            background: #388e3c;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 7px 16px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 5px;
        }
        .form-agregar-leccion button:hover, .form-agregar-modulo button:hover {
            background: #256029;
        }
        .eliminar-modulo-btn {
            background: #ff7043;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 5px 12px;
            cursor: pointer;
            font-size: 0.95em;
            margin-left: 10px;
        }
        .eliminar-modulo-btn:hover {
            background: #bf360c;
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <h1>Gestionar Contenido del Curso</h1>
        <?php if ($mensaje_exito): ?>
            <div class="mensaje-exito"><?php echo htmlspecialchars($mensaje_exito); ?></div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($mensaje_error); ?></div>
        <?php endif; ?>

        <?php if ($curso): ?>
            <h2><?php echo htmlspecialchars($curso['nombre_curso']); ?></h2>

            <!-- Formulario para agregar un nuevo módulo -->
            <h3>Agregar Módulo</h3>
            <form method="POST" class="form-agregar-modulo" style="margin-bottom: 30px;">
                <input type="hidden" name="accion" value="agregar_modulo">
                <div>
                    <label>Nombre del módulo:</label>
                    <input type="text" name="nombre_modulo" required>
                </div>
                <div>
                    <label>Descripción:</label>
                    <input type="text" name="descripcion_modulo">
                </div>
                <div>
                    <label>Orden:</label>
                    <input type="number" name="orden_modulo" value="1" min="1" style="width: 60px;">
                </div>
                <button type="submit">Agregar Módulo</button>
            </form>

            <!-- Mostrar módulos y sus lecciones -->
            <?php if (!empty($modulos)): ?>
                <?php foreach ($modulos as $modulo): ?>
                    <div class="modulo-card">
                        <div class="modulo-header">
                            <h3><?php echo htmlspecialchars($modulo['nombre_modulo']); ?></h3>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="accion" value="eliminar_modulo">
                                <input type="hidden" name="id_modulo_a_eliminar" value="<?php echo $modulo['id_modulo']; ?>">
                                <button type="submit" class="eliminar-modulo-btn" onclick="return confirm('¿Eliminar este módulo y todas sus lecciones?');">Eliminar módulo</button>
                            </form>
                        </div>
                        <div class="modulo-desc"><?php echo htmlspecialchars($modulo['descripcion_modulo']); ?></div>
                        <h4>Lecciones</h4>
                        <?php if (!empty($modulo['lecciones'])): ?>
                            <ul class="leccion-list">
                                <?php foreach ($modulo['lecciones'] as $leccion): ?>
                                    <?php if (!empty($leccion['nombre_leccion']) && !empty($leccion['tipo'])): // Solo mostrar lecciones válidas ?>
                                        <li class="leccion-item">
                                            <div class="leccion-info">
                                                <span class="leccion-nombre"><?php echo htmlspecialchars($leccion['nombre_leccion']); ?></span>
                                                <span class="leccion-tipo">(<?php echo htmlspecialchars($leccion['tipo']); ?>)</span>
                                            </div>
                                            <div class="leccion-actions">
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="accion" value="eliminar_leccion">
                                                    <input type="hidden" name="id_leccion_a_eliminar" value="<?php echo $leccion['id_leccion']; ?>">
                                                    <button type="submit" onclick="return confirm('¿Eliminar esta lección?');">Eliminar</button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <em style="color:#888;">No hay lecciones en este módulo.</em>
                            <?php endif; ?>

                        <!-- Formulario para agregar lección al módulo -->
                        <form method="POST" enctype="multipart/form-data" class="form-agregar-leccion">
                            <input type="hidden" name="accion" value="agregar_leccion">
                            <input type="hidden" name="id_modulo_leccion" value="<?php echo $modulo['id_modulo']; ?>">
                            <div>
                                <label>Nombre de la lección:</label>
                                <input type="text" name="nombre_leccion" required>
                            </div>
                            <div>
                                <label>Tipo:</label>
                                <select name="tipo" required>
                                    <option value="video">Video</option>
                                    <option value="documento">Documento</option>
                                    <option value="texto">Texto</option>
                                    <option value="audio">Audio</option>
                                </select>
                            </div>
                            <div>
                                <label>Contenido (texto):</label>
                                <input type="text" name="contenido_texto">
                            </div>
                            <div>
                                <label>URL recurso externo:</label>
                                <input type="text" name="url_recurso">
                            </div>
                            <div>
                                <label>Archivo (opcional):</label>
                                <input type="file" name="archivo_leccion">
                            </div>
                            <div>
                                <label>Orden:</label>
                                <input type="number" name="orden_leccion" value="1" min="1" style="width: 60px;">
                            </div>
                            <button type="submit">Agregar Lección</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="margin:30px 0; color:#888;">No hay módulos en este curso.</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="mensaje-error">No se encontró el curso o no tienes permisos.</div>
        <?php endif; ?>
    </div>
</body>
</html>