<?php
// {"_META_file_path_": "refor/create-budget.php"}
// Generador de presupuestos con formulario dinámico

require_once 'includes/config.php';
require_once 'includes/tariff-helpers.php';
require_once 'includes/budget-helpers.php';

requireAuth();

// Aceptar tanto tariff_id como tariff_uuid para compatibilidad
$tariff_id = $_GET['tariff_id'] ?? null;
$tariff_uuid = $_GET['tariff_uuid'] ?? null;

if (!$tariff_id && !$tariff_uuid) {
    header('Location: tariffs.php');
    exit;
}

$pdo = getConnection();

if ($tariff_uuid) {
    // Buscar por UUID (desde tariffs.php)
    $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE uuid = ? AND user_id = ?");
    $stmt->execute([$tariff_uuid, $_SESSION['user_id']]);
    $tariff = $stmt->fetch();
    if ($tariff) {
        $tariff_id = $tariff['id']; // Obtener ID para uso interno
    }
} else {
    // Buscar por ID
    $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ? AND user_id = ?");
    $stmt->execute([$tariff_id, $_SESSION['user_id']]);
    $tariff = $stmt->fetch();
}

if (!$tariff) {
    header('Location: tariffs.php');
    exit;
}

$tariffData = json_decode($tariff['json_tariff_data'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Presupuesto - <?= htmlspecialchars($tariff['name']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/create-budget.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <div class="container">
        <!-- Cabecera de empresa -->
        <div class="company-header">
            <div class="company-logo">
                <?php if ($tariff['logo_url']): ?>
                    <img src="<?= htmlspecialchars($tariff['logo_url']) ?>" alt="Logo" class="logo-img">
                <?php endif; ?>
            </div>
            <div class="company-info">
                <h1><?= htmlspecialchars($tariff['name']) ?></h1>
                <div class="company-details">
                    <div><?= htmlspecialchars($tariff['nif']) ?></div>
                    <div><?= htmlspecialchars($tariff['address']) ?></div>
                    <div><?= htmlspecialchars($tariff['contact']) ?></div>
                </div>
            </div>
        </div>

        <form id="budgetForm" action="process-budget.php" method="POST">
            <input type="hidden" name="tariff_id" value="<?= $tariff_id ?>">
            
            <!-- Datos del Cliente -->
            <div class="section">
                <div class="section-header">Datos del Cliente</div>
                <div class="section-content">
                    <!-- Selector de tipo de cliente -->
                    <div class="client-type-selector">
                        <div class="client-type-option selected" data-type="particular">Particular</div>
                        <div class="client-type-option" data-type="autonomo">Autónomo</div>
                        <div class="client-type-option" data-type="empresa">Empresa</div>
                    </div>
                    <input type="hidden" name="client_type" id="clientType" value="particular">
                    
                    <!-- Campos del formulario -->
                    <div class="form-grid">
                        <div class="form-row">
                            <div class="form-group span-2">
                                <label for="client_name">Nombre *</label>
                                <input type="text" id="client_name" name="client_name" required>
                            </div>
                            <div class="form-group span-1">
                                <label for="client_nif_nie">NIF/NIE *</label>
                                <input type="text" id="client_nif_nie" name="client_nif_nie" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group span-1">
                                <label for="client_phone">Teléfono *</label>
                                <input type="text" id="client_phone" name="client_phone" required>
                            </div>
                            <div class="form-group span-1">
                                <label for="client_email">Correo electrónico *</label>
                                <input type="email" id="client_email" name="client_email" required>
                            </div>
                            <div class="form-group span-1">
                                <label for="client_web">Web</label>
                                <input type="url" id="client_web" name="client_web">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group span-2">
                                <label for="client_address">Dirección *</label>
                                <input type="text" id="client_address" name="client_address" required>
                            </div>
                            <div class="form-group span-1">
                                <label for="client_postal_code">Código Postal *</label>
                                <input type="text" id="client_postal_code" name="client_postal_code" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group span-2">
                                <label for="client_locality">Localidad *</label>
                                <input type="text" id="client_locality" name="client_locality" required>
                            </div>
                            <div class="form-group span-1">
                                <label for="client_province">Provincia *</label>
                                <input type="text" id="client_province" name="client_province" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group span-3">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="client_acceptance" name="client_acceptance" required>
                                    Acepto los términos y condiciones y la política de privacidad *
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Legal -->
            <div class="legal-info">
                <h3>Información legal</h3>
                <div class="legal-content">
                    <?= nl2br(htmlspecialchars($tariff['legal_note'])) ?>
                </div>
            </div>

            <!-- Datos del Presupuesto -->
            <div class="section">
                <div class="section-header">Datos del Presupuesto</div>
                <div class="section-content">
                    <!-- Totales del presupuesto -->
                    <div class="budget-summary">
                        <div class="totals-grid">
                            <div class="total-row">
                                <span>BASE</span>
                                <span id="totalBase" class="total-amount">0,00 €</span>
                            </div>
                            <div id="ivaBreakdown"></div>
                            <div class="total-row final-total">
                                <span>TOTAL PRESUPUESTO</span>
                                <span id="totalFinal" class="total-amount">0,00 €</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="action-buttons">
                        <button type="button" class="btn btn--danger" id="clearBtn">Limpiar</button>
                        <button type="submit" class="btn btn--primary" id="saveBtn">Guardar</button>
                    </div>

                    <!-- Formulario jerárquico -->
                    <div class="budget-tree" id="budgetTree">
                        <!-- Se genera dinámicamente con JavaScript -->
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal de descripción -->
    <div id="descriptionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Descripción</h3>
                <button type="button" class="modal-close" id="closeModal">×</button>
            </div>
            <div class="modal-body" id="modalDescription">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="confirmTitle">Confirmación</h3>
            </div>
            <div class="modal-body" id="confirmMessage">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" id="confirmCancel">Cancelar</button>
                <button type="button" class="btn btn--primary" id="confirmAccept">Aceptar</button>
            </div>
        </div>
    </div>

    <!-- Modal de éxito -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Presupuesto Guardado</h3>
            </div>
            <div class="modal-body">
                Los datos del presupuesto se han guardado correctamente.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" id="successContinue">Continuar</button>
                <button type="button" class="btn btn--primary" id="successGoToBudgets">Ir a Presupuestos</button>
            </div>
        </div>
    </div>

    <script>
        // Configuración inicial
        const tariffData = <?= json_encode($tariffData) ?>;
        const tariffId = <?= $tariff_id ?>;
        
        // Datos del formulario en memoria
        let budgetData = {};
        
        // Inicializar la aplicación
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar iconos
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            // Cargar datos de tarifa en memoria
            loadTariffData();
            
            // Generar formulario
            generateBudgetForm();
            
            // Inicializar eventos
            initializeEvents();
            
            // Abrir primer capítulo y primera partida
            openFirstChapterAndItem();
        });

        function loadTariffData() {
            budgetData = JSON.parse(JSON.stringify(tariffData));
        }

        function generateBudgetForm() {
            const container = document.getElementById('budgetTree');
            container.innerHTML = buildHierarchyHTML(budgetData);
        }

        function buildHierarchyHTML(data) {
            let html = '';
            let i = 0;

            while (i < data.length) {
                const item = data[i];
                const children = getDirectChildren(data, i);
                
                if (item.level === 'chapter') {
                    html += buildChapterHTML(item, children);
                } else if (item.level === 'subchapter') {
                    html += buildSubchapterHTML(item, children);
                } else if (item.level === 'section') {
                    html += buildSectionHTML(item, children);
                } else if (item.level === 'item') {
                    html += buildItemHTML(item);
                }

                i += children.length + 1;
            }

            return html;
        }

        function getDirectChildren(data, parentIndex) {
            const parentItem = data[parentIndex];
            const children = [];
            
            for (let i = parentIndex + 1; i < data.length; i++) {
                const item = data[i];
                
                if (isDirectChild(parentItem, item)) {
                    children.push(item);
                } else if (item.level === parentItem.level || isAncestor(parentItem, item)) {
                    break;
                }
            }
            
            return children;
        }

        function isDirectChild(parent, child) {
            const parentParts = parent.id.split('.');
            const childParts = child.id.split('.');
            
            return childParts.length === parentParts.length + 1 && 
                   child.id.startsWith(parent.id + '.');
        }

        function isAncestor(ancestor, descendant) {
            return descendant.id.startsWith(ancestor.id + '.') && 
                   descendant.id !== ancestor.id;
        }

        function buildChapterHTML(item, children) {
            return `
                <div class="tree-item chapter" data-id="${item.id}">
                    <div class="item-header" onclick="toggleAccordion('${item.id}')">
                        <div class="item-toggle">
                            <i data-lucide="chevron-down"></i>
                        </div>
                        <div class="item-name">${item.name}</div>
                        <div class="item-amount" id="amount-${item.id}">0,00 €</div>
                    </div>
                    <div class="item-content" id="content-${item.id}">
                        ${buildHierarchyHTML(children)}
                    </div>
                </div>
            `;
        }

        function buildSubchapterHTML(item, children) {
            return `
                <div class="tree-item subchapter" data-id="${item.id}">
                    <div class="item-header" onclick="toggleAccordion('${item.id}')">
                        <div class="item-toggle">
                            <i data-lucide="chevron-down"></i>
                        </div>
                        <div class="item-name">${item.name}</div>
                        <div class="item-amount" id="amount-${item.id}">0,00 €</div>
                    </div>
                    <div class="item-content" id="content-${item.id}">
                        ${buildHierarchyHTML(children)}
                    </div>
                </div>
            `;
        }

        function buildSectionHTML(item, children) {
            return `
                <div class="tree-item section" data-id="${item.id}">
                    <div class="item-header" onclick="toggleAccordion('${item.id}')">
                        <div class="item-toggle">
                            <i data-lucide="chevron-down"></i>
                        </div>
                        <div class="item-name">${item.name}</div>
                        <div class="item-amount" id="amount-${item.id}">0,00 €</div>
                    </div>
                    <div class="item-content" id="content-${item.id}">
                        ${buildHierarchyHTML(children)}
                    </div>
                </div>
            `;
        }

        function buildItemHTML(item) {
            return `
                <div class="tree-item item" data-id="${item.id}">
                    <div class="item-header">
                        <div class="item-name">${item.name}</div>
                        <div class="item-amount" id="amount-${item.id}">0,00 €</div>
                    </div>
                    <div class="item-details">
                        <div class="item-controls">
                            <button type="button" class="btn-icon" onclick="showDescription('${item.id}')">
                                <i data-lucide="file-text"></i>
                            </button>
                            <span class="item-unit">${item.unit}</span>
                            <span class="item-iva">IVA: ${formatNumber(item.iva_percentage)}%</span>
                            <div class="quantity-controls">
                                <button type="button" class="btn-quantity" onclick="changeQuantity('${item.id}', -1)">-</button>
                                <input type="number" 
                                       class="quantity-input" 
                                       id="quantity-${item.id}"
                                       value="0,00" 
                                       step="0.01" 
                                       min="0" 
                                       onchange="updateQuantity('${item.id}', this.value)">
                                <button type="button" class="btn-quantity" onclick="changeQuantity('${item.id}', 1)">+</button>
                            </div>
                            <span class="item-pvp">PVP: ${formatNumber(item.pvp)} €</span>
                        </div>
                    </div>
                </div>
            `;
        }

        function toggleAccordion(id) {
            const content = document.getElementById(`content-${id}`);
            const toggle = document.querySelector(`[data-id="${id}"] .item-toggle i`);
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                toggle.style.transform = 'rotate(0deg)';
            } else {
                content.style.display = 'none';
                toggle.style.transform = 'rotate(-90deg)';
            }
        }

        function openFirstChapterAndItem() {
            // Cerrar todos los acordeones
            document.querySelectorAll('.item-content').forEach(content => {
                content.style.display = 'none';
            });
            
            document.querySelectorAll('.item-toggle i').forEach(toggle => {
                toggle.style.transform = 'rotate(-90deg)';
            });

            // Buscar el primer capítulo
            const firstChapter = budgetData.find(item => item.level === 'chapter');
            if (firstChapter) {
                openAncestors(firstChapter.id);
                
                // Buscar la primera partida del primer capítulo
                const firstItem = budgetData.find(item => 
                    item.level === 'item' && item.id.startsWith(firstChapter.id + '.')
                );
                
                if (firstItem) {
                    openAncestors(firstItem.id);
                }
            }
        }

        function openAncestors(itemId) {
            const parts = itemId.split('.');
            let currentId = '';
            
            for (let i = 0; i < parts.length; i++) {
                currentId += (i > 0 ? '.' : '') + parts[i];
                const content = document.getElementById(`content-${currentId}`);
                const toggle = document.querySelector(`[data-id="${currentId}"] .item-toggle i`);
                
                if (content) {
                    content.style.display = 'block';
                    if (toggle) {
                        toggle.style.transform = 'rotate(0deg)';
                    }
                }
            }
        }

        function changeQuantity(itemId, change) {
            const input = document.getElementById(`quantity-${itemId}`);
            const currentValue = parseFloat(input.value.replace(',', '.')) || 0;
            const newValue = Math.max(0, currentValue + change);
            
            input.value = formatNumber(newValue);
            updateQuantity(itemId, newValue);
        }

        function updateQuantity(itemId, value) {
            const quantity = Math.max(0, parseFloat(value.toString().replace(',', '.')) || 0);
            
            // Actualizar en memoria
            const item = budgetData.find(item => item.id === itemId);
            if (item) {
                item.quantity = quantity.toFixed(2);
                item.amount = (quantity * parseFloat(item.pvp)).toFixed(2);
            }
            
            // Actualizar visual
            const input = document.getElementById(`quantity-${itemId}`);
            if (input) {
                input.value = formatNumber(quantity);
            }
            
            // Recalcular totales
            updateTotals();
        }

        function updateTotals() {
            // Calcular totales de partidas
            budgetData.forEach(item => {
                if (item.level === 'item') {
                    const quantity = parseFloat(item.quantity || 0);
                    const pvp = parseFloat(item.pvp || 0);
                    item.amount = (quantity * pvp).toFixed(2);
                }
            });

            // Calcular totales de capítulos, subcapítulos y secciones
            budgetData.forEach(item => {
                if (item.level !== 'item') {
                    item.amount = calculateGroupTotal(item.id).toFixed(2);
                }
            });

            // Actualizar importes en pantalla
            budgetData.forEach(item => {
                const amountElement = document.getElementById(`amount-${item.id}`);
                if (amountElement) {
                    amountElement.textContent = formatNumber(item.amount) + ' €';
                }
            });

            // Calcular totales generales
            calculateBudgetTotals();
        }

        function calculateGroupTotal(groupId) {
            let total = 0;
            
            budgetData.forEach(item => {
                if (item.level === 'item' && item.id.startsWith(groupId + '.')) {
                    const quantity = parseFloat(item.quantity || 0);
                    const pvp = parseFloat(item.pvp || 0);
                    total += quantity * pvp;
                }
            });
            
            return total;
        }

        function calculateBudgetTotals() {
            let totalBase = 0;
            let ivaBreakdown = {};
            
            budgetData.forEach(item => {
                if (item.level === 'item') {
                    const quantity = parseFloat(item.quantity || 0);
                    const pvp = parseFloat(item.pvp || 0);
                    const ivaRate = parseFloat(item.iva_percentage || 0);
                    
                    if (quantity > 0) {
                        const totalItem = quantity * pvp;
                        const baseAmount = totalItem / (1 + ivaRate / 100);
                        const ivaAmount = totalItem - baseAmount;
                        
                        totalBase += baseAmount;
                        
                        if (!ivaBreakdown[ivaRate]) {
                            ivaBreakdown[ivaRate] = 0;
                        }
                        ivaBreakdown[ivaRate] += ivaAmount;
                    }
                }
            });
            
            const totalIva = Object.values(ivaBreakdown).reduce((sum, val) => sum + val, 0);
            const totalFinal = totalBase + totalIva;
            
            // Actualizar pantalla
            document.getElementById('totalBase').textContent = formatNumber(totalBase) + ' €';
            document.getElementById('totalFinal').textContent = formatNumber(totalFinal) + ' €';
            
            // Actualizar desglose de IVA
            const ivaContainer = document.getElementById('ivaBreakdown');
            ivaContainer.innerHTML = '';
            
            Object.entries(ivaBreakdown).forEach(([rate, amount]) => {
                if (amount > 0) {
                    const div = document.createElement('div');
                    div.className = 'total-row';
                    div.innerHTML = `
                        <span>IVA ${formatNumber(rate)}%</span>
                        <span class="total-amount">${formatNumber(amount)} €</span>
                    `;
                    ivaContainer.appendChild(div);
                }
            });
        }

        function showDescription(itemId) {
            const item = budgetData.find(item => item.id === itemId);
            if (item && item.description) {
                document.getElementById('modalDescription').textContent = item.description;
                document.getElementById('descriptionModal').style.display = 'block';
            }
        }

        function formatNumber(value) {
            return parseFloat(value || 0).toFixed(2).replace('.', ',');
        }

        function initializeEvents() {
            // Selector de tipo de cliente
            document.querySelectorAll('.client-type-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('.client-type-option').forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('clientType').value = this.dataset.type;
                });
            });

            // Botón limpiar
            document.getElementById('clearBtn').addEventListener('click', function() {
                showConfirmModal(
                    'Limpiar Formulario',
                    '¿Está seguro que desea limpiar el formulario? Se perderán todos los datos ingresados',
                    function() {
                        location.reload();
                    }
                );
            });

            // Cierre de modales
            document.getElementById('closeModal').addEventListener('click', function() {
                document.getElementById('descriptionModal').style.display = 'none';
            });

            document.getElementById('confirmCancel').addEventListener('click', function() {
                document.getElementById('confirmModal').style.display = 'none';
            });

            document.getElementById('successContinue').addEventListener('click', function() {
                document.getElementById('successModal').style.display = 'none';
            });

            document.getElementById('successGoToBudgets').addEventListener('click', function() {
                window.location.href = 'budgets.php';
            });

            // Envío del formulario
            document.getElementById('budgetForm').addEventListener('submit', function(e) {
                e.preventDefault();
                saveBudget();
            });

            // Cerrar modales al hacer clic fuera
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    e.target.style.display = 'none';
                }
            });
        }

        function showConfirmModal(title, message, callback) {
            document.getElementById('confirmTitle').textContent = title;
            document.getElementById('confirmMessage').textContent = message;
            document.getElementById('confirmModal').style.display = 'block';
            
            document.getElementById('confirmAccept').onclick = function() {
                document.getElementById('confirmModal').style.display = 'none';
                callback();
            };
        }

        function saveBudget() {
            // Validar formulario
            const form = document.getElementById('budgetForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Preparar datos
            const formData = new FormData(form);
            
            // Añadir cantidades
            budgetData.forEach(item => {
                if (item.level === 'item' && parseFloat(item.quantity || 0) > 0) {
                    formData.append(`quantities[${item.id}]`, item.quantity);
                }
            });

            // Añadir datos del presupuesto
            formData.append('budget_data', JSON.stringify(budgetData));

            // Enviar al servidor
            fetch('process-budget.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('successModal').style.display = 'block';
                } else {
                    alert('Error al guardar el presupuesto: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                alert('Error al guardar el presupuesto: ' + error.message);
            });
        }
    </script>
</body>
</html>