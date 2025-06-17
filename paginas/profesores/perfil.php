<?php
// Iniciar sesión y cargar archivos de configuración y base de datos
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar autenticación y permisos (solo profesor)
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'profesor') {
    $_SESSION['mensaje_error'] = "Acceso denegado. Solo los profesores pueden acceder a su perfil.";
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

$id_profesor = $_SESSION['id_usuario'];
$mensaje_exito = '';
$mensaje_error = '';

// Directorio donde se guardarán las fotos de perfil
$upload_dir = '../../public/img/perfiles/'; // Asegúrate de que esta carpeta exista y tenga permisos de escritura

// Procesar formulario de actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $especialidad = trim($_POST['especialidad'] ?? '');
    $biografia = trim($_POST['biografia'] ?? '');
    $titulo_academico = trim($_POST['titulo_academico'] ?? '');
    $anios_experiencia = intval($_POST['anios_experiencia'] ?? 0);
    $foto_perfil_db = $_POST['foto_perfil_actual'] ?? 'default.jpg'; // Valor actual de la foto desde el campo oculto

    // Validación básica
    if ($especialidad === '' || $biografia === '' || $titulo_academico === '' || $anios_experiencia < 0) {
        $mensaje_error = "Todos los campos son obligatorios y los años de experiencia deben ser positivos.";
    } else {
        // Manejo de la subida de la foto de perfil
        // Solo procesar si se seleccionó un archivo y no hay error UPLOAD_ERR_NO_FILE
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['foto_perfil']['tmp_name'];
                $file_name = basename($_FILES['foto_perfil']['name']);
                $file_size = $_FILES['foto_perfil']['size'];
                $file_type = $_FILES['foto_perfil']['type'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                $extensions = array("jpeg", "jpg", "png", "gif");

                if (in_array($file_ext, $extensions) === false) {
                    $mensaje_error = "Error de archivo: Extensión no permitida, por favor elige un archivo JPEG, JPG, PNG o GIF. (Extensión: " . htmlspecialchars($file_ext) . ")";
                } elseif ($file_size > 2097152) { // 2MB
                    $mensaje_error = "Error de archivo: El tamaño del archivo debe ser menor a 2 MB.";
                } else {
                    $new_file_name = uniqid('profile_') . '.' . $file_ext;
                    $upload_path = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        // Eliminar la foto anterior si no es la por defecto y si existe
                        if ($foto_perfil_db !== 'default.jpg' && !empty($foto_perfil_db) && file_exists($upload_dir . $foto_perfil_db)) {
                            // Intentar eliminar la foto anterior
                            if (!unlink($upload_dir . $foto_perfil_db)) {
                                // Si no se puede eliminar, registrar el error pero no detener el proceso
                                error_log("Error al eliminar la foto de perfil anterior: " . $upload_dir . $foto_perfil_db);
                            }
                        }
                        $foto_perfil_db = $new_file_name; // Actualizar el nombre de la foto para la BD
                    } else {
                        $mensaje_error = "Error al mover la imagen subida. Revisa los permisos de la carpeta `public/img/perfiles/`.";
                        // Registrar el código de error de PHP para depuración
                        error_log("Error move_uploaded_file: " . $_FILES['foto_perfil']['error'] . " | Destino: " . $upload_path);
                    }
                }
            } else {
                // Manejar errores específicos de subida de PHP
                switch ($_FILES['foto_perfil']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $mensaje_error = "Error de subida: El archivo excede el tamaño máximo permitido por el servidor (revisa `upload_max_filesize` en `php.ini`).";
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $mensaje_error = "Error de subida: El archivo excede el tamaño máximo especificado en el formulario.";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $mensaje_error = "Error de subida: El archivo fue subido solo parcialmente.";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $mensaje_error = "Error de subida: Falta una carpeta temporal para la subida.";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $mensaje_error = "Error de subida: Fallo al escribir el archivo en el disco. Revisa los permisos de la carpeta temporal de PHP y la de destino.";
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $mensaje_error = "Error de subida: Una extensión de PHP detuvo la subida del archivo.";
                        break;
                    default:
                        $mensaje_error = "Error de subida desconocido: Código " . $_FILES['foto_perfil']['error'];
                        break;
                }
            }
        }

        // Si no hay errores después de la validación de la imagen o si no se subió una nueva imagen
        if (empty($mensaje_error)) {
            $sql = "UPDATE profesores SET especialidad = ?, biografia = ?, titulo_academico = ?, anios_experiencia = ?, foto_perfil = ? WHERE id_profesor = ?";
            $stmt = $conexion->prepare($sql);
            if ($stmt === false) {
                $mensaje_error = "Error al preparar la consulta de actualización: " . $conexion->error;
            } else {
                $stmt->bind_param("sssiis", $especialidad, $biografia, $titulo_academico, $anios_experiencia, $foto_perfil_db, $id_profesor);
                if ($stmt->execute()) {
                    // Usamos una redirección para evitar reenvío de formulario y para mostrar el mensaje de éxito una vez
                    $_SESSION['mensaje_exito'] = "Perfil actualizado correctamente.";
                    header("Location: " . RUTA_BASE . "paginas/profesores/perfil.php");
                    exit();
                } else {
                    $mensaje_error = "Error al actualizar el perfil en la base de datos: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Obtener datos actuales del profesor
$sql = "SELECT p.especialidad, p.biografia, p.titulo_academico, p.anios_experiencia, p.foto_perfil, u.nombre, u.apellido
        FROM profesores p
        JOIN usuarios u ON p.id_profesor = u.id_usuario
        WHERE p.id_profesor = ?";
$stmt = $conexion->prepare($sql);
if ($stmt === false) {
    die("Error al preparar la consulta de obtención de perfil: " . $conexion->error);
}
$stmt->bind_param("i", $id_profesor);
$stmt->execute();
$resultado = $stmt->get_result();
$profesor = $resultado->fetch_assoc();
$stmt->close();
$conexion->close(); // Cerrar la conexión después de obtener los datos

if (!$profesor) {
    $_SESSION['mensaje_error'] = "No se encontraron datos de perfil.";
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

$especialidad = $profesor['especialidad'] ?? '';
$biografia = $profesor['biografia'] ?? '';
$titulo_academico = $profesor['titulo_academico'] ?? '';
$anios_experiencia = $profesor['anios_experiencia'] ?? '';
$foto_perfil = $profesor['foto_perfil'] ?? 'default.jpg';
$nombre_profesor = $profesor['nombre'] ?? '';
$apellido_profesor = $profesor['apellido'] ?? '';

// Construir la ruta completa de la foto de perfil para la previsualización
$ruta_foto_perfil = RUTA_BASE . 'public/img/perfiles/' . htmlspecialchars($foto_perfil);

// Obtener mensajes de sesión si existen
if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']);
}
if (isset($_SESSION['mensaje_error'])) {
    $mensaje_error = $_SESSION['mensaje_error'];
    unset($_SESSION['mensaje_error']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - MindSchool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?php echo RUTA_BASE; ?>public/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .perfil-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 600px;
            box-sizing: border-box;
            text-align: center;
        }
        .perfil-container h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 2em;
            font-weight: 600;
        }
        .perfil-container .profile-pic-section {
            margin-bottom: 30px;
        }
        .perfil-container .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #4CAF50;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }
        .perfil-container .input-file-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .perfil-container .input-file-label {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: bold;
        }
        .perfil-container .input-file-label:hover {
            background-color: #0056b3;
        }
        .perfil-container input[type="file"] {
            display: none;
        }
        .perfil-container .file-name-display {
            font-size: 0.9em;
            color: #555;
            font-style: italic;
        }
        .perfil-container form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .perfil-container label {
            text-align: left;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        .perfil-container input[type="text"],
        .perfil-container input[type="number"],
        .perfil-container textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        .perfil-container input[type="text"]:focus,
        .perfil-container input[type="number"]:focus,
        .perfil-container textarea:focus {
            border-color: #007bff;
            outline: none;
        }
        .perfil-container textarea {
            resize: vertical;
            min-height: 80px;
        }
        .perfil-container button {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 20px;
        }
        .perfil-container button:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        .perfil-container .mensaje-exito {
            color: green;
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .perfil-container .mensaje-error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="perfil-container">
        <h2>Mi Perfil</h2>
        <?php if ($mensaje_exito): ?>
            <div class="mensaje-exito"><?php echo $mensaje_exito; ?></div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="mensaje-error"><?php echo $mensaje_error; ?></div>
        <?php endif; ?>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="profile-pic-section">
                <img id="profile-pic-preview" src="<?php echo htmlspecialchars($ruta_foto_perfil); ?>" alt="Foto de Perfil" class="profile-pic">
                <div class="input-file-group">
                    <label for="foto_perfil" class="input-file-label">
                        <i class="fas fa-upload"></i> Cambiar Foto
                    </label>
                    <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
                    <span id="file-name-display" class="file-name-display"></span>
                </div>
                <input type="hidden" name="foto_perfil_actual" value="<?php echo htmlspecialchars($foto_perfil); ?>">
            </div>
            <label for="especialidad">Especialidad:</label>
            <input type="text" id="especialidad" name="especialidad" value="<?php echo htmlspecialchars($especialidad); ?>" required>
            <label for="biografia">Biografía:</label>
            <textarea id="biografia" name="biografia" rows="4" required><?php echo htmlspecialchars($biografia); ?></textarea>
            <label for="titulo_academico">Título Académico:</label>
            <input type="text" id="titulo_academico" name="titulo_academico" value="<?php echo htmlspecialchars($titulo_academico); ?>" required>
            <label for="anios_experiencia">Años de experiencia:</label>
            <input type="number" id="anios_experiencia" name="anios_experiencia" min="0" value="<?php echo htmlspecialchars($anios_experiencia); ?>" required>
            <button type="submit"><i class="fas fa-save"></i> Guardar Cambios</button>
        </form>
    </div>
    <script>
        document.getElementById('foto_perfil').addEventListener('change', function() {
            var fileName = this.files[0] ? this.files[0].name : '';
            document.getElementById('file-name-display').textContent = fileName;

            // Lógica para previsualizar la imagen seleccionada
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-pic-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                // Si no se selecciona ningún archivo, revertir a la foto de perfil actual
                document.getElementById('profile-pic-preview').src = '<?php echo $ruta_foto_perfil; ?>';
            }
        });
    </script>
</body>
</html>