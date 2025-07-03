<?php
// {"_META_file_path_": "refor/create-budget.php"}
// Formulario para crear presupuestos desde tarifa

require_once 'includes/config.php';
require_once 'includes/tariffs-helpers.php';

requireAuth();

$tariff_uuid = $_GET['uuid'] ?? null;
if (!$tariff_uuid) {
    header('Location: tariffs.php');
    exit;
}

// Obtener datos de la tarifa por UUID
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM tariffs WHERE uuid = ? AND user_id = ?");
$stmt->execute([$tariff_uuid, $_SESSION['user_id']]);
$tariff = $stmt->fetch();

if (!$tariff) {
    header('Location: tariffs.php?error=' . urlencode('Tarifa no encontrada'));
    exit;
}

$tariffData = json_decode($tariff['json_tariff_data'], true) ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Presupuesto - <?= htmlspecialchars($tariff['title']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Cabecera de empresa -->
    <div class="company-header">
        <?php if ($tariff['logo_url']): ?>
            <img src="<?= htmlspecialchars($tariff['logo_url']) ?>" alt="Logo" class="company-logo">
        <?php endif; ?>
        <div class="company-info">
            <h1><?= htmlspecialchars($tariff['name']) ?></h1>
            <div class="company-details">
                <?= htmlspecialchars($tariff['nif']) ?><br>
                <?= htmlspecialchars($tariff['address']) ?><br>
                <?= htmlspecialchars($tariff['contact']) ?>
            </div>
        </div>
    </div>

    <div class="container">
        <form id="budgetForm" action="process/create-budget.php" method="POST">
            <input type="hidden" name="tariff_id" value="<?= $tariff['id'] ?>">
            
            <!-- Datos del Cliente -->
            <div class="form-section">
                <div class="section-header">
                    <h2>Datos del Cliente</h2>
                </div>
                <div class="section-content">
                    <!-- Selector de tipo de cliente -->
                    <div class="client-type-selector">
                        <button type="button" class="client-type-btn active" data-type="particular">
                            <i data-lucide="user"></i>
                            <span>Particular</span>
                        </button>
                        <button type="button" class="client-type-btn" data-type="autonomo">
                            <i data-lucide="briefcase"></i>
                            <span>Autónomo</span>
                        </button>
                        <button type="button" class="client-type-btn" data-type="empresa">
                            <i data-lucide="building"></i>
                            <span>Empresa</span>
                        </button>
                    </div>
                    <input type="hidden" name="client_type" id="clientType" value="particular">
                    
                    <!-- Campos del cliente -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="client_name">Nombre *</label>
                            <input type="text" id="client_name" name="client_name" required>
                        </div>
                        
                        <div class="form-group" id="nifGroup">
                            <label for="client_nif_nie">NIF/NIE</label>
                            <input type="text" id="client_nif_nie" name="client_nif_nie">
                        </div>
                        
                        <div class="form-group">
                            <label for="client_phone">Teléfono</label>
                            <input type="tel" id="client_phone" name="client_phone">
                        </div>
                        
                        <div class="form-group">
                            <label for="client_email">Email</label>
                            <input type="email" id="client_email" name="client_email">
                        </div>
                        
                        <div class="form-group" id="webGroup" style="display: none;">
                            <label for="client_web">Web</label>
                            <input type="url" id="client_web" name="client_web">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="client_address">Dirección</label>
                            <textarea id="client_address" name="client_address" rows="2"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="client_postal_code">Código Postal</label>
                            <input type="text" id="client_postal_code" name="client_postal_code">
                        </div>
                        
                        <div class="form-group">
                            <label for="client_locality">Localidad</label>
                            <input type="text" id="client_locality" name="client_locality">
                        </div>
                        
                        <div class="form-group">
                            <label for="client_province">Provincia</label>
                            <input type="text" id="client_province" name="client_province">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Partidas del Presupuesto -->
            <div class="form-section">
                <div class="section-header">
                    <h2>Partidas del Presupuesto</h2>
                    <p>Selecciona las cantidades para cada partida</p>
                </div>
                <div class="section-content">
                    <div id="tariffItems">
                        <?php if (!empty($tariffData)): ?>
                            <?php foreach ($tariffData as $item): ?>
                                <?php if ($item['level'] === 'chapter'): ?>
                                    <div class="chapter-header">
                                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                                    </div>
                                <?php elseif ($item['level'] === 'item'): ?>
                                    <div class="tariff-item">
                                        <div class="item-info">
                                            <span class="item-code"><?= htmlspecialchars($item['id']) ?></span>
                                            <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                                            <span class="item-description"><?= htmlspecialchars($item['description'] ?? '') ?></span>
                                        </div>
                                        <div class="item-controls">
                                            <div class="quantity-control">
                                                <label for="qty_<?= $item['id'] ?>">Cantidad</label>
                                                <input type="number" 
                                                       id="qty_<?= $item['id'] ?>" 
                                                       name="quantity[<?= $item['id'] ?>]" 
                                                       min="0" 
                                                       step="0.01" 
                                                       value="0"
                                                       data-price="<?= $item['pvp'] ?? 0 ?>"
                                                       data-iva="<?= $item['iva_percentage'] ?? 21 ?>"
                                                       class="quantity-input">
                                                <span class="unit"><?= htmlspecialchars($item['unit'] ?? 'ud') ?></span>
                                            </div>
                                            <div class="price-info">
                                                <span class="unit-price"><?= number_format($item['pvp'] ?? 0, 2) ?> €</span>
                                                <span class="total-price" id="total_<?= $item['id'] ?>">0,00 €</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>Esta tarifa no tiene partidas configuradas.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Resumen del Presupuesto -->
            <div class="form-section">
                <div class="section-header">
                    <h2>Resumen del Presupuesto</h2>
                </div>
                <div class="section-content">
                    <div class="budget-summary">
                        <div class="summary-row">
                            <span>Base imponible:</span>
                            <span id="baseAmount">0,00 €</span>
                        </div>
                        <div class="summary-row">
                            <span>IVA:</span>
                            <span id="ivaAmount">0,00 €</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span id="totalAmount">0,00 €</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración del Presupuesto -->
            <div class="form-section">
                <div class="section-header">
                    <h2>Configuración</h2>
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="start_date">Fecha de inicio</label>
                            <input type="date" id="start_date" name="start_date" value="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="validity_days">Validez (días)</label>
                            <input type="number" id="validity_days" name="validity_days" value="30" min="1">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="observations">Observaciones</label>
                            <textarea id="observations" name="observations" rows="3" placeholder="Observaciones adicionales del presupuesto..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="form-actions">
                <a href="tariffs.php" class="btn btn--secondary">Cancelar</a>
                <button type="submit" name="action" value="draft" class="btn btn--outline">
                    <i data-lucide="save"></i>
                    <span>Guardar Borrador</span>
                </button>
                <button type="submit" name="action" value="pending" class="btn btn--primary">
                    <i data-lucide="check"></i>
                    <span>Crear Presupuesto</span>
                </button>
            </div>
        </form>
    </div>

    <script src="assets/js/budget-form.js"></script>
    <script>
        lucide.createIcons();
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof BudgetFormManager !== 'undefined') {
                new BudgetFormManager();
            }
        });
    </script>
</body>
</html>