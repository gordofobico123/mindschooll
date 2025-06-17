-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-06-2025 a las 04:34:37
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `mindschool`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `id_alumno` int(11) NOT NULL,
  `nivel_educativo` varchar(50) DEFAULT NULL,
  `institucion_anterior` varchar(100) DEFAULT NULL,
  `necesidades_especiales` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`id_alumno`, `nivel_educativo`, `institucion_anterior`, `necesidades_especiales`) VALUES
(7, NULL, NULL, NULL),
(10, NULL, NULL, NULL),
(11, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `anuncios`
--

CREATE TABLE `anuncios` (
  `id_anuncio` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `contenido` text NOT NULL,
  `fecha_publicacion` datetime DEFAULT current_timestamp(),
  `importancia` enum('normal','importante','urgente') DEFAULT 'normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aulas`
--

CREATE TABLE `aulas` (
  `id_aula` int(11) NOT NULL,
  `nombre_aula` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `id_profesor` int(11) NOT NULL,
  `codigo_invitacion` varchar(50) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aulas`
--

INSERT INTO `aulas` (`id_aula`, `nombre_aula`, `descripcion`, `id_profesor`, `codigo_invitacion`, `fecha_creacion`) VALUES
(2, 'Bachillerato 9 sementre', 'codificando con el inge', 6, '4NK7OX8F', '2025-06-07 16:08:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aula_alumnos`
--

CREATE TABLE `aula_alumnos` (
  `id_aula_alumno` int(11) NOT NULL,
  `id_aula` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `fecha_inscripcion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aula_cursos`
--

CREATE TABLE `aula_cursos` (
  `id_aula_curso` int(11) NOT NULL,
  `id_aula` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `fecha_asignacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aula_cursos`
--

INSERT INTO `aula_cursos` (`id_aula_curso`, `id_aula`, `id_curso`, `fecha_asignacion`) VALUES
(1, 2, 6, '2025-06-07 16:10:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones`
--

CREATE TABLE `calificaciones` (
  `id_calificacion` int(11) NOT NULL,
  `id_entrega` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `puntuacion` decimal(5,2) DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `fecha_calificacion` datetime DEFAULT current_timestamp(),
  `retroalimentacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `certificados`
--

CREATE TABLE `certificados` (
  `id_certificado` int(11) NOT NULL,
  `id_inscripcion` int(11) NOT NULL,
  `codigo_unico` varchar(50) NOT NULL,
  `fecha_emision` datetime DEFAULT current_timestamp(),
  `url_certificado` varchar(255) NOT NULL,
  `horas_certificadas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chats_grupales`
--

CREATE TABLE `chats_grupales` (
  `id_mensaje_chat` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clases`
--

CREATE TABLE `clases` (
  `id_clase` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_hora_inicio` datetime NOT NULL,
  `fecha_hora_fin` datetime NOT NULL,
  `url_clase` varchar(255) DEFAULT NULL,
  `grabacion_url` varchar(255) DEFAULT NULL,
  `estado` enum('programada','en_progreso','finalizada','cancelada') DEFAULT 'programada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id_config` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` enum('string','number','boolean','json') DEFAULT 'string',
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id_config`, `nombre`, `valor`, `tipo`, `descripcion`, `categoria`) VALUES
(1, 'nombre_escuela', 'MindSchool', 'string', 'Nombre de la institución educativa', 'general'),
(2, 'logo_url', 'assets/img/logo.png', 'string', 'URL del logo de la escuela', 'general'),
(3, 'tema_color_principal', '#3f51b5', 'string', 'Color principal de la interfaz', 'apariencia'),
(4, 'habilitar_chats', 'true', 'boolean', 'Habilitar función de chats grupales', 'funcionalidad'),
(5, 'tamano_maximo_archivo', '10', 'number', 'Tamaño máximo de archivo en MB', 'archivos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id_curso` int(11) NOT NULL,
  `nombre_curso` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `id_profesor` int(11) DEFAULT NULL,
  `nivel_dificultad` enum('principiante','intermedio','avanzado') DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `imagen_portada` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `estado` enum('activo','inactivo','en_edicion') DEFAULT 'activo',
  `precio` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id_curso`, `nombre_curso`, `descripcion`, `id_profesor`, `nivel_dificultad`, `categoria`, `imagen_portada`, `fecha_creacion`, `estado`, `precio`) VALUES
(1, 'Introducción a la Programación', 'Aprende los fundamentos de la programación desde cero.', NULL, 'principiante', 'Tecnología', NULL, '2025-06-07 00:52:34', 'activo', 49.99),
(2, 'Matemáticas para Bachillerato', 'Curso completo de matemáticas para estudiantes de bachillerato.', NULL, 'intermedio', 'Ciencias', NULL, '2025-06-07 00:52:34', 'activo', 59.99),
(3, 'Diseño Gráfico Básico', 'Explora las herramientas y principios del diseño gráfico.', NULL, 'principiante', '0', NULL, '2025-06-07 00:52:34', '', 39.99),
(6, 'Curso de Habilidades Blandas para el Desarrollo Profesional', 'Desarrolla tus habilidades blandas para potenciar tu perfil profesional. Aprende a comunicarte, gestionar el tiempo, liderar equipos y crecer en el entorno laboral. Practica con técnicas probadas y testimonios de expertos en la materia.', 6, 'principiante', 'habilidades blandas', '/mindschool/imagenes_cursos/curso_6844ad3156dc5.jpg', '2025-06-07 02:02:17', 'activo', 20.00),
(7, 'Operaciones Matemáticas en Programación: Fundamentos y Aplicaciones', 'programación sencilla', 6, 'principiante', 'habilidades blandas', '/mindschool/imagenes_cursos/curso_684534d5c74b3.jpg', '2025-06-08 00:59:33', 'en_edicion', 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entregas`
--

CREATE TABLE `entregas` (
  `id_entrega` int(11) NOT NULL,
  `id_tarea` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `contenido` text DEFAULT NULL,
  `url_archivo` varchar(255) DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT current_timestamp(),
  `estado` enum('entregado','calificado','rechazado') DEFAULT 'entregado',
  `comentario_alumno` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entregas_tarea`
--

CREATE TABLE `entregas_tarea` (
  `id_entrega` int(11) NOT NULL,
  `id_tarea` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `contenido_texto` text DEFAULT NULL,
  `url_entrega` varchar(255) DEFAULT NULL,
  `archivo_entrega` varchar(255) DEFAULT NULL,
  `fecha_entrega` timestamp NOT NULL DEFAULT current_timestamp(),
  `calificacion` decimal(5,2) DEFAULT NULL,
  `comentarios_profesor` text DEFAULT NULL,
  `estado_entrega` enum('pendiente','entregado','calificado','retrasado') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos_mensajeria`
--

CREATE TABLE `grupos_mensajeria` (
  `id_grupo` int(11) NOT NULL,
  `nombre_grupo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `id_creador` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id_inscripcion` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `fecha_inscripcion` datetime DEFAULT current_timestamp(),
  `estado_inscripcion` varchar(50) NOT NULL DEFAULT 'inscrito',
  `estado` enum('activo','completado','cancelado') DEFAULT 'activo',
  `progreso` int(11) DEFAULT 0,
  `fecha_completado` datetime DEFAULT NULL,
  `calificacion_final` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inscripciones`
--

INSERT INTO `inscripciones` (`id_inscripcion`, `id_alumno`, `id_curso`, `fecha_inscripcion`, `estado_inscripcion`, `estado`, `progreso`, `fecha_completado`, `calificacion_final`) VALUES
(1, 7, 1, '2025-06-08 22:46:26', 'activa', 'activo', 0, NULL, NULL),
(2, 7, 6, '2025-06-08 22:51:08', 'activa', 'activo', 0, NULL, NULL),
(3, 7, 2, '2025-06-08 22:51:17', 'activa', 'activo', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insignias`
--

CREATE TABLE `insignias` (
  `id_insignia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) NOT NULL,
  `criterio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insignias_alumnos`
--

CREATE TABLE `insignias_alumnos` (
  `id_insignia_alumno` int(11) NOT NULL,
  `id_insignia` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `fecha_obtencion` datetime DEFAULT current_timestamp(),
  `id_profesor_otorga` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lecciones`
--

CREATE TABLE `lecciones` (
  `id_leccion` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `contenido` text DEFAULT NULL,
  `tipo` enum('video','texto','quiz','tarea','enlace') NOT NULL,
  `contenido_texto` longtext DEFAULT NULL,
  `duracion_minutos` int(11) DEFAULT NULL,
  `url_recurso` varchar(255) DEFAULT NULL,
  `orden` int(11) NOT NULL,
  `fecha_publicacion` datetime DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `nombre_leccion` varchar(255) NOT NULL,
  `tipo_leccion` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `lecciones`
--

INSERT INTO `lecciones` (`id_leccion`, `id_modulo`, `titulo`, `contenido`, `tipo`, `contenido_texto`, `duracion_minutos`, `url_recurso`, `orden`, `fecha_publicacion`, `fecha_creacion`, `nombre_leccion`, `tipo_leccion`) VALUES
(1, 1, 'liderazgo ¿Que es ?', 'averiguaremos que es el liderazgo', 'video', NULL, 20, 'https://youtu.be/E8UQrDD2nQw', 1, NULL, '2025-06-07 14:44:19', '', ''),
(2, 1, 'Como aplicar el liderazgo', 'El liderazgo es vocación de servicio', '', NULL, 30, '', 1, NULL, '2025-06-07 14:46:34', '', ''),
(3, 1, 'Como aplicar el liderazgo', '.', '', NULL, 30, '/mindschool/recursos_lecciones/leccion_6844a6ef17cd1.docx', 1, NULL, '2025-06-07 14:54:07', '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales`
--

CREATE TABLE `materiales` (
  `id_material` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `tipo` enum('documento','presentacion','video','enlace','otro') NOT NULL,
  `url_material` varchar(255) NOT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_grupales`
--

CREATE TABLE `mensajes_grupales` (
  `id_mensaje_grupal` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `id_remitente` int(11) NOT NULL,
  `contenido` text NOT NULL,
  `fecha_envio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_privados`
--

CREATE TABLE `mensajes_privados` (
  `id_mensaje` int(11) NOT NULL,
  `id_remitente` int(11) NOT NULL,
  `id_destinatario` int(11) NOT NULL,
  `asunto` varchar(255) DEFAULT NULL,
  `contenido` text NOT NULL,
  `fecha_envio` datetime DEFAULT current_timestamp(),
  `leido` tinyint(1) DEFAULT 0,
  `fecha_leido` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `miembros_grupo`
--

CREATE TABLE `miembros_grupo` (
  `id_miembro` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_union` datetime DEFAULT current_timestamp(),
  `rol` enum('admin','miembro') DEFAULT 'miembro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos`
--

CREATE TABLE `modulos` (
  `id_modulo` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) NOT NULL,
  `fecha_publicacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modulos`
--

INSERT INTO `modulos` (`id_modulo`, `id_curso`, `titulo`, `descripcion`, `orden`, `fecha_publicacion`) VALUES
(1, 6, 'introducción a la clases sociales y como nos percibimos', 'si terminas seras del 1%', 1, '2025-06-07 11:49:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos_curso`
--

CREATE TABLE `modulos_curso` (
  `id_modulo` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `nombre_modulo` varchar(255) NOT NULL,
  `descripcion_modulo` text DEFAULT NULL,
  `orden` int(11) NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modulos_curso`
--

INSERT INTO `modulos_curso` (`id_modulo`, `id_curso`, `nombre_modulo`, `descripcion_modulo`, `orden`, `fecha_creacion`) VALUES
(1, 7, 'modulo de lapiz y papel', 'entenderemos la programación con accione cotidianas', 1, '2025-06-08 01:00:14'),
(2, 7, 'modulo de lapiz y papel', 'entenderemos la programación con accione cotidianas', 1, '2025-06-08 01:03:12'),
(3, 7, 'modulo de lapiz y papel', 'entenderemos la programación con accione cotidianas', 1, '2025-06-08 01:03:49'),
(4, 7, 'modulo de lapiz y papel', 'entenderemos la programación con accione cotidianas', 1, '2025-06-08 01:04:55'),
(5, 7, 'modulo de lapiz y papel', 'entenderemos la programación con accione cotidianas', 1, '2025-06-08 01:10:43'),
(6, 7, 'modulo de lapiz y papel', 'entenderemos la programación con accione cotidianas', 1, '2025-06-08 01:13:30'),
(7, 7, 'modulo de lapiz y papel', 'entenderemos la programación con accione cotidianas', 1, '2025-06-08 01:22:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` enum('sistema','curso','mensaje','tarea') NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `leida` tinyint(1) DEFAULT 0,
  `fecha_leida` datetime DEFAULT NULL,
  `url_accion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `padres`
--

CREATE TABLE `padres` (
  `id_padre` int(11) NOT NULL,
  `ocupacion` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `padres_alumnos`
--

CREATE TABLE `padres_alumnos` (
  `id_relacion` int(11) NOT NULL,
  `id_padre` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `parentesco` enum('madre','padre','tutor','otro') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes`
--

CREATE TABLE `planes` (
  `id_plan` int(11) NOT NULL,
  `nombre_plan` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `duracion_dias` int(11) NOT NULL,
  `caracteristicas` text DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `limite_cursos` int(11) DEFAULT NULL,
  `limite_alumnos` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `id_profesor` int(11) NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `biografia` text DEFAULT NULL,
  `titulo_academico` varchar(100) DEFAULT NULL,
  `años_experiencia` int(11) DEFAULT NULL,
  `anios_experiencia` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`id_profesor`, `especialidad`, `biografia`, `titulo_academico`, `años_experiencia`, `anios_experiencia`) VALUES
(6, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `progreso_alumno`
--

CREATE TABLE `progreso_alumno` (
  `id_progreso` int(11) NOT NULL,
  `id_inscripcion` int(11) NOT NULL,
  `id_leccion` int(11) NOT NULL,
  `estado` enum('no_iniciado','en_progreso','completado') DEFAULT 'no_iniciado',
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_completado` datetime DEFAULT NULL,
  `tiempo_dedicado` int(11) DEFAULT 0,
  `intentos` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas`
--

CREATE TABLE `tareas` (
  `id_tarea` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha_publicacion` datetime DEFAULT current_timestamp(),
  `fecha_entrega` datetime NOT NULL,
  `puntos_maximos` int(11) DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transacciones`
--

CREATE TABLE `transacciones` (
  `id_transaccion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_plan` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `moneda` varchar(3) DEFAULT 'USD',
  `fecha_transaccion` datetime DEFAULT current_timestamp(),
  `metodo_pago` enum('tarjeta','paypal','transferencia','otro') NOT NULL,
  `estado` enum('pendiente','completado','fallido','reembolsado') DEFAULT 'pendiente',
  `id_transaccion_externa` varchar(255) DEFAULT NULL,
  `detalles` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `rol_usuario` varchar(50) NOT NULL DEFAULT 'alumno',
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` enum('masculino','femenino','otro') DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `rol` enum('admin','profesor','alumno','padre') NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL,
  `estado` enum('activo','inactivo','suspendido') DEFAULT 'activo',
  `token_recuperacion` varchar(255) DEFAULT NULL,
  `expiracion_token` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellido`, `rol_usuario`, `email`, `password_hash`, `google_id`, `fecha_nacimiento`, `genero`, `telefono`, `direccion`, `avatar`, `rol`, `fecha_registro`, `ultimo_acceso`, `estado`, `token_recuperacion`, `expiracion_token`, `password`) VALUES
(6, 'Saul', '.', 'alumno', 'saulis@gmail.com', '$2y$10$6IJ0aXr.giPla6THLBwCVOzKM6sHtJa91yABlf0Z9w8ORmgD2xLhK', NULL, NULL, NULL, NULL, NULL, NULL, 'profesor', '2025-06-07 01:56:13', NULL, 'activo', NULL, NULL, ''),
(7, 'Alex', '.', 'alumno', 'Alex@gmail.com', '$2y$10$r/TTH4DNybv7KOj4ajv.ieCsW8usnf9uRNtJvUrE4gIIsxFLYpUle', NULL, NULL, NULL, NULL, NULL, NULL, 'alumno', '2025-06-07 02:12:44', NULL, 'activo', NULL, NULL, ''),
(8, 'Admin', 'Principal', 'alumno', 'admin@mindschool.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, NULL, 'admin', '2025-06-08 02:38:25', NULL, 'activo', NULL, NULL, ''),
(9, 'jose', 'matinez', 'alumno', 'marinez@117.com', '', NULL, NULL, NULL, NULL, NULL, NULL, 'padre', '2025-06-08 02:56:43', NULL, 'activo', NULL, NULL, '$2y$10$D31uIoxHk7hl2legrvZgqeodGvzi7iy2QK5AJIBdfxAqDOujwAw8S'),
(10, 'Renuar', 'Olivares', 'alumno', 'renuarolivares1711@gmail.com', '$2y$10$sxHIu6sbzpgl05d1A.TB9.9dYAN0/5wJa8FL46pTDBWPzqO3FlXb.', NULL, NULL, NULL, NULL, NULL, NULL, 'alumno', '2025-06-10 08:46:18', NULL, 'activo', NULL, NULL, ''),
(11, 'alumno ', '1', 'alumno', 'alumno1@gmail.com', '$2y$10$Dd4uLI6zG4LPOfTj4OD0buOZBY5/DXGwgT1VJM/88aFRxlb9s6Qum', NULL, NULL, NULL, NULL, NULL, NULL, 'alumno', '2025-06-11 13:57:12', NULL, 'activo', NULL, NULL, '');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`id_alumno`);

--
-- Indices de la tabla `anuncios`
--
ALTER TABLE `anuncios`
  ADD PRIMARY KEY (`id_anuncio`),
  ADD KEY `id_curso` (`id_curso`),
  ADD KEY `id_profesor` (`id_profesor`);

--
-- Indices de la tabla `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id_aula`),
  ADD UNIQUE KEY `codigo_invitacion` (`codigo_invitacion`),
  ADD KEY `id_profesor` (`id_profesor`);

--
-- Indices de la tabla `aula_alumnos`
--
ALTER TABLE `aula_alumnos`
  ADD PRIMARY KEY (`id_aula_alumno`),
  ADD UNIQUE KEY `idx_aula_alumno_unique` (`id_aula`,`id_alumno`),
  ADD KEY `id_alumno` (`id_alumno`);

--
-- Indices de la tabla `aula_cursos`
--
ALTER TABLE `aula_cursos`
  ADD PRIMARY KEY (`id_aula_curso`),
  ADD UNIQUE KEY `idx_aula_curso_unique` (`id_aula`,`id_curso`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Indices de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD PRIMARY KEY (`id_calificacion`),
  ADD KEY `id_entrega` (`id_entrega`),
  ADD KEY `id_profesor` (`id_profesor`);

--
-- Indices de la tabla `certificados`
--
ALTER TABLE `certificados`
  ADD PRIMARY KEY (`id_certificado`),
  ADD UNIQUE KEY `codigo_unico` (`codigo_unico`),
  ADD KEY `id_inscripcion` (`id_inscripcion`);

--
-- Indices de la tabla `chats_grupales`
--
ALTER TABLE `chats_grupales`
  ADD PRIMARY KEY (`id_mensaje_chat`),
  ADD KEY `id_grupo` (`id_grupo`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `clases`
--
ALTER TABLE `clases`
  ADD PRIMARY KEY (`id_clase`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id_config`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id_curso`),
  ADD KEY `id_profesor` (`id_profesor`);

--
-- Indices de la tabla `entregas`
--
ALTER TABLE `entregas`
  ADD PRIMARY KEY (`id_entrega`),
  ADD UNIQUE KEY `id_tarea` (`id_tarea`,`id_alumno`),
  ADD KEY `id_alumno` (`id_alumno`);

--
-- Indices de la tabla `entregas_tarea`
--
ALTER TABLE `entregas_tarea`
  ADD PRIMARY KEY (`id_entrega`),
  ADD UNIQUE KEY `uq_tarea_alumno` (`id_tarea`,`id_alumno`),
  ADD KEY `id_alumno` (`id_alumno`);

--
-- Indices de la tabla `grupos_mensajeria`
--
ALTER TABLE `grupos_mensajeria`
  ADD PRIMARY KEY (`id_grupo`),
  ADD KEY `id_creador` (`id_creador`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id_inscripcion`),
  ADD UNIQUE KEY `id_alumno` (`id_alumno`,`id_curso`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Indices de la tabla `insignias`
--
ALTER TABLE `insignias`
  ADD PRIMARY KEY (`id_insignia`);

--
-- Indices de la tabla `insignias_alumnos`
--
ALTER TABLE `insignias_alumnos`
  ADD PRIMARY KEY (`id_insignia_alumno`),
  ADD KEY `id_insignia` (`id_insignia`),
  ADD KEY `id_alumno` (`id_alumno`),
  ADD KEY `id_profesor_otorga` (`id_profesor_otorga`);

--
-- Indices de la tabla `lecciones`
--
ALTER TABLE `lecciones`
  ADD PRIMARY KEY (`id_leccion`),
  ADD KEY `id_modulo` (`id_modulo`);

--
-- Indices de la tabla `materiales`
--
ALTER TABLE `materiales`
  ADD PRIMARY KEY (`id_material`),
  ADD KEY `id_curso` (`id_curso`),
  ADD KEY `id_profesor` (`id_profesor`);

--
-- Indices de la tabla `mensajes_grupales`
--
ALTER TABLE `mensajes_grupales`
  ADD PRIMARY KEY (`id_mensaje_grupal`),
  ADD KEY `id_grupo` (`id_grupo`),
  ADD KEY `id_remitente` (`id_remitente`);

--
-- Indices de la tabla `mensajes_privados`
--
ALTER TABLE `mensajes_privados`
  ADD PRIMARY KEY (`id_mensaje`),
  ADD KEY `id_remitente` (`id_remitente`),
  ADD KEY `id_destinatario` (`id_destinatario`);

--
-- Indices de la tabla `miembros_grupo`
--
ALTER TABLE `miembros_grupo`
  ADD PRIMARY KEY (`id_miembro`),
  ADD UNIQUE KEY `id_grupo` (`id_grupo`,`id_usuario`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD PRIMARY KEY (`id_modulo`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Indices de la tabla `modulos_curso`
--
ALTER TABLE `modulos_curso`
  ADD PRIMARY KEY (`id_modulo`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `padres`
--
ALTER TABLE `padres`
  ADD PRIMARY KEY (`id_padre`);

--
-- Indices de la tabla `padres_alumnos`
--
ALTER TABLE `padres_alumnos`
  ADD PRIMARY KEY (`id_relacion`),
  ADD UNIQUE KEY `id_padre` (`id_padre`,`id_alumno`),
  ADD KEY `id_alumno` (`id_alumno`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `email` (`email`);

--
-- Indices de la tabla `planes`
--
ALTER TABLE `planes`
  ADD PRIMARY KEY (`id_plan`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`id_profesor`);

--
-- Indices de la tabla `progreso_alumno`
--
ALTER TABLE `progreso_alumno`
  ADD PRIMARY KEY (`id_progreso`),
  ADD UNIQUE KEY `id_inscripcion` (`id_inscripcion`,`id_leccion`),
  ADD KEY `id_leccion` (`id_leccion`);

--
-- Indices de la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD PRIMARY KEY (`id_tarea`),
  ADD KEY `id_curso` (`id_curso`),
  ADD KEY `id_profesor` (`id_profesor`);

--
-- Indices de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD PRIMARY KEY (`id_transaccion`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_plan` (`id_plan`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `anuncios`
--
ALTER TABLE `anuncios`
  MODIFY `id_anuncio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id_aula` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `aula_alumnos`
--
ALTER TABLE `aula_alumnos`
  MODIFY `id_aula_alumno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `aula_cursos`
--
ALTER TABLE `aula_cursos`
  MODIFY `id_aula_curso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  MODIFY `id_calificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `certificados`
--
ALTER TABLE `certificados`
  MODIFY `id_certificado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `chats_grupales`
--
ALTER TABLE `chats_grupales`
  MODIFY `id_mensaje_chat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clases`
--
ALTER TABLE `clases`
  MODIFY `id_clase` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id_curso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `entregas`
--
ALTER TABLE `entregas`
  MODIFY `id_entrega` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entregas_tarea`
--
ALTER TABLE `entregas_tarea`
  MODIFY `id_entrega` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupos_mensajeria`
--
ALTER TABLE `grupos_mensajeria`
  MODIFY `id_grupo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id_inscripcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `insignias`
--
ALTER TABLE `insignias`
  MODIFY `id_insignia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `insignias_alumnos`
--
ALTER TABLE `insignias_alumnos`
  MODIFY `id_insignia_alumno` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `lecciones`
--
ALTER TABLE `lecciones`
  MODIFY `id_leccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `materiales`
--
ALTER TABLE `materiales`
  MODIFY `id_material` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes_grupales`
--
ALTER TABLE `mensajes_grupales`
  MODIFY `id_mensaje_grupal` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes_privados`
--
ALTER TABLE `mensajes_privados`
  MODIFY `id_mensaje` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `miembros_grupo`
--
ALTER TABLE `miembros_grupo`
  MODIFY `id_miembro` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `modulos_curso`
--
ALTER TABLE `modulos_curso`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `padres_alumnos`
--
ALTER TABLE `padres_alumnos`
  MODIFY `id_relacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `id_plan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `progreso_alumno`
--
ALTER TABLE `progreso_alumno`
  MODIFY `id_progreso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tareas`
--
ALTER TABLE `tareas`
  MODIFY `id_tarea` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  MODIFY `id_transaccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD CONSTRAINT `alumnos_ibfk_1` FOREIGN KEY (`id_alumno`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `anuncios`
--
ALTER TABLE `anuncios`
  ADD CONSTRAINT `anuncios_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE,
  ADD CONSTRAINT `anuncios_ibfk_2` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`);

--
-- Filtros para la tabla `aulas`
--
ALTER TABLE `aulas`
  ADD CONSTRAINT `aulas_ibfk_1` FOREIGN KEY (`id_profesor`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `aula_alumnos`
--
ALTER TABLE `aula_alumnos`
  ADD CONSTRAINT `aula_alumnos_ibfk_1` FOREIGN KEY (`id_aula`) REFERENCES `aulas` (`id_aula`) ON DELETE CASCADE,
  ADD CONSTRAINT `aula_alumnos_ibfk_2` FOREIGN KEY (`id_alumno`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `aula_cursos`
--
ALTER TABLE `aula_cursos`
  ADD CONSTRAINT `aula_cursos_ibfk_1` FOREIGN KEY (`id_aula`) REFERENCES `aulas` (`id_aula`) ON DELETE CASCADE,
  ADD CONSTRAINT `aula_cursos_ibfk_2` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD CONSTRAINT `calificaciones_ibfk_1` FOREIGN KEY (`id_entrega`) REFERENCES `entregas` (`id_entrega`) ON DELETE CASCADE,
  ADD CONSTRAINT `calificaciones_ibfk_2` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`);

--
-- Filtros para la tabla `certificados`
--
ALTER TABLE `certificados`
  ADD CONSTRAINT `certificados_ibfk_1` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripciones` (`id_inscripcion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `chats_grupales`
--
ALTER TABLE `chats_grupales`
  ADD CONSTRAINT `chats_grupales_ibfk_1` FOREIGN KEY (`id_grupo`) REFERENCES `grupos_mensajeria` (`id_grupo`) ON DELETE CASCADE,
  ADD CONSTRAINT `chats_grupales_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `clases`
--
ALTER TABLE `clases`
  ADD CONSTRAINT `clases_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`);

--
-- Filtros para la tabla `entregas`
--
ALTER TABLE `entregas`
  ADD CONSTRAINT `entregas_ibfk_1` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id_tarea`) ON DELETE CASCADE,
  ADD CONSTRAINT `entregas_ibfk_2` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE;

--
-- Filtros para la tabla `entregas_tarea`
--
ALTER TABLE `entregas_tarea`
  ADD CONSTRAINT `entregas_tarea_ibfk_1` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id_tarea`) ON DELETE CASCADE,
  ADD CONSTRAINT `entregas_tarea_ibfk_2` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE;

--
-- Filtros para la tabla `grupos_mensajeria`
--
ALTER TABLE `grupos_mensajeria`
  ADD CONSTRAINT `grupos_mensajeria_ibfk_1` FOREIGN KEY (`id_creador`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscripciones_ibfk_2` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE;

--
-- Filtros para la tabla `insignias_alumnos`
--
ALTER TABLE `insignias_alumnos`
  ADD CONSTRAINT `insignias_alumnos_ibfk_1` FOREIGN KEY (`id_insignia`) REFERENCES `insignias` (`id_insignia`) ON DELETE CASCADE,
  ADD CONSTRAINT `insignias_alumnos_ibfk_2` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `insignias_alumnos_ibfk_3` FOREIGN KEY (`id_profesor_otorga`) REFERENCES `profesores` (`id_profesor`);

--
-- Filtros para la tabla `lecciones`
--
ALTER TABLE `lecciones`
  ADD CONSTRAINT `lecciones_ibfk_1` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `materiales`
--
ALTER TABLE `materiales`
  ADD CONSTRAINT `materiales_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE,
  ADD CONSTRAINT `materiales_ibfk_2` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`);

--
-- Filtros para la tabla `mensajes_grupales`
--
ALTER TABLE `mensajes_grupales`
  ADD CONSTRAINT `mensajes_grupales_ibfk_1` FOREIGN KEY (`id_grupo`) REFERENCES `grupos_mensajeria` (`id_grupo`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensajes_grupales_ibfk_2` FOREIGN KEY (`id_remitente`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mensajes_privados`
--
ALTER TABLE `mensajes_privados`
  ADD CONSTRAINT `mensajes_privados_ibfk_1` FOREIGN KEY (`id_remitente`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensajes_privados_ibfk_2` FOREIGN KEY (`id_destinatario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `miembros_grupo`
--
ALTER TABLE `miembros_grupo`
  ADD CONSTRAINT `miembros_grupo_ibfk_1` FOREIGN KEY (`id_grupo`) REFERENCES `grupos_mensajeria` (`id_grupo`) ON DELETE CASCADE,
  ADD CONSTRAINT `miembros_grupo_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD CONSTRAINT `modulos_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE;

--
-- Filtros para la tabla `modulos_curso`
--
ALTER TABLE `modulos_curso`
  ADD CONSTRAINT `modulos_curso_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `padres`
--
ALTER TABLE `padres`
  ADD CONSTRAINT `padres_ibfk_1` FOREIGN KEY (`id_padre`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `padres_alumnos`
--
ALTER TABLE `padres_alumnos`
  ADD CONSTRAINT `padres_alumnos_ibfk_1` FOREIGN KEY (`id_padre`) REFERENCES `padres` (`id_padre`) ON DELETE CASCADE,
  ADD CONSTRAINT `padres_alumnos_ibfk_2` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE;

--
-- Filtros para la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD CONSTRAINT `profesores_ibfk_1` FOREIGN KEY (`id_profesor`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `progreso_alumno`
--
ALTER TABLE `progreso_alumno`
  ADD CONSTRAINT `progreso_alumno_ibfk_1` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripciones` (`id_inscripcion`) ON DELETE CASCADE,
  ADD CONSTRAINT `progreso_alumno_ibfk_2` FOREIGN KEY (`id_leccion`) REFERENCES `lecciones` (`id_leccion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD CONSTRAINT `tareas_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE,
  ADD CONSTRAINT `tareas_ibfk_2` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`);

--
-- Filtros para la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD CONSTRAINT `transacciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `transacciones_ibfk_2` FOREIGN KEY (`id_plan`) REFERENCES `planes` (`id_plan`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
