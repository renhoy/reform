<?php
// {"_META_file_path_": "public/dashboard.php"}
// Dashboard rediseñado con estilos coherentes

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
require_once SRC_PATH . '/config/config.php';
requireAuth();

$pdo = getConnection();
$tariffsCount = $pdo->query("SELECT COUNT(*) FROM tariffs WHERE user_id = " . $_SESSION['user_id'])->fetchColumn();
$budgetsCount = $pdo->query("SELECT COUNT(*) FROM budgets WHERE user_id = " . $_SESSION['user_id'])->fetchColumn();
$recentBudgets = $pdo->query("SELECT COUNT(*) FROM budgets WHERE user_id = " . $_SESSION['user_id'] . " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/common-styles.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/header-styles.css') ?>">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 3rem;
        }

        .welcome-section h1 {
            color: var(--dark-gray);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .welcome-section p {
            color: #666;
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .action-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .action-card h3 {
            color: var(--dark-gray);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .action-card p {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--secondary-orange);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 768px) {
            .welcome-section h1 {
                font-size: 2rem;
            }
            
            .welcome-section p {
                font-size: 1rem;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Generador de Presupuestos</div>
            <nav class="main-nav">
                <a href="dashboard.php" class="nav-item active">Dashboard</a>
                <a href="tariffs.php" class="nav-item">Tarifas</a>
                <a href="budgets.php" class="nav-item">Presupuestos</a>
            </nav>
            <div class="user-menu">
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h1>Bienvenido al Generador de Presupuestos</h1>
            <p>Gestiona tus tarifas y genera presupuestos profesionales de forma rápida y sencilla.</p>
        </div>

        <div class="quick-actions">
            <div class="action-card">
                <div class="card-icon">📊</div>
                <h3>Gestionar Tarifas</h3>
                <p>Crea, edita y organiza tus tarifas de precios</p>
                <a href="tariffs.php" class="btn btn-secondary">Ir a Tarifas</a>
            </div>

            <div class="action-card">
                <div class="card-icon">📋</div>
                <h3>Ver Presupuestos</h3>
                <p>Consulta todos los presupuestos generados</p>
                <a href="budgets.php" class="btn btn-primary">Ver Presupuestos</a>
            </div>

            <div class="action-card">
                <div class="card-icon">✨</div>
                <h3>Crear Nueva Tarifa</h3>
                <p>Comienza creando una nueva tarifa de precios</p>
                <a href="upload-tariff.php" class="btn btn-secondary">Nueva Tarifa</a>
            </div>
        </div>

        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-number"><?= $tariffsCount ?></div>
                <div class="stat-label">Tarifas Creadas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $budgetsCount ?></div>
                <div class="stat-label">Presupuestos Generados</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $recentBudgets ?></div>
                <div class="stat-label">Esta Semana</div>
            </div>
        </div>
    </div>
</body>
</html>