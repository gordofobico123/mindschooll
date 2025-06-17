<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$id_curso = $_GET['id'] ?? null;
$curso = null;
$mensaje_error = "";
$mensaje_exito = "";
$rol_usuario = $_SESSION['rol_usuario'];
$id_usuario = $_SESSION['id_usuario'];
$ya_inscrito = false;

if (isset($_SESSION['mensaje_exito_detalle'])) {
    $mensaje_exito = $_SESSION['mensaje_exito_detalle'];
    unset($_SESSION['mensaje_exito_detalle']);
}
if (isset($_SESSION['mensaje_error_detalle'])) {
    $mensaje_error = $_SESSION['mensaje_error_detalle'];
    unset($_SESSION['mensaje_error_detalle']);
}

// Inscripción (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['inscribir_curso']) && $rol_usuario === 'alumno') {
    $id_curso_inscribir = $_POST['id_curso'];
    $id_alumno = $id_usuario;

    $stmt_check = $conexion->prepare("SELECT id_inscripcion FROM inscripciones WHERE id_alumno = ? AND id_curso = ?");
    $stmt_check->bind_param("ii", $id_alumno, $id_curso_inscribir);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    if ($res_check->num_rows > 0) {
        $_SESSION['mensaje_error_detalle'] = "Ya estás inscrito en este curso.";
    } else {
        $stmt_insert = $conexion->prepare("INSERT INTO inscripciones (id_alumno, id_curso, fecha_inscripcion, estado) VALUES (?, ?, NOW(), 'activo')");
        $stmt_insert->bind_param("ii", $id_alumno, $id_curso_inscribir);
        if ($stmt_insert->execute()) {
            $_SESSION['mensaje_exito_detalle'] = "¡Te has inscrito al curso exitosamente!";
        } else {
            $_SESSION['mensaje_error_detalle'] = "Error al inscribirte en el curso: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
    header("Location: " . RUTA_BASE . "paginas/cursos/detalle_curso.php?id=" . $id_curso_inscribir);
    exit();
}

// Obtener detalles del curso
if (!$id_curso || !is_numeric($id_curso)) {
    $mensaje_error = "ID de curso no válido. Por favor, especifica un curso para ver sus detalles.";
} else {
    $stmt = $conexion->prepare("SELECT c.*, CONCAT(u.nombre, ' ', u.apellido) AS nombre_profesor
                                FROM cursos c
                                LEFT JOIN usuarios u ON c.id_profesor = u.id_usuario
                                WHERE c.id_curso = ?");
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $curso = $res->fetch_assoc();
        if ($rol_usuario === 'alumno') {
            $stmt_inscrito = $conexion->prepare("SELECT id_inscripcion FROM inscripciones WHERE id_alumno = ? AND id_curso = ?");
            $stmt_inscrito->bind_param("ii", $id_usuario, $id_curso);
            $stmt_inscrito->execute();
            $res_inscrito = $stmt_inscrito->get_result();
            if ($res_inscrito->num_rows > 0) {
                $ya_inscrito = true;
            }
            $stmt_inscrito->close();
        }
    } else {
        $mensaje_error = "Curso no encontrado.";
    }
    $stmt->close();
}
$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $curso ? htmlspecialchars($curso['nombre_curso']) : 'Detalle del Curso'; ?> - MindSchool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #222;
            margin: 0;
            padding: 0;
        }
        .contenedor {
            max-width: 800px;
            margin: 32px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            padding: 32px 24px;
        }
        h1 {
            color: #3f51b5;
            margin-bottom: 24px;
            text-align: center;
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
        .curso-detalle {
            display: flex;
            flex-wrap: wrap;
            background: #f5f5f5;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .curso-imagen {
            flex: 1 1 40%;
            min-width: 240px;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .curso-imagen img {
            width: 100%;
            max-height: 220px;
            object-fit: cover;
            border-radius: 8px;
        }
        .curso-info {
            flex: 1 1 60%;
            padding: 24px;
        }
        .curso-info p {
            margin-bottom: 10px;
            font-size: 1.08em;
            color: #555;
        }
        .curso-info .precio {
            color: #388e3c;
            font-weight: bold;
            font-size: 1.2em;
        }
        .curso-info .estado {
            color: #3f51b5;
            font-weight: bold;
        }
        .descripcion {
            margin-top: 18px;
            border-top: 1px solid #eee;
            padding-top: 14px;
        }
        .acciones {
            margin-top: 28px;
            display: flex;
            gap: 16px;
            justify-content: center;
        }
        .acciones a, .acciones button, .acciones span {
            padding: 10px 22px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1em;
            transition: background 0.2s;
            border: none;
        }
        .btn-editar { background: #3f51b5; color: #fff; }
        .btn-editar:hover { background: #283593; }
        .btn-inscribirse { background: #388e3c; color: #fff; cursor: pointer; }
        .btn-inscribirse:hover { background: #256029; }
        .btn-inscrito, .btn-inactivo {
            background: #bdbdbd;
            color: #fff;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <?php if ($mensaje_exito): ?>
            <div class="mensaje-exito"><?php echo htmlspecialchars($mensaje_exito); ?></div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($mensaje_error); ?></div>
        <?php endif; ?>

        <?php if ($curso): ?>
            <h1><?php echo htmlspecialchars($curso['nombre_curso']); ?></h1>
            <div class="curso-detalle">
                <div class="curso-imagen">
                    <?php
                    $ruta_fisica = __DIR__ . '/../../imagenes_cursos/' . basename($curso['imagen_portada']);
                    $ruta_web = RUTA_BASE . 'imagenes_cursos/' . basename($curso['imagen_portada']);
                    $ruta_default = RUTA_BASE . 'imagenes_cursos/default_course.png';
                    $imagen = (!empty($curso['imagen_portada']) && file_exists($ruta_fisica)) ? $ruta_web : $ruta_default;
                    ?>
                    <img src="<?php echo $imagen; ?>" alt="Portada del curso">
                </div>
                <div class="curso-info">
                    <p><strong>Profesor:</strong> <?php echo htmlspecialchars($curso['nombre_profesor'] ?? 'N/A'); ?></p>
                    <p><strong>Nivel:</strong> <?php echo htmlspecialchars($curso['nivel_dificultad']); ?></p>
                    <p><strong>Categoría:</strong> <?php echo htmlspecialchars($curso['categoria']); ?></p>
                    <p class="precio">Precio: $<?php echo number_format($curso['precio'], 2, '.', ','); ?> MXN</p>
                    <p class="estado">Estado: <?php echo ucfirst(htmlspecialchars($curso['estado'])); ?></p>
                    <div class="descripcion">
                        <strong>Descripción:</strong>
                        <p><?php echo nl2br(htmlspecialchars($curso['descripcion'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="acciones">
                <?php if ($rol_usuario == 'admin' || ($rol_usuario == 'profesor' && $curso['id_profesor'] == $id_usuario)): ?>
                    <a href="<?php echo RUTA_BASE; ?>paginas/cursos/editar_curso.php?id=<?php echo $curso['id_curso']; ?>" class="btn-editar">Editar Curso</a>
                <?php endif; ?>
                <?php if ($rol_usuario == 'alumno'):
                    if ($curso['estado'] === 'activo' && !$ya_inscrito): ?>
                        <form action="" method="POST" style="display:inline;">
                            <input type="hidden" name="inscribir_curso" value="1">
                            <input type="hidden" name="id_curso" value="<?php echo htmlspecialchars($curso['id_curso']); ?>">
                            <button type="submit" class="btn-inscribirse">Inscribirme</button>
                        </form>
                    <?php elseif ($ya_inscrito): ?>
                        <span class="btn-inscrito">Ya Inscrito</span>
                    <?php else: ?>
                        <span class="btn-inactivo">Curso Inactivo</span>
                    <?php endif;
                endif; ?>
            </div>
        <?php else: ?>
            <div class="mensaje-error">No se pudo cargar la información del curso o el ID es inválido.</div>
        <?php endif; ?>
    </div>
</body>
</html>