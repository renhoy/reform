<?php
// {"_META_file_path_": "public/templates.php"}
// Página plantillas de tarifas

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
require_once SRC_PATH . '/config/config.php';
requireAuth();

$pdo = getConnection();

// Filtros
$search = $_GET['search'] ?? '';
$author_filter = $_GET['author'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Por ahora, las plantillas son tarifas marcadas como públicas
$where_conditions = ["t.access = 'public'"];
$params = [];

if ($search) {
    $where_conditions[] = "t.title LIKE ?";
    $params[] = "%$search%";
}

if ($author_filter) {
    $where_conditions[] = "t.user_id = ?";
    $params[] = $author_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(t.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(t.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

$templates = $pdo->prepare("
    SELECT t.*, u.name as author_name
    FROM tariffs t 
    LEFT JOIN users u ON t.user_id = u.id
    WHERE $where_clause
    ORDER BY t.created_at DESC
");
$templates->execute($params);
$templates = $templates->fetchAll();

// Obtener autores para filtro
$authors = $pdo->query("
    SELECT DISTINCT u.id, u.name 
    FROM users u 
    INNER JOIN tariffs t ON u.id = t.user_id 
    WHERE t.access = 'public'
    ORDER BY u.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plantillas de Tarifas - Generador de Presupuestos</title>
    <link rel="stylesheet" href="<?= asset('css/common-styles.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/header-styles.css') ?>">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Generador de Presupuestos</div>
            <nav class="main-nav">
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="tariffs.php" class="nav-item active">Tarifas</a>
                <a href="budgets.php" class="nav-item">Presupuestos</a>
            </nav>
            <div class="user-menu">
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Línea 1: Título y botones -->
        <div class="page-header-row">
            <h1 class="page-title">Plantillas de Tarifas</h1>
            <div class="header-buttons">
                <a href="upload-tariff.php" class="btn btn-secondary">Crear Tarifas</a>
            </div>
        </div>

        <!-- Línea 3: Filtros -->
        <div class="filters-row">
            <form method="GET" class="filters-form" style="grid-template-columns: 2fr 1fr 1fr 1fr auto auto;">
                <input type="text" name="search" placeholder="Buscar por nombre de plantilla..." value="<?= htmlspecialchars($search) ?>" class="filter-input">
                
                <select name="author" class="filter-select">
                    <option value="">Todos los autores</option>
                    <?php foreach ($authors as $author): ?>
                        <option value="<?= $author['id'] ?>" <?= $author_filter == $author['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($author['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="date" name="date_from" value="<?= $date_from ?>" class="filter-date">
                <input type="date" name="date_to" value="<?= $date_to ?>" class="filter-date">
                
                <button type="submit" class="btn btn-secondary">Filtrar</button>
                <a href="templates.php" class="btn btn-outline">Limpiar</a>
            </form>
        </div>

        <?php if (empty($templates)): ?>
            <div class="empty-state">
                <h2>No hay plantillas disponibles</h2>
                <p>Las plantillas son tarifas públicas que otros usuarios han compartido</p>
                <a href="tariffs.php" class="btn btn-secondary">Ver Mis Tarifas</a>
            </div>
        <?php else: ?>
            <!-- Línea 4: Tabla -->
            <div class="data-table">
                <div class="table-header" style="grid-template-columns: 2fr 2fr 1fr 1fr 1fr;">
                    <div>Nombre</div>
                    <div>Descripción</div>
                    <div>Autor</div>
                    <div>Fecha</div>
                    <div>Acciones</div>
                </div>
                
                <?php foreach ($templates as $template): ?>
                    <div class="table-row" style="grid-template-columns: 2fr 2fr 1fr 1fr 1fr;">
                        <div><?= htmlspecialchars($template['title']) ?></div>
                        
                        <div><?= htmlspecialchars($template['description'] ?? '') ?></div>
                        
                        <div><?= htmlspecialchars($template['author_name'] ?? 'Usuario') ?></div>
                        
                        <div><?= date('d/m/Y', strtotime($template['created_at'])) ?></div>
                        
                        <div class="action-buttons">
                            <?php if ($template['user_id'] == $_SESSION['user_id']): ?>
                                <button class="btn-icon black btn-edit" data-type="tariff" data-id="<?= $template['id'] ?>" title="Editar">
                                    <i data-lucide="pencil"></i>
                                </button>
                            <?php endif; ?>
                            <button class="btn-icon black btn-duplicate" data-type="tariff" data-id="<?= $template['id'] ?>" title="Duplicar como mía">
                                <i data-lucide="copy"></i>
                            </button>
                            <?php if ($template['user_id'] == $_SESSION['user_id']): ?>
                                <button class="btn-icon red btn-delete" data-type="tariff" data-id="<?= $template['id'] ?>" title="Borrar">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="<?= asset('js/icon-buttons.js') ?>"></script>
</body>
</html>