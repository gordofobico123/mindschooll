<?php
session_start();
include_once '../../includes/db.php';
include_once '../../includes/config.php';

// Verificar si el usuario es admin
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'admin') {
    header("Location: " . RUTA_BASE . "dashboard.php");
    exit();
}

// Consultar datos para las gráficas
// Ejemplo: Número de usuarios por rol
$usuarios_por_rol = [];
$resultado = $conexion->query("SELECT rol, COUNT(*) as cantidad FROM usuarios GROUP BY rol");
if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $usuarios_por_rol[$fila['rol']] = $fila['cantidad'];
    }
}
// Ejemplo: Cursos activos, inactivos y en edición
$cursos_por_estado = [];
$resultado = $conexion->query("SELECT estado, COUNT(*) as cantidad FROM cursos GROUP BY estado");
if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $cursos_por_estado[$fila['estado']] = $fila['cantidad'];
    }
}
// Ejemplo: Inscripciones por mes (últimos 6 meses)
$inscripciones_por_mes = [];
$resultado = $conexion->query("SELECT DATE_FORMAT(fecha_inscripcion, '%Y-%m') as mes, COUNT(*) as cantidad FROM inscripciones GROUP BY mes ORDER BY mes DESC LIMIT 6");
if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $inscripciones_por_mes[$fila['mes']] = $fila['cantidad'];
    }
}
$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Estadísticas - MindSchool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
            max-width: 1100px;
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
        .graficas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 32px;
            margin-bottom: 40px;
        }
        .grafica-card {
            background: #f5f5f5;
            padding: 24px 18px 18px 18px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        .btn-pdf {
            background: #388e3c;
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: background 0.3s, transform 0.2s;
            margin: 0 auto 30px auto;
            display: block;
        }
        .btn-pdf:hover {
            background: #2e7031;
            transform: translateY(-2px);
        }
        @media (max-width: 700px) {
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
            <a href="<?php echo RUTA_BASE; ?>dashboard.php">Panel de Control</a>
            <a href="<?php echo RUTA_BASE; ?>cerrar_sesion.php">Cerrar Sesión</a>
        </div>
        <h1>Reportes y Estadísticas</h1>
        <button class="btn-pdf" onclick="generarPDF()"><i class="fas fa-file-pdf"></i> Descargar Reporte en PDF</button>
        <div class="graficas">
            <div class="grafica-card">
                <h3>Usuarios por Rol</h3>
                <canvas id="graficaUsuariosRol" width="300" height="220"></canvas>
            </div>
            <div class="grafica-card">
                <h3>Cursos por Estado</h3>
                <canvas id="graficaCursosEstado" width="300" height="220"></canvas>
            </div>
            <div class="grafica-card">
                <h3>Inscripciones por Mes</h3>
                <canvas id="graficaInscripcionesMes" width="300" height="220"></canvas>
            </div>
        </div>
    </div>
    <script>
        // Datos de PHP a JavaScript
        const usuariosPorRol = <?php echo json_encode($usuarios_por_rol); ?>;
        const cursosPorEstado = <?php echo json_encode($cursos_por_estado); ?>;
        const inscripcionesPorMes = <?php echo json_encode($inscripciones_por_mes); ?>;

        // Gráfica de Usuarios por Rol
        new Chart(document.getElementById('graficaUsuariosRol'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(usuariosPorRol),
                datasets: [{
                    data: Object.values(usuariosPorRol),
                    backgroundColor: ['#388e3c', '#fd7e14', '#1976d2', '#d32f2f', '#bdbdbd'],
                }]
            },
            options: {
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Gráfica de Cursos por Estado
        new Chart(document.getElementById('graficaCursosEstado'), {
            type: 'pie',
            data: {
                labels: Object.keys(cursosPorEstado),
                datasets: [{
                    data: Object.values(cursosPorEstado),
                    backgroundColor: ['#388e3c', '#fd7e14', '#bdbdbd'],
                }]
            },
            options: {
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Gráfica de Inscripciones por Mes
        new Chart(document.getElementById('graficaInscripcionesMes'), {
            type: 'bar',
            data: {
                labels: Object.keys(inscripcionesPorMes).reverse(),
                datasets: [{
                    label: 'Inscripciones',
                    data: Object.values(inscripcionesPorMes).reverse(),
                    backgroundColor: '#1976d2',
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Función para generar PDF de las gráficas
        function generarPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            doc.setFontSize(18);
            doc.text('Reporte de Estadísticas - MindSchool', 15, 18);
            doc.setFontSize(12);
            doc.text('Usuarios por Rol:', 15, 32);
            doc.addImage(document.getElementById('graficaUsuariosRol').toDataURL('image/png'), 'PNG', 15, 36, 80, 60);
            doc.text('Cursos por Estado:', 110, 32);
            doc.addImage(document.getElementById('graficaCursosEstado').toDataURL('image/png'), 'PNG', 110, 36, 80, 60);
            doc.text('Inscripciones por Mes:', 15, 105);
            doc.addImage(document.getElementById('graficaInscripcionesMes').toDataURL('image/png'), 'PNG', 15, 109, 175, 60);
            doc.save('reporte_mindschool.pdf');
        }
    </script>
</body>
</html>