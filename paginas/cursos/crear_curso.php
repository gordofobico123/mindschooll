<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario está autenticado y tiene rol de profesor o admin
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol_usuario'] !== 'profesor' && $_SESSION['rol_usuario'] !== 'admin')) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$id_usuario_creador = $_SESSION['id_usuario'];
$rol_usuario = $_SESSION['rol_usuario'];

$mensaje_exito = '';
$mensaje_error = '';

// Ruta donde se guardarán las imágenes de portada de los cursos
$upload_dir = realpath(__DIR__ . '/../../imagenes_cursos/') . DIRECTORY_SEPARATOR;

// Lógica para manejar el envío del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_curso = trim($_POST['nombre_curso']);
    $descripcion = trim($_POST['descripcion']);
    $nivel_dificultad = $_POST['nivel_dificultad'];
    $categoria = trim($_POST['categoria']);
    $precio = filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT);
    $estado = $_POST['estado'] ?? 'en_edicion'; // Por defecto 'en_edicion'

    $id_profesor_asignado = ($rol_usuario === 'admin' && isset($_POST['id_profesor_asignado'])) ? $_POST['id_profesor_asignado'] : $id_usuario_creador;

    $imagen_portada_url = null; // Inicializar la URL de la imagen

    // Lógica para subir la imagen si se seleccionó una
    if (isset($_FILES['imagen_portada']) && $_FILES['imagen_portada']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['imagen_portada']['tmp_name'];
        $file_name = basename($_FILES['imagen_portada']['name']);
        $file_size = $_FILES['imagen_portada']['size'];
        $file_type = $_FILES['imagen_portada']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif']; // Tipos de imagen permitidos
        $max_size = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file_ext, $allowed_extensions)) {
            $mensaje_error = "Tipo de archivo no permitido. Solo se permiten JPG, JPEG, PNG y GIF.";
        } elseif ($file_size > $max_size) {
            $mensaje_error = "La imagen es demasiado grande (máx. 5MB).";
        } else {
            // Generar un nombre de archivo único para evitar colisiones
            $new_file_name = uniqid('curso_') . '.' . $file_ext;
            $destination_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $destination_path)) {
                $imagen_portada_url = RUTA_BASE . 'imagenes_cursos/' . $new_file_name;
            } else {
                $mensaje_error = "Error al subir la imagen de portada. Código de error: " . $_FILES['imagen_portada']['error'];
            }
        }
    }

    // Si hay un error en la subida, no procesar la inserción del curso
    if ($mensaje_error) {
        // El mensaje de error ya está establecido, simplemente no continuamos
    } elseif (empty($nombre_curso) || empty($descripcion) || empty($categoria) || $precio === false || $precio < 0) {
        $mensaje_error = "Todos los campos obligatorios deben ser rellenados y el precio debe ser un número válido.";
    } else {
        // Insertar el nuevo curso en la base de datos
        $stmt = $conexion->prepare("INSERT INTO cursos (nombre_curso, descripcion, nivel_dificultad, categoria, precio, estado, id_profesor, imagen_portada, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssdsss", $nombre_curso, $descripcion, $nivel_dificultad, $categoria, $precio, $estado, $id_profesor_asignado, $imagen_portada_url);
        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Curso '{$nombre_curso}' creado con éxito.";
            header("Location: " . RUTA_BASE . "paginas/cursos/listar_cursos.php");
            exit();
        } else {
            $mensaje_error = "Error al crear el curso: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Lógica para obtener profesores (solo si el usuario actual es admin)
$profesores_disponibles = [];
if ($rol_usuario === 'admin') {
    $sql_profesores = "SELECT u.id_usuario, CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo 
                       FROM usuarios u 
                       WHERE u.rol = 'profesor' ORDER BY u.nombre ASC";
    $resultado_profesores = $conexion->query($sql_profesores);
    if ($resultado_profesores) {
        while ($fila = $resultado_profesores->fetch_assoc()) {
            $profesores_disponibles[] = $fila;
        }
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
    <title>Crear Nuevo Curso - MindSchool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 700px;
            margin: 32px auto;
            padding: 40px 32px 32px 32px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            width: 100%;
        }
        .navegacion {
            margin-bottom: 25px;
            text-align: center;
        }
        .navegacion a {
            margin: 0 10px;
            text-decoration: none;
            color: #007bff;
            padding: 8px 15px;
            border: 1px solid #007bff;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
            font-weight: 500;
        }
        .navegacion a:hover {
            background-color: #007bff;
            color: white;
        }
        h1 {
            text-align: center;
            color: #444;
            margin-bottom: 30px;
            font-size: 2em;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #333;
        }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select,
        .form-group input[type="number"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            box-sizing: border-box;
            background: #f5f5f5;
            font-size: 1em;
            color: #333;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .btn-submit {
            background: #388e3c;
            color: white;
            padding: 14px 0;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            display: block;
            width: 100%;
            margin-top: 20px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: background 0.3s, transform 0.2s;
        }
        .btn-submit:hover {
            background: #2e7031;
            transform: translateY(-2px);
        }
        .mensaje-exito, .mensaje-error {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 1em;
            font-weight: bold;
            text-align: center;
        }
        .mensaje-exito {
            background: #e8f5e9;
            color: #388e3c;
            border: 1px solid #c8e6c9;
        }
        .mensaje-error {
            background: #fff3f3;
            color: #d32f2f;
            border: 1px solid #f8d7da;
        }
        @media (max-width: 600px) {
            .container {
                padding: 18px 6px 18px 6px;
            }
            h1 {
                font-size: 1.3em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navegacion">
            <a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php">Volver a Cursos</a>
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">Panel de Control</a>
            <a href="<?php echo RUTA_BASE; ?>cerrar_sesion.php">Cerrar Sesión</a>
        </div>
        <h1>Crear Nuevo Curso</h1>
        <?php
        if ($mensaje_exito) {
            echo "<div class='mensaje-exito'>" . $mensaje_exito . "</div>";
        }
        if ($mensaje_error) {
            echo "<div class='mensaje-error'>" . $mensaje_error . "</div>";
        }
        ?>
        <form action="<?php echo RUTA_BASE; ?>paginas/cursos/crear_curso.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nombre_curso">Nombre del Curso:</label>
                <input type="text" id="nombre_curso" name="nombre_curso" required>
            </div>
            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" required></textarea>
            </div>
            <?php if ($rol_usuario === 'admin'): ?>
                <div class="form-group">
                    <label for="id_profesor_asignado">Asignar Profesor:</label>
                    <select id="id_profesor_asignado" name="id_profesor_asignado" required>
                        <option value="">-- Seleccionar Profesor --</option>
                        <?php foreach ($profesores_disponibles as $profesor): ?>
                            <option value="<?php echo $profesor['id_usuario']; ?>"><?php echo htmlspecialchars($profesor['nombre_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <p>El curso se asignará automáticamente a tu perfil de profesor.</p>
            <?php endif; ?>
            <div class="form-group">
                <label for="nivel_dificultad">Nivel de Dificultad:</label>
                <select id="nivel_dificultad" name="nivel_dificultad" required>
                    <option value="principiante">Principiante</option>
                    <option value="intermedio">Intermedio</option>
                    <option value="avanzado">Avanzado</option>
                </select>
            </div>
            <div class="form-group">
                <label for="categoria">Categoría:</label>
                <input type="text" id="categoria" name="categoria" required>
            </div>
            <div class="form-group">
                <label for="precio">Precio (MXN):</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0" value="0.00" required>
            </div>
            <div class="form-group">
                <label for="estado">Estado del Curso:</label>
                <select id="estado" name="estado" required>
                    <option value="en_edicion">En Edición</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
            <div class="form-group">
                <label for="imagen_portada">Imagen de Portada del Curso (Opcional):</label>
                <input type="file" id="imagen_portada" name="imagen_portada" accept="image/jpeg,image/png,image/gif">
                <small>Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB.</small>
            </div>
            <button type="submit" class="btn-submit">Crear Curso</button>
        </form>
    </div>
</body>
</html>