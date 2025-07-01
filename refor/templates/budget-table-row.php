<?php
// {"_META_file_path_": "refor/templates/budget-table-row.php"}
// Fila de tabla de presupuesto reutilizable

$budgetData = json_decode($budget['json_budget_data'], true);
$hasNotes = !empty($budget['json_observations']);
?>

<div class="table-row" data-budget-id="<?= $budget['id'] ?>">
    <!-- Cliente (NIF/NIE) -->
    <div class="client-info">
        <div class="client-name"><?= htmlspecialchars($budget['client_name']) ?></div>
        <div class="client-nif">(<?= htmlspecialchars($budget['client_nif_nie']) ?>)</div>
    </div>
    
    <!-- Apuntes -->
    <div class="notes-column">
        <?php if ($hasNotes): ?>
            <div class="notes-indicator active" title="Tiene apuntes" onclick="showNotes(<?= $budget['id'] ?>)">
                <i data-lucide="edit-3" class="notes-icon"></i>
            </div>
        <?php else: ?>
            <div class="notes-indicator" title="Sin apuntes" onclick="showNotes(<?= $budget['id'] ?>)">
                <i data-lucide="edit-3" class="notes-icon"></i>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Total -->
    <div class="total-info" 
         title="Base: <?= formatNumber($budget['base']) ?>€ | IVA: <?= formatNumber($budget['iva']) ?>€ | Total: <?= formatNumber($budget['total']) ?>€">
        <span class="total-amount"><?= formatNumber($budget['total']) ?> €</span>
    </div>
    
    <!-- Tarifa -->
    <div class="tariff-info">
        <button class="btn-icon black" onclick="viewTariff(<?= $budget['tariff_id'] ?>)" title="Ver tarifa">
            <i data-lucide="eye"></i>
        </button>
        <span class="tariff-name"><?= htmlspecialchars($budget['tariff_name']) ?></span>
    </div>
    
    <!-- Estado -->
    <div class="status-info">
        <select class="status-select <?= getStatusBadgeClass($budget['status']) ?>" 
                onchange="updateStatus(<?= $budget['id'] ?>, this.value)">
            <option value="draft" <?= $budget['status'] === 'draft' ? 'selected' : '' ?>>Borrador</option>
            <option value="pending" <?= $budget['status'] === 'pending' ? 'selected' : '' ?>>Pendiente</option>
            <option value="sent" <?= $budget['status'] === 'sent' ? 'selected' : '' ?>>Enviado</option>
            <option value="approved" <?= $budget['status'] === 'approved' ? 'selected' : '' ?>>Aprobado</option>
            <option value="rejected" <?= $budget['status'] === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
            <option value="expired" <?= $budget['status'] === 'expired' ? 'selected' : '' ?>>Expirado</option>
        </select>
    </div>
    
    <!-- Fecha -->
    <div class="date-info">
        <div class="date-range">
            <?= formatDate($budget['created_at'], 'd/m/Y') ?> - 
            <?php if ($budget['end_date']): ?>
                <?= formatDate($budget['end_date'], 'd/m/Y') ?>
                <?php 
                $daysLeft = (strtotime($budget['end_date']) - time()) / (60 * 60 * 24);
                if ($daysLeft > 0): ?>
                    <span class="days-left">(<?= ceil($daysLeft) ?> días)</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="no-date">Sin fecha límite</span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Autor -->
    <div class="author-info">
        <?= htmlspecialchars($budget['author_name'] ?? 'Desconocido') ?>
    </div>
    
    <!-- Acciones -->
    <div class="action-buttons">
        <!-- [Ver PDF] - Si hay PDF -->
        <?php if (!empty($budget['pdf_url'])): ?>
            <a href="<?= htmlspecialchars($budget['pdf_url']) ?>" target="_blank" class="btn-icon black" title="Ver PDF">
                <i data-lucide="file-check"></i>
            </a>
        <?php else: ?>
            <button class="btn-icon black" disabled title="PDF no disponible">
                <i data-lucide="file-check" style="opacity: 0.3;"></i>
            </button>
        <?php endif; ?>
        
        <!-- [Crear PDF] - Si no hay PDF -->
        <?php if (empty($budget['pdf_url'])): ?>
            <button class="btn-icon black btn-create-pdf" onclick="createPDF(<?= $budget['id'] ?>)" title="Crear PDF">
                <i data-lucide="file-stack"></i>
            </button>
        <?php else: ?>
            <button class="btn-icon black" disabled title="PDF ya creado">
                <i data-lucide="file-stack" style="opacity: 0.3;"></i>
            </button>
        <?php endif; ?>
        
        <!-- [Editar] - Siempre activo -->
        <a href="budget-form.php?id=<?= $budget['id'] ?>" class="btn-icon black" title="Editar">
            <i data-lucide="pencil"></i>
        </a>
        
        <!-- [Duplicar] - Siempre activo -->
        <button class="btn-icon black btn-duplicate" onclick="duplicateBudget(<?= $budget['id'] ?>)" title="Duplicar">
            <i data-lucide="copy"></i>
        </button>
        
        <!-- [Borrar] - Siempre activo -->
        <button class="btn-icon red btn-delete" onclick="deleteBudget(<?= $budget['id'] ?>)" title="Borrar">
            <i data-lucide="trash-2"></i>
        </button>
    </div>
</div>

<div class="action-buttons">
             