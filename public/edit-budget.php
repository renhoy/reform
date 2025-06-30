<?php
// {"_META_file_path_": "public/edit-budget.php"}
// Editar presupuesto existente

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$uuid = $_GET['uuid'] ?? null;
if (!$uuid) {
    header('Location: budgets.php');
    exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM budgets WHERE uuid = ? AND user_id = ?");
$stmt->execute([$uuid, $_SESSION['user_id']]);
$budget = $stmt->fetch();

if (!$budget) {
    header('Location: budgets.php');
    exit;
}

$tariff_data = json_decode($budget['json_tariff_data'], true);
$budget_data = json_decode($budget['json_budget_data'], true);

// Procesar datos del cliente para pre-llenar el formulario
$client_data = [
    'type' => $budget['client_type'],
    'name' => $budget['client_name'],
    'nif_nie' => $budget['client_nif_nie'],
    'phone' => $budget['client_phone'],
    'email' => $budget['client_email'],
    'web' => $budget['client_web'],
    'address' => $budget['client_address']
];

// Procesar cantidades para pre-llenar
$quantities = [];
if (isset($budget_data['items'])) {
    foreach ($budget_data['items'] as $item) {
        $quantities[$item['id']] = $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Presupuesto - <?= htmlspecialchars($tariff_data['title']) ?></title>
    <link rel="stylesheet" href="assets/css/form-styles.css">
</head>
<body>
    <!-- Cabecera de empresa -->
    <div class="company-header">
        <?php if ($tariff_data['logo_url']): ?>
            <img src="<?= htmlspecialchars($tariff_data['logo_url']) ?>" alt="Logo" class="company-logo">
        <?php endif; ?>
        <div class="company-info">
            <h1><?= htmlspecialchars($tariff_data['name']) ?></h1>
            <div class="company-details">
                <?= htmlspecialchars($tariff_data['nif']) ?><br>
                <?= htmlspecialchars($tariff_data['address']) ?><br>
                <?= htmlspecialchars($tariff_data['contact']) ?>
            </div>
        </div>
    </div>

    <div class="container">
        <form id="budgetForm" action="update-budget.php" method="POST">
            <input type="hidden" name="budget_uuid" value="<?= $uuid ?>">
            
            <!-- Datos del Cliente -->
            <div class="section">
                <div class="section-header">Datos del Cliente</div>
                <div class="section-content">
                    <div class="client-type-selector">
                        <div class="client-type <?= $client_data['type'] === 'particular' ? 'selected' : '' ?>" data-type="particular">Particular</div>
                        <div class="client-type <?= $client_data['type'] === 'autonomo' ? 'selected' : '' ?>" data-type="autonomo">Autónomo</div>
                        <div class="client-type <?= $client_data['type'] === 'empresa' ? 'selected' : '' ?>" data-type="empresa">Empresa</div>
                    </div>
                    <input type="hidden" name="client_type" id="clientType" value="<?= $client_data['type'] ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Nombre:</label>
                            <input type="text" id="name" name="name" required value="<?= htmlspecialchars($client_data['name']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="nif_nie">NIF/NIE:</label>
                            <input type="text" id="nif_nie" name="nif_nie" required value="<?= htmlspecialchars($client_data['nif_nie']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Teléfono:</label>
                            <input type="tel" id="phone" name="phone" required value="<?= htmlspecialchars($client_data['phone']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Correo Electrónico:</label>
                            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($client_data['email']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="web">Web:</label>
                            <input type="text" id="web" name="web" value="<?= htmlspecialchars($client_data['web']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="address">Dirección:</label>
                            <textarea id="address" name="address" rows="3" required><?= htmlspecialchars($client_data['address']) ?></textarea>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="acceptance" name="acceptance" <?= $budget['client_acceptance'] ? 'checked' : '' ?> required>
                        <label for="acceptance">Acepto la política de privacidad</label>
                    </div>
                </div>
            </div>

            <!-- Presupuesto -->
            <div class="section">
                <div class="section-header">Datos del Presupuesto</div>
                <div class="section-content">
                    <div class="budget-tree">
                        <?php 
                        $tariff_items = json_decode($tariff_data['json_tariff_data'], true);
                        foreach ($tariff_items as $item): ?>
                            <?php if ($item['level'] === 'chapter'): ?>
                                <div class="tree-item chapter">
                                    <?= htmlspecialchars($item['name']) ?>
                                    <span class="chapter-total" data-chapter="<?= $item['id'] ?>">0,00 €</span>
                                </div>
                            <?php elseif ($item['level'] === 'item'): ?>
                                <div class="tree-item item">
                                    <div class="item-details">
                                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                        <div class="item-unit"><?= htmlspecialchars($item['unit']) ?></div>
                                        <div class="item-iva">IVA: <?= str_replace('.', ',', $item['iva_percentage']) ?>%</div>
                                        <div class="item-pvp">PVP: <?= str_replace('.', ',', $item['pvp']) ?> €</div>
                                        <input type="number" 
                                               class="quantity-input" 
                                               name="quantity[<?= $item['id'] ?>]" 
                                               data-item-id="<?= $item['id'] ?>"
                                               data-pvp="<?= $item['pvp'] ?>"
                                               data-iva="<?= $item['iva_percentage'] ?>"
                                               step="0.01" 
                                               min="0" 
                                               value="<?= $quantities[$item['id']] ?? 0 ?>"
                                               placeholder="Cantidad">
                                        <div class="item-total" data-item="<?= $item['id'] ?>">0,00 €</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Totales -->
                    <div class="totals-section">
                        <div class="totals-row">
                            <span>Base Imponible:</span>
                            <span id="totalBase">0,00 €</span>
                        </div>
                        <div id="ivaBreakdown"></div>
                        <div class="totals-row final">
                            <span>TOTAL PRESUPUESTO:</span>
                            <span id="totalFinal">0,00 €</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='budgets.php'">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar Presupuesto</button>
            </div>
        </form>
    </div>

    <script src="assets/js/form-calculator.js"></script>
    <script>
    // Gestión de tipo de cliente
    document.querySelectorAll('.client-type').forEach(type => {
        type.addEventListener('click', function() {
            document.querySelectorAll('.client-type').forEach(t => t.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('clientType').value = this.dataset.type;
        });
    });
    
    // Calcular totales al cargar
    document.addEventListener('DOMContentLoaded', function() {
        // Disparar cálculo inicial
        document.querySelectorAll('.quantity-input')[0]?.dispatchEvent(new Event('input'));
    });
    </script>
</body>
</html>