<?php
session_start();
include_once 'includes/db.php';
include_once 'includes/config.php';

// Verificar si el usuario está autenticado, si no, redirigir al login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . RUTA_BASE . "paginas/autenticacion/login.php");
    exit();
}

$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';
$rol_usuario = $_SESSION['rol_usuario'] ?? 'indefinido';
$apellido_usuario = $_SESSION['apellido'] ?? '';

// Obtener mensajes de sesión si existen
$mensaje_exito = '';
$mensaje_error = '';
if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']);
}
if (isset($_SESSION['mensaje_error'])) {
    $mensaje_error = $_SESSION['mensaje_error'];
    unset($_SESSION['mensaje_error']);
}

// --- INICIO DE CÓDIGO MOVADO Y CORREGIDO ---

// Obtener información del usuario y su foto de perfil
$foto_perfil = RUTA_BASE . 'public/img/perfiles/default.jpg'; // Valor por defecto

if ($rol_usuario === 'profesor') {
    // Para profesores, obtener la foto de perfil de la tabla 'profesores'
    $sql_foto = "SELECT p.foto_perfil FROM profesores p WHERE p.id_profesor = ?"; // id_profesor es la FK a usuarios
    $stmt_foto = $conexion->prepare($sql_foto);
    if ($stmt_foto) {
        $stmt_foto->bind_param("i", $_SESSION['id_usuario']);
        $stmt_foto->execute();
        $stmt_foto->bind_result($foto_perfil_db);
        if ($stmt_foto->fetch() && !empty($foto_perfil_db)) {
            $foto_perfil = RUTA_BASE . 'public/img/perfiles/' . htmlspecialchars($foto_perfil_db);
        }
        $stmt_foto->close();
    }
} else {
    // Para otros roles (alumno, padre, admin), se podría obtener un avatar de la tabla 'usuarios' si existe o usar el default.
    // Asumimos que la tabla 'usuarios' tiene una columna 'avatar' o 'foto_perfil' para otros roles.
    // Si no es el caso, siempre se usará el default.
    $sql_foto = "SELECT avatar FROM usuarios WHERE id_usuario = ?"; // Asumiendo 'avatar' para usuarios generales
    $stmt_foto = $conexion->prepare($sql_foto);
    if ($stmt_foto) {
        $stmt_foto->bind_param("i", $_SESSION['id_usuario']);
        $stmt_foto->execute();
        $stmt_foto->bind_result($avatar_db);
        if ($stmt_foto->fetch() && !empty($avatar_db)) {
            $foto_perfil = RUTA_BASE . 'public/img/perfiles/' . htmlspecialchars($avatar_db);
        }
        $stmt_foto->close();
    }
}
// Puedes cerrar la conexión a la DB aquí si no necesitas más operaciones.
// Si necesitas más operaciones de DB en esta página, cierra la conexión al final.
// $conexion->close();

// Saludo dinámico
$hora = (int)date('H');
if ($hora >= 6 && $hora < 12) {
    $saludo = '¡Buenos días';
} elseif ($hora >= 12 && $hora < 20) {
    $saludo = '¡Buenas tardes';
} else {
    $saludo = '¡Buenas noches';
}
$nombre_completo = trim(($nombre_usuario ?? '') . ' ' . ($apellido_usuario ?? ''));

// --- FIN DE CÓDIGO MOVADO Y CORREGIDO ---
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MindSchool</title>
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
        .dashboard-container {
            max-width: 1200px;
            margin: 32px auto;
            padding: 40px 32px 32px 32px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            width: 100%;
        }
        .welcome-header {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .welcome-header h1 {
            color: #444;
            font-size: 2.2em;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .welcome-header p {
            font-size: 1.1em;
            color: #666;
        }
        .secciones-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .seccion-card {
            background: #f5f5f5;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e0e0e0;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .seccion-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.12);
        }
        .seccion-card h3 {
            color: #444;
            margin-bottom: 15px;
            font-size: 1.3em;
            font-weight: 600;
        }
        .seccion-card p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        .btn-accion {
            display: inline-block;
            padding: 12px 25px;
            background: #e0e0e0;
            color: #222;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: background 0.3s, transform 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            width: 100%;
            box-sizing: border-box;
        }
        .btn-accion:hover {
            background: #bdbdbd;
            transform: translateY(-2px);
        }
        .btn-accion.verde {
            background: #e8f5e9;
            color: #388e3c;
        }
        .btn-accion.verde:hover {
            background: #c8e6c9;
        }
        .btn-accion.naranja {
            background: #fff3e0;
            color: #fd7e14;
        }
        .btn-accion.naranja:hover {
            background: #ffe0b2;
        }
        .btn-accion.gris {
            background: #ececec;
            color: #444;
        }
        .btn-accion.gris:hover {
            background: #d6d6d6;
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
        .logout-link {
            text-align: center;
            margin-top: 40px;
        }
        .logout-link a {
            color: #d32f2f;
            text-decoration: underline;
            font-weight: bold;
            transition: color 0.2s;
        }
        .logout-link a:hover {
            color: #b71c1c;
        }
        .seccion-card .icon-placeholder {
            font-size: 2.5em;
            margin-bottom: 15px;
        }
        .seccion-card .icon-placeholder.student { color: #388e3c; }
        .seccion-card .icon-placeholder.teacher { color: #fd7e14; }
        .seccion-card .icon-placeholder.admin { color: #d32f2f; }
        .seccion-card .icon-placeholder.parent { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="welcome-header">
            <img src="<?php echo $foto_perfil . '?v=' . time(); ?>" alt="Foto de perfil" style="width:90px;height:90px;border-radius:50%;border:3px solid #388e3c;margin-bottom:10px;object-fit:cover;box-shadow:0 2px 8px rgba(0,0,0,0.12);">
            <h1><?php echo $saludo . ', ' . htmlspecialchars($nombre_completo); ?>!</h1>
            <p>Tu rol: <span style="font-weight: bold; color: #388e3c;"><?php echo htmlspecialchars(ucfirst($rol_usuario)); ?></span></p>
        </div>
        <?php
        if ($mensaje_exito) {
            echo "<div class='mensaje-exito'>" . $mensaje_exito . "</div>";
        }
        if ($mensaje_error) {
            echo "<div class='mensaje-error'>" . $mensaje_error . "</div>";
        }
        ?>
        <div class="secciones-dashboard">
            <?php if ($rol_usuario === 'alumno'): ?>
                <div class="seccion-card">
                    <span class="icon-placeholder student"><i class="fas fa-book-open"></i></span>
                    <h3>Mis Cursos</h3>
                    <p>Accede a todos los cursos en los que te has inscrito y continúa tu aprendizaje.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/alumnos/mis_cursos.php" class="btn-accion verde">Ver Mis Cursos</a>
                </div>
                <div class="seccion-card">
                    <span class="icon-placeholder student"><i class="fas fa-search"></i></span>
                    <h3>Explorar Cursos</h3>
                    <p>Descubre nuevos cursos disponibles para inscribirte y expandir tus conocimientos.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php" class="btn-accion">Explorar Cursos</a>
                </div>
                <div class="seccion-card">
                    <span class="icon-placeholder student"><i class="fas fa-chart-line"></i></span>
                    <h3>Mi Progreso</h3>
                    <p>Revisa tu avance en los cursos, lecciones completadas y próximos objetivos.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/alumnos/progreso.php" class="btn-accion naranja">Ver Progreso</a>
                </div>
            <?php elseif ($rol_usuario === 'profesor'): ?>
                <div class="seccion-card">
                    <span class="icon-placeholder teacher"><i class="fas fa-id-badge"></i></span>
                    <h3>Mi Perfil</h3>
                    <p>Consulta y edita tu información profesional, especialidad y experiencia.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/profesores/perfil.php" class="btn-accion gris">Ver Perfil</a>
                </div>
                <div class="seccion-card">
                    <span class="icon-placeholder teacher"><i class="fas fa-chalkboard-teacher"></i></span>
                    <h3>Mis Cursos</h3>
                    <p>Gestiona tus cursos, añade módulos, lecciones y revisa a tus alumnos.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php?filtro=mis_cursos" class="btn-accion verde">Gestionar Mis Cursos</a>
                </div>
                <div class="seccion-card">
                    <span class="icon-placeholder teacher"><i class="fas fa-plus-circle"></i></span>
                    <h3>Crear Nuevo Curso</h3>
                    <p>Empieza a crear un nuevo curso para compartir tus conocimientos.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/cursos/crear_curso.php" class="btn-accion">Crear Curso</a>
                </div>
                <div class="seccion-card">
                    <span class="icon-placeholder teacher"><i class="fas fa-user-friends"></i></span>
                    <h3>Alumnos Inscritos</h3>
                    <p>Revisa la lista de alumnos en tus cursos y su progreso general.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/profesores/listar_alumnos.php" class="btn-accion naranja">Ver Alumnos</a>
                </div>
                <div class="card">
                <div class="seccion-card">
                    <span class="icon-placeholder teacher"><i class="fas fa-book"></i></span>
                    <h3>Asignar Tarea</h3>
                    <p>Crea y asigna nuevas tareas a tus alumnos en los cursos.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/profesores/asignar_tarea.php" class="btn-accion">Asignar Tarea Ahora</a>
                </div>
            <?php elseif ($rol_usuario === 'admin'): ?>
                <div class="seccion-card">
                    <span class="icon-placeholder admin"><i class="fas fa-users-cog"></i></span>
                    <h3>Administrar Usuarios</h3>
                    <p>Gestiona cuentas de alumnos, profesores y otros administradores.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/admin/gestionar_usuarios.php" class="btn-accion verde">Gestionar Usuarios</a>
                </div>
                <div class="seccion-card">
                    <span class="icon-placeholder admin"><i class="fas fa-book"></i></span>
                    <h3>Administrar Cursos</h3>
                    <p>Revisa y edita todos los cursos, sin importar el profesor.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php" class="btn-accion">Gestionar Cursos</a>
                </div>
                <div class="seccion-card">
                    <span class="icon-placeholder admin"><i class="fas fa-chart-bar"></i></span>
                    <h3>Estadísticas y Reportes</h3>
                    <p>Accede a datos y reportes de la plataforma.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/admin/reportes.php" class="btn-accion naranja">Ver Reportes</a>
                </div>
            <?php elseif ($rol_usuario === 'padre'): ?>
                <div class="seccion-card">
                    <span class="icon-placeholder parent"><i class="fas fa-user-graduate"></i></span>
                    <h3>Hijos Matriculados</h3>
                    <p>Supervisa el progreso y los cursos de tus hijos.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/padres/hijos_matriculados.php" class="btn-accion verde">Ver Hijos</a>
                </div>
                <div class="seccion-card">
                    <span class="icon-placeholder parent"><i class="fas fa-hand-holding-usd"></i></span>
                    <h3>Pagos y Suscripciones</h3>
                    <p>Gestiona los pagos y suscripciones de tus hijos.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/padres/pagos.php" class="btn-accion">Ver Pagos</a>
                </div>
            <?php else: ?>
                <div class="seccion-card">
                    <h3>Bienvenido a MindSchool</h3>
                    <p>Inicia sesión o regístrate para explorar nuestros cursos y herramientas de aprendizaje.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/autenticacion/login.php" class="btn-accion">Iniciar Sesión</a>
                    <a href="<?php echo RUTA_BASE; ?>paginas/autenticacion/registro.php" class="btn-accion gris">Registrarse</a>
                </div>
                <div class="seccion-card">
                    <h3>Explorar Cursos</h3>
                    <p>Descubre nuestra variedad de cursos educativos disponibles.</p>
                    <a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php" class="btn-accion">Ver Cursos</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="logout-link">
            <a href="<?php echo RUTA_BASE; ?>paginas/autenticacion/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
    </div>
</body>
</html>
<div class="seccion-card">
    <span class="icon-placeholder"><i class="fas fa-envelope"></i></span>
    <h3>Mensajería Interna</h3>
    <p>Envía y recibe mensajes con otros usuarios de la plataforma para una mejor colaboración.</p>
    <a href="<?php echo RUTA_BASE; ?>paginas/mensajeria/bandeja.php" class="btn-accion naranja">Ir a Mensajes</a>
</div>
</div>
</html>