<?php
// {"_META_file_path_": "public/form.php"}
// Generador dinámico de formularios de presupuesto

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
requireAuth();

$tariff_id = $_GET['tariff_id'] ?? null;
if (!$tariff_id) {
    header('Location: dashboard.php');
    exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ?");
$stmt->execute([$tariff_id]);
$data = $stmt->fetch();

if (!$data) {
    header('Location: dashboard.php');
    exit;
}

$tariffData = json_decode($data['json_tariff_data'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuesto - <?= htmlspecialchars($data['title']) ?></title>
    <link rel="stylesheet" href="<?= asset('css/form-styles.css') ?>">
</head>
<body>
    <!-- Cabecera de empresa -->
    <div class="company-header">
        <?php if ($data['logo_url']): ?>
            <img src="<?= htmlspecialchars($data['logo_url']) ?>" alt="Logo" class="company-logo">
        <?php endif; ?>
        <div class="company-info">
            <h1><?= htmlspecialchars($data['name']) ?></h1>
            <div class="company-details">
                <?= htmlspecialchars($data['nif']) ?><br>
                <?= htmlspecialchars($data['address']) ?><br>
                <?= htmlspecialchars($data['contact']) ?>
            </div>
        </div>
    </div>

    <div class="container">
        <form id="budgetForm" action="process-budget.php" method="POST">
            <input type="hidden" name="tariff_id" value="<?= $tariff_id ?>">
            
            <!-- Datos del Cliente -->
            <div class="section">
                <div class="section-header">Datos del Cliente</div>
                <div class="section-content">
                    <div class="client-type-selector">
                        <div class="client-type selected" data-type="particular">Particular</div>
                        <div class="client-type" data-type="autonomo">Autónomo</div>
                        <div class="client-type" data-type="empresa">Empresa</div>
                    </div>
                    <input type="hidden" name="client_type" id="clientType" value="particular">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Nombre:</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="nif_nie">NIF/NIE:</label>
                            <input type="text" id="nif_nie" name="nif_nie" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Teléfono:</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Correo Electrónico:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="web">Web:</label>
                            <input type="text" id="web" name="web">
                        </div>
                        <div class="form-group">
                            <label for="address">Dirección:</label>
                            <textarea id="address" name="address" rows="3" required></textarea>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="acceptance" name="acceptance" required>
                        <label for="acceptance">Acepto la política de privacidad</label>
                    </div>
                    
                    <?php if ($data['legal_note']): ?>
                        <div class="legal-text">
                            <?= nl2br(htmlspecialchars($data['legal_note'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Presupuesto -->
            <div class="section">
                <div class="section-header">Datos del Presupuesto</div>
                <div class="section-content">
                    <div class="budget-tree">
                        <?php foreach ($tariffData as $item): ?>
                            <?php if ($item['level'] === 'chapter'): ?>
                                <div class="tree-item chapter">
                                    <?= htmlspecialchars($item['name']) ?>
                                    <span class="chapter-total" data-chapter="<?= $item['id'] ?>">0,00 €</span>
                                </div>
                            <?php elseif ($item['level'] === 'subchapter'): ?>
                                <div class="tree-item subchapter">
                                    <?= htmlspecialchars($item['name']) ?>
                                    <span class="subchapter-total" data-subchapter="<?= $item['id'] ?>">0,00 €</span>
                                </div>
                            <?php elseif ($item['level'] === 'section'): ?>
                                <div class="tree-item section">
                                    <?= htmlspecialchars($item['name']) ?>
                                    <span class="section-total" data-section="<?= $item['id'] ?>">0,00 €</span>
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
                                               value="0"
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
                <button type="button" class="btn btn-secondary" onclick="window.location.href='dashboard.php'">Cancelar</button>
                <button type="submit" class="btn btn-primary">Generar Presupuesto</button>
            </div>
        </form>
    </div>

    <script src="<?= asset('js/form-calculator.js') ?>"></script>
</body>
</html>