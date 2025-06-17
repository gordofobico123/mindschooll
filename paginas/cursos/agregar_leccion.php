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

$id_modulo = $_GET['id_modulo'] ?? null;
$modulo_existente = null;
$curso_existente = null;
$mensaje_exito = '';
$mensaje_error = '';

// Ruta donde se guardarán los archivos de las lecciones
// Asegúrate de que esta ruta sea correcta y que la carpeta tenga permisos de escritura.
// Si esta en el mismo nivel que paginas/, la ruta relativa es '../recursos_lecciones/'
// Pero es mejor usar una ruta absoluta o una relativa al DOCUMENT_ROOT
$upload_dir = realpath(__DIR__ . '/../../recursos_lecciones/') . DIRECTORY_SEPARATOR;

// Verificar que se haya proporcionado un ID de módulo válido
if (!$id_modulo || !is_numeric($id_modulo)) {
    $_SESSION['mensaje_error'] = "ID de módulo no válido para añadir lección.";
    header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php");
    exit();
}

// Obtener detalles del módulo y su curso asociado para verificar permisos
$stmt_modulo = $conexion->prepare("SELECT m.id_modulo, m.titulo AS titulo_modulo, m.id_curso,
                                         c.nombre_curso, c.id_profesor
                                  FROM modulos m
                                  JOIN cursos c ON m.id_curso = c.id_curso
                                  WHERE m.id_modulo = ?");
$stmt_modulo->bind_param("i", $id_modulo);
$stmt_modulo->execute();
$resultado_modulo = $stmt_modulo->get_result();

if ($resultado_modulo->num_rows === 1) {
    $modulo_existente = $resultado_modulo->fetch_assoc();
    $curso_existente = [
        'id_curso' => $modulo_existente['id_curso'],
        'nombre_curso' => $modulo_existente['nombre_curso'],
        'id_profesor' => $modulo_existente['id_profesor']
    ];

    // Verificar permisos: solo admin o el profesor asignado al curso del módulo
    if ($rol_usuario !== 'admin' && ($rol_usuario === 'profesor' && $curso_existente['id_profesor'] !== $id_usuario_sesion)) {
        $_SESSION['mensaje_error'] = "No tienes permiso para añadir lecciones a este módulo.";
        header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php");
        exit();
    }
} else {
    $_SESSION['mensaje_error'] = "Módulo no encontrado para añadir lección.";
    header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php");
    exit();
}
$stmt_modulo->close();

// Lógica para manejar el envío del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo_leccion = trim($_POST['titulo']);
    $contenido_leccion = trim($_POST['contenido']);
    $tipo_leccion = $_POST['tipo'];
    $duracion_minutos = filter_var($_POST['duracion_minutos'], FILTER_VALIDATE_INT);
    $orden_leccion = filter_var($_POST['orden'], FILTER_VALIDATE_INT);

    $url_recurso = ''; // Inicializar la URL del recurso

    // Lógica para subir el archivo si se seleccionó uno
    if (isset($_FILES['archivo_recurso']) && $_FILES['archivo_recurso']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['archivo_recurso']['tmp_name'];
        $file_name = basename($_FILES['archivo_recurso']['name']);
        $file_size = $_FILES['archivo_recurso']['size'];
        $file_type = $_FILES['archivo_recurso']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_file_types = [];
        if ($tipo_leccion === 'video') {
            $allowed_file_types = ['mp4', 'avi', 'mov', 'wmv', 'flv'];
        } elseif ($tipo_leccion === 'documento') {
            $allowed_file_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];
        }
        // Para 'texto' o 'quiz', no se espera subida de archivo normalmente
        // Si se permite, se podría ampliar la lista o no restringir para esos tipos.

        if (!empty($allowed_file_types) && !in_array($file_ext, $allowed_file_types)) {
            $mensaje_error = "Tipo de archivo no permitido para el tipo de lección seleccionado. Tipos permitidos para '$tipo_leccion': " . implode(', ', $allowed_file_types);
        } elseif ($file_size > 50000000) { // Límite de 50MB (ajustable)
            $mensaje_error = "El archivo es demasiado grande (máx. 50MB).";
        } else {
            // Generar un nombre de archivo único para evitar colisiones
            $new_file_name = uniqid('leccion_') . '.' . $file_ext;
            $destination_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $destination_path)) {
                // Guardar la ruta relativa o el nombre del archivo en la DB
                // Para acceso web, necesitamos la ruta relativa al DOCUMENT_ROOT
                $url_recurso = RUTA_BASE . 'recursos_lecciones/' . $new_file_name;
            } else {
                $mensaje_error = "Error al subir el archivo. Código de error: " . $_FILES['archivo_recurso']['error'];
            }
        }
    } elseif (isset($_POST['url_recurso_manual']) && !empty(trim($_POST['url_recurso_manual']))) {
        // Si no se subió un archivo, pero se proporcionó una URL manual
        $url_recurso = trim($_POST['url_recurso_manual']);
    }

    if (empty($titulo_leccion) || empty($contenido_leccion) || empty($tipo_leccion) || 
        $duracion_minutos === false || $duracion_minutos < 0 || 
        $orden_leccion === false || $orden_leccion < 1) {
        $mensaje_error = "Todos los campos obligatorios (título, contenido, tipo, duración y orden) deben ser rellenados correctamente.";
    } elseif (empty($url_recurso) && ($tipo_leccion === 'video' || $tipo_leccion === 'documento')) {
        $mensaje_error = "Para el tipo de lección 'Video' o 'Documento', se requiere un archivo subido o una URL de recurso.";
    } else {
        // Insertar la nueva lección
        $stmt_insert = $conexion->prepare("INSERT INTO lecciones (id_modulo, titulo, contenido, tipo, duracion_minutos, url_recurso, `orden`, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt_insert->bind_param("isssisi", $id_modulo, $titulo_leccion, $contenido_leccion, $tipo_leccion, $duracion_minutos, $url_recurso, $orden_leccion);

        if ($stmt_insert->execute()) {
            $_SESSION['mensaje_exito'] = "Lección '{$titulo_leccion}' añadida con éxito al módulo '{$modulo_existente['titulo_modulo']}'.";
            header("Location: " . RUTA_BASE . "paginas/cursos/editar_curso.php?id=" . $curso_existente['id_curso']);
            exit();
        } else {
            $mensaje_error = "Error al añadir la lección: " . $stmt_insert->error;
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
    <title>Añadir Lección a Módulo - MindSchool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f7f6; color: #333; }
        .container { max-width: 700px; margin: 0 auto; padding: 25px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #0056b3; margin-bottom: 30px; }
        h2 { text-align: center; color: #0056b3; margin-bottom: 20px; font-size: 1.5em; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group input[type="number"],
        .form-group select,
        .form-group input[type="file"] { /* Añadir estilo para input file */
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
        .resource-options {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .resource-options label {
            display: inline-block;
            margin-right: 15px;
        }
        .resource-options input[type="radio"] {
            margin-right: 5px;
        }
        .resource-field {
            margin-top: 10px;
            display: none; /* Hidden by default */
        }
        .resource-field.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navegacion">
            <?php if ($curso_existente): ?>
                <a href="<?php echo RUTA_BASE; ?>paginas/cursos/editar_curso.php?id=<?php echo $curso_existente['id_curso']; ?>">Volver a Editar Curso</a>
            <?php endif; ?>
            <a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php">Volver a Cursos</a>
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">Panel de Control</a>
            <a href="<?php echo RUTA_BASE; ?>cerrar_sesion.php">Cerrar Sesión</a>
        </div>

        <h1>Añadir Nueva Lección</h1>
        <?php if ($modulo_existente): ?>
            <h2>Para el Módulo: "<?php echo htmlspecialchars($modulo_existente['titulo_modulo']); ?>" del Curso: "<?php echo htmlspecialchars($curso_existente['nombre_curso']); ?>"</h2>
        <?php endif; ?>

        <?php
        if ($mensaje_exito) {
            echo "<p class='mensaje-exito'>" . $mensaje_exito . "</p>";
        }
        if ($mensaje_error) {
            echo "<p class='mensaje-error'>" . $mensaje_error . "</p>";
        }
        ?>

        <form action="<?php echo RUTA_BASE; ?>paginas/cursos/agregar_leccion.php?id_modulo=<?php echo $id_modulo; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="titulo">Título de la Lección:</label>
                <input type="text" id="titulo" name="titulo" required>
            </div>
            <div class="form-group">
                <label for="contenido">Contenido de la Lección:</label>
                <textarea id="contenido" name="contenido" required></textarea>
                <small>Ej. Resumen, texto explicativo, etc.</small>
            </div>
            <div class="form-group">
                <label for="tipo">Tipo de Lección:</label>
                <select id="tipo" name="tipo" required onchange="toggleResourceFields()">
                    <option value="video">Video</option>
                    <option value="texto">Texto</option>
                    <option value="documento">Documento</option>
                    <option value="quiz">Quiz</option>
                </select>
            </div>

            <div class="form-group resource-options">
                <label>Seleccionar Recurso:</label><br>
                <input type="radio" id="recurso_upload" name="recurso_option" value="upload" checked onclick="toggleResourceFields()"> <label for="recurso_upload">Subir Archivo</label>
                <input type="radio" id="recurso_url" name="recurso_option" value="url" onclick="toggleResourceFields()"> <label for="recurso_url">Usar URL Externa</label>
            </div>

            <div class="form-group resource-field" id="upload_field">
                <label for="archivo_recurso">Subir Archivo de Recurso:</label>
                <input type="file" id="archivo_recurso" name="archivo_recurso">
                <small>Formatos permitidos: Video (mp4, avi, mov, etc.), Documento (pdf, doc, ppt, xls, etc.). Máx. 50MB.</small>
            </div>

            <div class="form-group resource-field" id="url_field" style="display: none;">
                <label for="url_recurso_manual">URL del Recurso Externo:</label>
                <input type="text" id="url_recurso_manual" name="url_recurso_manual" placeholder="Ej: https://www.youtube.com/watch?v=video_id">
                <small>Enlace a video (YouTube, Vimeo), o cualquier otro recurso en línea.</small>
            </div>
            
            <div class="form-group">
                <label for="duracion_minutos">Duración (minutos):</label>
                <input type="number" id="duracion_minutos" name="duracion_minutos" min="0" value="0" required>
                <small>Tiempo estimado para completar esta lección.</small>
            </div>
            <div class="form-group">
                <label for="orden">Orden de la Lección:</label>
                <input type="number" id="orden" name="orden" min="1" value="1" required>
                <small>Define la posición de la lección dentro del módulo (ej. 1, 2, 3...).</small>
            </div>
            <button type="submit" class="btn-submit">Añadir Lección</button>
        </form>
    </div>

    <script>
        function toggleResourceFields() {
            const tipoLeccion = document.getElementById('tipo').value;
            const uploadOption = document.getElementById('recurso_upload');
            const urlOption = document.getElementById('recurso_url');
            const uploadField = document.getElementById('upload_field');
            const urlField = document.getElementById('url_field');
            const archivoRecursoInput = document.getElementById('archivo_recurso');
            const urlRecursoManualInput = document.getElementById('url_recurso_manual');

            // Mostrar/ocultar opciones de subida/URL según el tipo de lección
            if (tipoLeccion === 'texto' || tipoLeccion === 'quiz') {
                // Para texto y quiz, ocultar ambas opciones de recurso y desmarcar radios
                uploadOption.closest('.resource-options').style.display = 'none';
                uploadField.classList.remove('active');
                urlField.classList.remove('active');
                archivoRecursoInput.removeAttribute('required');
                urlRecursoManualInput.removeAttribute('required');
                archivoRecursoInput.value = ''; // Limpiar el campo de archivo
                urlRecursoManualInput.value = ''; // Limpiar el campo de URL
            } else {
                // Para video o documento, mostrar las opciones de recurso
                uploadOption.closest('.resource-options').style.display = 'block';

                // Determinar qué campo de recurso mostrar basado en la opción seleccionada
                if (uploadOption.checked) {
                    uploadField.classList.add('active');
                    urlField.classList.remove('active');
                    archivoRecursoInput.setAttribute('required', 'required'); // Hacerlo requerido
                    urlRecursoManualInput.removeAttribute('required');
                    urlRecursoManualInput.value = ''; // Limpiar el campo de URL
                } else if (urlOption.checked) {
                    urlField.classList.add('active');
                    uploadField.classList.remove('active');
                    urlRecursoManualInput.setAttribute('required', 'required'); // Hacerlo requerido
                    archivoRecursoInput.removeAttribute('required');
                    archivoRecursoInput.value = ''; // Limpiar el campo de archivo
                }
            }
        }

        // Ejecutar al cargar la página para establecer el estado inicial
        document.addEventListener('DOMContentLoaded', toggleResourceFields);
    </script>
</body>
</html>