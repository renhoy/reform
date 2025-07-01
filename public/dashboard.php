<?php
// {"_META_file_path_": "public/dashboard.php"}
// Dashboard con layout GitHub reorganizado

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
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-title {
            font-size: 2rem;
            color: var(--dark-gray);
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: left;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
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
            margin-bottom: 1rem;
            display: flex;
            justify-content: center;
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
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
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
        <!-- Línea 1: Título -->
        <h1 class="page-title">Dashboard</h1>

        <!-- Línea 2: Estadísticas -->
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

        <!-- Línea 3: Acciones -->
        <div class="quick-actions">
            <div class="action-card">
                <div class="card-icon">
                    <i data-lucide="folder" style="width: 48px; height: 48px; color: var(--secondary-orange);"></i>
                </div>
                <h3>Tarifas</h3>
                <p>Crea, edita y organiza tus tarifas de precios</p>
                <a href="tariffs.php" class="btn btn-secondary">Ir a Tarifas</a>
            </div>

            <div class="action-card">
                <div class="card-icon">
                    <i data-lucide="file-text" style="width: 48px; height: 48px; color: var(--primary-green);"></i>
                </div>
                <h3>Presupuestos</h3>
                <p>Consulta todos los presupuestos generados</p>
                <a href="budgets.php" class="btn btn-primary">Ir a Presupuestos</a>
            </div>

            <div class="action-card">
                <div class="card-icon">
                    <i data-lucide="plus-circle" style="width: 48px; height: 48px; color: var(--secondary-orange);"></i>
                </div>
                <h3>Crear Nueva Tarifa</h3>
                <p>Comienza creando una nueva tarifa de precios</p>
                <a href="upload-tariff.php" class="btn btn-secondary">Nueva Tarifa</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>