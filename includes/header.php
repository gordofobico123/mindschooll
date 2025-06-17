<?php
// includes/header.php
// Este archivo debe ser incluido al inicio de cada página que lo necesite.
// Asume que la sesión ya ha sido iniciada en la página que lo incluye.

// Definir RUTA_BASE si no está definida (importante para las rutas absolutas de CSS, JS, etc.)
// Esto asume que config.php ya fue incluido. Si no, podrías definirla aquí
// define('RUTA_BASE', '/mindschool/'); // Ajusta esto a la URL base de tu proyecto

$nombre_usuario_sesion = $_SESSION['nombre'] ?? 'Invitado';
$rol_usuario_sesion = $_SESSION['rol_usuario'] ?? 'invitado'; // rol_usuario o el nombre de tu columna de rol
$apellido_usuario_sesion = $_SESSION['apellido'] ?? '';
?>

<header class="main-header">
    <div class="header-content contenedor">
        <div class="logo">
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">
                 
                <span class="site-name">MindSchool</span>
            </a>
        </div>
        <nav class="main-nav">
            <ul>
                <?php if (isset($_SESSION['id_usuario'])): // Si el usuario está logueado ?>
                    <?php if ($rol_usuario_sesion === 'admin'): ?>
                        <li><a href="<?php echo RUTA_BASE; ?>dashboard.php">Dashboard Admin</a></li>
                        <li><a href="<?php echo RUTA_BASE; ?>paginas/usuarios/listar_usuarios.php">Gestionar Usuarios</a></li>
                        <li><a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php">Gestionar Cursos</a></li>
                        <?php elseif ($rol_usuario_sesion === 'profesor'): ?>
                        <li><a href="<?php echo RUTA_BASE; ?>dashboard.php">Dashboard Profesor</a></li>
                        <li><a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php?filtro=mis_cursos">Mis Cursos Impartidos</a></li>
                        <li><a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php">Explorar Todos los Cursos</a></li>
                        <?php elseif ($rol_usuario_sesion === 'alumno'): ?>
                        <li><a href="<?php echo RUTA_BASE; ?>dashboard.php">Dashboard Alumno</a></li>
                        <li><a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php">Explorar Cursos</a></li>
                        <li><a href="<?php echo RUTA_BASE; ?>paginas/alumnos/mis_cursos.php">Mis Cursos</a></li>
                        <?php elseif ($rol_usuario_sesion === 'padre'): ?>
                        <li><a href="<?php echo RUTA_BASE; ?>dashboard.php">Dashboard Padre</a></li>
                        <li><a href="<?php echo RUTA_BASE; ?>paginas/alumnos/hijos_matriculados.php">Mis Hijos</a></li>
                        <?php else: // Rol no reconocido o por defecto ?>
                        <li><a href="<?php echo RUTA_BASE; ?>dashboard.php">Dashboard</a></li>
                        <li><a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php">Explorar Cursos</a></li>
                    <?php endif; ?>

                    <li><a href="<?php echo RUTA_BASE; ?>paginas/autenticacion/logout.php" class="btn-logout">Cerrar Sesión (<?php echo htmlspecialchars($nombre_usuario_sesion . ' ' . $apellido_usuario_sesion); ?>)</a></li>
                <?php else: // Si el usuario NO está logueado ?>
                    <li><a href="<?php echo RUTA_BASE; ?>paginas/autenticacion/login.php">Iniciar Sesión</a></li>
                    <li><a href="<?php echo RUTA_BASE; ?>paginas/autenticacion/registro.php">Registrarse</a></li>
                    <li><a href="<?php echo RUTA_BASE; ?>paginas/cursos/listar_cursos.php">Explorar Cursos</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>