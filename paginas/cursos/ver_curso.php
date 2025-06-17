<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$id_curso = null;
$curso = null;
$mensaje_error = '';

// Obtener el ID del curso de la URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_curso = intval($_GET['id']);

    // Consultar los detalles del curso
    $sql = "SELECT c.id_curso, c.nombre_curso, c.descripcion, c.nivel_dificultad, 
                   c.categoria, c.precio, c.estado, c.imagen_portada,
                   u.nombre AS nombre_profesor, u.apellido AS apellido_profesor
            FROM cursos c
            LEFT JOIN usuarios u ON c.id_profesor = u.id_usuario
            WHERE c.id_curso = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $curso = $resultado->fetch_assoc();
    } else {
        $mensaje_error = "Curso no encontrado.";
    }
    $stmt->close();
} else {
    $mensaje_error = "ID de curso no proporcionado o inválido.";
}

// Cerrar la conexión
if (isset($conexion) && $conexion instanceof mysqli) {
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Curso - MindSchool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f7f6; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 25px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #0056b3; margin-bottom: 30px; }
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
        .curso-detalle {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .curso-detalle p {
            margin-bottom: 10px;
        }
        .curso-detalle strong {
            color: #0056b3;
        }
        .curso-imagen-detalle {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 15px;
            display: block; /* Para centrar si es un bloque */
            margin-left: auto;
            margin-right: auto;
        }
        .mensaje-error { color: red; font-weight: bold; text-align: center; margin-bottom: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="navegacion">
            <a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php">Volver al Listado de Cursos</a>
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">Panel de Control</a>
            <a href="<?php echo RUTA_BASE; ?>cerrar_sesion.php">Cerrar Sesión</a>
        </div>

        <h1>Detalles del Curso</h1>

        <?php if ($mensaje_error): ?>
            <p class="mensaje-error"><?php echo $mensaje_error; ?></p>
        <?php elseif ($curso): ?>
            <div class="curso-detalle">
                <?php if (!empty($curso['imagen_portada'])): ?>
                    <img src="<?php echo htmlspecialchars($curso['imagen_portada']); ?>" alt="Portada del Curso" class="curso-imagen-detalle">
                <?php endif; ?>
                <p><strong>Nombre del Curso:</strong> <?php echo htmlspecialchars($curso['nombre_curso']); ?></p>
                <p><strong>Descripción:</strong> <?php echo htmlspecialchars($curso['descripcion']); ?></p>
                <p><strong>Profesor:</strong> 
                    <?php 
                    echo (isset($curso['nombre_profesor']) && isset($curso['apellido_profesor'])) ? 
                         htmlspecialchars($curso['nombre_profesor'] . ' ' . $curso['apellido_profesor']) : 
                         'Sin profesor asignado'; 
                    ?>
                </p>
                <p><strong>Nivel de Dificultad:</strong> <?php echo htmlspecialchars($curso['nivel_dificultad']); ?></p>
                <p><strong>Categoría:</strong> <?php echo htmlspecialchars($curso['categoria']); ?></p>
                <p><strong>Precio:</strong> $<?php echo htmlspecialchars(number_format($curso['precio'], 2)); ?></p>
                <p><strong>Estado:</strong> <?php echo htmlspecialchars($curso['estado']); ?></p>
            </div>
        <?php else: ?>
            <p>No se pudo cargar la información del curso.</p>
        <?php endif; ?>
    </div>
</body>
</html>