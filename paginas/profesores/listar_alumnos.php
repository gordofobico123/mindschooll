<?php
// Iniciar sesión y cargar archivos de configuración y base de datos
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar autenticación y permisos (solo profesor)
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'profesor') {
    $_SESSION['mensaje_error'] = "Acceso denegado. Solo los profesores pueden gestionar sus alumnos.";
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

$id_profesor_sesion = $_SESSION['id_usuario'];

$alumnos = [];
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
$mensaje_error = $_SESSION['mensaje_error'] ?? '';
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

// Lógica para obtener los alumnos inscritos en los cursos de este profesor
$sql = "SELECT DISTINCT u.id_usuario, u.nombre, u.apellido, u.email, a.nivel_educativo
        FROM usuarios u
        JOIN alumnos a ON u.id_usuario = a.id_alumno
        JOIN inscripciones i ON u.id_usuario = i.id_alumno
        JOIN cursos c ON i.id_curso = c.id_curso
        WHERE u.rol = 'alumno' AND c.id_profesor = ?
        ORDER BY u.apellido, u.nombre";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_profesor_sesion);
$stmt->execute();
$resultado = $stmt->get_result();

while ($fila = $resultado->fetch_assoc()) {
    $alumnos[] = $fila;
}
$stmt->close();
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Alumnos - AmindSchool</title>
    <link rel="stylesheet" href="<?php echo RUTA_BASE; ?>public/css/style.css">
    <style>
        /* Estilos básicos para este ejemplo, preferiblemente en style.css */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 20px auto;
        }
        h1 {
            color: #0056b3;
            margin-bottom: 25px;
            text-align: center;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        .message.exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alumnos-lista {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .alumno-card {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .alumno-card h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #007bff;
        }
        .alumno-card p {
            margin: 5px 0;
            font-size: 0.9em;
            color: #555;
        }
        .alumno-card .acciones {
            margin-top: 15px;
            text-align: right;
        }
        .alumno-card .acciones a {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }
        .alumno-card .acciones a:hover {
            background-color: #0056b3;
        }
        .no-alumnos {
            text-align: center;
            padding: 30px;
            color: #777;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mis Alumnos</h1>

        <?php if ($mensaje_exito): ?>
            <p class="message exito"><?php echo $mensaje_exito; ?></p>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <p class="message error"><?php echo $mensaje_error; ?></p>
        <?php endif; ?>

        <?php if (!empty($alumnos)): ?>
            <div class="alumnos-lista">
                <?php foreach ($alumnos as $alumno): ?>
                    <div class="alumno-card">
                        <h3><?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido']); ?></h3>
                        <p>Email: <?php echo htmlspecialchars($alumno['email']); ?></p>
                        <p>Nivel Educativo: <?php echo htmlspecialchars($alumno['nivel_educativo'] ?? 'N/A'); ?></p>
                        <div class="acciones">
                            <a href="<?php echo RUTA_BASE; ?>paginas/profesores/detalle_alumno.php?id_alumno=<?php echo htmlspecialchars($alumno['id_usuario']); ?>">Ver Detalles</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-alumnos">No tienes alumnos inscritos en tus cursos.</p>
        <?php endif; ?>
    </div>
</body>
</html>