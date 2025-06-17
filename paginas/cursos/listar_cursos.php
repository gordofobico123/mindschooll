<?php
// Iniciar sesión y cargar configuración y base de datos
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar autenticación
if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

// Variables de sesión y mensajes
$rol_usuario = $_SESSION['rol_usuario'];
$id_usuario = $_SESSION['id_usuario'];
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
$mensaje_error = $_SESSION['mensaje_error'] ?? '';
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

// Obtener cursos según el rol del usuario
$cursos = [];
if ($rol_usuario === 'admin') {
    // Admin ve todos los cursos
    $sql = "SELECT c.*, CONCAT(u.nombre, ' ', u.apellido) AS nombre_profesor
            FROM cursos c
            LEFT JOIN usuarios u ON c.id_profesor = u.id_usuario
            ORDER BY c.fecha_creacion DESC";
    $stmt = $conexion->prepare($sql);
} elseif ($rol_usuario === 'profesor') {
    // Profesor ve solo sus cursos
    $sql = "SELECT c.*, CONCAT(u.nombre, ' ', u.apellido) AS nombre_profesor
            FROM cursos c
            LEFT JOIN usuarios u ON c.id_profesor = u.id_usuario
            WHERE c.id_profesor = ?
            ORDER BY c.fecha_creacion DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
} else {
    // Alumno ve solo cursos activos
    $sql = "SELECT c.*, CONCAT(u.nombre, ' ', u.apellido) AS nombre_profesor
            FROM cursos c
            LEFT JOIN usuarios u ON c.id_profesor = u.id_usuario
            WHERE c.estado = 'activo'
            ORDER BY c.fecha_creacion DESC";
    $stmt = $conexion->prepare($sql);
}
$stmt->execute();
$resultado = $stmt->get_result();
while ($curso = $resultado->fetch_assoc()) {
    $cursos[] = $curso;
}
$stmt->close();

// Si es alumno, obtener cursos en los que ya está inscrito
$cursos_inscritos = [];
if ($rol_usuario === 'alumno') {
    $sql = "SELECT id_curso FROM inscripciones WHERE id_alumno = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $cursos_inscritos[$fila['id_curso']] = true;
    }
    $stmt->close();
}
$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Cursos - MindSchool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Estilos sencillos, fondo blanco y color secundario -->
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #222;
            margin: 0;
            padding: 0;
        }
        .contenedor {
            max-width: 1100px;
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
        .grid-cursos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }
        .curso-card {
            background: #f5f5f5;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .curso-card img {
            width: 100%;
            max-height: 160px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 12px;
            background: #e0e0e0;
        }
        .curso-card h2 {
            color: #3f51b5;
            font-size: 1.3em;
            margin: 0 0 8px 0;
        }
        .curso-card .profesor {
            font-size: 0.98em;
            color: #444;
            margin-bottom: 6px;
        }
        .curso-card .info-extra {
            font-size: 0.95em;
            color: #666;
            margin-bottom: 8px;
        }
        .curso-card .precio {
            color: #388e3c;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .curso-card .estado {
            font-size: 0.95em;
            color: #d32f2f;
            margin-bottom: 8px;
        }
        .curso-card .acciones {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .curso-card .acciones a, .curso-card .acciones span {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.98em;
            transition: background 0.2s;
        }
        .btn-detalles { background: #3f51b5; color: #fff; }
        .btn-detalles:hover { background: #283593; }
        .btn-inscribirse { background: #388e3c; color: #fff; }
        .btn-inscribirse:hover { background: #256029; }
        .btn-inscrito, .btn-inactivo {
            background: #bdbdbd;
            color: #fff;
            cursor: not-allowed;
        }
        .btn-editar { background: #ffa000; color: #fff; }
        .btn-editar:hover { background: #ff6f00; }
        .btn-eliminar { background: #d32f2f; color: #fff; }
        .btn-eliminar:hover { background: #b71c1c; }
        .btn-contenido { background: #607d8b; color: #fff; }
        .btn-contenido:hover { background: #455a64; }
        .no-cursos {
            text-align: center;
            color: #888;
            font-size: 1.1em;
            margin: 40px 0;
        }
        .volver-panel {
            display: inline-block;
            margin-bottom: 24px;
            background: #ececec;
            color: #3f51b5;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.2s;
        }
        .volver-panel:hover {
            background: #d1d9ff;
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <a href="<?php echo RUTA_BASE; ?>dashboard.php" class="volver-panel">← Volver al Panel</a>
        <h1>Listado de Cursos</h1>
        <?php if ($mensaje_exito): ?>
            <div class="mensaje-exito"><?php echo htmlspecialchars($mensaje_exito); ?></div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($mensaje_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($cursos)): ?>
        <div class="grid-cursos">
            <?php foreach ($cursos as $curso): ?>
                <div class="curso-card">
                    <?php
                    // Determinar imagen de portada
                    $ruta_fisica = __DIR__ . '/../../imagenes_cursos/' . basename($curso['imagen_portada']);
                    $ruta_web = RUTA_BASE . 'imagenes_cursos/' . basename($curso['imagen_portada']);
                    $ruta_default = RUTA_BASE . 'imagenes_cursos/default_course.png';
                    $imagen = (!empty($curso['imagen_portada']) && file_exists($ruta_fisica)) ? $ruta_web : $ruta_default;
                    ?>
                    <img src="<?php echo $imagen; ?>" alt="Portada del curso">
                    <h2><?php echo htmlspecialchars($curso['nombre_curso']); ?></h2>
                    <div class="profesor">Profesor: <?php echo htmlspecialchars($curso['nombre_profesor'] ?? 'N/A'); ?></div>
                    <div class="info-extra">
                        Nivel: <?php echo htmlspecialchars($curso['nivel_dificultad']); ?> |
                        Categoría: <?php echo htmlspecialchars($curso['categoria']); ?> |
                        Fecha: <?php echo date('d/m/Y', strtotime($curso['fecha_creacion'])); ?>
                    </div>
                    <div class="precio">Precio: $<?php echo number_format($curso['precio'], 2, '.', ','); ?></div>
                    <div class="estado">Estado: <?php echo ucfirst(htmlspecialchars($curso['estado'])); ?></div>
                    <div class="info-extra">
                        <?php echo nl2br(htmlspecialchars(substr($curso['descripcion'], 0, 120))); ?>...
                    </div>
                    <div class="acciones">
                        <a href="<?php echo RUTA_BASE; ?>paginas/cursos/detalle_curso.php?id=<?php echo $curso['id_curso']; ?>" class="btn-detalles">Ver Detalles</a>
                        <?php if ($rol_usuario === 'alumno'):
                            $ya_inscrito = isset($cursos_inscritos[$curso['id_curso']]);
                            $curso_activo = ($curso['estado'] === 'activo');
                        ?>
                            <?php if ($curso_activo && !$ya_inscrito): ?>
                                <a href="<?php echo RUTA_BASE; ?>paginas/inscripciones/inscribir_curso.php?id=<?php echo $curso['id_curso']; ?>" class="btn-inscribirse">Inscribirme</a>
                            <?php elseif ($ya_inscrito): ?>
                                <span class="btn-inscrito">Inscrito</span>
                            <?php else: ?>
                                <span class="btn-inactivo">Inactivo</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php
                        $es_profesor_propio = ($rol_usuario === 'profesor' && isset($curso['id_profesor']) && $curso['id_profesor'] == $id_usuario);
                        ?>
                        <?php if ($rol_usuario === 'admin' || $es_profesor_propio): ?>
                            <a href="<?php echo RUTA_BASE; ?>paginas/cursos/editar_curso.php?id=<?php echo $curso['id_curso']; ?>" class="btn-editar">Editar</a>
                            <a href="<?php echo RUTA_BASE; ?>paginas/cursos/eliminar_curso.php?id=<?php echo $curso['id_curso']; ?>" class="btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar este curso?');">Eliminar</a>
                            <a href="<?php echo RUTA_BASE; ?>paginas/cursos/gestionar_contenido_curso.php?id_curso=<?php echo $curso['id_curso']; ?>" class="btn-contenido">Gestionar Contenido</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="no-cursos">No hay cursos disponibles en este momento.</div>
        <?php endif; ?>
    </div>
</body>
</html>